<?php

/*
 * CORS: qué otros sitios pueden llamar a la API desde un navegador.
 *
 * Antes se aceptaba cualquier origen (*). Ahora solo el propio sitio (APP_URL) y los que se agreguen en
 * CORS_ALLOWED_ORIGINS (separados por comas), por ejemplo un panel web externo.
 *
 * La app móvil NO depende de esto: CORS es una regla de los navegadores y una app nativa no la aplica.
 * Los webhooks (Mercado Pago) tampoco, porque llaman de servidor a servidor.
 */

$propios = array_filter([
    rtrim((string) env('APP_URL', ''), '/'),
]);

$extra = array_filter(array_map(
    fn ($o) => rtrim(trim($o), '/'),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
));

// Con y sin «www.», para que funcione con cualquiera de las dos direcciones del dominio
$conWww = [];
foreach (array_merge($propios, $extra) as $origen) {
    $conWww[] = $origen;
    if (preg_match('#^(https?://)(www\.)?(.+)$#', $origen, $m)) {
        $conWww[] = $m[1] . ($m[2] ? '' : 'www.') . $m[3];
    }
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_unique($conWww)),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
