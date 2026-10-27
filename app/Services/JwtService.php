<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sesiones de la app móvil: emite el JWT de acceso (HS256) y el refresh token, valida el
 * JWT de cada petición y rota el refresh token. Ver config/jwt.php.
 */
class JwtService
{
    private const ALGORITMO = 'HS256';

    /**
     * Abre una sesión (inicio de sesión o registro) o continúa una familia al rotar.
     *
     * @return array{token: string, access_token: string, token_type: string, expires_in: int, refresh_token: string}
     */
    public function issue(User $user, Request $request, ?string $family = null): array
    {
        // Reloj de Laravel (no time()): el mismo que usan la base y las pruebas.
        $ahora = now()->getTimestamp();
        $ttl = max(1, (int) config('jwt.ttl')) * 60;

        $jwt = JWT::encode([
            'iss' => $this->issuer(),
            'sub' => (string) $user->id,
            'iat' => $ahora,
            'nbf' => $ahora,
            'exp' => $ahora + $ttl,
            'jti' => (string) Str::uuid(),
            'typ' => 'access',
            'role' => $user->role,
        ], $this->secret(), self::ALGORITMO);

        $refresh = Str::random(64);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refresh),
            'family' => $family ?? (string) Str::uuid(),
            'expires_at' => now()->addMinutes(max(1, (int) config('jwt.refresh_ttl'))),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        return [
            // "token" se conserva para versiones anteriores de la app.
            'token' => $jwt,
            'access_token' => $jwt,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
            'refresh_token' => $refresh,
        ];
    }

    /** El usuario dueño de un JWT de acceso válido, o null (firma, emisor, vigencia o cuenta). */
    public function userFromToken(string $jwt): ?User
    {
        try {
            JWT::$leeway = (int) config('jwt.leeway', 30);
            JWT::$timestamp = now()->getTimestamp();
            $claims = JWT::decode($jwt, new Key($this->secret(), self::ALGORITMO));
        } catch (Throwable) {
            return null;
        }

        if (($claims->typ ?? null) !== 'access' || ($claims->iss ?? null) !== $this->issuer()) {
            return null;
        }

        $user = User::find((int) ($claims->sub ?? 0));

        return $user && $user->is_active ? $user : null;
    }

    /**
     * Cambia un refresh token por un par nuevo. Devuelve null si no existe, venció, la
     * cuenta está suspendida o ya se había usado (en ese caso cierra toda la familia:
     * alguien más lo tiene).
     */
    public function refresh(string $plain, Request $request): ?array
    {
        return DB::transaction(function () use ($plain, $request) {
            $actual = RefreshToken::where('token_hash', hash('sha256', $plain))->lockForUpdate()->first();

            if (!$actual) {
                return null;
            }

            if ($actual->revoked_at !== null) {
                $this->revokeFamily($actual->family);
                Log::warning('[JWT] Reuso de refresh token: se cerró la sesión completa', [
                    'user_id' => $actual->user_id,
                    'ip' => $request->ip(),
                ]);

                return null;
            }

            $user = $actual->user;
            if ($actual->expires_at->isPast() || !$user || !$user->is_active) {
                $actual->update(['revoked_at' => now()]);

                return null;
            }

            $nuevo = $this->issue($user, $request, $actual->family);
            $actual->update([
                'revoked_at' => now(),
                'replaced_by_id' => RefreshToken::where('token_hash', hash('sha256', $nuevo['refresh_token']))->value('id'),
            ]);

            return $nuevo;
        });
    }

    /** Cierra la sesión de este refresh token (y sus rotaciones). */
    public function revoke(string $plain): void
    {
        $family = RefreshToken::where('token_hash', hash('sha256', $plain))->value('family');
        if ($family) {
            $this->revokeFamily($family);
        }
    }

    /** Cierra todas las sesiones de la app del usuario (cambio de contraseña, baja). */
    public function revokeAll(User $user): void
    {
        RefreshToken::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    private function revokeFamily(string $family): void
    {
        RefreshToken::where('family', $family)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    private function issuer(): string
    {
        return (string) config('jwt.issuer');
    }

    /** JWT_SECRET, o una llave derivada de APP_KEY (32 bytes) si no está configurado. */
    private function secret(): string
    {
        $secret = (string) config('jwt.secret');
        if (strlen($secret) >= 32) {
            return $secret;
        }

        $appKey = (string) config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }

        return hash_hmac('sha256', 'la501-jwt', $appKey, true);
    }
}
