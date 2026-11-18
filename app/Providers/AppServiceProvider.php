<?php

namespace App\Providers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Guard "jwt": sesiones de la app móvil con "Authorization: Bearer <JWT>".
        // Un token de Sanctum ("id|texto") no tiene forma de JWT y lo atiende el guard sanctum.
        Auth::viaRequest('jwt', function (Request $request) {
            $token = $request->bearerToken();

            return $token && substr_count($token, '.') === 2
                ? app(JwtService::class)->userFromToken($token)
                : null;
        });

        // Un token de Sanctum de una cuenta suspendida deja de servir (el JWT ya lo revisaba).
        Sanctum::authenticateAccessTokensUsing(
            fn ($token, bool $isValid) => $isValid && $token->tokenable instanceof User && $token->tokenable->is_active
        );

        $this->configureRateLimiting();
        $this->configureProduction();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('public-api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('staff-api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Formularios públicos que guardan algo o mandan correos (registro, contacto, reservaciones,
        // boletín): pocos por minuto y un tope por hora, para frenar spam y bots sin estorbar a nadie.
        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(5)->by('forms-min:' . $request->ip()),
            Limit::perHour(30)->by('forms-hour:' . $request->ip()),
        ]);
    }

    /** En producción todo va por HTTPS y se avisa si algo quedó con valores de desarrollo. */
    private function configureProduction(): void
    {
        if (!$this->app->environment('production')) {
            return;
        }

        URL::forceScheme('https');

        if (config('app.debug')) {
            // Se apaga aquí mismo: un error en producción nunca debe mostrar código, consultas ni claves
            config(['app.debug' => false]);
            Log::critical('APP_DEBUG estaba activo en producción y se apagó automáticamente. Ponlo en false en el .env.');
        }
    }
}
