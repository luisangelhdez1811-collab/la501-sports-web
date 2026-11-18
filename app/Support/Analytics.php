<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Google Analytics 4, solo con permiso.
 *
 * - Sin GOOGLE_ANALYTICS_ID en el .env no se carga nada y el aviso de cookies es solo informativo.
 * - Con el ID, el aviso pide «Aceptar / Rechazar» y gtag.js se descarga únicamente después de «Aceptar»
 *   (la ley pide consentimiento para cookies que no son indispensables).
 * - Nunca se mide el panel, la cocina, el mesero ni las páginas con datos de una persona (su cuenta, el
 *   seguimiento de un pedido, el pago, enlaces con códigos): así no viaja a Google nada que identifique a
 *   alguien. Tampoco a quien inició sesión como personal.
 */
class Analytics
{
    /** Páginas que nunca se miden. */
    private const SIN_MEDIR = [
        'admin*', 'cocina*', 'mesero*', 'api/*',
        'perfil', 'configuracion*', 'mis-pedidos', 'pedido/*', 'checkout*', 'carrito',
        'recuperar*', 'restablecer*', 'verificar*', 'confirmar-contrasena', 'aceptar-avisos', 'newsletter/*',
    ];

    /** El ID con formato válido de GA4 (G-XXXXXXX) o null. Se valida porque va dentro de un <script> y de la CSP. */
    public static function id(): ?string
    {
        $id = trim((string) config('services.google.analytics_id'));

        return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) ? $id : null;
    }

    public static function enabled(): bool
    {
        return self::id() !== null;
    }

    /** Si en esta página se puede cargar Analytics (siempre que la persona lo acepte). */
    public static function allowedOn(Request $request): bool
    {
        if (!self::enabled() || $request->is(...self::SIN_MEDIR)) {
            return false;
        }

        $user = $request->user();

        return !$user || $user->role === 'cliente';
    }
}
