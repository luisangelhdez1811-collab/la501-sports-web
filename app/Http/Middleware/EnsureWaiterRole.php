<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWaiterRole
{
    private const ALLOWED_ROLES = ['empleado', 'mesero', 'admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, self::ALLOWED_ROLES, true)) {
            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'No tienes permisos de mesero.',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        return $next($request);
    }
}
