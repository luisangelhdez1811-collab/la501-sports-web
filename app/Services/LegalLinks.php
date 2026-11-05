<?php

namespace App\Services;

/**
 * URL públicas de los documentos legales y de la página para eliminar la cuenta. Se arman con APP_URL
 * (no con el host de la petición) y, en producción, siempre con https: las tiendas de apps las revisan
 * y la app rechaza imágenes o enlaces por http.
 */
class LegalLinks
{
    public static function privacy(): string
    {
        return self::absolute('aviso-privacidad');
    }

    public static function terms(): string
    {
        return self::absolute('terminos-condiciones');
    }

    public static function deleteAccount(): string
    {
        return self::absolute('eliminar-cuenta');
    }

    private static function absolute(string $routeName): string
    {
        $url = rtrim((string) config('app.url'), '/') . route($routeName, [], false);

        return app()->environment('production') ? preg_replace('#^http://#i', 'https://', $url) : $url;
    }
}
