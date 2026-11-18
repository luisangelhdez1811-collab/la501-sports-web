<?php

/*
 * Plazos de conservación (B-31). Son los mismos que publica el Aviso de Privacidad, sección 12
 * (resources/legal/privacy/*.php): si cambian aquí, cambia el aviso (versión nueva) y al revés.
 * php artisan privacy:purge los aplica todos los días a las 3:30 a. m.
 */

return [
    'retention_days' => [
        'order_gps' => 30,         // ubicación GPS de entregas ya entregadas o canceladas
        'order_notes' => 90,       // notas de los pedidos (pueden traer alergias)
        'reservations' => 365,     // reservaciones, contadas desde la fecha reservada
        'contact_messages' => 365, // mensajes del formulario de contacto
        'server_logs' => 14,       // storage/logs
        'backups' => 30,           // respaldos de la base de datos (storage/app/.../backups)
        'otp_hours' => 1,          // códigos de recuperación de contraseña (vencen a los 5 minutos)
    ],
];
