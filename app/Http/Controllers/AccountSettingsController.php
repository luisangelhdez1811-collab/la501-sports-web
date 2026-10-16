<?php

namespace App\Http\Controllers;

use App\Models\PromoSubscriber;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\PhoneVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountSettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $subscribedToPromos = $user->role === 'cliente'
            ? PromoSubscriber::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->exists()
            : false;

        return view('configuracion', compact('user', 'subscribedToPromos'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', '¡Tus datos se actualizaron con éxito!');
    }

    public function requestPhoneOtp(Request $request, PhoneVerification $sms)
    {
        $validated = $request->validate([
            'telefono' => ['required', 'digits:10'],
        ]);
        $user = Auth::user();
        $telefono = $validated['telefono'];

        if (User::where('telefono', $telefono)->where('id', '!=', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Ese número ya pertenece a otra cuenta.'], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $phoneKey = 'sms-otp-phone:' . $telefono;
        $ipKey = 'sms-otp-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return response()->json(['success' => false, 'message' => 'Espera un momento antes de pedir otro código.'], 429, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $sms->send($telefono, 'sms');
            Log::info('phone_verification', ['flujo' => 'change_phone_web', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'enviado']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No pudimos enviar el SMS. Intenta de nuevo en unos minutos.'], 503, [], JSON_UNESCAPED_UNICODE);
        }

        RateLimiter::hit($phoneKey, 600);
        RateLimiter::hit($ipKey, 3600);

        return response()->json(['success' => true, 'message' => 'Te mandamos un código a tu nuevo número.'], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Paso 2: confirma el código y guarda el número nuevo. */
    public function confirmPhone(Request $request, PhoneVerification $sms)
    {
        $validated = $request->validate([
            'telefono' => ['required', 'digits:10'],
            'codigo' => ['required', 'string'],
        ]);
        $user = Auth::user();

        if (User::where('telefono', $validated['telefono'])->where('id', '!=', $user->id)->exists()) {
            return back()->with('error', 'Ese número ya pertenece a otra cuenta.');
        }

        if (!$sms->check($validated['telefono'], $validated['codigo'])) {
            return back()->with('error', 'El código no es correcto o ya caducó.');
        }

        $user->telefono = $validated['telefono'];
        $user->save();

        Log::info('phone_verification', ['flujo' => 'change_phone_web', 'telefono' => PhoneVerification::mask($validated['telefono']), 'resultado' => 'aprobado']);

        return back()->with('success', '¡Tu teléfono fue verificado y actualizado con éxito!');
    }

    /**
     * Derecho de Cancelación (ARCO): elimina/anonimiza la cuenta. Misma
     * lógica que usa la app (AccountDeletionService) — ver ese archivo.
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'confirmacion' => ['required', 'in:ELIMINAR'],
        ], [
            'confirmacion.in' => 'Debes escribir ELIMINAR (en mayúsculas) para confirmar.',
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            return back()->with('error', 'Contraseña incorrecta.');
        }

        if ($user->role !== 'cliente') {
            return back()->with('error', 'Esta acción solo está disponible para cuentas de cliente. El personal se da de baja desde administración.');
        }

        (new AccountDeletionService())->delete($user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Tu cuenta y tus datos personales fueron eliminados. Gracias por haber sido parte de La 501.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Tu contraseña actual no es correcta.');
        }

        $user->password = $request->password;
        $user->save();

        return back()->with('success', '¡Contraseña actualizada con éxito!');
    }

    public function togglePromoEmail(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'cliente') {
            return back()->with('error', 'Esta opción solo está disponible para clientes.');
        }

        $existing = PromoSubscriber::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Ya no recibirás correos de promociones.');
        }

        PromoSubscriber::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'unsubscribe_token' => Str::random(48),
        ]);

        return back()->with('success', '¡Listo! Te avisaremos por correo cuando haya nuevas promociones.');
    }
}
