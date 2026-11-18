<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'verify_sid' => env('TWILIO_VERIFY_SID'),
    ],

    // Pagos con tarjeta. El webhook_secret es la clave secreta de Webhooks en el panel de Mercado Pago
    // (Tus integraciones → tu aplicación → Webhooks): con ella se valida la firma x-signature de los avisos.
    // Google Analytics 4 (G-XXXXXXX): sin valor no se carga y el aviso de cookies es solo informativo.
    // site_verification: el código de la etiqueta meta que da Search Console (solo el content="...").
    'google' => [
        'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

];
