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
        $alitas = Product::create(['name' => 'Alitas BBQ', 'price' => 139, 'category' => 'Botanas', 'available' => 1]);

        $this->postJson('/carrito/agregar', [
            'id' => $alitas->id,
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

    public function test_el_precio_del_carrito_sale_de_la_base_de_datos_y_no_del_navegador(): void
    {
        $alitas = Product::create(['name' => 'Alitas BBQ', 'price' => 139, 'category' => 'Botanas', 'available' => 1]);

        $this->postJson('/carrito/agregar', ['id' => $alitas->id, 'quantity' => 1, 'name' => 'Otra cosa', 'price' => 1])
            ->assertOk();

        $linea = collect(session('cart'))->first();
        $this->assertEquals(139, $linea['price']);
        $this->assertSame('Alitas BBQ', $linea['name']);
    }

    public function test_el_carrito_rechaza_datos_incompletos(): void
    {
        $this->postJson('/carrito/agregar', ['quantity' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id', 'quantity']);
    }

    public function test_el_carrito_rechaza_un_platillo_que_no_existe(): void
    {
        $this->postJson('/carrito/agregar', ['id' => 999999, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
