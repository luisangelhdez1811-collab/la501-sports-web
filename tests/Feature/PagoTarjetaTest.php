<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\OrderPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pagos con tarjeta (B-01) y puntos de lealtad una sola vez por pedido (B-02).
 * Mercado Pago se simula con Http::fake(): estas pruebas no tocan su API real.
 */
class PagoTarjetaTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'secreto-de-prueba';

    private Product $hamburguesa;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercadopago.access_token' => 'TEST-token',
            'services.mercadopago.webhook_secret' => self::SECRETO,
        ]);

        $this->hamburguesa = Product::create(['name' => 'Hamburguesa Clásica', 'price' => 195, 'category' => 'Hamburguesas', 'available' => 1]);
    }

    private function cliente(): User
    {
        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        return User::create([
            'name' => 'Cliente', 'email' => 'cliente@la501.test', 'password' => 'La501Sports!',
            'telefono' => '5512345678', 'role' => 'cliente', 'is_active' => 1,
        ]);
    }

    private function pedido(array $extra = []): Order
    {
        $pedido = Order::create(array_merge([
            'customer_name' => 'Ana Prueba',
            'customer_phone' => '5512345678',
            'customer_address' => 'Calle 1',
            'total' => 195,
            'status' => 'pending',
            'payment_method' => 'tarjeta',
        ], $extra));

        OrderItem::create([
            'order_id' => $pedido->id, 'product_id' => $this->hamburguesa->id, 'product_name' => 'Hamburguesa Clásica',
            'quantity' => 1, 'price' => 195, 'subtotal' => 195,
        ]);

        return $pedido;
    }

    /** Respuesta de la API de Mercado Pago para un pago. */
    private function pago(int $id, Order $pedido, array $extra = []): array
    {
        return array_merge([
            'id' => $id,
            'status' => 'approved',
            'external_reference' => (string) $pedido->id,
            'transaction_amount' => 195.0,
            'currency_id' => 'MXN',
        ], $extra);
    }

    private function fingirPago(int $id, array $respuesta, int $http = 200): void
    {
        Http::fake(["api.mercadopago.com/v1/payments/{$id}" => Http::response($respuesta, $http)]);
    }

    private function regreso(Order $pedido, int $idPago, string $ruta = 'payment.success')
    {
        return $this->get(route($ruta, ['t' => $pedido->trackingToken(), 'payment_id' => $idPago, 'external_reference' => $pedido->id]));
    }

    private function avisoFirmado(int $idPago, ?string $secreto = self::SECRETO, array $cuerpo = null)
    {
        $ts = '1700000000';
        $cabeceras = ['x-request-id' => 'req-1'];
        if ($secreto !== null) {
            $firma = hash_hmac('sha256', "id:{$idPago};request-id:req-1;ts:{$ts};", $secreto);
            $cabeceras['x-signature'] = "ts={$ts},v1={$firma}";
        }

        return $this->postJson('/api/webhooks/mercadopago', $cuerpo ?? ['type' => 'payment', 'data' => ['id' => (string) $idPago]], $cabeceras);
    }

    // ── B-01: la página de regreso no es prueba de pago ──────────────────────────────────────

    public function test_abrir_pago_exitoso_a_mano_con_una_referencia_inventada_no_marca_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(1, [], 404);

        $this->get('/pago/exitoso?external_reference=' . $pedido->id . '&payment_id=1')
            ->assertRedirect(route('pedido'));

        $this->assertSame('pending', $pedido->fresh()->status);
        $this->assertNull($pedido->fresh()->payment_id);
    }

    public function test_con_el_token_pero_con_un_pago_que_no_existe_el_pedido_no_cambia(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(1, ['message' => 'not found'], 404);

        $this->regreso($pedido, 1)->assertRedirect(route('payment.confirmation', $pedido->id));

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_sin_payment_id_no_cambia_nada_y_no_se_consulta_a_mercado_pago(): void
    {
        $pedido = $this->pedido();
        Http::fake();

        $this->get(route('payment.success', ['t' => $pedido->trackingToken()]))->assertRedirect();

        $this->assertSame('pending', $pedido->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_un_pago_aprobado_y_verificado_marca_el_pedido_como_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido));

        $this->regreso($pedido, 111)
            ->assertRedirect(route('payment.confirmation', $pedido->id))
            ->assertSessionHas('success');

        $this->assertSame('paid', $pedido->fresh()->status);
        $this->assertSame('111', $pedido->fresh()->payment_id);
    }

    public function test_un_pago_aprobado_con_monto_distinto_no_marca_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido, ['transaction_amount' => 100.0]));

        $this->regreso($pedido, 111);

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_un_pago_aprobado_en_otra_moneda_no_marca_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido, ['currency_id' => 'USD']));

        $this->regreso($pedido, 111);

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_el_pago_de_otro_pedido_no_se_aplica_al_pedido_de_la_pagina_de_regreso(): void
    {
        $mio = $this->pedido();
        $ajeno = $this->pedido();
        // Alguien arma la URL con el token de su pedido y el id de un pago ajeno
        $this->fingirPago(222, $this->pago(222, $ajeno));

        $this->regreso($mio, 222);

        $this->assertSame('pending', $mio->fresh()->status);
        $this->assertSame('pending', $ajeno->fresh()->status);
    }

    public function test_un_pago_pendiente_o_en_proceso_no_marca_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido, ['status' => 'in_process']));

        $this->regreso($pedido, 111);

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_un_pago_rechazado_deja_el_pedido_en_failed_y_no_llega_a_cocina(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido, ['status' => 'rejected']));

        $this->regreso($pedido, 111, 'payment.failure')->assertRedirect(route('pedido'));

        $this->assertSame('failed', $pedido->fresh()->status);
    }

    public function test_un_pedido_en_efectivo_no_cambia_por_un_pago_con_su_referencia(): void
    {
        $pedido = $this->pedido(['payment_method' => 'efectivo']);
        $this->fingirPago(111, $this->pago(111, $pedido));

        $this->avisoFirmado(111)->assertOk();

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_la_pagina_pendiente_no_cambia_nada(): void
    {
        $pedido = $this->pedido();

        $this->get(route('payment.pending', ['t' => $pedido->trackingToken()]))
            ->assertRedirect(route('payment.confirmation', $pedido->id));

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    // ── B-01: webhook ────────────────────────────────────────────────────────────────────────

    public function test_el_webhook_sin_firma_o_con_firma_falsa_responde_401_y_no_cambia_nada(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido));

        $this->avisoFirmado(111, null)->assertStatus(401);
        $this->avisoFirmado(111, 'otro-secreto')->assertStatus(401);

        $this->assertSame('pending', $pedido->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_el_webhook_con_firma_valida_y_pago_aprobado_marca_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido));

        // Aunque el cliente nunca regrese a la página del comercio
        $this->avisoFirmado(111)->assertOk()->assertJsonPath('ok', true);

        $this->assertSame('paid', $pedido->fresh()->status);
        $this->assertSame('111', $pedido->fresh()->payment_id);
    }

    public function test_el_webhook_con_pago_aprobado_de_monto_distinto_no_marca_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido, ['transaction_amount' => 1.0]));

        $this->avisoFirmado(111)->assertOk();

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_el_webhook_ignora_lo_que_no_es_un_pago_y_un_pago_que_no_existe(): void
    {
        $pedido = $this->pedido();
        Http::fake();

        $this->avisoFirmado(111, self::SECRETO, ['type' => 'merchant_order', 'data' => ['id' => '111']])->assertOk();
        Http::assertNothingSent();

        $this->fingirPago(123456, [], 404);
        $this->avisoFirmado(123456)->assertOk();

        $this->assertSame('pending', $pedido->fresh()->status);
    }

    public function test_la_firma_se_valida_con_el_id_en_minusculas_como_manda_mercado_pago(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido));

        // data.id alfanumérico en mayúsculas: la firma se calcula con el id en minúsculas
        $ts = '1700000000';
        $firma = hash_hmac('sha256', "id:abc;request-id:req-1;ts:{$ts};", self::SECRETO);

        $this->postJson('/api/webhooks/mercadopago', ['type' => 'payment', 'data' => ['id' => 'ABC']], [
            'x-request-id' => 'req-1',
            'x-signature' => "ts={$ts},v1={$firma}",
        ])->assertOk();
    }

    // ── B-02: puntos una sola vez ────────────────────────────────────────────────────────────

    public function test_recargar_la_pagina_de_pago_exitoso_da_los_puntos_una_sola_vez(): void
    {
        $cliente = $this->cliente();
        $pedido = $this->pedido(['user_id' => $cliente->id]);
        $this->fingirPago(111, $this->pago(111, $pedido));

        foreach (range(1, 5) as $_) {
            $this->regreso($pedido, 111);
        }

        // 1 punto por cada $10: $195 → 19
        $this->assertSame(19, (int) $cliente->fresh()->points);
        $this->assertNotNull($pedido->fresh()->points_awarded_at);
    }

    public function test_el_webhook_y_la_pagina_de_regreso_juntos_dan_los_puntos_una_sola_vez(): void
    {
        $cliente = $this->cliente();
        $pedido = $this->pedido(['user_id' => $cliente->id]);
        $this->fingirPago(111, $this->pago(111, $pedido));

        $this->avisoFirmado(111)->assertOk();
        $this->avisoFirmado(111)->assertOk();
        $this->regreso($pedido, 111);

        $this->assertSame(19, (int) $cliente->fresh()->points);
    }

    public function test_un_pedido_de_invitado_no_da_puntos_pero_si_se_marca_pagado(): void
    {
        $pedido = $this->pedido();
        $this->fingirPago(111, $this->pago(111, $pedido));

        $this->avisoFirmado(111)->assertOk();

        $this->assertSame('paid', $pedido->fresh()->status);
        $this->assertNull($pedido->fresh()->points_awarded_at);
    }

    public function test_en_efectivo_los_puntos_tambien_se_dan_una_sola_vez(): void
    {
        $cliente = $this->cliente();
        $pedido = $this->pedido(['user_id' => $cliente->id, 'payment_method' => 'efectivo', 'status' => 'paid']);

        $servicio = app(OrderPayments::class);
        $this->assertTrue($servicio->awardPoints($pedido));
        $this->assertFalse($servicio->awardPoints($pedido));
        $this->assertFalse($servicio->awardPoints($pedido->fresh()));

        $this->assertSame(19, (int) $cliente->fresh()->points);
    }

    // ── B-01: el cliente paga tarde, con el pedido ya cancelado ──────────────────────────────

    public function test_un_pago_aprobado_de_un_pedido_cancelado_se_devuelve_en_automatico(): void
    {
        $pedido = $this->pedido(['status' => 'cancelled']);
        Http::fake([
            'api.mercadopago.com/v1/payments/111/refunds' => Http::response(['id' => 987, 'status' => 'approved'], 201),
            'api.mercadopago.com/v1/payments/111' => Http::response($this->pago(111, $pedido)),
        ]);

        $this->avisoFirmado(111)->assertOk();

        $pedido = $pedido->fresh();
        $this->assertSame('cancelled', $pedido->status, 'el pedido cancelado no resucita');
        $this->assertSame('refunded', $pedido->refund_status);
        $this->assertSame('987', $pedido->refund_id);
        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), '/v1/payments/111/refunds') && $r->hasHeader('X-Idempotency-Key'));
    }

    public function test_si_la_devolucion_falla_queda_registrada_para_hacerla_a_mano(): void
    {
        $pedido = $this->pedido(['status' => 'cancelled']);
        Http::fake([
            'api.mercadopago.com/v1/payments/111/refunds' => Http::response(['message' => 'error'], 500),
            'api.mercadopago.com/v1/payments/111' => Http::response($this->pago(111, $pedido)),
        ]);

        $this->avisoFirmado(111)->assertOk();

        $this->assertSame('manual', $pedido->fresh()->refund_status);
        $this->assertSame('cancelled', $pedido->fresh()->status);
    }

    // ── B-01: lo que se le manda a Mercado Pago al crear el pago ─────────────────────────────

    private function datosApp(array $extra = []): array
    {
        return array_merge([
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1]],
            'customer_name' => 'Ana Prueba',
            'customer_phone' => '5512345678',
            'customer_address' => 'Calle 1',
            'payment_method' => 'tarjeta',
            'delivery_terms_accepted' => true,
        ], $extra);
    }

    public function test_la_preferencia_trae_webhook_token_de_regreso_y_solo_tarjeta_en_una_exhibicion(): void
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['init_point' => 'https://mp.test/pagar/abc'], 201)]);

        $respuesta = $this->postJson('/api/v1/app/checkout', $this->datosApp(['client_ref' => 'app_folio_0001']))
            ->assertCreated()
            ->assertJsonPath('checkout_url', 'https://mp.test/pagar/abc')
            ->assertJsonPath('order.status', 'pending');

        $pedido = Order::findOrFail($respuesta->json('order.id'));

        Http::assertSent(function (ClientRequest $r) use ($pedido) {
            $cuerpo = $r->data();

            return str_contains($r->url(), '/checkout/preferences')
                && $cuerpo['external_reference'] === (string) $pedido->id
                && str_ends_with($cuerpo['notification_url'], '/api/webhooks/mercadopago')
                && $cuerpo['binary_mode'] === true
                && $cuerpo['auto_return'] === 'approved'
                && $cuerpo['payment_methods']['installments'] === 1
                && $cuerpo['payment_methods']['excluded_payment_types'] === [['id' => 'ticket'], ['id' => 'atm'], ['id' => 'bank_transfer']]
                && str_contains($cuerpo['back_urls']['success'], 't=' . $pedido->trackingToken())
                && str_contains($cuerpo['back_urls']['failure'], 't=' . $pedido->trackingToken())
                && str_contains($cuerpo['back_urls']['pending'], '/pago/pendiente')
                && $cuerpo['items'][0]['unit_price'] === 195.0
                && $r->hasHeader('Authorization', 'Bearer TEST-token');
        });
    }

    public function test_un_pedido_con_tarjeta_queda_pendiente_y_fuera_de_cocina(): void
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['init_point' => 'https://mp.test/pagar/abc'], 201)]);

        $this->postJson('/api/v1/app/checkout', $this->datosApp())->assertCreated();

        $this->assertSame('pending', Order::first()->status);
        $this->assertNull(Order::first()->points_awarded_at);
    }

    public function test_si_mercado_pago_no_responde_se_avisa_y_se_puede_pagar_en_efectivo(): void
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['message' => 'down'], 503)]);

        $this->postJson('/api/v1/app/checkout', $this->datosApp())
            ->assertStatus(502)
            ->assertJsonPath('error', 'mercadopago_unavailable');
    }

    public function test_reintentar_un_pedido_con_tarjeta_sin_pagar_devuelve_un_enlace_del_mismo_pedido(): void
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['init_point' => 'https://mp.test/pagar/abc'], 201)]);
        $datos = $this->datosApp(['client_ref' => 'app_folio_0001']);

        $primero = $this->postJson('/api/v1/app/checkout', $datos)->assertCreated();
        $segundo = $this->postJson('/api/v1/app/checkout', $datos)
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('checkout_url', 'https://mp.test/pagar/abc');

        $this->assertSame($primero->json('order.id'), $segundo->json('order.id'));
        $this->assertSame(1, Order::count());
    }

    // ── B-03: checkout web ───────────────────────────────────────────────────────────────────

    private function agregarAlCarrito(): void
    {
        $this->postJson(route('cart.add'), [
            'id' => $this->hamburguesa->id, 'name' => 'Hamburguesa Clásica', 'price' => 195, 'quantity' => 1,
        ])->assertOk();
    }

    public function test_el_checkout_web_trae_el_folio_y_desactiva_el_boton_al_enviar(): void
    {
        $this->agregarAlCarrito();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('name="client_ref"', false)
            ->assertSee('value="web_', false)
            ->assertSee('id="checkout-form"', false)
            ->assertSee('boton.disabled = true', false);
    }

    public function test_enviar_dos_veces_el_checkout_web_con_el_mismo_folio_crea_un_solo_pedido(): void
    {
        $this->agregarAlCarrito();
        $datos = [
            'customer_name' => 'Ana Prueba', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'payment_method' => 'efectivo', 'client_ref' => 'web_folio_de_prueba_0001', 'delivery_terms_accepted' => '1',
        ];

        $this->post(route('checkout.process'), $datos)->assertRedirect();
        $pedido = Order::firstOrFail();

        // Doble clic o reenvío: el carrito ya se vació, pero el folio reconoce el pedido
        $this->post(route('checkout.process'), $datos)
            ->assertRedirect(route('payment.confirmation', $pedido->id));

        $this->assertSame(1, Order::count());
    }

    public function test_el_folio_del_checkout_web_de_otro_cliente_no_se_reutiliza(): void
    {
        $this->agregarAlCarrito();
        $datos = [
            'customer_name' => 'Ana Prueba', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'payment_method' => 'efectivo', 'client_ref' => 'web_folio_de_prueba_0001', 'delivery_terms_accepted' => '1',
        ];
        $this->post(route('checkout.process'), $datos)->assertRedirect();

        $this->post(route('checkout.process'), array_merge($datos, ['customer_phone' => '7711112222']))
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, Order::count());
    }

    public function test_un_pedido_web_con_tarjeta_va_a_mercado_pago_y_queda_pendiente(): void
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['init_point' => 'https://mp.test/pagar/web'], 201)]);
        $this->agregarAlCarrito();

        $this->post(route('checkout.process'), [
            'customer_name' => 'Ana Prueba', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'payment_method' => 'tarjeta', 'client_ref' => 'web_folio_de_prueba_0002', 'delivery_terms_accepted' => '1',
        ])->assertRedirect('https://mp.test/pagar/web');

        $this->assertSame('pending', Order::first()->status);
    }
}
