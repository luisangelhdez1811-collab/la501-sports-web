<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidosInventarioTest extends TestCase
{
    use RefreshDatabase;

    private function pedido(array $datos = []): Order
    {
        return Order::create(array_merge([
            'customer_name' => 'Cliente de prueba',
            'customer_phone' => '7711234567',
            'total' => 278,
            'status' => 'pending',
        ], $datos));
    }

    public function test_cada_pedido_recibe_un_token_de_seguimiento_unico(): void
    {
        $a = $this->pedido();
        $b = $this->pedido();

        $this->assertSame(48, strlen($a->tracking_token));
        $this->assertNotSame($a->tracking_token, $b->tracking_token);
    }

    public function test_al_cobrar_un_pedido_se_descuenta_el_inventario_respetando_exclusiones(): void
    {
        $pollo = Inventory::create(['name' => 'Alitas de pollo', 'category' => 'Proteína', 'unit' => 'kg', 'current_stock' => 10, 'min_stock' => 2]);
        $apio = Inventory::create(['name' => 'Apio', 'category' => 'Verdura', 'unit' => 'kg', 'current_stock' => 5, 'min_stock' => 1]);

        $producto = Product::create(['name' => 'Alitas BBQ', 'price' => 139, 'category' => 'Botanas', 'available' => 1]);
        Ingrediente::create(['product_id' => $producto->id, 'nombre' => 'Alitas', 'inventory_id' => $pollo->id, 'cantidad_usada' => 0.5]);
        Ingrediente::create(['product_id' => $producto->id, 'nombre' => 'Apio', 'inventory_id' => $apio->id, 'cantidad_usada' => 0.25]);

        $pedido = $this->pedido(['table_number' => 4]);
        OrderItem::create([
            'order_id' => $pedido->id,
            'product_id' => $producto->id,
            'product_name' => 'Alitas BBQ',
            'quantity' => 2,
            'price' => 139,
            'subtotal' => 278,
            'excluded_ingredients' => ['apio'],
        ]);

        // Mientras no se cobra, el inventario no cambia
        $this->assertEquals(10, $pollo->fresh()->current_stock);

        $pedido->update(['status' => 'paid']);

        $this->assertEquals(9, $pollo->fresh()->current_stock);  // 2 × 0.5 kg
        $this->assertEquals(5, $apio->fresh()->current_stock);   // excluido por el cliente
    }

    public function test_el_reporte_de_ventas_solo_cuenta_pedidos_cobrados(): void
    {
        $this->pedido(['table_number' => 1, 'status' => 'paid']);        // mesa cobrada
        $this->pedido(['table_number' => 2, 'status' => 'ready']);       // mesa aún sin cobrar
        $this->pedido(['table_number' => null, 'status' => 'delivered']); // domicilio entregado
        $this->pedido(['table_number' => null, 'status' => 'pending']);   // domicilio sin pagar
        $this->pedido(['table_number' => null, 'status' => 'cancelled']); // cancelado

        $this->assertSame(2, Order::vendidas()->count());
    }
}
