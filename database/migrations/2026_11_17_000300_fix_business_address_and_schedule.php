<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * B-21 (LFPC; NMX-COE-001-SCFI-2018): todos los canales deben mostrar la misma dirección real del proveedor.
 * La de settings decía «Av. Deportiva #501» (y el respaldo de Ubicación, Tulancingo), distinta de la del
 * Aviso, los Términos y provider_address. El horario decía «13:00 PM», que no existe: se corrige solo el
 * formato, sin cambiar la hora. Solo toca los valores viejos; si el restaurante ya capturó otros, se respetan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $poner = function (string $clave, string $valor, callable $esViejo) {
            $actual = DB::table('settings')->where('key', $clave)->value('value');
            if ($actual === null || trim((string) $actual) === '' || $esViejo((string) $actual)) {
                DB::table('settings')->updateOrInsert(['key' => $clave], ['value' => $valor, 'updated_at' => now()]);
            }
        };

        $poner('address_line1', 'Plaza de la Revolución Mexicana #16', fn ($v) => str_contains($v, 'Deportiva'));
        $poner('address_line2', 'Col. Centro', fn ($v) => false);
        $poner('address_line3', 'C.P. 43000, Huejutla de Reyes, Hgo.', fn ($v) => str_contains($v, 'Tulancingo'));

        foreach (DB::table('settings')->where('key', 'like', 'schedule_%')->get() as $dia) {
            $corregido = preg_replace('/\b13:00\s*PM\b/i', '1:00 PM', (string) $dia->value);
            if ($corregido !== $dia->value) {
                DB::table('settings')->where('key', $dia->key)->update(['value' => $corregido, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Corrección de datos: no se regresa a los valores incorrectos.
    }
};
