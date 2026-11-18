<?php

namespace App\Http\Middleware;

use App\Services\LegalDocuments;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B-32 / B-39: si el Aviso de Privacidad, los Términos (clientes) o el aviso para personal cambiaron de
 * versión, la cuenta debe aceptar la nueva antes de seguir usando el sitio. Se puede leer los textos,
 * aceptar, cerrar sesión o eliminar la cuenta sin aceptar.
 */
class EnsureLegalAccepted
{
    private const LIBRES = [
        'legal.accept', 'legal.accept.store', 'logout',
        'aviso-privacidad', 'terminos-condiciones', 'personal.aviso-privacidad',
        'eliminar-cuenta', 'configuracion.eliminar', 'configuracion.mis-datos',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $request->routeIs(...self::LIBRES) || LegalDocuments::pendingFor($user) === []) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'legal_update_required',
                'message' => 'Actualizamos nuestros documentos legales. Acéptalos para continuar.',
            ], 409);
        }

        return redirect()->guest(route('legal.accept'));
    }
}
