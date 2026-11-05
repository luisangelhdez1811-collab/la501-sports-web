<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\StaffToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * B-10: cancelar un pedido (cliente, personal o panel) pasa SIEMPRE por OrderPayments::cancel: quita los
 * puntos y devuelve el pago con tarjeta. B-12: el cliente cancela desde la web y la app mientras la cocina
 * no empiece. Mercado Pago se simula con Http::fake().
 */
class CancelacionPedidoTest extends TestCase
{
    use RefreshDatabase;

    private const REFUND = 'api.mercadopago.com/v1/payments/777/refunds';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.access_token' => 'TEST-token']);
    }

    private function cliente(int $puntos = 0): User
    {
        return User::create([
            'name' => 'Cliente', 'email' => 'cliente@la501.test', 'password' => 'La501Sports!',
            'telefono' => '5512345678', 'role' => 'cliente', 'is_active' => 1, 'points' => $puntos,
        ]);
    }

    private function pedido(array $extra = []): Order
    {
        $pedido = Order::create(array_merge([
            'customer_name' => 'Ana', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'total' => 200, 'status' => 'pending', 'payment_method' => 'efectivo',
        ], $extra));
        $pedido->items()->create(['product_name' => 'Hamburguesa', 'station' => 'cocina', 'quantity' => 1, 'price' => 200, 'subtotal' => 200]);

        return $pedido->fresh();
    }

    private function conTarjetaPagada(array $extra = []): Order
    {
        return $this->pedido(array_merge(['status' => 'paid', 'payment_method' => 'tarjeta', 'payment_id' => '777'], $extra));
    }

    private function personal(string $rol = 'admin'): array
    {
        $usuario = User::create(['name' => 'Staff', 'email' => $rol . '@la501.test', 'password' => 'La501Sports!', 'role' => $rol, 'is_active' => 1]);
        $token = StaffToken::create(['user_id' => $usuario->id, 'token' => StaffToken::generateTokenString(), 'expires_at' => now()->addDay()]);

        return ['Authorization' => 'Bearer ' . $token->token];
    }

    private function urlWeb(Order $p): string
    {
        return route('order.track.cancel', $p->trackingToken());
    }

    private function urlApi(Order $p, string $sufijo = 'cancel'): string
    {
        return '/api/v1/app/orders/track/' . $p->trackingToken() . '/' . $sufijo;
    }

    // ── B-12: el cliente cancela ─────────────────────────────────────────────────────────────

    public function test_cancelar_un_pedido_en_efectivo_pendiente_lo_cancela(): void
    {
        $pedido = $this->pedido();

        $this->postJson($this->urlApi($pedido), ['reason' => 'Me equivoqué'])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('refund_status', null);

        $pedido = $pedido->fresh();
        $this->assertSame('cancelled', $pedido->status);
        $this->assertNotNull($pedido->cancelled_at);
        $this->assertSame('Me equivoqué', $pedido->cancel_reason);
    }

    public function test_un_pedido_confirmado_en_efectivo_tambien_se_puede_cancelar(): void
    {
        $pedido = $this->pedido(['status' => 'paid']);

        $this->postJson($this->urlApi($pedido))->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertSame('Cancelado por el cliente', $pedido->fresh()->cancel_reason);
    }

    public function test_un_pedido_en_preparacion_no_se_puede_cancelar(): void
    {
        foreach (['preparing', 'ready', 'delivering', 'delivered'] as $estado) {
            $pedido = $this->pedido(['status' => $estado]);

            $this->postJson($this->urlApi($pedido))
                ->assertStatus(422)
                ->assertJsonPath('error', 'cannot_cancel');

            $this->assertSame($estado, $pedido->fresh()->status);
        }
    }

    public function test_un_pedido_de_mesa_no_lo_cancela_el_cliente(): void
    {
        $pedido = $this->pedido(['table_number' => 4]);

        $this->postJson($this->urlApi($pedido))->assertStatus(422)->assertJsonPath('error', 'cannot_cancel');
    }

    public function test_cancelar_uno_pagado_con_tarjeta_devuelve_el_dinero(): void
    {
        Http::fake([self::REFUND => Http::response(['id' => 9001], 201)]);
        $pedido = $this->conTarjetaPagada();

        $this->postJson($this->urlApi($pedido))
            ->assertOk()
            ->assertJsonPath('refund_status', 'refunded')
            ->assertJsonPath('message', 'Pedido cancelado. Te devolvimos tu pago; puede tardar unos días en verse en tu tarjeta.');

        $pedido = $pedido->fresh();
        $this->assertSame('refunded', $pedido->refund_status);
        $this->assertSame('9001', $pedido->refund_id);

        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), '/v1/payments/777/refunds')
            && $r->hasHeader('X-Idempotency-Key', 'pedido-' . $pedido->id . '-cancelacion-777'));
    }

    public function test_si_la_devolucion_falla_queda_manual_se_avisa_y_se_registra(): void
    {
        Http::fake([self::REFUND => Http::response(['message' => 'down'], 503)]);
        Log::spy();
        $pedido = $this->conTarjetaPagada();

        $this->postJson($this->urlApi($pedido))
            ->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('refund_status', 'manual')
            ->assertJsonPath('message', 'Pedido cancelado. Tu devolución se hará en un máximo de 5 días hábiles.');

        $this->assertSame('manual', $pedido->fresh()->refund_status);
        Log::shouldHaveReceived('critical')->once();
    }

    public function test_una_tarjeta_sin_pago_aprobado_no_intenta_devolver_nada(): void
    {
        Http::fake();
        $pedido = $this->pedido(['payment_method' => 'tarjeta', 'status' => 'pending', 'payment_id' => null]);

        $this->postJson($this->urlApi($pedido))->assertOk()->assertJsonPath('refund_status', null);

        Http::assertNothingSent();
    }

    public function test_cancelar_dos_veces_no_devuelve_dos_veces(): void
    {
        Http::fake([self::REFUND => Http::response(['id' => 9001], 201)]);
        $pedido = $this->conTarjetaPagada();

        $this->postJson($this->urlApi($pedido))->assertOk();
        $this->postJson($this->urlApi($pedido))->assertOk()->assertJsonPath('refund_status', 'refunded');

        Http::assertSentCount(1);
    }

    public function test_cancelar_quita_los_puntos_sin_dejar_el_saldo_negativo(): void
    {
        $cliente = $this->cliente(50);
        $pedido = $this->pedido(['user_id' => $cliente->id, 'status' => 'paid', 'points_awarded_at' => now()]);

        $this->postJson($this->urlApi($pedido))->assertOk();
        $this->assertSame(30, $cliente->fresh()->points, '200 pesos dieron 20 puntos');
        $this->assertNull($pedido->fresh()->points_awarded_at);

        $otro = $this->pedido(['user_id' => $cliente->id, 'status' => 'paid', 'total' => 900, 'points_awarded_at' => now()]);
        $this->postJson($this->urlApi($otro))->assertOk();
        $this->assertSame(0, $cliente->fresh()->points, 'nunca queda negativo');
    }

    public function test_un_pedido_que_no_dio_puntos_no_resta_nada(): void
    {
        $cliente = $this->cliente(50);
        $pedido = $this->pedido(['user_id' => $cliente->id, 'points_awarded_at' => null]);

        $this->postJson($this->urlApi($pedido))->assertOk();

        $this->assertSame(50, $cliente->fresh()->points);
    }

    public function test_el_token_inexistente_da_404(): void
    {
        $this->postJson('/api/v1/app/orders/track/' . str_repeat('x', 40) . '/cancel')->assertNotFound();
    }

    // ── B-12: lo que ve la app en track ──────────────────────────────────────────────────────

    public function test_track_trae_etiqueta_forma_de_pago_y_si_se_puede_cancelar(): void
    {
        $pedido = $this->pedido(['status' => 'paid']);

        $this->getJson($this->urlApi($pedido, ''))
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.status_label', 'Confirmado')
            ->assertJsonPath('data.payment_method', 'efectivo')
            ->assertJsonPath('data.can_cancel', true)
            ->assertJsonPath('data.cancelled_at', null)
            ->assertJsonPath('data.refund_status', null);

        $pedido->update(['status' => 'preparing']);
        $this->getJson($this->urlApi($pedido, ''))->assertJsonPath('data.can_cancel', false)->assertJsonPath('data.status_label', 'En preparación');
    }

    public function test_track_de_un_cancelado_trae_fecha_y_devolucion(): void
    {
        Http::fake([self::REFUND => Http::response(['id' => 9001], 201)]);
        $pedido = $this->conTarjetaPagada();
        $this->postJson($this->urlApi($pedido))->assertOk();

        $r = $this->getJson($this->urlApi($pedido, ''))->assertOk();
        $r->assertJsonPath('data.status_label', 'Cancelado')->assertJsonPath('data.can_cancel', false)->assertJsonPath('data.refund_status', 'refunded');
        $this->assertNotNull($r->json('data.cancelled_at'));
    }

    // ── B-12: web ────────────────────────────────────────────────────────────────────────────

    public function test_la_pagina_web_muestra_el_boton_solo_si_se_puede_cancelar(): void
    {
        $pedido = $this->pedido();
        $this->get(route('order.track', $pedido->trackingToken()))->assertOk()->assertSee('Cancelar pedido', false)->assertSee($this->urlWeb($pedido), false);

        $pedido->update(['status' => 'preparing']);
        $this->get(route('order.track', $pedido->trackingToken()))->assertOk()->assertDontSee('Cancelar pedido', false);
    }

    public function test_el_cliente_cancela_desde_la_web(): void
    {
        $pedido = $this->pedido();

        $this->post($this->urlWeb($pedido))
            ->assertRedirect(route('order.track', $pedido->trackingToken()))
            ->assertSessionHas('cancel_message', 'Pedido cancelado.');

        $this->assertSame('cancelled', $pedido->fresh()->status);
        $this->get(route('order.track', $pedido->trackingToken()))->assertSee('Pedido cancelado', false)->assertDontSee('Cancelar pedido</button>', false);
    }

    public function test_la_web_no_cancela_uno_en_preparacion(): void
    {
        $pedido = $this->pedido(['status' => 'preparing']);

        $this->post($this->urlWeb($pedido))->assertSessionHas('cancel_error');

        $this->assertSame('preparing', $pedido->fresh()->status);
    }

    public function test_la_web_devuelve_el_pago_con_tarjeta(): void
    {
        Http::fake([self::REFUND => Http::response(['id' => 9001], 201)]);
        $pedido = $this->conTarjetaPagada();

        $this->post($this->urlWeb($pedido))->assertSessionHas('cancel_message');

        $this->assertSame('refunded', $pedido->fresh()->refund_status);
    }

    // ── B-10: personal y panel ───────────────────────────────────────────────────────────────

    public function test_el_personal_cancela_y_el_pago_con_tarjeta_se_devuelve(): void
    {
        Http::fake([self::REFUND => Http::response(['id' => 9001], 201)]);
        $pedido = $this->conTarjetaPagada(['status' => 'preparing']);

        $this->postJson("/api/v1/staff/orders/{$pedido->id}/cancel", [], $this->personal())
            ->assertOk()
            ->assertJsonPath('success', true);

        $pedido = $pedido->fresh();
        $this->assertSame('cancelled', $pedido->status);
        $this->assertSame('refunded', $pedido->refund_status);
        $this->assertSame('Cancelado por el personal', $pedido->cancel_reason);
    }

    public function test_cambiar_el_estado_a_cancelled_tambien_devuelve_el_pago(): void
    {
        Http::fake([self::REFUND => Http::response(['id' => 9001], 201)]);
        $pedido = $this->conTarjetaPagada(['status' => 'preparing']);

        $this->patchJson("/api/v1/staff/orders/{$pedido->id}/status", ['status' => 'cancelled'], $this->personal())->assertOk();

        $this->assertSame('refunded', $pedido->fresh()->refund_status);
        Http::assertSentCount(1);
    }

    public function test_el_personal_ve_el_aviso_si_hay_que_devolver_a_mano(): void
    {
        Http::fake([self::REFUND => Http::response([], 500)]);
        $pedido = $this->conTarjetaPagada();

        $mensaje = $this->postJson("/api/v1/staff/orders/{$pedido->id}/cancel", [], $this->personal())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('message');

        $this->assertStringContainsString('devolverlo a mano', $mensaje);
        $this->assertSame('manual', $pedido->fresh()->refund_status);
    }

    public function test_el_personal_no_puede_marcar_pagado_a_mano_un_pedido_con_tarjeta_sin_pago(): void
    {
        $pedido = $this->pedido(['payment_method' => 'tarjeta', 'status' => 'pending', 'payment_id' => null]);

        $this->patchJson("/api/v1/staff/orders/{$pedido->id}/status", ['status' => 'paid'], $this->personal())
            ->assertStatus(422)
            ->assertJsonPath('error', 'payment_not_confirmed');

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_marcar_pagado_si_se_permite_con_pago_confirmado_o_en_efectivo(): void
    {
        $confirmado = $this->pedido(['payment_method' => 'tarjeta', 'status' => 'pending', 'payment_id' => '777']);
        $efectivo = $this->pedido(['status' => 'pending']);

        $personal = $this->personal();
        $this->patchJson("/api/v1/staff/orders/{$confirmado->id}/status", ['status' => 'paid'], $personal)->assertOk();

        // Otra petición de la misma prueba: se vuelve a armar la sesión del personal desde cero
        $this->app['auth']->forgetGuards();
        $this->patchJson("/api/v1/staff/orders/{$efectivo->id}/status", ['status' => 'paid'], $this->personal('empleado'))->assertOk();
    }

    public function test_un_pedido_cancelado_no_se_revive(): void
    {
        Http::fake([self::REFUND => Http::response(['id' => 9001], 201)]);
        $pedido = $this->conTarjetaPagada();
        $this->postJson($this->urlApi($pedido))->assertOk();

        $this->patchJson("/api/v1/staff/orders/{$pedido->id}/status", ['status' => 'preparing'], $this->personal())
            ->assertStatus(422)
            ->assertJsonPath('error', 'order_cancelled');

        $this->assertSame('cancelled', $pedido->fresh()->status);
    }

    public function test_cancelar_uno_con_tarjeta_sin_pagar_no_pierde_la_devolucion_si_paga_tarde(): void
    {
        // Cancelado sin pago: no hay nada que devolver todavía (no se llama a Mercado Pago)...
        $pedido = $this->pedido(['payment_method' => 'tarjeta', 'status' => 'pending']);
        $this->postJson($this->urlApi($pedido))->assertOk();
        $this->assertNull($pedido->fresh()->refund_status);

        // ...pero si Mercado Pago aprueba el pago tarde, B-01 lo devuelve (el pedido sigue cancelado)
        Http::fake([
            'api.mercadopago.com/v1/payments/888' => Http::response([
                'id' => 888, 'status' => 'approved', 'external_reference' => (string) $pedido->id,
                'transaction_amount' => 200, 'currency_id' => 'MXN',
            ]),
            'api.mercadopago.com/v1/payments/888/refunds' => Http::response(['id' => 9002], 201),
        ]);
        app(\App\Services\Payments\OrderPayments::class)->syncCardPayment(888);

        $pedido = $pedido->fresh();
        $this->assertSame('cancelled', $pedido->status);
        $this->assertSame('refunded', $pedido->refund_status);
    }
}
