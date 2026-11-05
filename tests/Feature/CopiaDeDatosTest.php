<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\PromoSubscriber;
use App\Models\Reservation;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * B-13: derecho de Acceso. La app y la web entregan la MISMA copia completa de los datos de la persona, en
 * español y con las fechas en hora de México (-06:00), nunca en UTC con «Z».
 */
class CopiaDeDatosTest extends TestCase
{
    use RefreshDatabase;

    private const EXPORT = '/api/v1/app/account/export';

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ana = User::create([
            'name' => 'Ana Prueba', 'email' => 'ana@la501.test', 'password' => 'La501Sports!',
            'telefono' => '5512345678', 'role' => 'cliente', 'is_active' => 1, 'points' => 120,
            'avatar' => 'avatars/av_ana.jpg',
        ]);
        DB::table('users')->where('id', $this->ana->id)->update([
            'created_at' => '2026-03-01 15:30:00',
            'direccion_favorita' => 'Calle 5 #10, Col. Centro',
            'aviso_privacidad_aceptado_at' => '2026-03-01 15:30:00',
        ]);
        $this->ana = $this->ana->fresh();
    }

    private function pedido(User $dueno, array $datos = [], array $linea = []): Order
    {
        $pedido = Order::create(array_merge([
            'user_id' => $dueno->id, 'customer_name' => $dueno->name, 'customer_phone' => '5512345678',
            'customer_address' => 'Calle 5 #10, Col. Centro', 'customer_email' => $dueno->email,
            'total' => 195, 'status' => 'delivered', 'payment_method' => 'efectivo',
        ], $datos));

        $pedido->items()->create(array_merge([
            'product_name' => 'Hamburguesa Clásica', 'station' => 'cocina', 'quantity' => 1, 'price' => 195, 'subtotal' => 195,
        ], $linea));

        return $pedido;
    }

    /** Todas las cadenas que parecen fecha con hora deben venir con la zona de México. */
    private function assertFechasDeMexico(array $datos): void
    {
        array_walk_recursive($datos, function ($valor, $clave) {
            if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $valor)) {
                $this->assertStringEndsWith('-06:00', $valor, "«{$clave}» debe terminar en -06:00 y no en Z/UTC");
            }
        });
    }

    private function datosCompletos(): void
    {
        $primero = $this->pedido($this->ana, [
            'delivery_lat' => 21.1394, 'delivery_lng' => -98.4197,
            'delivery_terms_accepted_at' => '2026-04-02 12:00:00', 'adult_confirmed_at' => '2026-04-02 12:00:00',
            'health_data_consent_at' => '2026-04-02 12:00:00', 'payment_id' => null,
        ], ['notes' => 'Soy alérgica al cacahuate', 'excluded_ingredients' => ['Cebolla'], 'options' => [['group' => 'Papas', 'name' => 'Gajo', 'quantity' => 1]]]);
        DB::table('orders')->where('id', $primero->id)->update(['created_at' => '2026-04-02 12:00:00']);

        $cancelado = $this->pedido($this->ana, [
            'status' => 'cancelled', 'payment_method' => 'tarjeta', 'payment_id' => '123456789',
            'cancelled_at' => '2026-04-05 20:30:00', 'cancel_reason' => 'Me equivoqué de pedido',
            'refund_status' => 'refunded', 'refund_id' => 'RF-1',
        ]);
        DB::table('orders')->where('id', $cancelado->id)->update(['created_at' => '2026-04-05 20:00:00']);

        Reservation::create([
            'nombre_completo' => 'Ana Prueba', 'telefono' => '5512345678', 'correo_electronico' => $this->ana->email,
            'fecha_reservacion' => '2026-05-10', 'hora_reservacion' => '19:00', 'cantidad_personas' => 4, 'zona' => 'Terraza', 'status' => 'confirmada',
        ]);
        ContactMessage::create([
            'name' => 'Ana Prueba', 'email' => $this->ana->email, 'type' => 'sugerencia',
            'subject' => 'Más salsas', 'message' => 'Me gustaría una salsa más picosa', 'status' => 'pendiente',
        ]);
        PromoSubscriber::create(['email' => $this->ana->email, 'user_id' => $this->ana->id, 'unsubscribe_token' => str_repeat('a', 48)]);

        $logro = Achievement::create(['slug' => 'primer-pedido', 'nombre' => 'Primer pedido', 'descripcion' => 'Hiciste tu primer pedido', 'icono' => 'x', 'categoria' => 'compras', 'requisito' => 1]);
        UserAchievement::create(['user_id' => $this->ana->id, 'achievement_id' => $logro->id, 'unlocked_at' => '2026-04-02 12:05:00']);
    }

    // ── API ──────────────────────────────────────────────────────────────────────────────────

    public function test_la_copia_de_datos_exige_sesion(): void
    {
        $this->getJson(self::EXPORT)->assertUnauthorized();
    }

    public function test_la_copia_trae_todas_las_secciones_del_contrato(): void
    {
        $this->datosCompletos();
        Sanctum::actingAs($this->ana);

        $r = $this->getJson(self::EXPORT)->assertOk()->assertJsonPath('success', true);

        $r->assertJsonStructure(['data' => [
            'documento', 'generado', 'responsable' => ['nombre_comercial', 'titular', 'domicilio', 'correo_de_privacidad'],
            'titular' => ['nombre', 'correo', 'telefono', 'foto_de_perfil', 'direccion_favorita', 'tipo_de_cuenta', 'miembro_desde', 'aviso_de_privacidad_aceptado'],
            'preferencias' => ['promociones_por_correo', 'promociones_por_sms', 'recomendaciones_segun_mis_pedidos', 'ultima_actualizacion'],
            'lealtad' => ['puntos', 'logros'],
            'pedidos', 'reservaciones', 'mensajes_de_contacto', 'lo_que_no_guardamos', 'otros_derechos',
        ]]);
    }

    public function test_el_titular_trae_miembro_desde_y_la_foto_completa(): void
    {
        Sanctum::actingAs($this->ana);

        $titular = $this->getJson(self::EXPORT)->json('data.titular');

        $this->assertSame('Ana Prueba', $titular['nombre']);
        $this->assertSame('ana@la501.test', $titular['correo']);
        $this->assertSame('Cliente', $titular['tipo_de_cuenta']);
        $this->assertSame('Calle 5 #10, Col. Centro', $titular['direccion_favorita']);
        $this->assertSame('2026-03-01T15:30:00-06:00', $titular['miembro_desde']);
        $this->assertNotNull($titular['miembro_desde']);
        $this->assertStringStartsWith('http', $titular['foto_de_perfil']);
        $this->assertStringEndsWith('/avatars/av_ana.jpg', $titular['foto_de_perfil']);
    }

    public function test_incluye_todos_los_pedidos_con_sus_datos_y_estados_en_espanol(): void
    {
        $this->datosCompletos();
        Sanctum::actingAs($this->ana);

        $pedidos = $this->getJson(self::EXPORT)->json('data.pedidos');

        $this->assertCount(2, $pedidos);

        [$entregado, $cancelado] = $pedidos;
        $this->assertSame('Entregado', $entregado['estado'], 'nunca «delivered»');
        $this->assertSame('A domicilio', $entregado['tipo']);
        $this->assertSame('Efectivo al recibir', $entregado['forma_de_pago']);
        $this->assertSame('2026-04-02T12:00:00-06:00', $entregado['fecha']);
        $this->assertSame('Calle 5 #10, Col. Centro', $entregado['datos_de_entrega']['direccion']);
        $this->assertEquals(21.1394, $entregado['datos_de_entrega']['ubicacion_gps']['latitud']);
        $this->assertSame('Soy alérgica al cacahuate', $entregado['productos'][0]['notas']);
        $this->assertSame(['Cebolla'], $entregado['productos'][0]['ingredientes_excluidos']);
        $this->assertSame('Papas: Gajo', $entregado['productos'][0]['opciones']);
        $this->assertSame('2026-04-02T12:00:00-06:00', $entregado['confirmaciones']['mayoria_de_edad_confirmada']);
        $this->assertSame('2026-04-02T12:00:00-06:00', $entregado['confirmaciones']['autorizacion_de_datos_de_salud']);
        $this->assertArrayNotHasKey('cancelacion', $entregado);

        $this->assertSame('Cancelado', $cancelado['estado']);
        $this->assertSame('Tarjeta de crédito o débito (Mercado Pago)', $cancelado['forma_de_pago']);
        $this->assertSame('123456789', $cancelado['numero_de_operacion']);
        $this->assertSame('Me equivoqué de pedido', $cancelado['cancelacion']['motivo']);
        $this->assertSame('Devuelto', $cancelado['cancelacion']['devolucion']);
        $this->assertSame('2026-04-05T20:30:00-06:00', $cancelado['cancelacion']['fecha']);
    }

    public function test_incluye_todos_los_pedidos_no_solo_los_recientes(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->pedido($this->ana);
        }
        Sanctum::actingAs($this->ana);

        $this->assertCount(30, $this->getJson(self::EXPORT)->json('data.pedidos'));
    }

    public function test_incluye_logros_puntos_reservaciones_mensajes_y_preferencias(): void
    {
        $this->datosCompletos();
        Sanctum::actingAs($this->ana);

        $datos = $this->getJson(self::EXPORT)->json('data');

        $this->assertSame(120, $datos['lealtad']['puntos']);
        $this->assertCount(1, $datos['lealtad']['logros'], 'no sale «0 logros» si tiene uno');
        $this->assertSame('Primer pedido', $datos['lealtad']['logros'][0]['nombre']);
        $this->assertSame('2026-04-02T12:05:00-06:00', $datos['lealtad']['logros'][0]['fecha']);

        $this->assertCount(1, $datos['reservaciones']);
        $this->assertSame('Terraza', $datos['reservaciones'][0]['zona']);
        $this->assertSame(4, $datos['reservaciones'][0]['personas']);
        $this->assertSame('2026-05-10', $datos['reservaciones'][0]['fecha']);

        $this->assertCount(1, $datos['mensajes_de_contacto']);
        $this->assertSame('Más salsas', $datos['mensajes_de_contacto'][0]['asunto']);

        $this->assertTrue($datos['preferencias']['promociones_por_correo']);
        $this->assertFalse($datos['preferencias']['promociones_por_sms']);
        $this->assertTrue($datos['preferencias']['recomendaciones_segun_mis_pedidos']);
    }

    public function test_todas_las_fechas_terminan_en_menos_seis_y_ninguna_en_z(): void
    {
        $this->datosCompletos();
        Sanctum::actingAs($this->ana);

        $json = $this->getJson(self::EXPORT);
        $this->assertFechasDeMexico($json->json('data'));
        $this->assertStringNotContainsString('Z"', $json->getContent());
    }

    public function test_no_trae_datos_de_otra_persona(): void
    {
        $this->datosCompletos();
        $luis = User::create(['name' => 'Luis Otro', 'email' => 'luis@la501.test', 'password' => 'La501Sports!', 'telefono' => '7711112222', 'role' => 'cliente', 'is_active' => 1]);
        $this->pedido($luis, ['customer_name' => 'Luis Otro', 'customer_phone' => '7711112222', 'customer_address' => 'Calle Secreta de Luis']);
        Reservation::create([
            'nombre_completo' => 'Luis Otro', 'telefono' => '7711112222', 'correo_electronico' => $luis->email,
            'fecha_reservacion' => '2026-06-01', 'hora_reservacion' => '20:00', 'cantidad_personas' => 2, 'zona' => 'Salón', 'status' => 'pendiente',
        ]);
        Sanctum::actingAs($this->ana);

        $json = $this->getJson(self::EXPORT);

        $this->assertCount(2, $json->json('data.pedidos'));
        $this->assertStringNotContainsString('Luis', $json->getContent());
        $this->assertStringNotContainsString('Calle Secreta de Luis', $json->getContent());
    }

    public function test_la_copia_explica_lo_que_no_se_guarda_y_los_otros_derechos(): void
    {
        Sanctum::actingAs($this->ana);

        $datos = $this->getJson(self::EXPORT)->json('data');

        $this->assertStringContainsString('tarjeta', implode(' ', $datos['lo_que_no_guardamos']));
        $this->assertStringContainsString('contraseña', implode(' ', $datos['lo_que_no_guardamos']));
        $this->assertSame(['Rectificación', 'Cancelación', 'Oposición', 'Más información'], array_keys($datos['otros_derechos']));
        $this->assertStringContainsString('/eliminar-cuenta', $datos['otros_derechos']['Cancelación']);
    }

    // ── Web ──────────────────────────────────────────────────────────────────────────────────

    public function test_la_descarga_web_exige_iniciar_sesion(): void
    {
        $this->get(route('configuracion.mis-datos'))->assertRedirect();
    }

    public function test_la_web_descarga_el_archivo_con_el_nombre_indicado(): void
    {
        $this->datosCompletos();

        $this->actingAs($this->ana)->get(route('configuracion.mis-datos'))
            ->assertOk()
            ->assertDownload('mis-datos-la501-' . now()->format('Y-m-d') . '.json');
    }

    public function test_la_web_y_la_app_entregan_exactamente_la_misma_copia(): void
    {
        $this->datosCompletos();

        $web = json_decode($this->actingAs($this->ana)->get(route('configuracion.mis-datos'))->streamedContent(), true);
        Sanctum::actingAs($this->ana);
        $app = $this->getJson(self::EXPORT)->json('data');

        unset($web['generado'], $app['generado']);
        $this->assertSame($app, $web);
        $this->assertSame('Ana Prueba', $web['titular']['nombre']);
        $this->assertNotEmpty($web['pedidos']);
    }

    public function test_configuracion_ofrece_la_descarga_y_el_enlace_a_eliminar_cuenta(): void
    {
        $this->actingAs($this->ana)->get(route('configuracion'))
            ->assertOk()
            ->assertSee(route('configuracion.mis-datos'), false)
            ->assertSee('Descargar mis datos', false)
            ->assertSee(route('eliminar-cuenta'), false);
    }

    // ── /profile: created_at y stats ─────────────────────────────────────────────────────────

    public function test_el_perfil_trae_created_at_y_los_numeros_reales(): void
    {
        $this->datosCompletos();
        Sanctum::actingAs($this->ana);

        $r = $this->getJson('/api/v1/app/profile')->assertOk();

        $r->assertJsonPath('data.user.created_at', '2026-03-01T15:30:00-06:00');
        $r->assertJsonPath('data.stats.achievements', 1);
        $r->assertJsonPath('data.stats.reservations', 1);
        $r->assertJsonPath('data.stats.orders', 2);
        $this->assertStringEndsWith('/avatars/av_ana.jpg', $r->json('data.user.avatar'));
        $this->assertStringStartsWith('http', $r->json('data.user.avatar'));
    }

    public function test_sin_foto_el_avatar_es_null(): void
    {
        $this->ana->forceFill(['avatar' => null])->save();
        Sanctum::actingAs($this->ana);

        $this->getJson('/api/v1/app/profile')->assertOk()->assertJsonPath('data.user.avatar', null);
        $this->assertNull($this->getJson(self::EXPORT)->json('data.titular.foto_de_perfil'));
    }

    // ── Estados en español ───────────────────────────────────────────────────────────────────

    public function test_los_estados_del_pedido_se_muestran_en_espanol(): void
    {
        $this->assertSame([
            'pending' => 'Pendiente', 'paid' => 'Confirmado', 'failed' => 'Pago rechazado', 'preparing' => 'En preparación',
            'ready' => 'Listo', 'delivering' => 'En camino', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado',
        ], Order::STATUS_LABELS);
        $this->assertSame('Confirmado', Order::statusLabelFor('paid'), 'en efectivo «paid» es confirmado, no «Pagado»');
    }
}
