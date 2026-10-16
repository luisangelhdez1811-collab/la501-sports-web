<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
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

    // =========================================================================
    // login() dividido para bajar returns de 7 → 2 (S1142)
    // =========================================================================

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $key = 'login-attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Cuenta bloqueada temporalmente por seguridad.']);
        }

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['email' => 'Credenciales incorrectas.']);
        }

        return $this->handleAuthenticatedUser($request, $key);
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
        $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users',
            'telefono'          => 'required',
            'password'          => [
                'required', 'confirmed', 'min:8',
                'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[!@#$%^&*]/',
            ],
            'pregunta_secreta'  => 'required',
            'respuesta_secreta' => 'required',
        ], [
            'password.regex' => 'La contraseña debe tener una mayúscula, un número y un carácter especial.',
            'email.unique'   => 'Este correo ya está registrado en La 501.',
        ]);

        User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'telefono'          => $request->telefono,
            'password'          => Hash::make($request->password),
            'pregunta_secreta'  => $request->pregunta_secreta,
            'respuesta_secreta' => Hash::make($request->respuesta_secreta),
            'role'              => 'cliente',
            'is_active'         => 1,
        ]);

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

        $orders = \App\Models\Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('auth.perfil', compact('user', 'allAchievements', 'unlockedIds', 'reservacionesTotales', 'orders'));
    }

    public function updatePerfil(Request $request)
    {
        $request->validate([
            'telefono' => 'nullable|string|max:15',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = \App\Models\User::find(Auth::id());
        $user->telefono = $request->telefono;

        if ($request->hasFile('avatar')) {
            // Borrar avatar anterior
            if ($user->avatar) {
                $oldPath = public_path($user->avatar);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file      = $request->file('avatar');
            $extension = $file->getClientOriginalExtension();
            $filename  = 'av_' . time() . '_' . Auth::id() . '.' . $extension;

            // Usar putFileAs con disco local apuntando a public/avatars
            \Storage::disk('public_avatars')->putFileAs('', $file, $filename);

            $user->avatar = 'avatars/' . $filename;
        }

        $user->save();
        return back()->with('status', '¡Perfil actualizado con éxito!');
    }

    // =========================================================================
    // Recuperación de contraseña OTP
    // =========================================================================

    public function enviarCodigo(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $key = 'password-reset-' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Has excedido el límite de intentos. Por favor espera unos segundos.']);
        }
        RateLimiter::hit($key, 60);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $codigo = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($codigo), 'created_at' => Carbon::now()]
            );
            Mail::send('emails.otp', ['codigo' => $codigo], function ($message) use ($user) {
                $message->to($user->email)->subject('Tu código de seguridad - La 501 Sports');
            });
        }

        session(['email_para_codigo' => $request->email]);
        return redirect()->route('password.verify.code');
    }

    public function showVerifyCode()
    {
        if (!session('email_para_codigo')) {
            return redirect()->route('password.request');
        }
        return view('auth.verify-code', ['email' => session('email_para_codigo')]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'code'  => 'required|string|size:8',
        ], [
            'code.required' => 'Debes ingresar el código.',
            'code.size'     => 'El código debe tener exactamente 8 caracteres.',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->code, $record->token) || Carbon::parse($record->created_at)->addMinutes(5)->isPast()) {
            return back()->withErrors(['code' => 'El código es incorrecto o ha caducado.']);
        }

        session(['allow_password_reset' => $request->email]);
        return redirect()->route('password.reset.form');
    }

    public function showCustomResetForm()
    {
        if (!session('allow_password_reset')) {
            return redirect()->route('login');
        }
        return view('auth.reset', ['email' => session('allow_password_reset')]);
    }

    public function updateCustomPassword(Request $request)
    {
        $email = session('allow_password_reset');
        if (!$email) {
            return redirect()->route('login');
        }

        $request->validate([
            'password' => [
                'required', 'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'password.min'     => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed'   => 'Debe incluir mayúscula y minúscula.',
            'password.numbers' => 'Debe incluir al menos un número.',
            'password.symbols' => 'Debe incluir al menos un símbolo (!@#$).',
        ]);

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget('allow_password_reset');

        return redirect()->route('login')->with('status', '¡Tu contraseña ha sido restablecida con éxito!');
    }
}
