<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * B-22 (LFPC art. 32): las frases comparativas que no se pueden demostrar («las mejores», «el mejor
 * ambiente») se cambian por descripciones. Solo toca los textos originales del carrusel; si el
 * restaurante ya los cambió, se respetan. B-25: la cerveza ya no aparece junto al texto deportivo.
 */
return new class extends Migration
{
    private const CAMBIOS = [
        'SABOR INIGUALABLE' => 'SABOR A LA PARRILLA',
        'Las mejores hamburguesas a la parrilla y cerveza artesanal bien fría.' => 'Hamburguesas a la parrilla con pan brioche y papas recién hechas.',
        'El mejor ambiente familiar para disfrutar de tus deportes favoritos.' => 'Un ambiente familiar para ver tus deportes favoritos.',
    ];

    public function up(): void
    {
        foreach (['title', 'description'] as $columna) {
            foreach (self::CAMBIOS as $viejo => $nuevo) {
                DB::table('carousel_slides')->where($columna, $viejo)->update([$columna => $nuevo]);
            }
        }
    }

    public function down(): void
    {
        // Corrección de contenido: no se regresa a las frases anteriores.
    }
};
