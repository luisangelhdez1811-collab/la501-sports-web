<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B-02: al migrar, los pedidos que ya dieron puntos quedan marcados para no volver a darlos.
 */
class MigracionPuntosTest extends TestCase
{
    use RefreshDatabase;

    private function pedido(?int $usuario, string $estado): Order
    {
        return Order::create([
            'user_id' => $usuario,
            'customer_name' => 'Ana',
            'customer_phone' => '5512345678',
            'customer_address' => 'Calle 1',
            'total' => 100,
            'status' => $estado,
            'payment_method' => 'efectivo',
        ]);
    }

    public function test_la_migracion_marca_como_premiados_solo_los_pedidos_que_ya_dieron_puntos(): void
    {
        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        $cliente = User::create(['name' => 'Cliente', 'email' => 'cliente@la501.test', 'password' => 'La501Sports!', 'role' => 'cliente']);

        $premiados = collect(['paid', 'preparing', 'ready', 'delivering', 'delivered'])
            ->map(fn ($estado) => $this->pedido($cliente->id, $estado));
        $noPremiados = [
            'pendiente' => $this->pedido($cliente->id, 'pending'),
            'rechazado' => $this->pedido($cliente->id, 'failed'),
            'cancelado' => $this->pedido($cliente->id, 'cancelled'),
            'invitado' => $this->pedido(null, 'paid'),
        ];

        // Se repite el paso de datos de la migración sobre pedidos que todavía no tienen la marca
        (require base_path('database/migrations/2026_11_05_000000_add_payment_tracking_to_orders_table.php'))->up();

        foreach ($premiados as $pedido) {
            $this->assertNotNull($pedido->fresh()->points_awarded_at, "El pedido {$pedido->status} ya dio puntos");
            $this->assertEquals($pedido->fresh()->created_at, $pedido->fresh()->points_awarded_at);
        }
        foreach ($noPremiados as $motivo => $pedido) {
            $this->assertNull($pedido->fresh()->points_awarded_at, "El pedido {$motivo} no dio puntos");
        }
    }

    public function test_correr_la_migracion_dos_veces_no_falla(): void
    {
        $migracion = require base_path('database/migrations/2026_11_05_000000_add_payment_tracking_to_orders_table.php');

        $migracion->up();
        $migracion->up();

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('orders', ['points_awarded_at', 'cancelled_at', 'cancel_reason', 'refund_status', 'refund_id']));
    }
}
