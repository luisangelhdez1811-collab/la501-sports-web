<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\StaffToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QR de Maps de los pedidos a domicilio: con GPS abre el punto exacto y, si el cliente no compartió
 * su ubicación (como en los pedidos de la app), busca la dirección escrita con la ciudad del restaurante.
 */
class QrDireccionMapsTest extends TestCase
{
    use RefreshDatabase;

    private function pedido(array $datos = []): Order
    {
        return Order::create(array_merge([
            'customer_name' => 'Ana', 'customer_phone' => '5512345678',
            'customer_address' => 'Av. Juárez 12, Col. Centro',
            'total' => 100, 'status' => 'delivering', 'payment_method' => 'efectivo',
        ], $datos));
    }

    public function test_la_busqueda_agrega_la_ciudad_del_restaurante(): void
    {
        Setting::create(['key' => 'address_line3', 'value' => 'Huejutla de Reyes, Hgo.']);

        $this->assertSame('Av. Juárez 12, Col. Centro, Huejutla de Reyes, Hgo.', $this->pedido()->direccionParaMaps());
        $this->assertSame(
            'Calle 5, Huejutla de Reyes, Hgo.',
            $this->pedido(['customer_address' => 'Calle 5, Huejutla de Reyes, Hgo.'])->direccionParaMaps()
        );
    }

    public function test_la_confirmacion_web_muestra_el_qr_de_la_direccion_sin_gps(): void
    {
        $pedido = $this->pedido(['status' => 'pending']);

        $this->get(route('order.track', $pedido->trackingToken()))
            ->assertOk()
            ->assertSee('<svg shape-rendering="crispEdges"', false)
            ->assertSee('Escanea para buscar tu dirección en Maps');
    }

    public function test_la_confirmacion_web_sigue_usando_el_gps_cuando_lo_hay(): void
    {
        $pedido = $this->pedido(['status' => 'pending', 'delivery_lat' => 21.1406, 'delivery_lng' => -98.4198]);

        $this->get(route('order.track', $pedido->trackingToken()))
            ->assertOk()
            ->assertSee('Escanea para abrir la ubicación en Maps')
            ->assertDontSee('Escanea para buscar tu dirección en Maps');
    }

    public function test_el_ticket_del_mandadito_trae_el_qr_de_la_direccion(): void
    {
        $mesero = User::create(['name' => 'Mesero', 'email' => 'mesero@la501.test', 'password' => 'La501Sports!', 'role' => 'empleado']);
        $pedido = $this->pedido();

        $this->actingAs($mesero)->get(route('mesero.domicilios.ticket', $pedido->id))
            ->assertOk()
            ->assertSee('<svg shape-rendering="crispEdges"', false)
            ->assertSee('Escanea para buscar la dirección en Maps (sin GPS)');
    }

    public function test_la_app_del_personal_recibe_el_enlace_de_la_direccion(): void
    {
        $usuario = User::create(['name' => 'Staff', 'email' => 'admin@la501.test', 'password' => 'La501Sports!', 'role' => 'admin', 'is_active' => 1]);
        $token = StaffToken::create(['user_id' => $usuario->id, 'token' => StaffToken::generateTokenString(), 'expires_at' => now()->addDay()]);
        $pedido = $this->pedido(['status' => 'pending']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token->token])
            ->getJson('/api/v1/staff/orders/' . $pedido->id)
            ->assertOk()
            ->assertJsonPath('data.maps_url', 'https://www.google.com/maps/search/?api=1&query=Av.+Ju%C3%A1rez+12%2C+Col.+Centro')
            ->assertJsonPath('data.maps_from_address', true);
    }

    public function test_un_pedido_de_mesa_no_trae_qr_ni_enlace(): void
    {
        $pedido = $this->pedido(['table_number' => 3, 'customer_address' => Order::DIRECCION_SUCURSAL, 'status' => 'pending']);

        $this->assertNull($pedido->mapsUrl());
        $this->get(route('order.track', $pedido->trackingToken()))
            ->assertOk()
            ->assertDontSee('Escanea para');
    }
}
