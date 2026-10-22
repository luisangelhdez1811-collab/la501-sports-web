<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Order;
use App\Models\PromoSubscriber;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\AchievementService;
use App\Services\PhoneVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        (new AchievementService())->check($user);

        $allAchievements = Achievement::orderBy('categoria')->get();
        $unlockedIds = $user->achievements()->pluck('achievement_id')->toArray();

        $reservacionesTotales = Reservation::where('correo_electronico', $user->email)->count();

        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'tracking_token', 'total', 'status', 'created_at']);

        $subscribedToPromos = $this->subscribedToPromos($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'telefono' => $user->telefono,
                    'role' => $user->role,
                    'points' => $user->points ?? 0,
                    'avatar' => $user->avatar,
                ],
                'achievements' => [
                    'all' => $allAchievements,
                    'unlocked_ids' => $unlockedIds,
                ],
                'reservations_total' => $reservacionesTotales,
                'orders' => $orders,
                'subscribed_to_promos' => $subscribedToPromos,
                'privacy' => $this->privacyPayload($user),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Oposición (ARCO): las finalidades secundarias que el cliente tiene encendidas. */
    public function privacy(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->privacyPayload($request->user()),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Oposición (ARCO): enciende o apaga cada finalidad secundaria. Solo cambia lo que se
     * manda; cada cambio queda fechado como evidencia.
     */
    public function updatePrivacy(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'promos_correo' => ['sometimes', 'boolean'],
            'promos_sms' => ['sometimes', 'boolean'],
            'recomendaciones_personalizadas' => ['sometimes', 'boolean'],
        ]);

        if ($validated === []) {
            return response()->json([
                'success' => false,
                'message' => 'Indica qué preferencia quieres cambiar.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('promos_correo', $validated)) {
            if ($user->role !== 'cliente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Las promociones por correo solo están disponibles para clientes.',
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }
            $this->setPromoSubscription($user, (bool) $validated['promos_correo']);
        }

        $user->fill(collect($validated)->only(['promos_sms', 'recomendaciones_personalizadas'])->all());
        $user->preferencias_privacidad_at = now();
        $user->save();

        Log::info('[ARCO] Preferencias de privacidad actualizadas', [
            'user_id' => $user->id,
            'cambios' => array_keys($validated),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tus preferencias de privacidad se guardaron.',
            'data' => $this->privacyPayload($user->fresh()),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function privacyPayload(User $user): array
    {
        return [
            'promos_correo' => $this->subscribedToPromos($user),
            'promos_sms' => (bool) ($user->promos_sms ?? false),
            'recomendaciones_personalizadas' => (bool) ($user->recomendaciones_personalizadas ?? true),
            'actualizado_at' => optional($user->preferencias_privacidad_at)->toISOString(),
        ];
    }

    private function subscribedToPromos(User $user): bool
    {
        return $user->role === 'cliente'
            && PromoSubscriber::where('user_id', $user->id)->orWhere('email', $user->email)->exists();
    }

    private function setPromoSubscription(User $user, bool $subscribed): void
    {
        $existing = PromoSubscriber::where('user_id', $user->id)->orWhere('email', $user->email)->first();

        if (!$subscribed) {
            $existing?->delete();
            return;
        }

        if (!$existing) {
            PromoSubscriber::create([
                'email' => $user->email,
                'user_id' => $user->id,
                'unsubscribe_token' => Str::random(48),
            ]);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignoreModel($user)],
            'telefono' => ['required', 'digits:10'],
        ]);

        // El teléfono no se cambia aquí: necesita código (ver
        // POST .../phone/request-otp y PUT .../phone).
        if ($validated['telefono'] !== $user->telefono) {
            return response()->json([
                'success' => false,
                'error' => 'phone_change_requires_otp',
                'message' => 'Para cambiar tu teléfono, primero pide un código con /account/phone/request-otp y confírmalo en /account/phone.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'role' => $user->role,
                'points' => $user->points ?? 0,
                'avatar' => $user->avatar,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Derecho de Cancelación (ARCO) / "eliminar cuenta" de Google Play.
     * Anonimiza al usuario en vez de borrar la fila (los pedidos quedan
     * ligados por user_id para contabilidad, pero sin datos identificables
     * — obligación fiscal de conservarlos 5 años) y revoca todos sus tokens.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'confirmacion' => ['required', 'in:ELIMINAR'],
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_password',
                'message' => 'Contraseña incorrecta',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        // Solo cuentas de cliente se auto-eliminan desde la app. El equipo
        // (admin, cocinero, empleado/mesero, limpieza) se da de baja desde
        // el panel, no con este endpoint.
        if ($user->role !== 'cliente') {
            return response()->json([
                'success' => false,
                'error' => 'role_not_allowed',
                'message' => 'Esta acción solo está disponible para cuentas de cliente.',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        (new AccountDeletionService())->delete($user);

        return response()->json(['success' => true], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Paso 1: pide el código al NÚMERO NUEVO antes de guardarlo. */
    public function requestPhoneOtp(Request $request, PhoneVerification $sms): JsonResponse
    {
        $validated = $request->validate([
            'telefono' => ['required', 'digits:10'],
            'canal' => ['nullable', 'in:sms,llamada'],
        ]);
        $user = $request->user();
        $telefono = $validated['telefono'];

        if (User::where('telefono', $telefono)->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'phone_taken',
                'message' => 'Ese número ya pertenece a otra cuenta.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        if ($tooMany = $this->tooManyPhoneAttempts($request, $telefono)) {
            return $tooMany;
        }

        try {
            $sms->send($telefono, $validated['canal'] ?? 'sms');
            Log::info('phone_verification', ['flujo' => 'change_phone', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'enviado']);
        } catch (\Throwable $e) {
            report($e);
            Log::info('phone_verification', ['flujo' => 'change_phone', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'fallo_envio']);

            // No consumimos el límite: la falla fue nuestra (Twilio/config).
            return response()->json([
                'success' => false,
                'error' => 'sms_unavailable',
                'message' => 'No pudimos enviar el SMS. Intenta con llamada o por correo.',
            ], 503, [], JSON_UNESCAPED_UNICODE);
        }

        $this->hitPhoneAttempts($request, $telefono);

        return response()->json([
            'success' => true,
            'message' => 'Te mandamos un código a tu nuevo número.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Paso 2: confirma el código y recién ahí guarda el número nuevo. */
    public function updatePhone(Request $request, PhoneVerification $sms): JsonResponse
    {
        $validated = $request->validate([
            'telefono' => ['required', 'digits:10'],
            'codigo' => ['required', 'string'],
        ]);
        $user = $request->user();

        if (User::where('telefono', $validated['telefono'])->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'phone_taken',
                'message' => 'Ese número ya pertenece a otra cuenta.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        if (!$sms->check($validated['telefono'], $validated['codigo'])) {
            Log::info('phone_verification', ['flujo' => 'change_phone', 'telefono' => PhoneVerification::mask($validated['telefono']), 'resultado' => 'codigo_invalido']);

            return response()->json([
                'success' => false,
                'error' => 'invalid_or_expired_code',
                'message' => 'El código no es correcto o ya caducó.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $user->telefono = $validated['telefono'];
        $user->save();

        Log::info('phone_verification', ['flujo' => 'change_phone', 'telefono' => PhoneVerification::mask($validated['telefono']), 'resultado' => 'aprobado']);

        return response()->json([
            'success' => true,
            'message' => '¡Tu teléfono fue verificado y actualizado con éxito!',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Mismo límite que AuthController/OrderController: 3 por teléfono/10min, 10 por IP/hora. */
    private function tooManyPhoneAttempts(Request $request, string $telefono): ?JsonResponse
    {
        $phoneKey = 'sms-otp-phone:' . $telefono;
        $ipKey = 'sms-otp-ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            $segundos = max(RateLimiter::availableIn($phoneKey), RateLimiter::availableIn($ipKey));

            return response()->json([
                'success' => false,
                'error' => 'too_many_attempts',
                'message' => 'Espera un momento antes de pedir otro código.',
            ], 429, ['Retry-After' => $segundos], JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    private function hitPhoneAttempts(Request $request, string $telefono): void
    {
        RateLimiter::hit('sms-otp-phone:' . $telefono, 600);
        RateLimiter::hit('sms-otp-ip:' . $request->ip(), 3600);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_current_password',
                'message' => 'Tu contraseña actual no es correcta.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $user->password = $validated['password'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => '¡Contraseña actualizada con éxito!',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function togglePromoSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Esta opción solo está disponible para clientes.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $existing = PromoSubscriber::where('user_id', $user->id)->orWhere('email', $user->email)->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'success' => true,
                'subscribed' => false,
                'message' => 'Ya no recibirás correos de promociones.',
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        PromoSubscriber::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'unsubscribe_token' => Str::random(48),
        ]);

        return response()->json([
            'success' => true,
            'subscribed' => true,
            'message' => '¡Listo! Te avisaremos por correo cuando haya nuevas promociones.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
