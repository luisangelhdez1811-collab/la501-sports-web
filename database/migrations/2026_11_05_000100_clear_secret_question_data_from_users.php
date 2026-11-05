<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * B-15: la pregunta secreta nunca se usó (la recuperación de cuenta es por código) y pedir datos que no
 * se usan va contra el principio de proporcionalidad de la LFPDPPP. Se borran las respuestas guardadas.
 * Las columnas se conservan por ahora (los registros de versiones anteriores no truenan).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pregunta_secreta', 'respuesta_secreta'] as $columna) {
            if (Schema::hasColumn('users', $columna)) {
                DB::table('users')->whereNotNull($columna)->update([$columna => null]);
            }
        }
    }

    public function down(): void
    {
        // Los datos borrados no se pueden recuperar.
    }
};
