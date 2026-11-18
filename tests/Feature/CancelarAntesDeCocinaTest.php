<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\KitchenQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El cliente puede cancelar su domicilio hasta que entra a cocina: en cuanto sale en grande en la
 * pantalla de cocina o de barra (las primeras KitchenQueue::ON_SCREEN órdenes) ya no. El ticket
 * dice «Tu pedido ya entró a cocina» para que sepa por qué.
 */
class CancelarAntesDeCocinaTest extends TestCase
{
    use RefreshDatabase;

    private int $minuto = 0;

    /** Pedidos a domicilio confirmados, cada uno un minuto después del anterior. */
    private function domicilio(string $estacion = 'cocina', array $extra = []): Order
    {
        $pedido = new Order(array_merge([
            'customer_name' => 'Ana', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'total' => 150, 'status' => 'paid', 'payment_method' => 'efectivo',
        ], $extra));
        $pedido->created_at = now()->subMinutes(60 - $this->minuto++);
        $pedido->save();
        $pedido->items()->create(['product_name' => $estacion === 'barra' ? 'Limonada' : 'Hamburguesa', 'station' => $estacion, 'quantity' => 1, 'price' => 150, 'subtotal' => 150]);

        return $pedido->fresh();
    }

    private function cocinero(): User
    {
        return User::create(['name' => 'Cocina', 'email' => 'cocina@la501.test', 'password' => 'La501Sports!', 'role' => 'cocinero', 'is_active' => 1]);
    }

    public function test_los_que_salen_en_pantalla_ya_no_se_cancelan_y_los_de_la_cola_si(): void
    {
        $pedidos = collect(range(1, KitchenQueue::ON_SCREEN + 1))->map(fn () => $this->domicilio());

        foreach ($pedidos->take(KitchenQueue::ON_SCREEN) as $enPantalla) {
            $this->assertFalse($enPantalla->puedeCancelarElCliente());
            $this->assertTrue($enPantalla->entroACocinaSinPoderCancelar());
        }

        $enCola = $pedidos->last();
        $this->assertTrue($enCola->puedeCancelarElCliente());
        $this->assertFalse($enCola->entroACocinaSinPoderCancelar());
    }

    public function test_la_api_rechaza_cancelar_uno_en_pantalla_y_explica_por_que(): void
    {
        $pedido = $this->domicilio();

        $this->postJson('/api/v1/app/orders/track/' . $pedido->trackingToken() . '/cancel')
            ->assertStatus(422)
            ->assertJsonPath('error', 'cannot_cancel')
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'ya entró a cocina'));

        $this->assertSame('paid', $pedido->fresh()->status);

        $this->getJson('/api/v1/app/orders/track/' . $pedido->trackingToken())
            ->assertOk()
            ->assertJsonPath('data.can_cancel', false)
            ->assertJsonPath('data.in_kitchen', true)
            ->assertJsonPath('data.cannot_cancel_reason', fn ($m) => str_contains($m, 'ya entró a cocina'));
    }

    public function test_la_barra_cuenta_igual_que_la_cocina(): void
    {
        // La cocina está llena, pero la barra no: la limonada sale en grande en la barra.
        foreach (range(1, KitchenQueue::ON_SCREEN) as $n) {
            $this->domicilio('cocina');
        }
        $bebida = $this->domicilio('barra');

        $this->assertFalse($bebida->puedeCancelarElCliente());
        $this->assertTrue($bebida->entroACocinaSinPoderCancelar());
    }

    public function test_al_avanzar_la_fila_el_de_la_cola_entra_a_cocina(): void
    {
        $primeros = collect(range(1, KitchenQueue::ON_SCREEN))->map(fn () => $this->domicilio());
        $enCola = $this->domicilio();
        $this->assertTrue($enCola->puedeCancelarElCliente());

        // La cocina termina el primero: el de la cola sube a la pantalla.
        $primeros->first()->items()->update(['prepared_at' => now()]);
        $primeros->first()->update(['status' => 'ready']);

        $this->assertFalse($enCola->fresh()->puedeCancelarElCliente());
    }

    public function test_la_pantalla_de_cocina_anota_cuando_lo_mostro(): void
    {
        $pedidos = collect(range(1, KitchenQueue::ON_SCREEN + 1))->map(fn () => $this->domicilio());

        $this->actingAs($this->cocinero())->getJson('/api/cocina/ordenes?estacion=cocina')->assertOk();

        foreach ($pedidos->take(KitchenQueue::ON_SCREEN) as $p) {
            $this->assertNotNull($p->fresh()->kitchen_shown_at);
        }
        $this->assertNull($pedidos->last()->fresh()->kitchen_shown_at);
    }

    public function test_si_ya_salio_en_pantalla_no_se_cancela_aunque_luego_quede_atras(): void
    {
        foreach (range(1, KitchenQueue::ON_SCREEN) as $n) {
            $this->domicilio();
        }
        $pedido = $this->domicilio();
        $pedido->forceFill(['kitchen_shown_at' => now()])->save(); // salió en pantalla antes de un «deshacer»

        $this->assertFalse($pedido->puedeCancelarElCliente());
    }

    public function test_si_ya_marcaron_algo_listo_no_se_cancela(): void
    {
        foreach (range(1, KitchenQueue::ON_SCREEN) as $n) {
            $this->domicilio();
        }
        $pedido = $this->domicilio();
        $pedido->items()->update(['prepared_at' => now()]);

        $this->assertFalse($pedido->fresh()->puedeCancelarElCliente());
    }

    public function test_el_ticket_avisa_que_ya_entro_a_cocina_y_quita_el_boton(): void
    {
        $pedido = $this->domicilio();

        $this->get(route('order.track', $pedido->trackingToken()))
            ->assertOk()
            ->assertSee('Tu pedido ya entró a cocina.')
            ->assertDontSee('Cancelar pedido</button>', false);

        $this->getJson(route('order.track.status', $pedido->trackingToken()))
            ->assertJson(['can_cancel' => false, 'in_kitchen' => true]);
    }

    public function test_el_ticket_de_uno_en_cola_deja_cancelar(): void
    {
        foreach (range(1, KitchenQueue::ON_SCREEN) as $n) {
            $this->domicilio();
        }
        $pedido = $this->domicilio();

        $this->get(route('order.track', $pedido->trackingToken()))
            ->assertOk()
            ->assertSee('Cancelar pedido')
            ->assertSee('hasta que tu pedido entre a cocina');

        $this->getJson(route('order.track.status', $pedido->trackingToken()))
            ->assertJson(['can_cancel' => true, 'in_kitchen' => false]);

        $this->post(route('order.track.cancel', $pedido->trackingToken()))->assertSessionHas('cancel_message');
        $this->assertSame('cancelled', $pedido->fresh()->status);
    }
}
