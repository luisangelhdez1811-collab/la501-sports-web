<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'No tienes permisos de administrador.',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        return $next($request);
    }
}
