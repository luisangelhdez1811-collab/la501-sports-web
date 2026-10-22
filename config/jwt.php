<?php

/*
|--------------------------------------------------------------------------
| Sesiones de la app móvil con JWT (Bearer)
|--------------------------------------------------------------------------
| - Token de acceso: JWT firmado con HS256, de vida corta. La app lo manda en
|   "Authorization: Bearer ...".
| - Refresh token: valor aleatorio de un solo uso (en la base solo se guarda su
|   SHA-256). Al usarlo se cambia por uno nuevo (rotación); si alguien reusa uno
|   ya cambiado, se cierran todas las sesiones de esa familia.
|
| JWT_SECRET: al menos 32 caracteres. Si no existe, se deriva de APP_KEY.
*/

return [
    'secret' => env('JWT_SECRET'),

    // Minutos de vida del token de acceso.
    'ttl' => (int) env('JWT_TTL', 15),

    // Minutos de vida del refresh token (30 días).
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 60 * 24 * 30),

    'issuer' => env('JWT_ISSUER', env('APP_URL', 'la501-sports')),

    // Segundos de tolerancia por diferencia de reloj entre teléfono y servidor.
    'leeway' => 30,
];
