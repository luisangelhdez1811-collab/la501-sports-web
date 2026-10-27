<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - menu_categories: las categorías de la carta se administran (agregar, renombrar, ordenar,
 *   quitar) en vez de salir del texto libre de cada platillo. products.category sigue siendo
 *   el nombre, así que la API y el historial no cambian.
 * - promotion_product.slot: los platillos con el mismo slot son un solo renglón de la
 *   promoción; si el renglón tiene varios, el cliente elige cuál ("1 hamburguesa a elegir").
 */
return new class extends Migration
{
    public function up(): void
    {
        // El nombre se compara con products.category: misma intercalación o MySQL no los compara
        $collation = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
            ? DB::selectOne("SHOW FULL COLUMNS FROM products WHERE Field = 'category'")?->Collation
            : null;

        Schema::create('menu_categories', function (Blueprint $table) use ($collation) {
            $table->id();
            $name = $table->string('name', 100)->unique();
            if ($collation) {
                $name->collation($collation);
            }
            $table->string('section', 20)->default('comidas');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Las categorías actuales, en el orden en que aparecen en la carta
        $categorias = DB::table('products')
            ->whereNull('archived_at')
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->selectRaw('category, MAX(section) AS section, MIN(sort_order) AS orden, MIN(id) AS primero')
            ->groupBy('category')
            ->orderByRaw("CASE WHEN MAX(section) = 'bebidas' THEN 1 ELSE 0 END")
            ->orderBy('orden')
            ->orderBy('primero')
            ->get();

        foreach ($categorias as $i => $categoria) {
            DB::table('menu_categories')->insert([
                'name' => $categoria->category,
                'section' => $categoria->section ?: 'comidas',
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('promotion_product', function (Blueprint $table) {
            $table->unsignedInteger('slot')->default(0)->after('quantity');
        });

        // Lo que ya existía: cada platillo es su propio renglón fijo
        DB::table('promotion_product')->update(['slot' => DB::raw('id')]);
    }

    public function down(): void
    {
        Schema::table('promotion_product', function (Blueprint $table) {
            $table->dropColumn('slot');
        });

        Schema::dropIfExists('menu_categories');
    }
};
