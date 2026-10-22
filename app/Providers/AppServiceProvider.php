<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use App\Services\JwtService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
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

        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->ip());
        });

        RateLimiter::for('staff-api', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip());
        });
    }
}
