<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pantallas del personal en la web (mesero, cocina). Uso: ->middleware('web.role:cocinero').
 * El rol se revisa aquí, en la ruta, y no solo dentro de cada método del controlador.
 */
class EnsureWebRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            abort(403);
        }

        return $next($request);
    }
}
