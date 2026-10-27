<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mapa de mesas: quitar una mesa que se agregó (solo la última, libre y nunca de las 13 fijas)
 * y el aviso de domicilios listos para el mandadito.
 */
class MapaMesasTest extends TestCase
{
    use RefreshDatabase;

    private User $mesero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mesero = User::create(['name' => 'Mesero', 'email' => 'mesero@la501.test', 'password' => 'La501Sports!', 'role' => 'empleado']);
        Setting::updateOrCreate(['key' => 'total_mesas'], ['value' => '15']);
    }

    public function test_se_quita_la_ultima_mesa_libre(): void
    {
        $this->actingAs($this->mesero)->deleteJson(route('mesero.mesas.quitar', 15))
            ->assertOk()
            ->assertJsonPath('total_mesas', 14);

        $this->assertSame('14', Setting::where('key', 'total_mesas')->value('value'));
    }

    public function test_solo_se_puede_quitar_la_ultima(): void
    {
        $this->actingAs($this->mesero)->deleteJson(route('mesero.mesas.quitar', 14))->assertStatus(422);

        $this->assertSame('15', Setting::where('key', 'total_mesas')->value('value'));
    }

    public function test_no_se_quita_una_mesa_con_pedidos_sin_cobrar(): void
    {
        Order::create(['customer_name' => 'Mesa 15', 'customer_phone' => 'Local', 'customer_address' => 'Consumo en sucursal', 'table_number' => 15, 'total' => 100, 'status' => 'pending', 'payment_method' => 'pendiente']);

        $this->actingAs($this->mesero)->deleteJson(route('mesero.mesas.quitar', 15))
            ->assertStatus(422)
            ->assertJsonPath('error', 'La mesa 15 tiene pedidos sin cobrar.');
    }

    public function test_las_mesas_fijas_no_se_quitan(): void
    {
        Setting::updateOrCreate(['key' => 'total_mesas'], ['value' => '13']);

        $this->actingAs($this->mesero)->deleteJson(route('mesero.mesas.quitar', 13))->assertStatus(422);
    }

    public function test_el_mapa_muestra_quitar_solo_en_la_ultima_mesa_agregada(): void
    {
        $this->actingAs($this->mesero)->get(route('mesero.mesas'))
            ->assertOk()
            ->assertSee('Quitar mesa')
            ->assertSee('domicilios listos para enviar', false);
    }

    public function test_el_aviso_cuenta_solo_los_domicilios_listos_del_turno(): void
    {
        $domicilio = fn () => Order::create(['customer_name' => 'Cliente', 'customer_phone' => '4440000000', 'customer_address' => 'Calle 1', 'total' => 200, 'status' => 'ready', 'payment_method' => 'efectivo']);
        $domicilio();
        $viejo = $domicilio();
        Order::whereKey($viejo->id)->update(['updated_at' => now()->subDays(3)]);

        $this->actingAs($this->mesero)->getJson(route('mesero.api_domicilios'))
            ->assertOk()
            ->assertJsonPath('listos_turno', 1)
            ->assertJsonCount(2, 'por_entregar');
    }
}
