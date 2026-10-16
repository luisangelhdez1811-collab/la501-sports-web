<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_menu_publico_solo_muestra_platillos_disponibles(): void
    {
        Product::create(['name' => 'Alitas BBQ', 'price' => 139, 'category' => 'Botanas', 'available' => 1]);
        Product::create(['name' => 'Platillo agotado', 'price' => 99, 'category' => 'Botanas', 'available' => 0]);

        $this->getJson('/api/menu/products')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Alitas BBQ');
    }

    public function test_agregar_un_platillo_lo_guarda_en_el_carrito(): void
    {
        $this->postJson('/carrito/agregar', [
            'id' => 10,
            'name' => 'Alitas BBQ',
            'price' => 139,
            'quantity' => 2,
            'excluded_ingredients' => ['apio'],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_items', 1);

        $carrito = session('cart');
        $linea = reset($carrito);
        $this->assertSame(2, $linea['quantity']);
        $this->assertSame(['apio'], $linea['excluded_ingredients']);
    }

    public function test_el_carrito_rechaza_datos_incompletos(): void
    {
        $this->postJson('/carrito/agregar', ['id' => 10, 'quantity' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'quantity']);
    }
}
