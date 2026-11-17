<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-36 (Ley Federal del Derecho de Autor y Ley Federal de Protección a la Propiedad Industrial): de dónde
 * salió cada imagen pública (propia, banco con licencia, proveedor). Las que ya existían quedan sin
 * origen y se completan al cambiar su imagen.
 */
return new class extends Migration
{
    private const TABLAS = ['products', 'promotions', 'news', 'carousel_slides'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('image_source', 150)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('image_source');
            });
        }
    }
};
