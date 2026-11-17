<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Venta y publicidad de bebidas con alcohol. Una sola clase para la web, la API y el panel.
 *
 *  - Leyenda (LGS art. 308 y Reglamento en Materia de Publicidad): toda pieza que muestra o promueve
 *    alcohol la lleva visible (B-25), junto con el aviso de que no es para menores (B-37).
 *  - Venta (licencia municipal): solo si el restaurante tiene licencia para vender a domicilio, dentro
 *    del horario autorizado y fuera de una ley seca (B-28). Por defecto la venta en línea está CERRADA
 *    hasta que el dueño confirme la licencia en el panel.
 *
 * Settings: alcohol_legend, alcohol_delivery_allowed ('1' / '0'), alcohol_hours (JSON por día:
 * {"lunes": "13:00-22:00", ...}; un día ausente o vacío = no se vende ese día; sin la clave = sin
 * restricción de horario) y alcohol_suspended_until (fecha y hora de fin de la ley seca).
 */
class AlcoholPolicy
{
    public const DEFAULT_LEGEND = 'El abuso en el consumo de este producto es nocivo para la salud.';

    public const ADULTS_ONLY = 'Venta de bebidas alcohólicas solo a mayores de 18 años.';

    public const TIMEZONE = 'America/Mexico_City';

    public const DAYS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];

    public static function legend(): string
    {
        $texto = trim((string) Setting::get('alcohol_legend'));

        return $texto !== '' ? $texto : self::DEFAULT_LEGEND;
    }

    public static function deliveryAllowed(): bool
    {
        return Setting::get('alcohol_delivery_allowed', '0') === '1';
    }

    /** Fin de la ley seca, o null si no hay una vigente. */
    public static function suspendedUntil(?Carbon $ahora = null): ?Carbon
    {
        $valor = trim((string) Setting::get('alcohol_suspended_until'));
        if ($valor === '') {
            return null;
        }

        $fin = Carbon::parse($valor, self::TIMEZONE);

        return $fin->isAfter(self::now($ahora)) ? $fin : null;
    }

    /** Horario autorizado por día ('lunes' => '13:00-22:00'), o null si no se capturó. */
    public static function hours(): ?array
    {
        $json = Setting::get('alcohol_hours');
        if (blank($json)) {
            return null;
        }

        $horario = json_decode((string) $json, true);

        return is_array($horario) ? $horario : null;
    }

    public static function salesOpen(?Carbon $ahora = null): bool
    {
        return self::closedMessage($ahora) === null;
    }

    /** Por qué no se puede vender alcohol en este momento, o null si sí se puede. */
    public static function closedMessage(?Carbon $ahora = null): ?string
    {
        $ahora = self::now($ahora);

        if (!self::deliveryAllowed()) {
            return 'Por ahora no vendemos bebidas con alcohol en línea. Puedes pedirlas en el restaurante.';
        }

        if ($fin = self::suspendedUntil($ahora)) {
            return 'La venta de bebidas con alcohol está suspendida por disposición oficial hasta el '
                . $fin->locale('es')->isoFormat('dddd D [de] MMMM [a las] h:mm a') . '.';
        }

        $horario = self::hours();
        if ($horario !== null && !self::withinHours($horario, $ahora)) {
            return 'En este horario no vendemos bebidas con alcohol a domicilio.'
                . (($hoy = self::todayRange($horario, $ahora)) ? " Hoy se venden de {$hoy}." : '');
        }

        return null;
    }

    private static function withinHours(array $horario, Carbon $ahora): bool
    {
        $rango = self::todayRange($horario, $ahora);
        if (!$rango || !preg_match('/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', $rango, $m)) {
            return false;
        }

        $minuto = $ahora->hour * 60 + $ahora->minute;
        $inicio = (int) $m[1] * 60 + (int) $m[2];
        $fin = (int) $m[3] * 60 + (int) $m[4];

        // Un horario que pasa de medianoche (20:00-02:00)
        return $fin > $inicio
            ? $minuto >= $inicio && $minuto < $fin
            : $minuto >= $inicio || $minuto < $fin;
    }

    private static function todayRange(array $horario, Carbon $ahora): ?string
    {
        $rango = trim((string) ($horario[self::DAYS[$ahora->dayOfWeek]] ?? ''));

        return $rango !== '' ? $rango : null;
    }

    private static function now(?Carbon $ahora): Carbon
    {
        return ($ahora ?? Carbon::now())->copy()->setTimezone(self::TIMEZONE);
    }

    /**
     * Red de seguridad para el panel (B-25): frases que la publicidad de alcohol no puede llevar (LGS
     * art. 308): imperativos que invitan a consumir y referencias a deportes. No sustituye la revisión
     * humana; solo detiene los casos obvios.
     */
    public const FORBIDDEN_IMPERATIVES = [
        'pide ya', 'pidela', 'pidelo', 'pide tu', 'tomate', 'tomatela', 'echate', 'bebete', 'a chupar',
        'hasta que el cuerpo aguante', 'no te quedes sin', 'aprovecha y toma', 'a beber',
    ];

    public const FORBIDDEN_SPORTS = [
        'partido', 'partidos', 'gol', 'goles', 'futbol', 'nfl', 'nba', 'mlb', 'beisbol', 'box', 'ufc',
        'super bowl', 'mundial', 'liga mx', 'final', 'clasico', 'jornada',
    ];

    /** Las frases prohibidas que aparecen en el texto, para decirle al admin cuáles cambiar. */
    public static function forbiddenPhrases(string $texto): array
    {
        $limpio = ' ' . preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower(\Illuminate\Support\Str::ascii($texto))) . ' ';

        return collect([...self::FORBIDDEN_IMPERATIVES, ...self::FORBIDDEN_SPORTS])
            ->filter(fn ($frase) => str_contains($limpio, ' ' . $frase . ' '))
            ->values()
            ->all();
    }

    /** Lo que la app necesita para mostrar la leyenda y ocultar el botón de agregar (/public/info). */
    public static function publicInfo(): array
    {
        $cerrado = self::closedMessage();

        return [
            'legend' => self::legend(),
            'adults_only' => self::ADULTS_ONLY,
            'sales_open' => $cerrado === null,
            'closed_message' => $cerrado,
        ];
    }
}
