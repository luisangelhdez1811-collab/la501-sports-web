<?php

namespace App\Services;

use App\Models\Setting;

/**
 * B-11: lo que el cliente debe saber y aceptar antes de pedir. UNA sola clase para la web y la API,
 * para que las dos apliquen exactamente las mismas reglas:
 *
 *  - Siempre: aceptar que el envío NO está incluido y se paga al repartidor (LFPC art. 7 Bis).
 *  - Si alguna línea lleva alcohol: confirmar que es mayor de 18 años (LGS art. 220).
 *  - Si alguna línea trae notas: autorizar su uso, porque pueden incluir alergias u otros datos de
 *    salud, que son datos sensibles (LFPDPPP).
 *
 * Si el producto lleva alcohol o no sale SIEMPRE de la base de datos (OrderLineBuilder y
 * PromotionLineBuilder); lo que mande el cliente nunca lo decide.
 */
class CheckoutCompliance
{
    public const DEFAULT_DELIVERY_NOTE = 'El envío a domicilio lo realiza un repartidor independiente y se le paga directamente a él al recibir. Su costo depende de la distancia; pregúntalo al hacer tu pedido por WhatsApp.';

    public const MESSAGE_ADULT = 'Tu pedido lleva bebidas con alcohol: confirma que eres mayor de 18 años. Al entregar se te pedirá una identificación oficial.';

    public const MESSAGE_HEALTH = 'Escribiste notas en tu pedido: autoriza que, si incluyen alergias o datos de salud, los usemos solo para preparar tu pedido.';

    /** El texto del envío que el restaurante edita en el panel (Configuración → Costo del envío a domicilio). */
    public static function deliveryNote(): string
    {
        $nota = trim((string) Setting::get('delivery_fee_note'));

        return $nota !== '' ? $nota : self::DEFAULT_DELIVERY_NOTE;
    }

    /** Reglas de validación de las casillas (se suman a las del checkout). */
    public static function rules(): array
    {
        return [
            'delivery_terms_accepted' => ['accepted'],
            'adult_confirmed' => ['nullable', 'boolean'],
            'health_data_consent' => ['nullable', 'boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'delivery_terms_accepted.accepted' => 'Confirma que leíste cómo se cobra el envío a domicilio.',
            'adult_confirmed.boolean' => 'La confirmación de mayoría de edad no es válida.',
            'health_data_consent.boolean' => 'La autorización de tus notas no es válida.',
        ];
    }

    /**
     * Revisa las líneas YA armadas desde la base de datos. Devuelve el mensaje de lo que falta
     * confirmar o null si todo está en orden.
     *
     * @param  array<int, array{alcoholic?: bool, notes?: ?string}>  $lines
     * @param  array<string, mixed>  $input  lo que mandó el cliente (casillas)
     */
    public static function check(array $lines, array $input): ?string
    {
        return self::failure($lines, $input)['message'] ?? null;
    }

    /** Igual que check(), pero dice también cuál casilla falta (la web marca ese campo). */
    public static function failure(array $lines, array $input): ?array
    {
        if (self::hasAlcohol($lines) && !self::isTrue($input['adult_confirmed'] ?? false)) {
            return ['field' => 'adult_confirmed', 'message' => self::MESSAGE_ADULT];
        }

        if (self::hasNotes($lines) && !self::isTrue($input['health_data_consent'] ?? false)) {
            return ['field' => 'health_data_consent', 'message' => self::MESSAGE_HEALTH];
        }

        return null;
    }

    /**
     * Las columnas de evidencia que se guardan en el pedido: cuándo aceptó el cliente cada cosa.
     * Solo se llena la fecha de lo que de verdad se le pidió.
     */
    public static function evidence(array $lines): array
    {
        $ahora = now();

        return [
            'delivery_terms_accepted_at' => $ahora,
            'adult_confirmed_at' => self::hasAlcohol($lines) ? $ahora : null,
            'health_data_consent_at' => self::hasNotes($lines) ? $ahora : null,
        ];
    }

    public static function hasAlcohol(array $lines): bool
    {
        return collect($lines)->contains(fn ($line) => !empty($line['alcoholic']));
    }

    public static function hasNotes(array $lines): bool
    {
        return collect($lines)->contains(fn ($line) => filled($line['notes'] ?? null));
    }

    private static function isTrue(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
