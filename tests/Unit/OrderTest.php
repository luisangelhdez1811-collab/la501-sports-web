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

    public function test_genera_el_enlace_de_google_maps_solo_con_ubicacion(): void
    {
        $conGps = new Order(['delivery_lat' => 21.1406, 'delivery_lng' => -98.4198]);
        $sinGps = new Order(['delivery_lat' => null, 'delivery_lng' => null]);

        $this->assertTrue($conGps->hasLocation());
        $this->assertSame('https://www.google.com/maps?q=21.1406,-98.4198', $conGps->mapsUrl());
        $this->assertFalse($sinGps->hasLocation());
        $this->assertNull($sinGps->mapsUrl());
    }

    public function test_genera_un_qr_svg_listo_para_incrustar_en_html(): void
    {
        $svg = (new Order(['delivery_lat' => 21.1406, 'delivery_lng' => -98.4198]))->mapsQrSvg();

        $this->assertNotNull($svg);
        $this->assertStringStartsWith('<svg shape-rendering="crispEdges" ', $svg);
        $this->assertStringNotContainsString('<?xml', $svg);
        $this->assertNull((new Order())->mapsQrSvg());
    }

    public function test_usa_el_correo_del_invitado_para_notificar(): void
    {
        $this->assertSame('cliente@la501.mx', (new Order(['customer_email' => 'cliente@la501.mx']))->correoNotificacion());
        $this->assertNull((new Order(['customer_email' => '']))->correoNotificacion());
    }
}
