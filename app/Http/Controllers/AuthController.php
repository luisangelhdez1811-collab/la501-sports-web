<?php

namespace App\Http\Controllers;

use App\Services\LegalDocuments;
use Illuminate\Http\Request;
use App\Models\PromoSubscriber;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\PhoneVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\PasswordResetCodes;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }
        return view('auth.login');
    }

    public function showRegistro()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }
        return view('auth.registro');
    }

    public function showRecuperar() { return view('auth.recuperar'); }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);
        $credentials['email'] = mb_strtolower(trim($credentials['email']));

        // Dos límites: por IP (alguien probando muchas cuentas) y por cuenta (alguien probando muchas
        // contraseñas de una misma cuenta desde varias IP). 5 fallos = 15 minutos de espera.
        $keyIp = 'login-attempts:' . $request->ip();
        $keyCuenta = 'login-attempts-account:' . hash('sha256', $credentials['email']);
        if (RateLimiter::tooManyAttempts($keyIp, 10) || RateLimiter::tooManyAttempts($keyCuenta, 5)) {
            $minutos = (int) ceil(max(RateLimiter::availableIn($keyIp), RateLimiter::availableIn($keyCuenta)) / 60);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Demasiados intentos. Por seguridad, espera {$minutos} min e intenta de nuevo."]);
        }

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($keyIp, 900);
            RateLimiter::hit($keyCuenta, 900);
            Log::channel('single')->info('Inicio de sesión fallido', ['ip' => $request->ip(), 'email_hash' => hash('sha256', $credentials['email'])]);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'Correo o contraseña incorrectos.']);
        }

        RateLimiter::clear($keyCuenta);

        return $this->handleAuthenticatedUser($request, $keyIp);
    }

    private function handleAuthenticatedUser(Request $request, string $key)
    {
        $user = Auth::user();

        if ($user->is_active == 0) {
            return $this->logoutAndRedirect($request, 'Tu cuenta está suspendida. Contacta a gerencia.');
        }

        if (in_array($user->role, ['limpieza'])) {
            return $this->logoutAndRedirect($request, 'Tu puesto no requiere acceso al sistema.');
        }

        $request->session()->regenerate();
        RateLimiter::clear($key);

        return $this->redirectByRole($user->role);
    }

    private function logoutAndRedirect(Request $request, string $message)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return back()->withErrors(['email' => $message]);
    }

    private function redirectByRole(string $role)
    {
        if ($role === 'cocinero') {
            return redirect()->route('kitchen.index');
        }
        if ($role === 'cliente') {
            return redirect()->route('pedido');
        }
        if ($role === 'empleado') {
            return redirect()->route('mesero.mesas');
        }
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect('/');
    }

    public function registrar(Request $request)
    {
        $request->merge(['telefono' => \App\Support\Phone::normalize($request->input('telefono'))]);

        $request->validate([
            'name'              => 'required|string|min:2|max:120',
            'email'             => 'required|email|max:254|unique:users,email',
            'telefono'          => 'required|digits:10',
            'password'          => ['required', 'confirmed', \App\Support\PasswordPolicy::rule()],
            // Los Términos dicen que el servicio es para mayores de 18 y se venden bebidas con alcohol.
            // Casilla sin premarcar: si no viene marcada, el navegador ni la manda y "accepted" rechaza.
            'mayor_de_edad'     => ['required', 'accepted'],
            // El checkbox de términos/privacidad del formulario — si no
            // viene marcado, el navegador ni lo manda, así que "accepted"
            // rechaza el registro solo con eso.
            'terms'             => ['required', 'accepted'],
        ], [
            'telefono.digits' => 'El teléfono debe tener 10 dígitos.',
            'email.unique'    => 'Este correo ya está registrado en La 501.',
            'terms.accepted'  => 'Debes aceptar los términos y el aviso de privacidad para registrarte.',
            'mayor_de_edad.required' => 'Debes confirmar que eres mayor de 18 años para registrarte.',
            'mayor_de_edad.accepted' => 'Debes confirmar que eres mayor de 18 años para registrarte.',
        ] + \App\Support\PasswordPolicy::messages());

        $user = User::create([
            'name'              => $request->name,
            'email'             => mb_strtolower(trim($request->email)),
            'telefono'          => $request->telefono,
            'password'          => Hash::make($request->password),
            'role'              => 'cliente',
            'is_active'         => 1,
            // Evidencia del consentimiento (derechos ARCO).
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

        return redirect()->route('login')->with('status', '¡Registro exitoso en La 501!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('status', 'Sesión cerrada con seguridad.');
    }

    public function showPerfil()
    {
        $user = \App\Models\User::find(Auth::id());

        // Verificar logros de fidelidad al visitar el perfil
        (new \App\Services\AchievementService())->check($user);

        $allAchievements      = \App\Models\Achievement::orderBy('categoria')->get();
        $unlockedIds          = $user->achievements()->pluck('achievement_id')->toArray();
        $reservacionesTotales = \App\Models\Reservation::where('correo_electronico', $user->email)->count();

        // Resumen: los últimos 5. La lista completa está en «Mis pedidos».
        $ordersTotal = \App\Models\Order::where('user_id', $user->id)->count();
        $orders = \App\Models\Order::with('items:id,order_id,product_name,quantity')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return view('auth.perfil', compact('user', 'allAchievements', 'unlockedIds', 'reservacionesTotales', 'orders', 'ordersTotal'));
    }

    public function updatePerfil(Request $request)
    {
        // Misma validación y mismo guardado seguro que la app (B-16)
        $request->validate([
            'avatar' => array_merge(['nullable'], AvatarService::rules()),
        ], AvatarService::messages());

        $user = \App\Models\User::find(Auth::id());
        // El teléfono verificado sirve para buscar pedidos con PIN: solo cambia con el código SMS
        // (Configuración). Se ignora aunque llegue en este formulario.

        if ($request->hasFile('avatar')) {
            app(AvatarService::class)->replace($user, $request->file('avatar'));
        }

        return back()->with('status', '¡Perfil actualizado con éxito!');
    }

    // =========================================================================
    // Recuperación de contraseña OTP
    // =========================================================================

    public function enviarCodigo(Request $request, PhoneVerification $sms)
    {
        $validated = $request->validate([
            'email' => ['required_without:telefono', 'nullable', 'email', 'max:254'],
            'telefono' => ['required_without:email', 'nullable', 'digits:10'],
            'canal' => ['nullable', 'in:sms,llamada'],
        ]);

        if (!empty($validated['telefono'])) {
            return $this->enviarCodigoPorTelefono($request, $sms, $validated['telefono'], $validated['canal'] ?? 'sms');
        }

        $key = 'password-reset-' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Has excedido el límite de intentos. Por favor espera unos segundos.']);
        }
        RateLimiter::hit($key, 60);

        PasswordResetCodes::send($validated['email']);

        session(['email_para_codigo' => $validated['email']]);
        session()->forget('telefono_para_codigo');
        return redirect()->route('password.verify.code');
    }

    /** Misma protección de enumeración que el correo: siempre redirige igual. */
    private function enviarCodigoPorTelefono(Request $request, PhoneVerification $sms, string $telefono, string $canal)
    {
        $phoneKey = 'sms-otp-phone:' . $telefono;
        $ipKey = 'sms-otp-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return back()->withErrors(['telefono' => 'Espera un momento antes de pedir otro código.']);
        }

        if (User::where('telefono', $telefono)->exists()) {
            try {
                $sms->send($telefono, $canal);
                Log::info('phone_verification', ['flujo' => 'forgot_password_web', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'enviado']);
            } catch (\Throwable $e) {
                report($e);
                Log::info('phone_verification', ['flujo' => 'forgot_password_web', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'fallo_envio']);
                return back()->withErrors(['telefono' => 'No pudimos enviar el SMS. Intenta de nuevo en unos minutos o usa tu correo.']);
            }
        }

        RateLimiter::hit($phoneKey, 600);
        RateLimiter::hit($ipKey, 3600);

        session(['telefono_para_codigo' => $telefono]);
        session()->forget('email_para_codigo');
        return redirect()->route('password.verify.code');
    }

    public function showVerifyCode()
    {
        $email = session('email_para_codigo');
        $telefono = session('telefono_para_codigo');

        if (!$email && !$telefono) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-code', [
            'email' => $email,
            'telefono' => $telefono,
            // El de correo lo genera este sistema y el de SMS Twilio Verify (configurado a 8 en su consola)
            'codeLength' => 8,
        ]);
    }

    public function verifyCode(Request $request, PhoneVerification $sms)
    {
        if ($request->filled('telefono')) {
            return $this->verifyCodigoPorTelefono($request, $sms);
        }

        $request->validate([
            'email' => 'required',
            'code'  => 'required|digits:8',
        ], [
            'code.required' => 'Debes ingresar el código.',
            'code.size'     => 'El código debe tener exactamente 8 caracteres.',
        ]);

        // El correo es el que se guardó al pedir el código, no el que venga en el formulario
        $email = (string) session('email_para_codigo', $request->email);

        if (!PasswordResetCodes::check($email, (string) $request->code)) {
            return back()->withErrors(['code' => 'El código es incorrecto o ha caducado. Después de 5 intentos hay que pedir uno nuevo.']);
        }

        session(['allow_password_reset' => $email]);
        session()->forget(['email_para_codigo', 'telefono_para_codigo']);
        return redirect()->route('password.reset.form');
    }

    private function verifyCodigoPorTelefono(Request $request, PhoneVerification $sms)
    {
        $request->validate([
            'telefono' => 'required|digits:10',
            'code' => 'required|string|max:12',
        ], [
            'code.required' => 'Debes ingresar el código.',
        ]);

        if (!User::where('telefono', $request->telefono)->exists() || !$sms->check($request->telefono, $request->code)) {
            Log::info('phone_verification', ['flujo' => 'forgot_password_web', 'telefono' => PhoneVerification::mask($request->telefono), 'resultado' => 'codigo_invalido']);
            return back()->withErrors(['code' => 'El código es incorrecto o ha caducado.']);
        }

        Log::info('phone_verification', ['flujo' => 'forgot_password_web', 'telefono' => PhoneVerification::mask($request->telefono), 'resultado' => 'aprobado']);

        session(['allow_password_reset_phone' => $request->telefono]);
        session()->forget(['email_para_codigo', 'telefono_para_codigo']);
        return redirect()->route('password.reset.form');
    }

    public function showCustomResetForm()
    {
        $email = session('allow_password_reset');
        $telefono = session('allow_password_reset_phone');

        if (!$email && !$telefono) {
            return redirect()->route('login');
        }

        return view('auth.reset', ['email' => $email, 'telefono' => $telefono]);
    }

    public function updateCustomPassword(Request $request)
    {
        $email = session('allow_password_reset');
        $telefono = session('allow_password_reset_phone');
        if (!$email && !$telefono) {
            return redirect()->route('login');
        }

        $request->validate([
            'password' => ['required', 'confirmed', \App\Support\PasswordPolicy::rule()],
        ], \App\Support\PasswordPolicy::messages());

        $user = $telefono ? User::where('telefono', $telefono)->first() : User::where('email', $email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
            // Quien tuviera la sesión o la contraseña vieja pierde el acceso en ese momento
            \App\Services\AccountSessions::revokeAll($user);
        }

        if ($email) {
            PasswordResetCodes::forget($email);
        }
        session()->forget(['allow_password_reset', 'allow_password_reset_phone']);
        session()->regenerate();

        return redirect()->route('login')->with('status', '¡Tu contraseña ha sido restablecida con éxito!');
    }
}
