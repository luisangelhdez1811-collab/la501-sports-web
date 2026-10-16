<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Ingrediente;
use Illuminate\Support\Facades\DB;

class IngredientInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        Ingrediente::truncate();
        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Obtener todos los productos de la categoría de hamburguesas o que contengan "hamburguesa" en el nombre
        $products = Product::where('category', 'Hamburguesas')
            ->orWhere('name', 'like', '%hamburguesa%')
            ->get();

        // Mapeo de ingredientes clave detectados en la descripción y su enlace al inventario
        $keywords = [
            'carne de res' => [
                'nombre'         => 'Carne de Res',
                'inventory_id'   => 9, // Carne de res (Hamburguesa)
                'cantidad_usada' => 1.00
            ],
            'carne' => [
                'nombre'         => 'Carne de Res',
                'inventory_id'   => 9, 
                'cantidad_usada' => 1.00
            ],
            'boneless' => [
                'nombre'         => 'Boneless de Pollo',
                'inventory_id'   => 12, // Boneless de pollo
                'cantidad_usada' => 0.15 // 150g
            ],
            'arrachera' => [
                'nombre'         => 'Carne de Arrachera',
                'inventory_id'   => 10, // Arrachera
                'cantidad_usada' => 0.15 // 150g
            ],
            'chorizo argentino' => [
                'nombre'         => 'Chorizo Argentino',
                'inventory_id'   => 19, // Chorizo Argentino
                'cantidad_usada' => 0.08 // 80g
            ],
            'salchichón' => [
                'nombre'         => 'Salchichón',
                'inventory_id'   => 16, // Salchichón
                'cantidad_usada' => 0.05 // 50g
            ],
            'salchichon' => [
                'nombre'         => 'Salchichón',
                'inventory_id'   => 16,
                'cantidad_usada' => 0.05
            ],
            'tocino' => [
                'nombre'         => 'Tocino',
                'inventory_id'   => 14, // Tocino
                'cantidad_usada' => 0.04 // 40g
            ],
            'jamón' => [
                'nombre'         => 'Jamón',
                'inventory_id'   => 15, // Jamón
                'cantidad_usada' => 0.03 // 30g
            ],
            'jamon' => [
                'nombre'         => 'Jamón',
                'inventory_id'   => 15,
                'cantidad_usada' => 0.03
            ],
            'queso amarillo' => [
                'nombre'         => 'Queso Amarillo',
                'inventory_id'   => 25, // Queso Amarillo (Rebanadas)
                'cantidad_usada' => 1.00
            ],
            'queso fundido' => [
                'nombre'         => 'Queso Mozzarella', // Queso fundido se asocia a Mozzarella
                'inventory_id'   => 28, // Queso Mozzarella
                'cantidad_usada' => 0.03
            ],
            'queso blanco' => [
                'nombre'         => 'Queso Mozzarella',
                'inventory_id'   => 28,
                'cantidad_usada' => 0.03
            ],
            'queso manchego' => [
                'nombre'         => 'Queso Manchego',
                'inventory_id'   => 26,
                'cantidad_usada' => 0.03
            ],
            'manchego' => [
                'nombre'         => 'Queso Manchego',
                'inventory_id'   => 26,
                'cantidad_usada' => 0.03
            ],
            'jitomate' => [
                'nombre'         => 'Jitomate',
                'inventory_id'   => 32, // Jitomate
                'cantidad_usada' => 0.03
            ],
            'tomate' => [
                'nombre'         => 'Jitomate',
                'inventory_id'   => 32,
                'cantidad_usada' => 0.03
            ],
            'cebolla morada' => [
                'nombre'         => 'Cebolla Morada',
                'inventory_id'   => 34, // Cebolla Morada
                'cantidad_usada' => 0.02
            ],
            'cebolla blanca' => [
                'nombre'         => 'Cebolla Blanca',
                'inventory_id'   => 33, // Cebolla Blanca
                'cantidad_usada' => 0.02
            ],
            'cebolla' => [
                'nombre'         => 'Cebolla Blanca',
                'inventory_id'   => 33, // Por defecto Cebolla Blanca
                'cantidad_usada' => 0.02
            ],
            'lechuga' => [
                'nombre'         => 'Lechuga',
                'inventory_id'   => 35, // Lechuga
                'cantidad_usada' => 0.02
            ],
            'aros de cebolla' => [
                'nombre'         => 'Aros de Cebolla',
                'inventory_id'   => 54, // Aros de Cebolla (Congelados)
                'cantidad_usada' => 0.05
            ],
            'camarón' => [
                'nombre'         => 'Camarón',
                'inventory_id'   => 23, // Camarón
                'cantidad_usada' => 0.10
            ],
            'camaron' => [
                'nombre'         => 'Camarón',
                'inventory_id'   => 23,
                'cantidad_usada' => 0.10
            ],
            'surimi' => [
                'nombre'         => 'Surimi',
                'inventory_id'   => 24, // Surimi
                'cantidad_usada' => 0.05
            ],
            'piña' => [
                'nombre'         => 'Piña',
                'inventory_id'   => 46, // Piña
                'cantidad_usada' => 0.05
            ],
            'pina' => [
                'nombre'         => 'Piña',
                'inventory_id'   => 46,
                'cantidad_usada' => 0.05
            ]
        ];

        foreach ($products as $product) {
            $desc = mb_strtolower($product->description . ' ' . $product->name, 'UTF-8');
            $matchedIngredients = [];

            // 1. Siempre agregar Pan de Hamburguesa si es una Hamburguesa
            if ($product->category === 'Hamburguesas' || str_contains(mb_strtolower($product->name, 'UTF-8'), 'hamburguesa')) {
                $matchedIngredients[] = [
                    'nombre'         => 'Pan de Hamburguesa',
                    'inventory_id'   => 70, // Pan de Hamburguesa
                    'cantidad_usada' => 1.00
                ];
            }

            // 2. Analizar la descripción contra el mapa de palabras clave
            foreach ($keywords as $key => $data) {
                if (str_contains($desc, $key)) {
                    // Evitar duplicar cebolla si ya detectó "cebolla morada" o "cebolla blanca"
                    if ($key === 'cebolla' && (str_contains($desc, 'cebolla morada') || str_contains($desc, 'cebolla blanca'))) {
                        continue;
                    }
                    // Evitar duplicar carne de res si es carne de arrachera
                    if ($key === 'carne' && str_contains($desc, 'arrachera')) {
                        continue;
                    }

                    // Evitar duplicar ingredientes en el mismo platillo
                    $alreadyMatched = false;
                    foreach ($matchedIngredients as $m) {
                        if ($m['nombre'] === $data['nombre']) {
                            $alreadyMatched = true;
                            break;
                        }
                    }
                    
                    if (!$alreadyMatched) {
                        $matchedIngredients[] = $data;
                    }
                }
            }

            // Registrar los ingredientes para el platillo
            foreach ($matchedIngredients as $ingData) {
                Ingrediente::create([
                    'product_id'     => $product->id,
                    'nombre'         => $ingData['nombre'],
                    'inventory_id'   => $ingData['inventory_id'],
                    'cantidad_usada' => $ingData['cantidad_usada']
                ]);
            }
        }
    }
}
