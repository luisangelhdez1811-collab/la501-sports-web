<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menú nuevo (octubre 2026):
 * - products: sección (comidas / bebidas), estación que lo prepara (cocina / barra / ninguna,
 *   esta última para lo que solo se entrega, como un refresco), presentación ("3 pzs", "1 L"),
 *   orden en la carta y fecha de archivado (los platillos del menú anterior se archivan en vez
 *   de borrarse para no romper el historial de ventas ni las promociones).
 * - product_variants: presentaciones con precio propio (vaso / litro, copeo / botella).
 * - option_groups + menu_options: listas reutilizables (salsas, papas, sabores...) que se
 *   asignan a cada platillo con su propio mínimo y máximo de selecciones.
 * - order_items: copia de las opciones elegidas, la estación y cuándo quedó lista y entregada,
 *   para que cocina y barra marquen cada cosa por separado y el mesero no la vuelva a pedir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('section', 20)->default('comidas')->after('subcategory');
            $table->string('station', 10)->default('cocina')->after('section');
            $table->string('presentation', 100)->nullable()->after('station');
            $table->unsignedInteger('sort_order')->default(0)->after('presentation');
            $table->timestamp('archived_at')->nullable()->after('available');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            // Lo que lee el cliente o el mesero: "Elige tu salsa"
            $table->string('label', 150);
            // Valores con los que se asigna a un platillo nuevo; cada platillo puede cambiarlos
            $table->unsignedTinyInteger('min_select')->default(0);
            $table->unsignedTinyInteger('max_select')->default(1);
            // Cuántas veces se puede repetir la misma opción (Arma tus Papas: hasta 3)
            $table->unsignedTinyInteger('max_per_option')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('menu_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('detail', 100)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_option_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            $table->unsignedTinyInteger('min_select')->default(0);
            $table->unsignedTinyInteger('max_select')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['product_id', 'option_group_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('station', 10)->nullable()->after('product_name');
            $table->text('options')->nullable()->after('excluded_ingredients');
            $table->timestamp('prepared_at')->nullable()->after('notes');
            $table->timestamp('served_at')->nullable()->after('prepared_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['station', 'options', 'prepared_at', 'served_at']);
        });

        Schema::dropIfExists('product_option_group');
        Schema::dropIfExists('menu_options');
        Schema::dropIfExists('option_groups');
        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['section', 'station', 'presentation', 'sort_order', 'archived_at']);
        });
    }
};
