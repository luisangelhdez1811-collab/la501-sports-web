<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\User;
use App\Services\CheckoutCompliance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B-11: lo que el cliente debe saber y aceptar antes de pedir. La web y la app aplican exactamente las
 * mismas reglas y cada pedido guarda la evidencia de lo que el cliente aceptó:
 *   - siempre: el envío NO está incluido y se paga al repartidor (LFPC art. 7 Bis)
 *   - si hay alcohol: mayoría de edad (LGS art. 220)
 *   - si hay notas: autorización de datos de salud (LFPDPPP)
 */
class CumplimientoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app/checkout';

    private Product $hamburguesa;
    private Product $cerveza;
    private Product $cervezaCero;
    private Product $preparado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hamburguesa = Product::create(['name' => 'Hamburguesa Clásica', 'price' => 195, 'category' => 'Hamburguesas', 'available' => 1, 'station' => 'cocina']);
        // Los productos de una categoría de alcohol nacen marcados «lleva alcohol» sin que nadie lo indique
        $this->cerveza = Product::create(['name' => 'Cerveza Corona', 'price' => 45, 'category' => 'Cervecería', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna']);
        $this->cervezaCero = Product::create(['name' => 'Corona Cero', 'price' => 45, 'category' => 'Cervecería', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna']);
        $this->preparado = Product::create(['name' => 'Preparado Michelada', 'price' => 30, 'category' => 'Cervecería', 'subcategory' => 'Preparados', 'section' => 'bebidas', 'available' => 1, 'station' => 'barra']);
    }

    private function datos(Product $producto, array $extra = [], array $linea = []): array
    {
        return array_merge([
            'items' => [array_merge(['product_id' => $producto->id, 'quantity' => 1], $linea)],
            'customer_name' => 'Ana Prueba',
            'customer_phone' => '5512345678',
            'customer_address' => 'Calle 1, Col. Centro',
            'payment_method' => 'efectivo',
            'delivery_terms_accepted' => true,
        ], $extra);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@la501.test', 'password' => 'La501Sports!', 'role' => 'admin']);
    }

    // ── Qué productos llevan alcohol ─────────────────────────────────────────────────────────

    public function test_cerveza_nace_con_alcohol_y_la_cero_y_los_preparados_no(): void
    {
        $this->assertTrue($this->cerveza->fresh()->alcoholic);
        $this->assertFalse($this->cervezaCero->fresh()->alcoholic, 'la cerveza 0.0 no lleva alcohol');
        $this->assertFalse($this->preparado->fresh()->alcoholic, 'un preparado es solo la mezcla');
        $this->assertFalse($this->hamburguesa->fresh()->alcoholic);

        foreach (['6 Shots', 'Coctelería', 'Destilados', 'Digestivos', 'Cervecería', 'Cervezas'] as $categoria) {
            $this->assertTrue(Product::defaultAlcoholic($categoria, null, 'Algo'), $categoria);
        }
        $this->assertTrue(Product::defaultAlcoholic('CERVECERIA', null, 'Algo'), 'sin importar mayúsculas ni acentos');
        $this->assertFalse(Product::defaultAlcoholic('Refrescos', null, 'Coca-Cola'));
    }

    public function test_un_alta_que_dice_explicitamente_que_no_lleva_alcohol_se_respeta(): void
    {
        $sinAlcohol = Product::create(['name' => 'Agua mineral', 'price' => 20, 'category' => 'Cervecería', 'available' => 1, 'alcoholic' => false]);

        $this->assertFalse($sinAlcohol->fresh()->alcoholic);
    }

    // ── API: el envío ────────────────────────────────────────────────────────────────────────

    public function test_la_api_rechaza_el_pedido_sin_aceptar_el_cobro_del_envio(): void
    {
        $this->postJson(self::API, $this->datos($this->hamburguesa, ['delivery_terms_accepted' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('delivery_terms_accepted')
            ->assertJsonPath('errors.delivery_terms_accepted.0', 'Confirma que leíste cómo se cobra el envío a domicilio.');

        $datos = $this->datos($this->hamburguesa);
        unset($datos['delivery_terms_accepted']);
        $this->postJson(self::API, $datos)->assertStatus(422)->assertJsonValidationErrors('delivery_terms_accepted');

        $this->assertSame(0, Order::count());
    }

    public function test_un_pedido_normal_guarda_solo_la_evidencia_del_envio(): void
    {
        $this->postJson(self::API, $this->datos($this->hamburguesa))->assertCreated();

        $pedido = Order::firstOrFail();
        $this->assertNotNull($pedido->delivery_terms_accepted_at);
        $this->assertNull($pedido->adult_confirmed_at, 'no se pidió mayoría de edad, no hay evidencia que guardar');
        $this->assertNull($pedido->health_data_consent_at);
    }

    // ── API: alcohol ─────────────────────────────────────────────────────────────────────────

    public function test_una_cerveza_sin_confirmar_la_mayoria_de_edad_se_rechaza(): void
    {
        $this->postJson(self::API, $this->datos($this->cerveza))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'confirmation_required')
            ->assertJsonPath('message', CheckoutCompliance::MESSAGE_ADULT);

        $this->assertStringContainsString('18 años', CheckoutCompliance::MESSAGE_ADULT);
        $this->assertSame(0, Order::count());
    }

    public function test_una_cerveza_con_la_casilla_se_crea_y_guarda_cuando_confirmo_la_edad(): void
    {
        $this->postJson(self::API, $this->datos($this->cerveza, ['adult_confirmed' => true]))->assertCreated();

        $pedido = Order::firstOrFail();
        $this->assertNotNull($pedido->adult_confirmed_at);
        $this->assertNotNull($pedido->delivery_terms_accepted_at);
    }

    public function test_adult_confirmed_falso_no_basta(): void
    {
        foreach ([false, 0, '0', null] as $valor) {
            $this->postJson(self::API, $this->datos($this->cerveza, ['adult_confirmed' => $valor]))
                ->assertStatus(422)
                ->assertJsonPath('error', 'confirmation_required');
        }

        // Un texto que no es un booleano ni lo acepta como confirmación
        $this->postJson(self::API, $this->datos($this->cerveza, ['adult_confirmed' => 'false']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('adult_confirmed');

        $this->assertSame(0, Order::count());
    }

    public function test_la_cerveza_cero_y_un_preparado_no_piden_mayoria_de_edad(): void
    {
        $this->postJson(self::API, $this->datos($this->cervezaCero))->assertCreated();
        $this->postJson(self::API, $this->datos($this->preparado))->assertCreated();

        $this->assertSame(2, Order::count());
        $this->assertNull(Order::whereNotNull('adult_confirmed_at')->first());
    }

    public function test_lo_que_manda_el_cliente_no_decide_si_hay_alcohol(): void
    {
        // Intenta engañar al servidor diciendo que la cerveza no lleva alcohol
        $this->postJson(self::API, $this->datos($this->cerveza, [], ['alcoholic' => false]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'confirmation_required');

        // Y al revés: una hamburguesa no pide edad aunque diga que sí lleva
        $this->postJson(self::API, $this->datos($this->hamburguesa, [], ['alcoholic' => true]))->assertCreated();
        $this->assertNull(Order::firstOrFail()->adult_confirmed_at);
    }

    public function test_si_la_carta_cambia_la_marca_de_un_producto_el_pedido_lo_respeta(): void
    {
        $this->hamburguesa->update(['alcoholic' => true]);

        $this->postJson(self::API, $this->datos($this->hamburguesa))
            ->assertStatus(422)
            ->assertJsonPath('error', 'confirmation_required');
    }

    public function test_una_cerveza_mezclada_con_comida_pide_la_edad(): void
    {
        $datos = $this->datos($this->hamburguesa);
        $datos['items'][] = ['product_id' => $this->cerveza->id, 'quantity' => 2];

        $this->postJson(self::API, $datos)->assertStatus(422)->assertJsonPath('error', 'confirmation_required');
        $this->postJson(self::API, array_merge($datos, ['adult_confirmed' => true]))->assertCreated();
    }

    // ── API: promociones con alcohol ─────────────────────────────────────────────────────────

    private function promocion(array $productos): Promotion
    {
        $promo = Promotion::create(['title' => 'Combo Cheve', 'description' => 'x', 'price_text' => '$200', 'price' => 200, 'active' => 1, 'sellable_online' => true]);
        $promo->products()->attach($productos);

        return $promo;
    }

    public function test_una_promocion_que_incluye_cerveza_pide_la_edad(): void
    {
        $promo = $this->promocion([
            $this->hamburguesa->id => ['quantity' => 1, 'slot' => 0],
            $this->cerveza->id => ['quantity' => 2, 'slot' => 1],
        ]);
        $datos = $this->datos($this->hamburguesa);
        // Los dos renglones son fijos (un solo platillo cada uno): no hay nada que elegir
        $datos['items'] = [['promotion_id' => $promo->id, 'quantity' => 1]];

        $this->postJson(self::API, $datos)->assertStatus(422)->assertJsonPath('error', 'confirmation_required');

        $this->postJson(self::API, array_merge($datos, ['adult_confirmed' => true]))->assertCreated();
        $this->assertNotNull(Order::firstOrFail()->adult_confirmed_at);
    }

    public function test_una_promocion_a_elegir_pide_la_edad_solo_si_elige_o_podria_elegir_alcohol(): void
    {
        // 1 bebida a elegir: cerveza (alcohol) o cerveza cero (sin alcohol)
        $promo = $this->promocion([
            $this->hamburguesa->id => ['quantity' => 1, 'slot' => 0],
            $this->cerveza->id => ['quantity' => 1, 'slot' => 1],
            $this->cervezaCero->id => ['quantity' => 1, 'slot' => 1],
        ]);
        $base = $this->datos($this->hamburguesa);

        // Elige la sin alcohol → no pide edad
        $base['items'] = [['promotion_id' => $promo->id, 'quantity' => 1, 'choices' => [1 => [['id' => $this->cervezaCero->id, 'quantity' => 1]]]]];
        $this->postJson(self::API, $base)->assertCreated();

        // Elige la cerveza → pide edad
        $base['items'] = [['promotion_id' => $promo->id, 'quantity' => 1, 'choices' => [1 => [['id' => $this->cerveza->id, 'quantity' => 1]]]]];
        $this->postJson(self::API, $base)->assertStatus(422)->assertJsonPath('error', 'confirmation_required');

        // Versión anterior de la app (sin choices): como podría tocarle cerveza, pide la edad
        $base['items'] = [['promotion_id' => $promo->id, 'quantity' => 1]];
        $this->postJson(self::API, $base)->assertStatus(422)->assertJsonPath('error', 'confirmation_required');
    }

    // ── API: notas y datos de salud ──────────────────────────────────────────────────────────

    public function test_unas_notas_sin_autorizacion_se_rechazan(): void
    {
        $this->postJson(self::API, $this->datos($this->hamburguesa, [], ['notes' => 'Soy alérgico al cacahuate']))
            ->assertStatus(422)
            ->assertJsonPath('error', 'confirmation_required')
            ->assertJsonPath('message', CheckoutCompliance::MESSAGE_HEALTH);

        $this->assertSame(0, Order::count());
    }

    public function test_unas_notas_con_autorizacion_se_guardan_con_su_evidencia(): void
    {
        $this->postJson(self::API, $this->datos($this->hamburguesa, ['health_data_consent' => true], ['notes' => 'Soy alérgico al cacahuate']))
            ->assertCreated();

        $pedido = Order::with('items')->firstOrFail();
        $this->assertNotNull($pedido->health_data_consent_at);
        $this->assertNull($pedido->adult_confirmed_at);
        $this->assertSame('Soy alérgico al cacahuate', $pedido->items->first()->notes);
    }

    public function test_notas_en_blanco_no_piden_autorizacion(): void
    {
        $this->postJson(self::API, $this->datos($this->hamburguesa, [], ['notes' => '   ']))->assertCreated();

        $this->assertNull(Order::firstOrFail()->health_data_consent_at);
    }

    public function test_las_notas_se_limitan_a_100_caracteres_en_el_servidor(): void
    {
        $this->postJson(self::API, $this->datos($this->hamburguesa, ['health_data_consent' => true], ['notes' => str_repeat('a', 101)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.notes');

        $this->postJson(self::API, $this->datos($this->hamburguesa, ['health_data_consent' => true], ['notes' => str_repeat('a', 100)]))
            ->assertCreated();
    }

    public function test_alcohol_y_notas_juntos_guardan_las_tres_evidencias(): void
    {
        $this->postJson(self::API, $this->datos($this->cerveza, ['adult_confirmed' => true, 'health_data_consent' => true], ['notes' => 'Bien fría']))
            ->assertCreated();

        $pedido = Order::firstOrFail();
        $this->assertNotNull($pedido->delivery_terms_accepted_at);
        $this->assertNotNull($pedido->adult_confirmed_at);
        $this->assertNotNull($pedido->health_data_consent_at);
    }

    // ── Texto del envío en el panel ──────────────────────────────────────────────────────────

    public function test_sin_texto_guardado_se_usa_el_texto_por_defecto(): void
    {
        $this->assertSame(CheckoutCompliance::DEFAULT_DELIVERY_NOTE, CheckoutCompliance::deliveryNote());

        Setting::updateOrCreate(['key' => 'delivery_fee_note'], ['value' => '   ']);
        $this->assertSame(CheckoutCompliance::DEFAULT_DELIVERY_NOTE, CheckoutCompliance::deliveryNote(), 'un texto vacío no deja al cliente sin información');
    }

    public function test_el_admin_cambia_el_texto_del_envio_y_la_web_lo_muestra(): void
    {
        $nuevo = 'Centro $30 · colonias $40 · comunidades cercanas $50. Se paga al repartidor al recibir.';

        $this->actingAs($this->admin())
            ->post(route('admin.settings.delivery'), ['delivery_fee_note' => $nuevo])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($nuevo, Setting::get('delivery_fee_note'));
        $this->assertSame($nuevo, CheckoutCompliance::deliveryNote());
    }

    public function test_el_cliente_que_pide_ve_el_texto_que_escribio_el_restaurante(): void
    {
        $nuevo = 'Centro $30 · colonias $40 · comunidades cercanas $50. Se paga al repartidor al recibir.';
        Setting::updateOrCreate(['key' => 'delivery_fee_note'], ['value' => $nuevo]);

        $this->alCarrito($this->hamburguesa);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee($nuevo, false)
            ->assertDontSee('pregúntalo al hacer tu pedido por WhatsApp', false);
    }

    public function test_el_texto_del_envio_es_obligatorio_y_de_maximo_500_caracteres(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.settings.delivery'), ['delivery_fee_note' => ''])
            ->assertSessionHasErrors('delivery_fee_note');
        $this->actingAs($admin)->post(route('admin.settings.delivery'), ['delivery_fee_note' => str_repeat('a', 501)])
            ->assertSessionHasErrors('delivery_fee_note');
        $this->actingAs($admin)->post(route('admin.settings.delivery'), ['delivery_fee_note' => str_repeat('a', 500)])
            ->assertSessionHasNoErrors();

        $this->assertSame(500, mb_strlen(Setting::get('delivery_fee_note')));
    }

    public function test_un_cliente_no_puede_cambiar_el_texto_del_envio(): void
    {
        $cliente = User::create(['name' => 'Cliente', 'email' => 'c@la501.test', 'password' => 'La501Sports!', 'role' => 'cliente']);

        // El middleware de admin manda a quien no lo es a otra pantalla (302) sin ejecutar nada
        $this->actingAs($cliente)->post(route('admin.settings.delivery'), ['delivery_fee_note' => 'Gratis'])->assertRedirect();
        auth()->logout();
        $this->post(route('admin.settings.delivery'), ['delivery_fee_note' => 'Gratis'])->assertRedirect();

        $this->assertNotSame('Gratis', Setting::get('delivery_fee_note'));
    }

    public function test_la_pantalla_de_configuracion_del_admin_muestra_la_seccion(): void
    {
        $this->actingAs($this->admin())->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Costo del envío a domicilio', false)
            ->assertSee('name="delivery_fee_note"', false);
    }

    // ── Web: checkout ────────────────────────────────────────────────────────────────────────

    private function alCarrito(Product $producto, array $extra = []): void
    {
        $this->postJson(route('cart.add'), array_merge(['id' => $producto->id, 'quantity' => 1], $extra))->assertOk();
    }

    private function datosWeb(array $extra = []): array
    {
        return array_merge([
            'customer_name' => 'Ana Prueba', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'payment_method' => 'efectivo', 'delivery_terms_accepted' => '1',
        ], $extra);
    }

    public function test_el_checkout_web_muestra_el_envio_con_la_casilla_sin_premarcar(): void
    {
        $this->alCarrito($this->hamburguesa);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee(CheckoutCompliance::DEFAULT_DELIVERY_NOTE, false)
            ->assertSee('name="delivery_terms_accepted"', false)
            ->assertSee('No incluye el envío', false)
            ->assertDontSee('name="delivery_terms_accepted" value="1" required checked', false)
            ->assertDontSee('name="adult_confirmed"', false)
            ->assertDontSee('name="health_data_consent"', false);
    }

    public function test_la_casilla_de_edad_solo_aparece_si_hay_alcohol(): void
    {
        $this->alCarrito($this->cerveza);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('name="adult_confirmed"', false)
            ->assertSee('mayor de 18 años', false)
            ->assertDontSee('name="health_data_consent"', false);
    }

    public function test_la_casilla_de_edad_no_aparece_con_cerveza_cero(): void
    {
        $this->alCarrito($this->cervezaCero);

        $this->get(route('checkout.index'))->assertOk()->assertDontSee('name="adult_confirmed"', false);
    }

    public function test_la_casilla_de_datos_de_salud_solo_aparece_si_hay_notas(): void
    {
        $this->alCarrito($this->hamburguesa, ['notes' => 'Sin picante, soy alérgico']);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('name="health_data_consent"', false)
            ->assertDontSee('name="adult_confirmed"', false);
    }

    public function test_el_carrito_dice_que_no_incluye_el_envio(): void
    {
        $this->alCarrito($this->hamburguesa);

        $this->get(route('cart.index'))->assertOk()->assertSee('No incluye el envío', false);
    }

    public function test_la_web_rechaza_el_pedido_sin_aceptar_el_envio(): void
    {
        $this->alCarrito($this->hamburguesa);

        $datos = $this->datosWeb();
        unset($datos['delivery_terms_accepted']);

        $this->post(route('checkout.process'), $datos)->assertSessionHasErrors('delivery_terms_accepted');
        $this->assertSame(0, Order::count());
    }

    public function test_la_web_rechaza_una_cerveza_sin_confirmar_la_edad_y_la_acepta_con_la_casilla(): void
    {
        $this->alCarrito($this->cerveza);

        $this->post(route('checkout.process'), $this->datosWeb())->assertSessionHasErrors('adult_confirmed');
        $this->assertSame(0, Order::count());

        $this->post(route('checkout.process'), $this->datosWeb(['adult_confirmed' => '1']))->assertRedirect();

        $pedido = Order::firstOrFail();
        $this->assertNotNull($pedido->adult_confirmed_at);
        $this->assertNotNull($pedido->delivery_terms_accepted_at);
    }

    public function test_la_web_rechaza_notas_sin_autorizacion_y_las_acepta_con_ella(): void
    {
        $this->alCarrito($this->hamburguesa, ['notes' => 'Sin cacahuate, por favor']);

        $this->post(route('checkout.process'), $this->datosWeb())->assertSessionHasErrors('health_data_consent');
        $this->assertSame(0, Order::count());

        $this->post(route('checkout.process'), $this->datosWeb(['health_data_consent' => '1']))->assertRedirect();
        $this->assertNotNull(Order::firstOrFail()->health_data_consent_at);
    }

    public function test_la_web_pide_la_edad_por_una_promocion_con_cerveza(): void
    {
        $promo = $this->promocion([
            $this->hamburguesa->id => ['quantity' => 1, 'slot' => 0],
            $this->cerveza->id => ['quantity' => 1, 'slot' => 1],
        ]);
        $this->post(route('cart.addPromotion'), ['promotion_id' => $promo->id])->assertSessionHasNoErrors();

        $this->post(route('checkout.process'), $this->datosWeb())->assertSessionHasErrors('adult_confirmed');
        $this->post(route('checkout.process'), $this->datosWeb(['adult_confirmed' => '1']))->assertRedirect();

        $this->assertNotNull(Order::firstOrFail()->adult_confirmed_at);
    }

    // ── Panel: casilla «Lleva alcohol» ───────────────────────────────────────────────────────

    private function datosProductoAdmin(Product $producto, array $extra = []): array
    {
        return array_merge([
            'name' => $producto->name, 'price' => $producto->price, 'category' => $producto->category,
            'station' => $producto->station ?: 'cocina', 'available' => '1',
        ], $extra);
    }

    public function test_el_admin_marca_y_desmarca_lleva_alcohol_en_un_platillo(): void
    {
        \App\Models\MenuCategory::create(['name' => 'Hamburguesas', 'section' => 'comidas', 'sort_order' => 1]);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.menu.update', $this->hamburguesa->id), $this->datosProductoAdmin($this->hamburguesa, ['alcoholic' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertTrue($this->hamburguesa->fresh()->alcoholic);

        $this->actingAs($admin)->put(route('admin.menu.update', $this->hamburguesa->id), $this->datosProductoAdmin($this->hamburguesa, ['alcoholic' => '0']))
            ->assertSessionHasNoErrors();
        $this->assertFalse($this->hamburguesa->fresh()->alcoholic);
    }

    public function test_editar_un_platillo_sin_mandar_el_campo_conserva_la_marca(): void
    {
        \App\Models\MenuCategory::create(['name' => 'Cervecería', 'section' => 'bebidas', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->put(route('admin.menu.update', $this->cerveza->id), $this->datosProductoAdmin($this->cerveza, ['price' => 50]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->cerveza->fresh()->alcoholic, 'otro cliente del panel no debe quitar la marca sin querer');
        $this->assertEquals(50, $this->cerveza->fresh()->price);
    }

    public function test_un_platillo_nuevo_de_una_categoria_de_alcohol_creado_en_el_panel_nace_marcado(): void
    {
        \App\Models\MenuCategory::create(['name' => 'Destilados', 'section' => 'bebidas', 'sort_order' => 1]);

        $this->actingAs($this->admin())->post(route('admin.menu.store'), [
            'name' => 'Tequila Reposado', 'price' => 80, 'category' => 'Destilados', 'station' => 'barra', 'available' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Product::where('name', 'Tequila Reposado')->firstOrFail()->alcoholic);
    }

    // ── Ticket del mandadito ─────────────────────────────────────────────────────────────────

    private function pedidoEnCamino(array $datos): Order
    {
        $pedido = Order::create(array_merge([
            'customer_name' => 'Ana', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'total' => 100, 'status' => 'delivering', 'payment_method' => 'efectivo',
        ], $datos));

        return $pedido;
    }

    public function test_el_ticket_del_mandadito_dice_pedir_ine_cuando_hay_alcohol(): void
    {
        $mesero = User::create(['name' => 'Mesero', 'email' => 'mesero@la501.test', 'password' => 'La501Sports!', 'role' => 'empleado']);
        $pedido = $this->pedidoEnCamino(['adult_confirmed_at' => now()]);

        $this->actingAs($mesero)->get(route('mesero.domicilios.ticket', $pedido->id))
            ->assertOk()
            ->assertSee('LLEVA ALCOHOL:', false)
            ->assertSee('PEDIR INE', false);
    }

    public function test_el_ticket_del_mandadito_no_lo_dice_sin_alcohol(): void
    {
        $mesero = User::create(['name' => 'Mesero', 'email' => 'mesero@la501.test', 'password' => 'La501Sports!', 'role' => 'empleado']);
        $pedido = $this->pedidoEnCamino([]);

        $this->actingAs($mesero)->get(route('mesero.domicilios.ticket', $pedido->id))
            ->assertOk()
            ->assertDontSee('PEDIR INE', false);
    }

    public function test_un_pedido_anterior_sin_evidencia_pero_con_cerveza_tambien_avisa(): void
    {
        $pedido = $this->pedidoEnCamino([]);
        $pedido->items()->create([
            'product_id' => $this->cerveza->id, 'product_name' => 'Cerveza Corona', 'station' => 'ninguna',
            'quantity' => 1, 'price' => 45, 'subtotal' => 45,
        ]);

        $this->assertTrue($pedido->fresh()->llevaAlcohol());
    }

    // ── Migración: marca el menú que ya existe ───────────────────────────────────────────────

    public function test_la_migracion_marca_el_alcohol_del_menu_existente_y_deja_el_texto_del_envio(): void
    {
        // Como estaba el menú antes de esta versión: nada marcado
        DB::table('products')->update(['alcoholic' => false]);
        DB::table('settings')->where('key', 'delivery_fee_note')->delete();
        DB::table('products')->insert([
            ['name' => 'Tequila', 'price' => 80, 'category' => 'Destilados', 'subcategory' => null, 'available' => 1],
            ['name' => 'Cerveza Victoria', 'price' => 40, 'category' => 'Cervezas', 'subcategory' => null, 'available' => 1],
            ['name' => 'Victoria Cero', 'price' => 40, 'category' => 'Cervezas', 'subcategory' => null, 'available' => 1],
            ['name' => 'Preparado Chelada', 'price' => 30, 'category' => 'Cervecería', 'subcategory' => 'Preparados', 'available' => 1],
            ['name' => 'Refresco', 'price' => 25, 'category' => 'Refrescos', 'subcategory' => null, 'available' => 1],
        ]);
        DB::table('products')->update(['alcoholic' => false]);

        (require base_path('database/migrations/2026_11_05_000200_add_checkout_compliance_columns.php'))->up();

        $marcados = DB::table('products')->where('alcoholic', true)->pluck('name')->all();
        sort($marcados);
        // Cerveza Corona (setUp) también está en Cervecería
        $this->assertSame(['Cerveza Corona', 'Cerveza Victoria', 'Tequila'], $marcados);

        $this->assertSame(CheckoutCompliance::DEFAULT_DELIVERY_NOTE, DB::table('settings')->where('key', 'delivery_fee_note')->value('value'));
    }

    public function test_la_migracion_no_pisa_el_texto_del_envio_que_ya_escribio_el_restaurante(): void
    {
        Setting::updateOrCreate(['key' => 'delivery_fee_note'], ['value' => 'Centro $30']);

        (require base_path('database/migrations/2026_11_05_000200_add_checkout_compliance_columns.php'))->up();

        $this->assertSame('Centro $30', Setting::get('delivery_fee_note'));
    }
}
