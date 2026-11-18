<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas.
 *
 * - CSP: de dónde puede cargar la página scripts, estilos, imágenes y marcos. 'unsafe-inline' y
 *   'unsafe-eval' siguen porque Alpine.js (versión normal) y el Tailwind del panel los necesitan;
 *   quitarlos exige migrar a la versión CSP de Alpine y compilar el CSS del panel.
 * - Las páginas con sesión iniciada no se guardan en cachés (ni del navegador ni de LiteSpeed en
 *   Hostinger): así nadie ve datos de otra persona por una página cacheada ni con el botón «atrás»
 *   después de cerrar sesión en una computadora compartida.
 */
class SecurityHeaders
{
    /** Mercado Pago: el pago con tarjeta redirige ahí después de enviar el formulario del checkout. */
    private const MERCADO_PAGO = 'https://www.mercadopago.com.mx https://*.mercadopago.com.mx https://*.mercadopago.com https://*.mercadolibre.com';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS solo tiene efecto por HTTPS; por HTTP el navegador lo ignora
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (!$headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->csp());
        }

        if ($this->conDatosPersonales($request)) {
            $headers->set('Cache-Control', 'no-store, private, max-age=0');
            $headers->set('Pragma', 'no-cache');
            $headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
        }

        // PHP agrega «X-Powered-By: PHP/8.x» por su cuenta: decirle a un atacante la versión exacta no ayuda a nadie
        $headers->remove('X-Powered-By');
        if (!headers_sent()) {
            header_remove('X-Powered-By');
        }
        $headers->remove('Server');

        return $response;
    }

    private function csp(): string
    {
        // Google Analytics: solo con ID configurado (y gtag.js solo se descarga si la persona acepta las cookies)
        $ga = \App\Support\Analytics::enabled();
        $gaScript = $ga ? ' https://www.googletagmanager.com' : '';
        $gaConnect = $ga ? ' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com' : '';
        $gaImg = $ga ? ' https://*.google-analytics.com https://*.googletagmanager.com' : '';

        return implode('; ', [
            "default-src 'self'",
            // Tailwind CDN (panel), Alpine.js (unpkg), Chart.js (jsDelivr), Pusher
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net https://js.pusher.com" . $gaScript,
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            // Cloudinary (logo, platillos, carrusel, novedades) y Unsplash (respaldo del carrusel)
            "img-src 'self' data: blob: https://fonts.gstatic.com https://res.cloudinary.com https://images.unsplash.com" . $gaImg,
            "connect-src 'self' https://sockjs-us2.pusher.com wss://ws-us2.pusher.com https://sockjs-mt1.pusher.com wss://ws-mt1.pusher.com" . $gaConnect,
            // Google Maps (Ubicación, solo si el visitante lo pide, y vista previa del panel)
            "frame-src 'self' https://www.google.com https://maps.google.com",
            // El formulario de pago redirige a Mercado Pago: sin esto el navegador bloquea el pago con tarjeta
            "form-action 'self' " . self::MERCADO_PAGO,
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "upgrade-insecure-requests",
        ]);
    }

    /** Páginas que pueden llevar datos de alguien: con sesión iniciada, el panel y el seguimiento de pedidos. */
    private function conDatosPersonales(Request $request): bool
    {
        if ($request->hasSession() && $request->user()) {
            return true;
        }

        return $request->is('admin*', 'pedido/*', 'api/pedido/*', 'configuracion*', 'perfil', 'mis-pedidos', 'recuperar*', 'checkout*', 'carrito');
    }
}
