<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',

        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'non-client' => \App\Http\Middleware\RedirectNonClients::class,
            'staff.auth' => \App\Http\Middleware\AuthenticateStaff::class,
            'role.admin' => \App\Http\Middleware\EnsureAdminRole::class,
            'role.waiter' => \App\Http\Middleware\EnsureWaiterRole::class,
            'no-store' => \App\Http\Middleware\NoStoreResponse::class,
            'web.role' => \App\Http\Middleware\EnsureWebRole::class,
            'owner' => \App\Http\Middleware\EnsureOwner::class,
            'password.recent' => \App\Http\Middleware\RequireRecentPassword::class,
            'cron.token' => \App\Http\Middleware\VerifyCronToken::class,
        ]);

        // B-32: las cuentas aceptan la versión vigente del Aviso y los Términos antes de seguir
        $middleware->web(append: [
            // Una cuenta suspendida sale aunque tenga la sesión abierta
            \App\Http\Middleware\EnsureAccountIsActive::class,
            \App\Http\Middleware\EnsureLegalAccepted::class,
        ]);

        // Primero las cabeceras (envuelven todo, también el 403 del filtro de ataques) y luego el filtro
        $middleware->append(
            \App\Http\Middleware\SecurityHeaders::class
        );

        // Tamaño máximo de cada texto y lista, aunque quiten los límites del navegador
        $middleware->append(
            \App\Http\Middleware\LimitInputSize::class
        );

        $middleware->append(
            \App\Http\Middleware\RaspSecurityMiddleware::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })
    ->create();