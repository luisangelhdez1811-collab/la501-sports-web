<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PromoSubscriber;
use App\Models\Reservation;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cumplimiento: huecos de seguridad y derecho de Cancelación (B-04, B-05 por HTTP, B-07, B-08).
 */
class CumplimientoSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol = 'cliente', array $extra = []): User
    {
        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        return User::create(array_merge([
            'name' => 'Ana Prueba',
            'email' => $rol . '@la501.test',
            'password' => 'La501Sports!',
            'telefono' => '5512345678',
            'role' => $rol,
        ], $extra));
    }

    // ── B-07: /clear-laravel-cache ───────────────────────────────────────────────────────────

    public function test_la_limpieza_de_cache_no_esta_abierta_a_internet(): void
    {
        $this->get('/clear-laravel-cache')->assertRedirect(route('login'));
    }

    public function test_un_cliente_no_puede_limpiar_la_cache(): void
    {
        $this->actingAs($this->usuario('cliente'))
            ->get('/clear-laravel-cache')
            ->assertRedirect('/');
    }

    public function test_un_administrador_si_puede_limpiar_la_cache(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->get('/clear-laravel-cache')
            ->assertOk()
            ->assertSee('Caché de Laravel limpia');
    }

    // ── B-08: el teléfono solo cambia con código SMS ─────────────────────────────────────────

    public function test_el_perfil_web_ignora_un_telefono_enviado_a_mano(): void
    {
        $cliente = $this->usuario('cliente');

        $this->actingAs($cliente)
            ->post(route('perfil.update'), ['telefono' => '7710000000'])
            ->assertRedirect();

        $this->assertSame('5512345678', $cliente->fresh()->telefono);
    }

    public function test_el_perfil_muestra_el_telefono_de_solo_lectura_con_enlace_a_configuracion(): void
    {
        $cliente = $this->usuario('cliente');

        $this->actingAs($cliente)
            ->get(route('perfil'))
            ->assertOk()
            ->assertSee('id="telefono-readonly"', false)
            ->assertDontSee('name="telefono"', false)
            ->assertSee(route('configuracion'), false);
    }

    // ── B-05 por HTTP: texto normal no se bloquea ────────────────────────────────────────────

    public function test_la_colonia_la_union_no_es_bloqueada_por_el_filtro(): void
    {
        $respuesta = $this->postJson('/carrito/agregar', [
            'id' => 1,
            'name' => 'Selecciona la salsa',
            'price' => 100,
            'quantity' => 1,
            'notes' => 'Col. La Unión',
        ]);

        // Cualquier cosa menos el bloqueo del filtro (403 blocked_input)
        $this->assertNotSame('blocked_input', $respuesta->json('error'));
        $this->assertNotSame(403, $respuesta->status());
    }

    public function test_un_ataque_si_es_bloqueado_por_el_filtro(): void
    {
        $this->postJson('/carrito/agregar', [
            'id' => 1,
            'name' => 'x',
            'price' => 100,
            'quantity' => 1,
            'notes' => '1 UNION SELECT password FROM users',
        ])->assertStatus(403)->assertJsonPath('error', 'blocked_input');
    }

    // ── B-04: eliminar una cuenta con pedidos ────────────────────────────────────────────────

    public function test_eliminar_una_cuenta_con_pedidos_no_falla_y_no_deja_datos_personales(): void
    {
        $correo = 'clienta@la501.test';
        $cliente = $this->usuario('cliente', [
            'email' => $correo,
            'points' => 834,
            'direccion_favorita' => 'Calle Secreta 123',
            'avatar' => 'avatars/prueba_cumplimiento.jpg',
        ]);

        $rutaFoto = public_path('avatars/prueba_cumplimiento.jpg');
        @mkdir(dirname($rutaFoto), 0777, true);
        file_put_contents($rutaFoto, 'foto');

        $pedidos = [];
        foreach ([1, 2] as $n) {
            $pedido = Order::create([
                'user_id' => $cliente->id,
                'customer_name' => 'Ana Prueba',
                'customer_phone' => '5512345678',
                'customer_address' => 'Calle Secreta 123',
                'customer_email' => $correo,
                'delivery_lat' => 21.1406,
                'delivery_lng' => -98.4198,
                'total' => 100 * $n,
                'status' => 'delivered',
            ]);
            OrderItem::create([
                'order_id' => $pedido->id,
                'product_name' => 'Alitas',
                'quantity' => 1,
                'price' => 100 * $n,
                'subtotal' => 100 * $n,
                'notes' => 'Soy alérgica al cacahuate',
            ]);
            $pedidos[] = $pedido->id;
        }

        $reservacion = Reservation::create([
            'nombre_completo' => 'Ana Prueba',
            'telefono' => '5512345678',
            'correo_electronico' => $correo,
            'fecha_reservacion' => now()->addDay()->toDateString(),
            'hora_reservacion' => '20:00:00',
            'cantidad_personas' => 4,
            'zona' => 'Terraza',
            'status' => 'pendiente',
        ]);
        $mensaje = ContactMessage::create([
            'name' => 'Ana Prueba', 'email' => $correo, 'type' => 'queja',
            'subject' => 'Mi pedido', 'message' => 'Me llamo Ana y vivo en Calle Secreta 123', 'status' => 'pendiente',
        ]);
        PromoSubscriber::create(['email' => $correo, 'user_id' => $cliente->id, 'unsubscribe_token' => str_repeat('a', 64)]);
        $logro = Achievement::create(['slug' => 'prueba', 'nombre' => 'Prueba', 'descripcion' => 'x', 'icono' => 'x', 'categoria' => 'compras', 'requisito' => 1]);
        UserAchievement::create(['user_id' => $cliente->id, 'achievement_id' => $logro->id, 'unlocked_at' => now()]);
        DB::table('sessions')->insert(['id' => 'sesion-de-prueba', 'user_id' => $cliente->id, 'payload' => 'x', 'last_activity' => time()]);

        (new AccountDeletionService())->delete($cliente);

        // Pedidos: se conservan montos y fechas, sin datos que identifiquen a la persona
        foreach ($pedidos as $id) {
            $pedido = Order::find($id);
            $this->assertNotNull($pedido, 'el pedido se conserva por obligación fiscal');
            $this->assertSame('Cliente eliminado', $pedido->customer_name);
            $this->assertSame('', $pedido->customer_phone);
            $this->assertNull($pedido->customer_address);
            $this->assertNull($pedido->customer_email);
            $this->assertNull($pedido->delivery_lat);
            $this->assertNull($pedido->delivery_lng);
            $this->assertGreaterThan(0, (float) $pedido->total);
            $this->assertNull(OrderItem::where('order_id', $id)->first()->notes, 'las notas pueden ser datos de salud');
        }

        // La cuenta queda anonimizada e inactiva
        $usuario = $cliente->fresh();
        $this->assertSame('Usuario eliminado', $usuario->name);
        $this->assertStringEndsWith('@la501.invalid', $usuario->email);
        $this->assertNull($usuario->telefono);
        $this->assertNull($usuario->avatar);
        $this->assertNull($usuario->direccion_favorita);
        $this->assertSame(0, (int) $usuario->points);
        $this->assertEquals(0, $usuario->is_active);

        // Reservaciones y mensajes de contacto anonimizados
        $this->assertSame('Cliente eliminado', $reservacion->fresh()->nombre_completo);
        $this->assertStringEndsWith('@la501.invalid', $reservacion->fresh()->correo_electronico);
        $this->assertSame('Cliente eliminado', $mensaje->fresh()->name);
        $this->assertStringNotContainsString('Calle Secreta', $mensaje->fresh()->message);

        // Suscripción, logros, sesiones y foto: borrados
        $this->assertFalse(PromoSubscriber::where('email', $correo)->exists());
        $this->assertSame(0, UserAchievement::where('user_id', $cliente->id)->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $cliente->id)->count());
        $this->assertFileDoesNotExist($rutaFoto);
    }

    public function test_eliminar_una_cuenta_sin_pedidos_tambien_funciona(): void
    {
        $cliente = $this->usuario('cliente');

        (new AccountDeletionService())->delete($cliente);

        $this->assertSame('Usuario eliminado', $cliente->fresh()->name);
        $this->assertEquals(0, $cliente->fresh()->is_active);
    }
}
