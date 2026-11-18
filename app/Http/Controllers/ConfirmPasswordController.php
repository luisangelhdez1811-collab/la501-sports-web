<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequireRecentPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/** Vuelve a pedir la contraseña antes de entrar a respaldos y personal (middleware password.recent). */
class ConfirmPasswordController extends Controller
{
    public function show()
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        if (!Hash::check($request->input('password'), $request->user()->password)) {
            Log::channel('single')->warning('Confirmación de contraseña fallida', ['usuario_id' => $request->user()->id, 'ip' => $request->ip()]);

            return back()->withErrors(['password' => 'La contraseña no es correcta.']);
        }

        $request->session()->put(RequireRecentPassword::SESSION_KEY, time());
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
