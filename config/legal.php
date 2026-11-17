<?php

/*
 * Versión vigente de cada documento legal (B-32). Cada versión es un archivo en resources/legal/<doc>/ que
 * no se vuelve a editar: así se puede mostrar lo que cada persona aceptó. Subir una versión obliga a las
 * cuentas a aceptarla al iniciar sesión (web) y hace que el checkout de la app responda 409
 * legal_update_required hasta que la acepten. Una errata se corrige en el mismo archivo SIN subir la versión.
 *
 * La app publica el mismo texto con la misma versión (src/content/legal.ts).
 */

return [
    'privacy_version' => '2026-11-17',
    'terms_version' => '2026-11-17',
    'staff_privacy_version' => '2026-11-17',

    // El checkout de la app responde 409 legal_update_required a quien no ha aceptado la versión vigente.
    // Si la versión publicada de la app todavía no maneja ese error (M-22), ponlo en false en el .env
    // hasta que salga la que sí, para no dejar sin poder pedir a las cuentas que ya existen.
    'enforce_in_api' => env('LEGAL_ENFORCE_API', true),
];
