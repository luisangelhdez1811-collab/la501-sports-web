<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lo que solo puede hacer el dueño del sistema (personal, respaldos, restaurar la base). Antes se
 * reconocía por «el usuario con id 2»; ahora es la columna users.is_owner, que no se puede llenar
 * desde ningún formulario.
 */
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isOwner()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', 'Solo el dueño del sistema puede entrar a esta sección.');
        }

        return $next($request);
    }
}
