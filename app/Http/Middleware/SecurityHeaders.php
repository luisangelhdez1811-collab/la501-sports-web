<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // ── Anti-Clickjacking ──
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // ── Evita sniffing de MIME ──
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ── Fuerza HTTPS ──
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // ── Referrer ──
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── Permisos ──
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=()');

        // ── CSP completo basado en tus CDNs reales ──
        $csp = implode('; ', [
            "default-src 'self'",

            // Scripts: Tailwind CDN, Alpine.js (unpkg), Chart.js (jsDelivr), Pusher
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
                "https://cdn.tailwindcss.com " .
                "https://unpkg.com " .
                "https://cdn.jsdelivr.net " .
                "https://js.pusher.com",

            // Estilos: inline (Tailwind genera estilos inline) + Google Fonts
            "style-src 'self' 'unsafe-inline' " .
                "https://fonts.googleapis.com",

            // Fuentes: Google Fonts static
            "font-src 'self' " .
                "https://fonts.gstatic.com",

            // Imágenes: self + data URIs (para íconos inline)
            "img-src 'self' data: blob: " .
                "https://fonts.gstatic.com",

            // Conexiones: self + Pusher websockets
            "connect-src 'self' " .
                "https://sockjs-mt1.pusher.com " .
                "wss://ws-mt1.pusher.com",

            // Frames: Google Maps embed
            "frame-src 'self' " .
                "https://www.google.com",

            // form-action: solo tu propio dominio
            "form-action 'self'",

            // frame-ancestors: evita clickjacking desde iframes externos
            "frame-ancestors 'self'",

            // base-uri
            "base-uri 'self'",

            // object-src
            "object-src 'none'",

            // Fuerza HTTPS en recursos
            "upgrade-insecure-requests",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        // ── Eliminar headers que revelan info del servidor ──
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
