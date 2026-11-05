<?php

namespace App\Services;

/**
 * URL públicas que salen del servidor hacia la app o hacia documentos (enlaces legales, foto de perfil).
 * Se arman con APP_URL —no con el host de la petición— y, en producción, siempre con https: la app
 * rechaza imágenes y enlaces por http y las tiendas de apps revisan estas direcciones.
 */
class PublicUrl
{
    /** Ruta relativa al sitio ("avatars/av_1.jpg" o "/eliminar-cuenta") → URL completa. */
    public static function forPath(string $path): string
    {
        return self::secure(rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/'));
    }

    /** En producción obliga a https; fuera de ella deja la URL como está (local y pruebas usan http). */
    public static function secure(string $url): string
    {
        return app()->environment('production') ? preg_replace('#^http://#i', 'https://', $url) : $url;
    }
}
