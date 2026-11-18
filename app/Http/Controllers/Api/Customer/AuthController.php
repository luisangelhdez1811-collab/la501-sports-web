<?php

namespace App\Http\Controllers\Api\Customer;

use App\Services\LegalDocuments;
use App\Http\Controllers\Controller;
use App\Models\PromoSubscriber;
use App\Models\User;
use App\Services\JwtService;
use App\Services\PhoneVerification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users'],
            'telefono' => ['required', 'string', 'max:20'],
            'password' => [
                'required', 'confirmed', 'min:8',
                'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[!@#$%^&*]/',
            ],
            // Ya no se pide pregunta secreta (nunca se usó: la recuperación es por código). Si una versión
            // vieja de la app todavía la manda, se ignora: solo se guarda lo validado.
            // Mayoría de edad: los Términos dicen que el servicio es para mayores de 18.
            'mayor_de_edad' => ['required', 'accepted'],
            // "accepted" exige true/1/"yes" — si no lo mandan o viene en
            // false, el registro se rechaza con un 422 solo.
            'acepta_aviso_privacidad' => ['required', 'accepted'],
            'acepta_promociones' => ['nullable', 'boolean'],
        ], [
            'password.regex' => 'La contraseña debe tener una mayúscula, un número y un carácter especial.',
            'email.unique' => 'Este correo ya está registrado en La 501.',
            'acepta_aviso_privacidad.accepted' => 'Debes aceptar el aviso de privacidad para registrarte.',
            'mayor_de_edad.required' => 'Debes confirmar que eres mayor de 18 años para registrarte.',
            'mayor_de_edad.accepted' => 'Debes confirmar que eres mayor de 18 años para registrarte.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'password' => Hash::make($validated['password']),
            'role' => 'cliente',
            'is_active' => 1,
            'aviso_privacidad_aceptado_at' => now(),
        ]);
        // Qué versión del Aviso y de los Términos aceptó (B-32)
        LegalDocuments::accept($user, [LegalDocuments::PRIVACY, LegalDocuments::TERMS]);

        if ($request->boolean('acepta_promociones')) {
            $subscriber = PromoSubscriber::firstOrNew(['email' => $user->email]);
            if (!$subscriber->exists) {
                $subscriber->unsubscribe_token = Str::random(48);
            }
            $subscriber->user_id = $user->id;
            $subscriber->save();
        }

        return response()->json([
            'success' => true,
            'message' => '¡Registro exitoso en La 501!',
            ...app(JwtService::class)->issue($user, $request),
            'user' => $this->userPayload($user),
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $key = 'app-login-attempts:' . $request->ip();
        // También por cuenta: 5 contraseñas equivocadas de una misma cuenta, aunque sea desde varias IP
        $keyCuenta = 'login-attempts-account:' . hash('sha256', mb_strtolower(trim($credentials['email'])));
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($keyCuenta, 5)) {
            return response()->json([
                'success' => false,
                'error' => 'too_many_attempts',
                'message' => 'Cuenta bloqueada temporalmente por seguridad. Intenta de nuevo en unos minutos.',
            ], 429, [], JSON_UNESCAPED_UNICODE);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 300);
            RateLimiter::hit($keyCuenta, 900);

            return response()->json([
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Credenciales incorrectas.',
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'inactive_user',
                'message' => 'Tu cuenta está suspendida. Contacta a gerencia.',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        if ($user->role === 'limpieza') {
            return response()->json([
                'success' => false,
                'error' => 'role_not_allowed',
                'message' => 'Tu puesto no requiere acceso a la app.',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        RateLimiter::clear($key);

        return response()->json([
            'success' => true,
            ...app(JwtService::class)->issue($user, $request),
            'user' => $this->userPayload($user),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Cambia el refresh token por un JWT de acceso y un refresh token nuevos (rotación).
     * Un refresh token ya usado cierra toda la sesión: alguien más lo tiene.
     */
    public function refresh(Request $request, JwtService $jwt): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string', 'max:200'],
        ]);

        $tokens = $jwt->refresh($validated['refresh_token'], $request);

        if (!$tokens) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_refresh_token',
                'message' => 'Tu sesión expiró. Inicia sesión de nuevo.',
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json(['success' => true, ...$tokens], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function logout(Request $request): JsonResponse
    {
        // Sesión JWT: se revoca su refresh token. Sesión de Sanctum: se borra su token.
        if ($refresh = $request->input('refresh_token')) {
            app(JwtService::class)->revoke((string) $refresh);
        }
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $this->userPayload($request->user()),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function forgotPassword(Request $request, PhoneVerification $sms): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required_without:telefono', 'nullable', 'email', 'max:254'],
            'telefono' => ['required_without:email', 'nullable', 'digits:10'],
            'canal' => ['nullable', 'in:sms,llamada'],
        ]);

        if (!empty($validated['telefono'])) {
            return $this->forgotPasswordByPhone($request, $sms, $validated['telefono'], $validated['canal'] ?? 'sms');
        }

        $key = 'app-password-reset-' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'success' => false,
                'error' => 'too_many_attempts',
                'message' => 'Has excedido el límite de intentos. Espera unos minutos.',
            ], 429, [], JSON_UNESCAPED_UNICODE);
        }
        RateLimiter::hit($key, 60);

        // No revelamos si el correo existe o no (mismo mensaje siempre).
        \App\Services\PasswordResetCodes::send($validated['email']);

        return response()->json([
            'success' => true,
            'message' => 'Si el correo existe, te enviamos un código de verificación.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function forgotPasswordByPhone(Request $request, PhoneVerification $sms, string $telefono, string $canal): JsonResponse
    {
        if ($tooMany = $this->tooManyPhoneAttempts($request, $telefono)) {
            return $tooMany;
        }

        // No revelamos si el teléfono existe o no (mismo mensaje siempre).
        if (User::where('telefono', $telefono)->exists()) {
            try {
                $sms->send($telefono, $canal);
                Log::info('phone_verification', ['flujo' => 'forgot_password', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'enviado']);
            } catch (\Throwable $e) {
                report($e);
                Log::info('phone_verification', ['flujo' => 'forgot_password', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'fallo_envio']);

                // No consumimos el límite: la falla fue nuestra (Twilio/config).
                return response()->json([
                    'success' => false,
                    'error' => 'sms_unavailable',
                    'message' => 'No pudimos enviar el SMS. Intenta con llamada o por correo.',
                ], 503, [], JSON_UNESCAPED_UNICODE);
            }
        }

        $this->hitPhoneAttempts($request, $telefono);

        return response()->json([
            'success' => true,
            'message' => 'Si el dato está registrado, te enviamos un código.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Verifica el código y cambia la contraseña en un solo paso. Acepta
     * {email, code} (igual que antes) o {telefono, code} verificado con
     * Twilio.
     */
    public function resetPassword(Request $request, PhoneVerification $sms): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required_without:telefono', 'nullable', 'email', 'max:254'],
            'telefono' => ['required_without:email', 'nullable', 'digits:10'],
            'code' => ['required', 'string', 'max:12'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[!@#$%^&*]/'],
        ], [
            'password.regex' => 'Debe incluir mayúscula, minúscula, número y símbolo (!@#$%^&*).',
        ]);

        if (!empty($validated['telefono'])) {
            $user = User::where('telefono', $validated['telefono'])->first();

            if (!$user || !$sms->check($validated['telefono'], $validated['code'])) {
                Log::info('phone_verification', ['flujo' => 'forgot_password', 'telefono' => PhoneVerification::mask($validated['telefono']), 'resultado' => 'codigo_invalido']);

                return response()->json([
                    'success' => false,
                    'error' => 'invalid_or_expired_code',
                    'message' => 'El código no es correcto o ya caducó.',
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $user->update(['password' => Hash::make($validated['password'])]);
            \App\Services\AccountSessions::revokeAll($user); // cierra las sesiones de la app y de la web

            Log::info('phone_verification', ['flujo' => 'forgot_password', 'telefono' => PhoneVerification::mask($validated['telefono']), 'resultado' => 'aprobado']);

            return response()->json([
                'success' => true,
                'message' => '¡Tu contraseña ha sido restablecida con éxito!',
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 10 min y 5 intentos; al quinto fallo el código se borra (PasswordResetCodes)
        if (!\App\Services\PasswordResetCodes::check($validated['email'], $validated['code'])) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_or_expired_code',
                'message' => 'El código no es correcto o ya caducó.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            $user->update(['password' => Hash::make($validated['password'])]);
            \App\Services\AccountSessions::revokeAll($user); // también la web y la app del personal
        }

        \App\Services\PasswordResetCodes::forget($validated['email']);

        return response()->json([
            'success' => true,
            'message' => '¡Tu contraseña ha sido restablecida con éxito!',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Límite compartido por todos los flujos de SMS de este controlador:
     * 3 códigos por teléfono cada 10 min, 10 por IP cada hora. Devuelve la
     * respuesta 429 lista para retornar, o null si se puede continuar.
     */
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
        RateLimiter::hit('sms-otp-phone:' . $telefono, 600); // 10 min
        RateLimiter::hit('sms-otp-ip:' . $request->ip(), 3600); // 1 hora
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'role' => $user->role,
            'points' => $user->points ?? 0,
            'avatar' => $user->avatarUrl(),
        ];
    }
}
