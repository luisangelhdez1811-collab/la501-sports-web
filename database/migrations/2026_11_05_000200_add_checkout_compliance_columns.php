<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * B-11: lo que el cliente debe saber y aceptar antes de pedir.
 *
 *  - products.alcoholic: el menú marca qué lleva alcohol (LGS art. 220: prohibido vender a menores).
 *  - orders: fecha en que el cliente confirmó ser mayor de edad, aceptó cómo se cobra el envío
 *    (LFPC art. 7 Bis) y autorizó el uso de sus notas, que pueden traer alergias (datos de salud,
 *    sensibles según la LFPDPPP). Son la evidencia de lo que aceptó.
 *  - settings.delivery_fee_note: el texto del envío que edita el restaurante desde el panel.
 */
return new class extends Migration
{
    /**
     * Categorías del menú que son bebidas alcohólicas (de 2% a 55% de alcohol, LGS art. 217).
     * Incluye el nombre del menú anterior («Cervezas») por si esta migración corre antes de
     * importar el menú nuevo. Es una copia a propósito: una migración no debe depender de código
     * de la app que cambie después.
     */
    private const ALCOHOLIC_CATEGORIES = ['6 Shots', 'Coctelería', 'Destilados', 'Digestivos', 'Cervecería', 'Cervezas'];

    private const DEFAULT_DELIVERY_NOTE = 'El envío a domicilio lo realiza un repartidor independiente y se le paga directamente a él al recibir. Su costo depende de la distancia; pregúntalo al hacer tu pedido por WhatsApp.';

    public function up(): void
    {
        if (!Schema::hasColumn('products', 'alcoholic')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('alcoholic')->default(false);
            });
        }

        // La cerveza 0.0 y los «preparados» (solo la mezcla, sin cerveza) no llevan alcohol
        DB::table('products')
            ->whereIn('category', self::ALCOHOLIC_CATEGORIES)
            ->where(function ($q) {
                $q->whereNull('subcategory')->orWhere('subcategory', '<>', 'Preparados');
            })
            ->where('name', 'not like', '%Cero%')
            ->where('name', 'not like', 'Preparado%')
            ->update(['alcoholic' => true]);

        Schema::table('orders', function (Blueprint $table) {
            foreach (['adult_confirmed_at', 'delivery_terms_accepted_at', 'health_data_consent_at'] as $columna) {
                if (!Schema::hasColumn('orders', $columna)) {
                    $table->timestamp($columna)->nullable();
                }
            }
        });

        if (!DB::table('settings')->where('key', 'delivery_fee_note')->exists()) {
            DB::table('settings')->insert([
                'key' => 'delivery_fee_note',
                'value' => self::DEFAULT_DELIVERY_NOTE,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['adult_confirmed_at', 'delivery_terms_accepted_at', 'health_data_consent_at'] as $columna) {
                if (Schema::hasColumn('orders', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });

        if (Schema::hasColumn('products', 'alcoholic')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('alcoholic');
            });
        }
    }
};
