<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Con sesión, los pedidos se ven en la cuenta (perfil y «Mis pedidos»), no en la búsqueda por teléfono.
 * «Volver» regresa al perfil si se entró desde ahí y al menú si se entró desde un ticket.
 */
class MisPedidosTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo = 'ana@la501.test'): User
    {
        return User::create([
            'name' => 'Ana', 'email' => $correo, 'password' => 'La501Sports!',
            'role' => 'cliente', 'is_active' => 1,
        ]);
    }

    private function pedidoDe(?User $user, string $estado = 'delivered'): Order
    {
        $pedido = Order::create([
            'user_id' => $user?->id, 'customer_name' => 'Ana', 'customer_phone' => '1234567890',
            'customer_address' => 'Calle 1', 'total' => 120, 'status' => $estado, 'payment_method' => 'efectivo',
        ]);
        $pedido->items()->create([
            'product_name' => 'Tacos', 'station' => 'cocina', 'quantity' => 2, 'price' => 60, 'subtotal' => 120,
        ]);

        return $pedido;
    }

    public function test_sin_sesion_pide_iniciar_sesion(): void
    {
        $this->get(route('mis-pedidos'))->assertRedirect(route('login'));
    }

    public function test_muestra_solo_los_pedidos_de_la_cuenta(): void
    {
        $ana = $this->cliente();
        $mio = $this->pedidoDe($ana);
        $ajeno = $this->pedidoDe($this->cliente('otro@la501.test'));

        $this->actingAs($ana)->get(route('mis-pedidos'))
            ->assertOk()
            ->assertSee('#' . str_pad($mio->id, 5, '0', STR_PAD_LEFT))
            ->assertDontSee('#' . str_pad($ajeno->id, 5, '0', STR_PAD_LEFT))
            ->assertSee('2× Tacos');
    }

    public function test_volver_depende_de_donde_vino(): void
    {
        $ana = $this->cliente();

        $this->actingAs($ana)->get(route('mis-pedidos', ['desde' => 'perfil']))
            ->assertSee('Volver a mi perfil')
            ->assertSee('href="' . route('perfil') . '"', false);

        $this->actingAs($ana)->get(route('mis-pedidos', ['desde' => 'ticket']))
            ->assertSee('Volver al menú')
            ->assertSee('href="' . route('pedido') . '"', false);
    }

    public function test_la_busqueda_por_telefono_manda_a_mis_pedidos_si_hay_sesion(): void
    {
        $this->actingAs($this->cliente())->get(route('order.lookup'))->assertRedirect(route('mis-pedidos'));

        auth()->logout();
        $this->get(route('order.lookup'))->assertOk()->assertSee('Te mandamos un código por SMS');
    }

    public function test_el_ticket_lleva_a_mis_pedidos_si_hay_sesion_y_a_la_busqueda_si_no(): void
    {
        $ana = $this->cliente();
        $pedido = $this->pedidoDe($ana);
        $url = route('order.track', $pedido->trackingToken());

        $this->actingAs($ana)->get($url)->assertSee(e(route('mis-pedidos', ['desde' => 'ticket'])), false);

        auth()->logout();
        $this->get($url)->assertSee(e(route('order.lookup') . '?telefono=1234567890'), false);
    }

    public function test_el_perfil_resume_los_ultimos_cinco_con_estados_en_espanol(): void
    {
        $ana = $this->cliente();
        foreach (range(1, 6) as $n) {
            $this->pedidoDe($ana);
        }
        $this->pedidoDe($ana, 'cancelled');

        $this->actingAs($ana)->get(route('perfil'))
            ->assertOk()
            ->assertSee('Ver todos mis pedidos (7)')
            ->assertSee(e(route('mis-pedidos', ['desde' => 'perfil'])), false)
            ->assertSee('Cancelado')
            ->assertDontSee('CANCELLED')
            ->assertDontSee('>Cancelled<', false);
    }
}
