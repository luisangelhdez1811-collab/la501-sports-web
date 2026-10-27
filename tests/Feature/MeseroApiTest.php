<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TableCharge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeseroApiTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app/waiter';

    private User $mesero;
    private Product $hamburguesa;

    protected function setUp(): void
    {
        parent::setUp();

        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        $this->mesero = User::create([
            'name' => 'Mesero de prueba',
            'email' => 'mesero@la501.test',
            'password' => 'La501Sports!',
            'role' => 'empleado',
        ]);
        $this->hamburguesa = Product::create(['name' => 'Hamburguesa 501', 'price' => 150, 'category' => 'Hamburguesas', 'available' => 1]);

        Sanctum::actingAs($this->mesero);
    }

    private function enviarPedido(int $mesa, array $extra = [], int $cantidad = 1)
    {
        return $this->postJson(self::API . "/tables/{$mesa}/orders", array_merge([
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => $cantidad]],
        ], $extra));
    }

    private function marcarListos(): void
    {
        Order::query()->update(['status' => 'ready']);
    }

    public function test_cada_comensal_queda_como_un_pedido_separado_de_la_mesa(): void
    {
        $this->enviarPedido(3, ['label' => 'Persona 1'])->assertCreated();
        $this->enviarPedido(3, ['label' => 'Persona 2'], 2)->assertCreated();

        $mesa = $this->getJson(self::API . '/tables/3')->assertOk()->json('data');

        $this->assertSame('ocupada', $mesa['status']);
        $this->assertEquals(450, $mesa['total']);
        $this->assertSame(['Persona 1', 'Persona 2'], array_column($mesa['orders'], 'label'));
        $this->assertEquals([150, 300], array_column($mesa['orders'], 'total'));
    }

    public function test_reenviar_un_pedido_guardado_sin_conexion_no_lo_duplica(): void
    {
        $pedido = ['client_ref' => 'pedido-offline-0001', 'offline' => true, 'label' => 'Persona 1'];

        $this->enviarPedido(5, $pedido)->assertCreated();
        $this->enviarPedido(5, $pedido)->assertOk()->assertJson(['duplicate' => true]);

        $this->assertSame(1, Order::where('table_number', 5)->count());
        $this->assertStringContainsString('sin conexión', Order::first()->customer_name);
    }

    public function test_por_separado_solo_se_cobra_el_pedido_de_ese_comensal(): void
    {
        $this->enviarPedido(2, ['label' => 'Persona 1']);
        $this->enviarPedido(2, ['label' => 'Persona 2']);
        $this->marcarListos();
        [$primero, $segundo] = Order::orderBy('id')->get();

        $this->postJson(self::API . '/tables/2/charge', [
            'payments' => [['method' => 'efectivo', 'amount' => 150]],
            'order_ids' => [$primero->id],
        ])->assertCreated()->assertJsonPath('data.status', 'applied');

        $this->assertSame('paid', $primero->fresh()->status);
        $this->assertSame('ready', $segundo->fresh()->status);
        $this->assertSame('ocupada', $this->getJson(self::API . '/tables/2')->json('data.status'));
    }

    public function test_en_linea_no_se_cobra_menos_del_total(): void
    {
        $this->enviarPedido(4);
        $this->marcarListos();

        $this->postJson(self::API . '/tables/4/charge', [
            'payments' => [['method' => 'efectivo', 'amount' => 100]],
        ])->assertStatus(422)->assertJson(['error' => 'insufficient_payment']);

        $this->assertSame(0, TableCharge::count());
    }

    public function test_un_cobro_sin_conexion_se_registra_y_se_marca_para_revision(): void
    {
        $this->enviarPedido(6, ['client_ref' => 'pedido-offline-0002', 'offline' => true]);

        // Sin internet no se sabe si la cocina terminó ni se valida el monto: se aplica igual.
        $cobro = [
            'payments' => [['method' => 'efectivo', 'amount' => 100]],
            'order_refs' => ['pedido-offline-0002'],
            'client_ref' => 'cobro-offline-0001',
            'offline' => true,
            'charged_at' => now()->subMinutes(30)->toISOString(),
        ];
        $this->postJson(self::API . '/tables/6/charge', $cobro)->assertCreated()->assertJsonPath('data.status', 'discrepancy');
        $this->postJson(self::API . '/tables/6/charge', $cobro)->assertOk()->assertJson(['duplicate' => true]);

        $registro = TableCharge::sole();
        $this->assertTrue($registro->offline);
        $this->assertTrue($registro->forced);
        $this->assertSame('paid', Order::first()->status);
        $this->assertSame($registro->id, Order::first()->table_charge_id);
    }

    public function test_un_cobro_sin_conexion_de_una_mesa_ya_cobrada_queda_como_conflicto(): void
    {
        $this->postJson(self::API . '/tables/7/charge', [
            'payments' => [['method' => 'tarjeta', 'amount' => 150]],
            'client_ref' => 'cobro-offline-0002',
            'offline' => true,
        ])->assertCreated()->assertJsonPath('data.status', 'conflict');

        $this->assertSame('conflict', TableCharge::sole()->status);
    }

    public function test_solo_se_quita_la_ultima_mesa_libre_y_nunca_debajo_de_las_base(): void
    {
        $this->deleteJson(self::API . '/tables/13')->assertStatus(422)->assertJson(['error' => 'min_tables']);

        Setting::updateOrCreate(['key' => 'total_mesas'], ['value' => '15']);
        $this->deleteJson(self::API . '/tables/14')->assertStatus(422)->assertJson(['error' => 'only_last_table']);

        $this->enviarPedido(15);
        $this->deleteJson(self::API . '/tables/15')->assertStatus(422)->assertJson(['error' => 'table_busy']);

        Order::query()->update(['status' => 'paid']);
        $this->deleteJson(self::API . '/tables/15')->assertOk()->assertJsonPath('data.total_tables', 14);
        $this->assertCount(14, $this->getJson(self::API . '/tables')->json('data'));
    }

    public function test_un_texto_bloqueado_responde_un_error_que_la_app_puede_leer(): void
    {
        $this->postJson(self::API . '/tables/3/orders', [
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1, 'notes' => 'sin cebolla -- extra queso']],
        ])->assertForbidden()->assertJson(['success' => false, 'error' => 'blocked_input']);

        $this->assertSame(0, Order::count());
    }

    public function test_un_cliente_no_puede_usar_la_api_del_mesero(): void
    {
        Sanctum::actingAs(User::create([
            'name' => 'Cliente',
            'email' => 'cliente@la501.test',
            'password' => 'La501Sports!',
            'role' => 'cliente',
        ]));

        $this->getJson(self::API . '/tables')->assertForbidden();
    }
}
