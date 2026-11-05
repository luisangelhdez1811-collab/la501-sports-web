<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Quién vende y cómo reclamar (LFPC art. 76 Bis III): el proveedor debe dar su domicilio físico, teléfonos y
 * medios para quejas ANTES de celebrar la transacción. Los datos se leen de la tabla settings (claves
 * provider_*) para que el restaurante los corrija sin tocar código; si falta alguno se usa el valor por
 * defecto de aquí. El RFC se agrega cuando el dueño lo entregue (provider_rfc).
 */
class ProviderInfo
{
    public const DEFAULTS = [
        'provider_name' => 'La 501 Sports Restaurant',
        'provider_owner' => 'Andrés Rivera Jiménez',
        'provider_address' => 'Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hgo.',
        'provider_phone' => '771 109 7827',
        'provider_email' => 'Equipolapapa501@gmail.com',
        'provider_complaint_hours' => 'de 1:00 p.m. a 10:00 p.m.',
        'provider_rfc' => '',
    ];

    public static function get(): array
    {
        $guardados = Setting::whereIn('key', array_keys(self::DEFAULTS))->pluck('value', 'key');

        $valor = fn (string $clave) => filled($guardados->get($clave)) ? trim((string) $guardados->get($clave)) : self::DEFAULTS[$clave];

        return [
            'name' => $valor('provider_name'),
            'owner' => $valor('provider_owner'),
            'address' => $valor('provider_address'),
            'phone' => $valor('provider_phone'),
            'email' => $valor('provider_email'),
            'complaint_hours' => $valor('provider_complaint_hours'),
            'rfc' => filled($guardados->get('provider_rfc')) ? trim((string) $guardados->get('provider_rfc')) : null,
        ];
    }

    /** La línea que se imprime bajo el botón de confirmar y en el ticket de seguimiento. */
    public static function line(): string
    {
        $p = self::get();

        return sprintf(
            'Vende: %s (%s) · %s · Tel. %s%s · Quejas: %s, %s',
            $p['name'],
            $p['owner'],
            $p['address'],
            $p['phone'],
            $p['rfc'] ? ' · RFC ' . $p['rfc'] : '',
            $p['email'],
            $p['complaint_hours']
        );
    }
}
