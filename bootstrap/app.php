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
        ]);

        $middleware->append(
            \App\Http\Middleware\RaspSecurityMiddleware::class
        );

        $middleware->append(
            \App\Http\Middleware\SecurityHeaders::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })
    ->create();