<?php

use App\Services\ProviderInfo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * B-29: datos del proveedor que se muestran antes de pagar. Se guardan en settings para poder corregirlos
 * sin tocar código; solo se crean los que no existan (no pisa lo que el restaurante ya haya escrito).
 * El RFC queda vacío hasta que el dueño lo entregue.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (ProviderInfo::DEFAULTS as $clave => $valor) {
            if (!DB::table('settings')->where('key', $clave)->exists()) {
                DB::table('settings')->insert(['key' => $clave, 'value' => $valor, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys(ProviderInfo::DEFAULTS))->delete();
    }
};
