<?php

namespace App\Services;

/**
 * URL públicas de los documentos legales y de la página para eliminar la cuenta (ver PublicUrl).
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
        return PublicUrl::forPath(route($routeName, [], false));
    }
}
