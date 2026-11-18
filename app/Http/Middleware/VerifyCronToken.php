<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** URLs para un cron externo: solo con ?token=<CRON_TOKEN>. Sin CRON_TOKEN configurado no existen. */
class VerifyCronToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = (string) config('app.cron_token');
        $recibido = (string) ($request->query('token') ?? $request->header('X-Cron-Token') ?? '');

        if ($esperado === '' || strlen($esperado) < 32 || !hash_equals($esperado, $recibido)) {
            abort(404);
        }

        return $next($request);
    }
}
