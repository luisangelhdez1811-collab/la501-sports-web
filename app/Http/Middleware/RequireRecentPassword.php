<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pide la contraseña otra vez antes de lo más delicado (respaldos, restaurar la base, personal) si no
 * se confirmó en los últimos minutos (config auth.password_timeout). Así una sesión olvidada abierta
 * o robada no basta para descargar toda la base de datos.
 */
class RequireRecentPassword
{
    public const SESSION_KEY = 'auth.password_confirmed_at';

    public function handle(Request $request, Closure $next): Response
    {
        $confirmada = (int) $request->session()->get(self::SESSION_KEY, 0);

        if (time() - $confirmada <= (int) config('auth.password_timeout', 900)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'password_confirmation_required', 'message' => 'Confirma tu contraseña para continuar.'], 423);
        }

        // Un GET vuelve a la misma página; un formulario vuelve a la página donde estaba (no se reenvía solo).
        $volver = $request->isMethod('GET') ? $request->fullUrl() : url()->previous();
        $request->session()->put('url.intended', $volver);

        return redirect()->route('password.confirm');
    }
}
