<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Red de seguridad para TODO lo que llega a la web y a la API, antes de cualquier controlador. Aunque
 * alguien quite el «maxlength» del navegador o llame directo al servidor, aquí se frena:
 *
 *  - textos más largos de lo que tiene sentido para ese campo (nombre, notas, contraseña...);
 *  - cualquier otro texto de más de 5000 caracteres;
 *  - listas con más de 200 elementos o anidadas más de 6 niveles;
 *  - el carácter nulo (\0), que algunos ataques usan para cortar textos.
 *
 * Cada controlador tiene además su propia validación con el límite exacto del campo. Esto no reemplaza
 * esa validación: es lo que queda si un día alguien la olvida.
 */
class LimitInputSize
{
    /** Límite por nombre de campo (en caracteres). Coincide con lo que permite la base de datos. */
    public const FIELD_LIMITS = [
        'password' => 128, 'password_confirmation' => 128, 'current_password' => 128,
        'confirm_password' => 128, 'new_password' => 128,
        'name' => 255, 'customer_name' => 120, 'nombre_completo' => 120, 'diner_label' => 40,
        'email' => 254, 'customer_email' => 254, 'correo_electronico' => 254,
        'telefono' => 20, 'customer_phone' => 20, 'phone' => 20,
        'customer_address' => 300, 'direccion_favorita' => 255,
        'notes' => 150, 'reason' => 255, 'cancel_reason' => 255, 'forced_reason' => 500,
        'subject' => 150, 'message' => 3000,
        'code' => 12, 'codigo' => 12, 'pin' => 8,
        'q' => 100, 'search' => 100, 'client_ref' => 64,
    ];

    public const DEFAULT_LIMIT = 5000;

    public const MAX_ITEMS = 200;

    public const MAX_DEPTH = 6;

    public function handle(Request $request, Closure $next): Response
    {
        // El aviso de Mercado Pago no lo escribe una persona y lo valida su firma
        if ($request->is('api/webhooks/*')) {
            return $next($request);
        }

        $problema = $this->revisar(array_merge($request->query(), $request->post()), 0);

        if ($problema === null) {
            return $next($request);
        }

        [$campo, $mensaje] = $problema;

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'input_too_large',
                'message' => $mensaje,
                'errors' => [$campo => [$mensaje]],
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        return back()->withInput($request->except(array_keys(self::FIELD_LIMITS)))->withErrors([$campo => $mensaje]);
    }

    /** @return array{0: string, 1: string}|null  el campo con problema y el mensaje */
    private function revisar(array $datos, int $nivel, string $padre = ''): ?array
    {
        if ($nivel > self::MAX_DEPTH) {
            return [$padre ?: 'datos', 'La información enviada tiene demasiados niveles.'];
        }

        if (count($datos) > self::MAX_ITEMS) {
            return [$padre ?: 'datos', 'Se enviaron demasiados elementos a la vez.'];
        }

        foreach ($datos as $clave => $valor) {
            $ruta = $padre === '' ? (string) $clave : $padre . '.' . $clave;

            if ($valor instanceof UploadedFile) {
                continue;
            }

            if (is_array($valor)) {
                if ($problema = $this->revisar($valor, $nivel + 1, $ruta)) {
                    return $problema;
                }
                continue;
            }

            if (!is_string($valor)) {
                continue;
            }

            if (str_contains($valor, "\0")) {
                return [$ruta, 'El texto contiene caracteres no permitidos.'];
            }

            $limite = self::FIELD_LIMITS[is_string($clave) ? $clave : ''] ?? self::DEFAULT_LIMIT;
            if (mb_strlen($valor) > $limite) {
                return [$ruta, "El texto es demasiado largo (máximo {$limite} caracteres)."];
            }
        }

        return null;
    }
}
