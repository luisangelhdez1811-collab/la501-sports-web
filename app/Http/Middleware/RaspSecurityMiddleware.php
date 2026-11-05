<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class RaspSecurityMiddleware
{
    /**
     * Patrones de ataque reales, no palabras sueltas: «union» y «select» aparecen en texto
     * normal («Col. La Unión», «Selecciona la salsa») y bloquearlos deja a clientes sin poder pedir.
     * La protección contra inyección SQL son las consultas con parámetros de Eloquent; este filtro
     * solo frena lo obvio.
     */
    public const PATTERNS = [
        '/union\s+(all\s+)?select/i',   // 1 UNION SELECT password FROM users
        '/;\s*drop\s+table/i',          // 1; DROP TABLE users
        '/<script/i',                   // <script>alert(1)</script>
        '/\bor\b\s+1\s*=\s*1/i',        // x OR 1=1
        '/\'\s*--/',                    // admin' --   (comilla seguida de comentario SQL)
        '/\.\.\//',                     // ../../.env  (recorrido de rutas)
    ];

    /**
     * Campos que nunca llegan a una consulta SQL ni se muestran como HTML: la contraseña se guarda
     * con hash y el token es del propio framework. Revisarlos solo impedía iniciar sesión con
     * contraseñas válidas que contenían, por ejemplo, «--».
     */
    private const IGNORED_KEYS = ['password', 'password_confirmation', 'current_password', '_token'];

    public function handle(Request $request, Closure $next)
    {

        if (!$request->isMethod('post') && !$request->isMethod('put') && !$request->isMethod('patch') && !$request->isMethod('delete')) {
            return $next($request);
        }

        $input = $request->all();

        // Revisamos las entradas llamando a una función que sabe manejar arreglos
        if ($this->containsMaliciousInput($input, self::PATTERNS)) {
            // La app móvil necesita un error que pueda leer: una página HTML con código 200
            // la haría creer que el pedido o el cobro se guardó.
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'blocked_input',
                    'message' => 'El texto contiene caracteres no permitidos. Corrígelo e intenta de nuevo.',
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
        foreach ($input as $key => $value) {
            if (is_string($key) && in_array($key, self::IGNORED_KEYS, true)) {
                continue;
            }

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
