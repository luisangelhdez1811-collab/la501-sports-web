<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Esta migración ya no modifica datos: se conservan la pregunta y la respuesta secretas guardadas.
 * Se deja el archivo (vacío) para que el historial de migraciones siga siendo el mismo en todos los
 * entornos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sin cambios en la base de datos.
    }

    public function down(): void
    {
        // Sin cambios en la base de datos.
    }
};
