<?php

namespace Tests\Feature;

use App\Exceptions\InvalidOrderLine;
use App\Models\OptionGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderLineBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menú nuevo: presentaciones, opciones (salsas, papas), cocina y barra por separado
 * y el "por entregar" del mesero.
 */
class MenuNuevoTest extends TestCase
{
    use RefreshDatabase;

    private Product $alitas;
    private Product $margarita;
    private Product $refresco;
    private OptionGroup $salsas;
    private OptionGroup $papas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salsas = OptionGroup::create(['name' => 'Salsa', 'label' => 'Elige tu salsa', 'min_select' => 1, 'max_select' => 1]);
        foreach (['Buffalo', 'Macha', 'BBQ'] as $i => $salsa) {
            $this->salsas->options()->create(['name' => $salsa, 'price' => 0, 'sort_order' => $i]);
        }
        $this->papas = OptionGroup::create(['name' => 'Papas', 'label' => '¿Con papas?', 'min_select' => 0, 'max_select' => 1]);
        $this->papas->options()->create(['name' => 'Con papas', 'price' => 22]);

        $this->alitas = Product::create(['name' => 'Alitas 700 g', 'price' => 270, 'category' => 'Alitas', 'available' => 1, 'station' => 'cocina']);
        $this->alitas->optionGroups()->attach($this->salsas->id, ['min_select' => 1, 'max_select' => 2, 'sort_order' => 1]);
        $this->alitas->optionGroups()->attach($this->papas->id, ['min_select' => 0, 'max_select' => 1, 'sort_order' => 2]);

        $this->margarita = Product::create(['name' => 'Margarita', 'price' => 80, 'category' => 'Coctelería', 'section' => 'bebidas', 'available' => 1, 'station' => 'barra']);
        $this->margarita->variants()->createMany([
            ['name' => 'Vaso', 'price' => 80, 'sort_order' => 1],
            ['name' => 'Litro', 'price' => 160, 'sort_order' => 2],
        ]);

        $this->refresco = Product::create(['name' => 'Coca-Cola', 'price' => 35, 'category' => 'Bebidas sin Alcohol', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna']);
    }

    private function opcion(OptionGroup $grupo, string $nombre): int
    {
        return $grupo->options()->where('name', $nombre)->value('id');
    }

    private function usuario(string $rol): User
    {
        return User::create(['name' => ucfirst($rol), 'email' => $rol . '@la501.test', 'password' => 'La501Sports!', 'role' => $rol]);
    }

    // ---------------------------------------------------------------------
    // Precio con presentación y opciones
    // ---------------------------------------------------------------------

    public function test_el_precio_suma_la_presentacion_y_las_opciones(): void
    {
        $builder = app(OrderLineBuilder::class);

        $litro = $this->margarita->variants()->where('name', 'Litro')->value('id');
        $linea = $builder->build($this->margarita, $litro, []);
        $this->assertEquals(160, $linea['price']);
        $this->assertSame('Margarita (Litro)', $linea['product_name']);

        $linea = $builder->build($this->alitas, null, [$this->opcion($this->salsas, 'Buffalo'), $this->opcion($this->papas, 'Con papas')]);
        $this->assertEquals(292, $linea['price']);
        $this->assertSame('Salsa: Buffalo · Papas: Con papas', (new OrderItem(['options' => $linea['options']]))->optionsSummary());
    }

    public function test_se_exige_el_minimo_y_se_respeta_el_maximo_de_cada_grupo(): void
    {
        $builder = app(OrderLineBuilder::class);

        try {
            $builder->build($this->alitas, null, []);
            $this->fail('Debió pedir la salsa');
        } catch (InvalidOrderLine $e) {
            $this->assertStringContainsString('Elige tu salsa', $e->getMessage());
        }

        $this->expectException(InvalidOrderLine::class);
        $builder->build($this->alitas, null, [
            $this->opcion($this->salsas, 'Buffalo'), $this->opcion($this->salsas, 'Macha'), $this->opcion($this->salsas, 'BBQ'),
        ]);
    }

    public function test_una_opcion_de_otro_platillo_no_se_acepta(): void
    {
        $this->expectException(InvalidOrderLine::class);

        app(OrderLineBuilder::class)->build($this->margarita, null, [$this->opcion($this->salsas, 'Buffalo')]);
    }

    public function test_el_carrito_web_guarda_las_opciones_con_su_precio(): void
    {
        $this->postJson('/carrito/agregar', [
            'id' => $this->alitas->id,
            'quantity' => 1,
            'options' => [['id' => $this->opcion($this->salsas, 'Macha'), 'quantity' => 1], ['id' => $this->opcion($this->papas, 'Con papas')]],
        ])->assertOk();

        $linea = collect(session('cart'))->first();
        $this->assertEquals(292, $linea['price']);
        $this->assertSame('Salsa: Macha · Papas: Con papas', $linea['options_summary']);

        $this->postJson('/carrito/agregar', ['id' => $this->alitas->id, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ---------------------------------------------------------------------
    // Cocina y barra
    // ---------------------------------------------------------------------

    private function pedidoDeMesa(int $mesa = 4): Order
    {
        $mesero = $this->usuario('empleado');
        $carrito = [
            ['id' => $this->alitas->id, 'cantidad' => 1, 'options' => [['id' => $this->opcion($this->salsas, 'Buffalo'), 'quantity' => 1]]],
            ['id' => $this->margarita->id, 'cantidad' => 2, 'variant_id' => $this->margarita->variants()->where('name', 'Vaso')->value('id')],
            ['id' => $this->refresco->id, 'cantidad' => 1],
        ];

        $this->actingAs($mesero)
            ->post("/mesero/mesas/{$mesa}/pedido", ['carrito' => json_encode($carrito), 'total' => 1])
            ->assertRedirect(route('mesero.mesas'));

        return Order::with('items')->where('table_number', $mesa)->latest('id')->firstOrFail();
    }

    public function test_el_mesero_no_puede_cambiar_el_precio_y_cada_linea_va_a_su_estacion(): void
    {
        $pedido = $this->pedidoDeMesa();

        $this->assertEquals(270 + 160 + 35, (float) $pedido->total);
        $this->assertSame('pending', $pedido->status);
        $this->assertEqualsCanonicalizing(['cocina', 'barra', 'ninguna'], $pedido->items->pluck('station')->all());
        // El refresco no se prepara: queda listo para entregar desde el inicio
        $this->assertNotNull($pedido->items->firstWhere('station', 'ninguna')->prepared_at);
    }

    public function test_cocina_y_barra_solo_ven_lo_suyo_y_el_pedido_queda_listo_cuando_ambas_terminan(): void
    {
        $pedido = $this->pedidoDeMesa();
        $cocinero = $this->usuario('cocinero');
        $this->actingAs($cocinero);

        $cocina = $this->getJson('/api/cocina/ordenes?estacion=cocina')->assertOk()->json();
        $barra = $this->getJson('/api/cocina/ordenes?estacion=barra')->assertOk()->json();

        $this->assertSame(['Alitas 700 g'], array_column($cocina[0]['items'], 'product_name'));
        $this->assertSame('Salsa: Buffalo', $cocina[0]['items'][0]['options']);
        $this->assertSame(2, $cocina[0]['other_station_pending']);
        $this->assertSame(['Margarita (Vaso)'], array_column($barra[0]['items'], 'product_name'));

        $this->postJson('/cocina/item/' . $cocina[0]['items'][0]['id'] . '/listo', ['listo' => true])->assertOk();
        $this->assertSame('pending', $pedido->fresh()->status);
        $this->assertSame([], $this->getJson('/api/cocina/ordenes?estacion=cocina')->json());

        $this->postJson('/cocina/item/' . $barra[0]['items'][0]['id'] . '/listo', ['listo' => true])
            ->assertOk()
            ->assertJsonPath('order_status', 'ready');
        $this->assertSame('ready', $pedido->fresh()->status);
    }

    public function test_un_check_por_error_se_puede_quitar_mientras_el_pedido_sigue_en_preparacion(): void
    {
        $pedido = $this->pedidoDeMesa();
        $this->actingAs($this->usuario('cocinero'));
        $alitas = $pedido->items->firstWhere('station', 'cocina');

        $this->postJson("/cocina/item/{$alitas->id}/listo", ['listo' => true])->assertOk();
        $this->postJson("/cocina/item/{$alitas->id}/listo", ['listo' => false])->assertOk();

        $this->assertNull($alitas->fresh()->prepared_at);
    }

    public function test_todo_listo_de_una_estacion_no_marca_lo_de_la_otra(): void
    {
        $pedido = $this->pedidoDeMesa();
        $this->actingAs($this->usuario('cocinero'));

        $this->postJson("/cocina/orden/{$pedido->id}/ready", ['estacion' => 'barra'])->assertOk();

        $this->assertNotNull($pedido->items->firstWhere('station', 'barra')->fresh()->prepared_at);
        $this->assertNull($pedido->items->firstWhere('station', 'cocina')->fresh()->prepared_at);
        $this->assertSame('pending', $pedido->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // Mesero: por entregar
    // ---------------------------------------------------------------------

    public function test_un_pedido_de_puras_bebidas_sin_preparar_queda_listo_y_por_entregar(): void
    {
        $mesero = $this->usuario('empleado');
        $this->actingAs($mesero)->post('/mesero/mesas/2/pedido', [
            'carrito' => json_encode([['id' => $this->refresco->id, 'cantidad' => 3]]),
        ]);

        $pedido = Order::where('table_number', 2)->firstOrFail();
        $this->assertSame('ready', $pedido->status);

        $mesa = collect($this->getJson('/mesero/api/mesas')->assertOk()->json())->firstWhere('id', 2);
        $this->assertSame('Coca-Cola', $mesa['por_entregar'][0]['nombre']);
        $this->assertSame(0, $mesa['en_preparacion']);
    }

    public function test_el_mesero_marca_lo_entregado_y_deja_de_aparecer(): void
    {
        $pedido = $this->pedidoDeMesa(6);
        $refresco = $pedido->items->firstWhere('station', 'ninguna');
        $alitas = $pedido->items->firstWhere('station', 'cocina');

        // Lo que sigue en cocina no se puede entregar todavía
        $this->postJson("/mesero/items/{$alitas->id}/entregado")->assertStatus(422);

        $this->postJson("/mesero/items/{$refresco->id}/entregado")->assertOk();
        $this->assertNotNull($refresco->fresh()->served_at);

        $mesa = collect($this->getJson('/mesero/api/mesas')->json())->firstWhere('id', 6);
        $this->assertSame([], $mesa['por_entregar']);
        $this->assertSame(3, $mesa['en_preparacion']);
    }

    // ---------------------------------------------------------------------
    // API de la app del mesero
    // ---------------------------------------------------------------------

    public function test_la_app_del_mesero_manda_opciones_y_marca_entregado(): void
    {
        Sanctum::actingAs($this->usuario('empleado'));

        $this->postJson('/api/v1/app/waiter/tables/8/orders', ['items' => [
            ['product_id' => $this->alitas->id, 'quantity' => 1, 'options' => [['id' => $this->opcion($this->salsas, 'BBQ')]]],
            ['product_id' => $this->refresco->id, 'quantity' => 1],
        ]])->assertCreated();

        $mesa = $this->getJson('/api/v1/app/waiter/tables/8')->assertOk()->json('data');
        $this->assertEquals(305, $mesa['total']);
        $this->assertSame(1, $mesa['to_serve']);

        $items = collect($mesa['orders'][0]['items']);
        $this->assertSame('Salsa: BBQ', $items->firstWhere('station', 'cocina')['options_summary']);
        $refresco = $items->firstWhere('station', 'ninguna');
        $this->assertSame('ready', $refresco['status']);

        $this->postJson("/api/v1/app/waiter/tables/8/items/{$refresco['id']}/served")
            ->assertOk()
            ->assertJsonPath('data.status', 'served');
    }

    public function test_una_version_anterior_de_la_app_sin_opciones_sigue_funcionando(): void
    {
        Sanctum::actingAs($this->usuario('empleado'));

        $this->postJson('/api/v1/app/waiter/tables/9/orders', ['items' => [
            ['product_id' => $this->alitas->id, 'quantity' => 1, 'notes' => 'Salsa buffalo'],
            ['product_id' => $this->margarita->id, 'quantity' => 1],
        ]])->assertCreated();

        // Sin presentación elegida se cobra la primera (la más barata)
        $this->assertEquals(270 + 80, (float) Order::where('table_number', 9)->value('total'));
    }

    public function test_si_la_app_manda_opciones_incompletas_se_rechaza(): void
    {
        Sanctum::actingAs($this->usuario('empleado'));

        $this->postJson('/api/v1/app/waiter/tables/9/orders', ['items' => [
            ['product_id' => $this->alitas->id, 'quantity' => 1, 'options' => [['id' => $this->opcion($this->papas, 'Con papas')]]],
        ]])->assertStatus(422)->assertJsonPath('error', 'invalid_options');
    }

    public function test_las_pantallas_de_mesero_y_cocina_cargan(): void
    {
        $this->pedidoDeMesa(3);

        $this->actingAs($this->usuario('cocinero'))->get('/cocina')->assertOk()->assertSee('Orden lista', false);

        $mesero = User::where('role', 'empleado')->firstOrFail();
        $this->actingAs($mesero)->get('/mesero/mesas')->assertOk()->assertSee('Por entregar', false);
        $this->actingAs($mesero)->get('/mesero/mesas/3/pedido')->assertOk()->assertSee('Enviar pedido', false);
    }

    public function test_al_enviar_la_orden_el_mesero_ve_un_aviso_corto(): void
    {
        $this->actingAs($this->usuario('empleado'))
            ->post('/mesero/mesas/2/pedido', ['carrito' => json_encode([['id' => $this->refresco->id, 'cantidad' => 1]])])
            ->assertSessionHas('success', 'Orden enviada');
    }

    // ---------------------------------------------------------------------
    // Domicilio
    // ---------------------------------------------------------------------

    public function test_un_domicilio_de_puras_bebidas_pasa_a_listo_al_pagarse(): void
    {
        $pedido = Order::create(['customer_name' => 'Ana', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1', 'total' => 35, 'status' => 'pending']);
        OrderItem::create(['order_id' => $pedido->id, 'product_id' => $this->refresco->id, 'product_name' => 'Coca-Cola', 'quantity' => 1, 'price' => 35, 'subtotal' => 35]);

        $pedido->update(['status' => 'paid']);

        $this->assertSame('ready', $pedido->fresh()->status);
    }
}
