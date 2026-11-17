<?php

namespace Tests\Unit;

use App\Models\Order;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_un_pedido_sin_mesa_es_a_domicilio(): void
    {
        $this->assertTrue((new Order(['table_number' => null]))->esADomicilio());
        $this->assertFalse((new Order(['table_number' => 5]))->esADomicilio());
    }

    public function test_genera_el_enlace_de_google_maps_con_la_ubicacion_gps(): void
    {
        $conGps = new Order(['delivery_lat' => 21.1406, 'delivery_lng' => -98.4198, 'customer_address' => 'Calle 1']);
        $sinNada = new Order(['delivery_lat' => null, 'delivery_lng' => null]);

        $this->assertTrue($conGps->hasLocation());
        $this->assertSame('https://www.google.com/maps?q=21.1406,-98.4198', $conGps->mapsUrl());
        $this->assertFalse($conGps->mapsUsaDireccion());
        $this->assertFalse($sinNada->hasLocation());
        $this->assertNull($sinNada->mapsUrl());
    }

    public function test_sin_gps_el_enlace_busca_la_direccion_escrita(): void
    {
        $pedido = new Order(['customer_address' => '  Av. Juárez 12,   Col. Centro ']);

        $this->assertFalse($pedido->hasLocation());
        $this->assertTrue($pedido->mapsUsaDireccion());
        $this->assertSame(
            'https://www.google.com/maps/search/?api=1&query=Av.+Ju%C3%A1rez+12%2C+Col.+Centro',
            $pedido->mapsUrl()
        );
    }

    public function test_no_busca_en_maps_los_pedidos_de_mesa_ni_de_sucursal(): void
    {
        $deMesa = new Order(['table_number' => 4, 'customer_address' => 'Calle 1']);
        $enSucursal = new Order(['customer_address' => Order::DIRECCION_SUCURSAL]);
        $sinDireccion = new Order(['customer_address' => '   ']);

        foreach ([$deMesa, $enSucursal, $sinDireccion] as $pedido) {
            $this->assertNull($pedido->mapsUrl());
            $this->assertFalse($pedido->mapsUsaDireccion());
            $this->assertNull($pedido->mapsQrSvg());
        }
    }

    public function test_recorta_las_direcciones_muy_largas_para_que_el_qr_se_pueda_leer(): void
    {
        $pedido = new Order(['customer_address' => str_repeat('Calle larga ', 60)]);

        $this->assertLessThanOrEqual(150, mb_strlen($pedido->direccionParaMaps()));
        $this->assertNotNull($pedido->mapsQrSvg());
    }

    public function test_genera_un_qr_svg_listo_para_incrustar_en_html(): void
    {
        $svg = (new Order(['delivery_lat' => 21.1406, 'delivery_lng' => -98.4198]))->mapsQrSvg();

        $this->assertNotNull($svg);
        $this->assertStringStartsWith('<svg shape-rendering="crispEdges" ', $svg);
        $this->assertStringNotContainsString('<?xml', $svg);
        $this->assertNotNull((new Order(['customer_address' => 'Calle 1']))->mapsQrSvg());
        $this->assertNull((new Order())->mapsQrSvg());
    }

    public function test_usa_el_correo_del_invitado_para_notificar(): void
    {
        $this->assertSame('cliente@la501.mx', (new Order(['customer_email' => 'cliente@la501.mx']))->correoNotificacion());
        $this->assertNull((new Order(['customer_email' => '']))->correoNotificacion());
    }
}
