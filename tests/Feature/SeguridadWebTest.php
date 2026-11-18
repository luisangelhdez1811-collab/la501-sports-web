<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireRecentPassword;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoSubscriber;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Newsletter;
use App\Services\PasswordResetCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Revisión de seguridad de la web: rutas que estaban abiertas, el dueño del sistema, contraseñas,
 * sesiones, límites de intentos, consentimientos y cabeceras.
 */
class SeguridadWebTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol = 'cliente', array $extra = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'name' => 'Usuario ' . $n, 'email' => "u{$n}@la501.test", 'password' => 'La501Sports!',
            'telefono' => '12345678' . str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'role' => $rol, 'is_active' => 1,
        ], $extra));
    }

    private function duenio(): User
    {
        $user = $this->usuario('admin');
        $user->forceFill(['is_owner' => true])->save();

        return $user;
    }

    // ── Rutas que estaban abiertas a internet ────────────────────────────────────────────────

    public function test_las_rutas_de_mantenimiento_y_de_prueba_ya_no_existen(): void
    {
        foreach (['/link-storage', '/test-errors/500', '/test-errors/403', '/run-delivery-reminders'] as $ruta) {
            $this->get($ruta)->assertNotFound();
        }

        $this->actingAs($this->usuario())->get('/link-storage')->assertNotFound();
    }

    public function test_el_respaldo_automatico_solo_responde_con_la_clave(): void
    {
        config(['app.cron_token' => '']);
        $this->get('/run-auto-backup')->assertNotFound();

        config(['app.cron_token' => str_repeat('a', 40)]);
        $this->get('/run-auto-backup')->assertNotFound();
        $this->get('/run-auto-backup?token=otra-clave')->assertNotFound();
        $this->get('/run-auto-backup?token=' . str_repeat('a', 40))->assertOk();
    }

    // ── El dueño del sistema ya no es «el usuario con id 2» ─────────────────────────────────

    public function test_un_admin_que_no_es_el_duenio_no_entra_a_personal_ni_respaldos(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('admin.database'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->post(route('admin.database.restore'), ['file_path' => 'backups/x.sql'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_el_duenio_confirma_su_contrasena_antes_de_entrar(): void
    {
        $duenio = $this->duenio();

        $this->actingAs($duenio)->get(route('admin.users.index'))->assertRedirect(route('password.confirm'));

        $this->post(route('password.confirm.store'), ['password' => 'equivocada'])->assertSessionHasErrors('password');
        $this->post(route('password.confirm.store'), ['password' => 'La501Sports!'])->assertRedirect(route('admin.users.index'));

        $this->get(route('admin.users.index'))->assertOk();
    }

    public function test_la_marca_de_duenio_no_se_puede_poner_desde_un_formulario(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@la501.test', 'password' => 'La501Sports!', 'role' => 'admin', 'is_owner' => true]);

        $this->assertFalse($user->fresh()->is_owner);
        $this->assertArrayNotHasKey('is_owner', $user->fresh()->toArray());
    }

    // ── Personal ─────────────────────────────────────────────────────────────────────────────

    public function test_el_personal_nuevo_necesita_puesto_valido_y_contrasena_fuerte(): void
    {
        $this->actingAs($this->duenio())->withSession([RequireRecentPassword::SESSION_KEY => time()]);

        $this->post(route('admin.users.store'), ['name' => 'A', 'email' => 'a@la501.test', 'password' => 'Fuerte#2026', 'role' => 'superadmin'])
            ->assertSessionHasErrors('role');
        $this->post(route('admin.users.store'), ['name' => 'A', 'email' => 'a@la501.test', 'password' => '123456', 'role' => 'cocinero'])
            ->assertSessionHasErrors('password');
        $this->post(route('admin.users.store'), ['name' => 'A', 'email' => 'a@la501.test', 'password' => 'Fuerte#2026', 'role' => 'cocinero'])
            ->assertSessionHasNoErrors();

        $this->assertSame('cocinero', User::where('email', 'a@la501.test')->value('role'));
    }

    public function test_el_duenio_no_cambia_la_contrasena_de_un_cliente_ni_se_borra_a_si_mismo(): void
    {
        $duenio = $this->duenio();
        $cliente = $this->usuario();
        $this->actingAs($duenio)->withSession([RequireRecentPassword::SESSION_KEY => time()]);

        $this->put(route('admin.users.update', $cliente->id), ['password' => 'Fuerte#2026'])->assertNotFound();
        $this->delete(route('admin.users.destroy', $duenio->id))->assertSessionHas('error');
        $this->assertNotNull($duenio->fresh());
    }

    public function test_suspender_a_alguien_cierra_sus_sesiones_y_lo_saca_de_la_web(): void
    {
        $mesero = $this->usuario('empleado');
        $mesero->createToken('app');

        $this->actingAs($this->duenio())->withSession([RequireRecentPassword::SESSION_KEY => time()])
            ->put(route('admin.users.toggle', $mesero->id))->assertSessionHas('success');

        $this->assertFalse((bool) $mesero->fresh()->is_active);
        $this->assertSame(0, $mesero->tokens()->count());

        // Aunque tuviera la sesión abierta, en su siguiente clic sale
        $this->actingAs($mesero->fresh())->get(route('mesero.mesas'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_las_pantallas_del_personal_revisan_el_rol_en_la_ruta(): void
    {
        $cliente = $this->usuario();

        $this->actingAs($cliente)->get(route('mesero.mesas'))->assertForbidden();
        $this->actingAs($cliente)->get(route('kitchen.index'))->assertForbidden();
        $this->actingAs($cliente)->getJson(route('kitchen.api_orders'))->assertForbidden();
    }

    // ── Inicio de sesión y contraseñas ───────────────────────────────────────────────────────

    public function test_cinco_contrasenas_equivocadas_bloquean_la_cuenta_aunque_cambie_la_ip(): void
    {
        $user = $this->usuario();

        foreach (range(1, 5) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->post('/login', ['email' => $user->email, 'password' => 'Mala#' . $i]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post('/login', ['email' => $user->email, 'password' => 'La501Sports!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_codigo_por_correo_se_anula_al_quinto_intento_fallido(): void
    {
        $user = $this->usuario();
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => Hash::make('12345678'), 'created_at' => now()]);

        foreach (range(1, 5) as $i) {
            $this->assertFalse(PasswordResetCodes::check($user->email, '0000000' . $i));
        }

        // Ya ni el código correcto sirve: hay que pedir otro
        $this->assertFalse(PasswordResetCodes::check($user->email, '12345678'));
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_restablecer_la_contrasena_cierra_las_sesiones_de_la_app(): void
    {
        $user = $this->usuario();
        $user->createToken('app');

        $this->withSession(['allow_password_reset' => $user->email])
            ->post(route('password.update'), ['password' => 'Nueva#2026x', 'password_confirmation' => 'Nueva#2026x'])
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Nueva#2026x', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_cambiar_la_contrasena_pide_una_fuerte_y_distinta(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)->post(route('configuracion.password'), [
            'current_password' => 'La501Sports!', 'password' => 'debil', 'password_confirmation' => 'debil',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->post(route('configuracion.password'), [
            'current_password' => 'La501Sports!', 'password' => 'La501Sports!', 'password_confirmation' => 'La501Sports!',
        ])->assertSessionHasErrors('password');
    }

    // ── Formularios públicos ─────────────────────────────────────────────────────────────────

    private function reservacion(array $extra = []): array
    {
        return array_merge([
            'nombre_completo' => 'Ana López', 'telefono' => '1234567890', 'correo_electronico' => 'ana@la501.test',
            'fecha_reservacion' => now()->addDays(2)->toDateString(), 'hora_reservacion' => '15:00',
            'cantidad_personas' => 4, 'zona' => 'General', 'acepta_privacidad' => '1',
        ], $extra);
    }

    public function test_una_reservacion_no_puede_llegar_ya_confirmada(): void
    {
        $this->post(route('reservations.store'), $this->reservacion(['status' => 'confirmada']))->assertSessionHasNoErrors();

        $reserva = Reservation::first();
        $this->assertSame('pendiente', $reserva->status);
        $this->assertNotNull($reserva->privacy_accepted_at);
        $this->assertNotNull($reserva->privacy_version);
    }

    public function test_reservar_y_escribir_piden_aceptar_el_aviso_de_privacidad(): void
    {
        $this->post(route('reservations.store'), $this->reservacion(['acepta_privacidad' => null]))->assertSessionHasErrors('acepta_privacidad');

        $mensaje = ['name' => 'Ana', 'email' => 'ana@la501.test', 'type' => 'pregunta', 'subject' => 'Horario', 'message' => '¿Abren el lunes?'];
        $this->post(route('contacto.enviar'), $mensaje)->assertSessionHasErrors('acepta_privacidad');
        $this->post(route('contacto.enviar'), $mensaje + ['acepta_privacidad' => '1'])->assertSessionHasNoErrors();

        $this->assertNotNull(DB::table('contact_messages')->value('privacy_accepted_at'));
    }

    public function test_los_formularios_publicos_tienen_limite_de_envios(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post(route('newsletter.subscribe'), ['email' => "n{$i}@la501.test"])->assertRedirect();
        }

        $this->post(route('newsletter.subscribe'), ['email' => 'n6@la501.test'])->assertStatus(429);
    }

    // ── Boletín con doble confirmación ───────────────────────────────────────────────────────

    public function test_sin_sesion_el_boletin_se_confirma_por_correo(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'Fan@La501.test'])
            ->assertSessionHas('success', Newsletter::message(Newsletter::PENDING));

        $suscriptor = PromoSubscriber::firstOrFail();
        $this->assertSame('fan@la501.test', $suscriptor->email);
        $this->assertFalse($suscriptor->isConfirmed());
        $this->assertSame(0, PromoSubscriber::confirmed()->count());

        $this->get(route('newsletter.confirm', $suscriptor->confirm_token))->assertOk()->assertSee('Suscripción confirmada');
        $this->assertTrue($suscriptor->fresh()->isConfirmed());
        $this->get(route('newsletter.confirm', 'token-que-no-existe'))->assertSee('ya no es válido');
    }

    public function test_con_sesion_y_su_propio_correo_queda_confirmado(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)->post(route('newsletter.subscribe'), ['email' => $user->email])
            ->assertSessionHas('success', Newsletter::message(Newsletter::CONFIRMED));

        $this->assertTrue(PromoSubscriber::firstOrFail()->isConfirmed());
    }

    // ── Pago ─────────────────────────────────────────────────────────────────────────────────

    public function test_el_pago_web_pide_aceptar_los_terminos_de_compra_y_guarda_la_evidencia(): void
    {
        config(['services.mercadopago.access_token' => 'TEST-token']);
        $producto = Product::create(['name' => 'Tacos', 'price' => 90, 'category' => 'Comida', 'available' => 1]);
        $carrito = [$producto->id => ['name' => 'Tacos', 'price' => 90, 'quantity' => 1, 'product_id' => $producto->id]];
        $datos = ['customer_name' => 'Ana', 'customer_phone' => '1234567890', 'customer_address' => 'Calle 1', 'payment_method' => 'efectivo', 'delivery_terms_accepted' => '1'];

        $this->withSession(['cart' => $carrito])->post(route('checkout.process'), $datos)->assertSessionHasErrors('purchase_terms_accepted');
        $this->assertSame(0, Order::count());

        $this->withSession(['cart' => $carrito])->post(route('checkout.process'), $datos + ['purchase_terms_accepted' => '1']);
        $this->assertNotNull(Order::firstOrFail()->purchase_terms_accepted_at);
    }

    public function test_el_formulario_de_pago_puede_ir_a_mercado_pago(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('form-action', $csp);
        $this->assertStringContainsString('mercadopago.com', $csp);
    }

    // ── Cabeceras, archivos y datos expuestos ────────────────────────────────────────────────

    public function test_las_paginas_con_sesion_no_se_guardan_en_cache(): void
    {
        $respuesta = $this->actingAs($this->usuario())->get(route('perfil'))->assertOk();

        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
    }

    public function test_un_ataque_obvio_se_frena_con_403(): void
    {
        $this->post(route('contacto.enviar'), ['name' => '<script>alert(1)</script>'])
            ->assertForbidden()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_el_logo_no_acepta_svg(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs($this->usuario('admin'))->post(route('admin.settings.logo'), ['logo' => $svg])->assertSessionHasErrors('logo');
    }

    public function test_la_carta_publica_no_expone_columnas_internas(): void
    {
        Product::create(['name' => 'Tacos', 'price' => 90, 'category' => 'Comida', 'available' => 1, 'station' => 'cocina', 'image_source' => 'Foto propia']);

        $producto = $this->getJson('/api/menu/products')->assertOk()->json(0);

        $this->assertSame('Tacos', $producto['name']);
        $this->assertArrayNotHasKey('station', $producto);
        $this->assertArrayNotHasKey('image_source', $producto);
        $this->assertArrayNotHasKey('created_at', $producto);
    }

    public function test_los_datos_secretos_de_la_cuenta_no_salen_en_json(): void
    {
        $user = $this->usuario('cliente', ['respuesta_secreta' => 'Firulais']);

        $this->assertArrayNotHasKey('respuesta_secreta', $user->toArray());
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    // ── Límites de tamaño e inyección ────────────────────────────────────────────────────────

    public function test_los_textos_tienen_limite_aunque_quiten_el_maxlength(): void
    {
        $this->postJson('/api/v1/app/newsletter/subscribe', ['email' => str_repeat('a', 300) . '@la501.test'])
            ->assertStatus(422)->assertJsonPath('error', 'input_too_large');

        $this->post(route('contacto.enviar'), ['name' => 'Ana', 'message' => str_repeat('hola ', 700)])
            ->assertSessionHasErrors('message');

        // Cualquier campo, aunque no tenga límite propio, se corta en 5000 caracteres
        $this->postJson('/api/v1/app/newsletter/subscribe', ['email' => 'a@la501.test', 'otro' => str_repeat('x', 5001)])
            ->assertStatus(422);
    }

    public function test_notas_de_pedido_largas_se_rechazan_en_la_app(): void
    {
        $this->postJson('/api/v1/app/checkout', [
            'items' => [['product_id' => 1, 'quantity' => 1, 'notes' => str_repeat('sin cebolla ', 20)]],
        ])->assertStatus(422);
    }

    public function test_listas_enormes_y_el_caracter_nulo_se_rechazan(): void
    {
        $this->postJson('/api/v1/app/checkout', ['items' => array_fill(0, 300, ['product_id' => 1, 'quantity' => 1])])
            ->assertStatus(422)->assertJsonPath('error', 'input_too_large');

        $this->postJson('/api/v1/app/newsletter/subscribe', ['email' => "a@la501.test\0.evil"])
            ->assertStatus(422);
    }

    public function test_una_contrasena_de_mas_de_64_caracteres_no_se_acepta_al_registrarse(): void
    {
        $larga = 'Aa1!' . str_repeat('x', 70);

        $this->post('/registro', [
            'name' => 'Ana', 'email' => 'ana@la501.test', 'telefono' => '1234567890',
            'password' => $larga, 'password_confirmation' => $larga, 'mayor_de_edad' => '1', 'terms' => '1',
        ])->assertSessionHasErrors('password');
    }

    public function test_la_inyeccion_sql_en_el_inicio_de_sesion_no_entra_ni_truena(): void
    {
        $this->usuario('admin', ['email' => 'admin@la501.test']);

        foreach (["admin@la501.test' OR 1=1 --", "' UNION SELECT password FROM users --", 'admin@la501.test"; DROP TABLE users; --'] as $correo) {
            $estado = $this->post('/login', ['email' => $correo, 'password' => 'x'])->getStatusCode();
            $this->assertLessThan(500, $estado);
            $this->assertGuest();
        }

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('users'));
    }

    public function test_el_codigo_para_el_navegador_se_frena_o_se_muestra_como_texto(): void
    {
        foreach (['<img src=x onerror=alert(1)>', '<svg/onload=alert(1)>', 'javascript:alert(1)', '<iframe src=//evil>'] as $ataque) {
            $this->post(route('contacto.enviar'), ['name' => $ataque])->assertForbidden();
        }

        // Lo que no parece ataque se guarda, pero al mostrarse sale como texto, nunca como HTML
        $this->post(route('reservations.store'), [
            'nombre_completo' => 'Ana <b>Negritas</b>', 'telefono' => '1234567890', 'correo_electronico' => 'ana@la501.test',
            'fecha_reservacion' => now()->addDays(2)->toDateString(), 'hora_reservacion' => '15:00',
            'cantidad_personas' => 2, 'zona' => 'General', 'acepta_privacidad' => '1',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->usuario('admin'))->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee('Ana &lt;b&gt;Negritas&lt;/b&gt;', false)
            ->assertDontSee('<b>Negritas</b>', false);
    }

    public function test_el_telefono_se_guarda_en_10_digitos_aunque_lo_escriban_con_formato(): void
    {
        $this->post(route('reservations.store'), [
            'nombre_completo' => 'Ana', 'telefono' => '+52 (123) 456-7890', 'correo_electronico' => 'ana@la501.test',
            'fecha_reservacion' => now()->addDays(2)->toDateString(), 'hora_reservacion' => '15:00',
            'cantidad_personas' => 2, 'zona' => 'General', 'acepta_privacidad' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('1234567890', Reservation::first()->telefono);
    }

    public function test_el_mapa_solo_acepta_un_mapa_de_google(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->post(route('admin.settings.map'), ['map_url' => 'https://evil.example/phishing'])
            ->assertSessionHasErrors('map_url');
        $this->actingAs($admin)->post(route('admin.settings.map'), ['map_url' => 'https://www.google.com/maps/embed?pb=!1m18'])
            ->assertSessionHasNoErrors();
    }

    // ── Cookies y mapa ───────────────────────────────────────────────────────────────────────

    public function test_el_aviso_de_cookies_sale_y_el_mapa_espera_permiso(): void
    {
        $this->get(route('ubicacion'))
            ->assertOk()
            ->assertSee('cookies técnicas')
            ->assertSee('Mostrar mapa')
            ->assertSee('<template x-if="mapa">', false);
    }
}
