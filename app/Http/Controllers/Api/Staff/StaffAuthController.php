<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffAuthController extends Controller
{
    private const ROLES_STAFF = ['admin', 'cocinero', 'empleado'];

    private const HORAS_EXPIRACION = 8;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'string', 'size:4'],
        ]);

        $user = User::query()
            ->where('pin', $validated['pin'])
            ->whereIn('role', self::ROLES_STAFF)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_pin',
                'message' => 'PIN incorrecto o no pertenece a un usuario del staff activo.',
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        StaffToken::where('user_id', $user->id)
            ->where('expires_at', '<', now())
            ->delete();

        $token = StaffToken::create([
            'user_id' => $user->id,
            'token' => StaffToken::generateTokenString(),
            'expires_at' => now()->addHours(self::HORAS_EXPIRACION),
        ]);

        return response()->json([
            'success' => true,
            'token' => $token->token,
            'expires_at' => $token->expires_at->toISOString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenString = $request->bearerToken();

        if (filled($tokenString)) {
            StaffToken::where('token', $tokenString)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('staffUser');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
