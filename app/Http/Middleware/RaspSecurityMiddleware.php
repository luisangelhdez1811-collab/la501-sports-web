<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class RaspSecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        if (!$request->isMethod('post') && !$request->isMethod('put') && !$request->isMethod('patch') && !$request->isMethod('delete')) {
            return $next($request);
        }

        $input = $request->all();
        $patterns = ['/select/i', '/union/i', '/<script>/i', '/\.\.\//', '/--/'];

        // Revisamos las entradas llamando a una función que sabe manejar arreglos
        if ($this->containsMaliciousInput($input, $patterns)) {
            // La app móvil necesita un error que pueda leer: una página HTML con código 200
            // la haría creer que el pedido o el cobro se guardó.
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'blocked_input',
                    'message' => 'El texto contiene caracteres no permitidos (por ejemplo "--"). Corrígelo e intenta de nuevo.',
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }

            ob_clean();
            $html = view('errors.403')->render();
            die($html);
        }

        return $next($request);
    }

    /**
     * Función recursiva para revisar strings y arreglos anidados
     */
    private function containsMaliciousInput($input, $patterns)
    {
        foreach ($input as $value) {
            // Si es un archivo, lo ignoramos (no se revisa con estas reglas básicas)
            if ($value instanceof UploadedFile) {
                continue;
            }

            // Si es un arreglo (como los ingredientes), nos metemos a revisarlo
            if (is_array($value)) {
                if ($this->containsMaliciousInput($value, $patterns)) {
                    return true;
                }
            }
            // Si es texto o número, le pasamos las reglas de seguridad
            elseif (is_string($value) || is_numeric($value)) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, (string)$value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
