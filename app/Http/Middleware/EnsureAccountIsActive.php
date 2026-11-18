<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el dueño suspende una cuenta, esa persona sale en su siguiente clic aunque tuviera la sesión
 * abierta (antes solo se revisaba al iniciar sesión).
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'isActive') && !$user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['error' => 'account_suspended', 'message' => 'Tu cuenta está suspendida.'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está suspendida. Contacta a gerencia.']);
        }

        return $next($request);
    }
}
