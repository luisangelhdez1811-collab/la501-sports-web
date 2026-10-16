<?php

namespace App\Http\Middleware;

use App\Models\StaffToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStaff
{
    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $tokenString = $request->bearerToken();

        if (blank($tokenString)) {
            return response()->json([
                'success' => false,
                'error' => 'missing_token',
                'message' => 'Falta el token de autenticación del staff.',
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $token = StaffToken::with('user')
            ->where('token', $tokenString)
            ->first();

        if (!$token || $token->isExpired()) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_or_expired_token',
                'message' => 'El token no es válido o ya expiró. Vuelve a iniciar sesión.',
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $user = $token->user;

        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'inactive_user',
                'message' => 'El usuario ya no está activo.',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        if (
            !empty($rolesPermitidos)
            && !in_array($user->role, $rolesPermitidos, true)
        ) {
            return response()->json([
                'success' => false,
                'error' => 'forbidden_role',
                'message' => 'Tu rol no tiene permiso para esta acción.',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('staffUser', $user);
        $request->attributes->set('staffToken', $token);

        return $next($request);
    }
}
