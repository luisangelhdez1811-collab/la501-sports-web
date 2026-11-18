<?php

namespace App\Services;

use App\Models\StaffToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cierra las sesiones abiertas de una cuenta en todos lados: navegadores (sesiones guardadas en la base
 * y la cookie «recordarme»), la app móvil (tokens de Sanctum y refresh tokens del JWT) y la app del
 * personal. Se usa al cambiar o restablecer la contraseña y al suspender una cuenta, para que quien
 * tuviera una sesión robada la pierda en ese momento.
 */
class AccountSessions
{
    public static function revokeAll(User $user, ?string $exceptoSesion = null): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when($exceptoSesion, fn ($q) => $q->where('id', '!=', $exceptoSesion))
                ->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
        $user->tokens()->delete();
        app(JwtService::class)->revokeAll($user);
        StaffToken::where('user_id', $user->id)->delete();
    }
}
