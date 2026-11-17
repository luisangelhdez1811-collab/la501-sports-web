<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\JwtService;
use App\Services\LegalDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Textos legales versionados (B-18, B-30, B-32) y aviso para personal (B-39). Aquí se usa be() en lugar
 * de actingAs() para que la cuenta NO venga al día con los textos.
 */
class TextosLegalesTest extends TestCase
{
    use RefreshDatabase;

    private function cuenta(string $rol = 'cliente', string $correo = 'ana@la501.test'): User
    {
        return User::create(['name' => 'Ana', 'email' => $correo, 'password' => 'La501Sports!', 'telefono' => '1234567890', 'role' => $rol, 'is_active' => 1]);
    }

    public function test_las_paginas_muestran_la_version_y_la_fecha(): void
    {
        $this->get(route('aviso-privacidad'))->assertOk()
            ->assertSee('Versión ' . config('legal.privacy_version'))
            ->assertSee('id="cookies"', false)
            ->assertSee('Aviso de Privacidad Simplificado')
            ->assertSee('20 días hábiles')
            ->assertSee('15 días hábiles');

        $this->get(route('terminos-condiciones'))->assertOk()->assertSee('Versión ' . config('legal.terms_version'));
        $this->get(route('personal.aviso-privacidad'))->assertOk()->assertSee('Aviso de Privacidad para Personal');
    }

    public function test_una_cuenta_sin_la_version_vigente_debe_aceptarla_antes_de_seguir(): void
    {
        $this->be($cliente = $this->cuenta());

        $this->get(route('configuracion'))->assertRedirect(route('legal.accept'));
        $this->get(route('legal.accept'))->assertOk()->assertSee('Aceptar y continuar');

        // Puede leer los textos y salir sin aceptar
        $this->get(route('aviso-privacidad'))->assertOk();

        $this->post(route('legal.accept.store'))->assertSessionHasErrors('acepto');
        $this->post(route('legal.accept.store'), ['acepto' => '1'])->assertRedirect(route('configuracion'));

        $cliente->refresh();
        $this->assertSame(config('legal.privacy_version'), $cliente->privacy_version_accepted);
        $this->assertSame(config('legal.terms_version'), $cliente->terms_version_accepted);
        $this->assertNotNull($cliente->legal_accepted_at);
        $this->get(route('configuracion'))->assertOk();
    }

    public function test_el_registro_guarda_las_versiones_aceptadas(): void
    {
        $this->post('/registro', [
            'name' => 'Ana Prueba', 'email' => 'nueva@la501.test', 'telefono' => '1234567890',
            'password' => 'La501Sports!', 'password_confirmation' => 'La501Sports!',
            'mayor_de_edad' => '1', 'terms' => '1',
        ]);

        $usuario = User::where('email', 'nueva@la501.test')->firstOrFail();
        $this->assertSame([], LegalDocuments::pendingFor($usuario));
    }

    public function test_el_personal_acepta_su_propio_aviso(): void
    {
        $this->be($mesero = $this->cuenta('empleado', 'mesero@la501.test'));

        $this->get(route('mesero.mesas'))->assertRedirect(route('legal.accept'));
        $this->get(route('legal.accept'))->assertSee('Aviso de Privacidad para Personal');

        $this->post(route('legal.accept.store'), ['acepto' => '1']);
        $this->assertSame(config('legal.staff_privacy_version'), $mesero->fresh()->staff_privacy_version_accepted);
        $this->get(route('mesero.mesas'))->assertOk();
    }

    public function test_la_app_pide_aceptar_antes_de_pedir_y_guarda_la_version_en_el_pedido(): void
    {
        $cliente = $this->cuenta();
        $token = app(JwtService::class)->issue($cliente, request())['access_token'];
        $hamburguesa = Product::create(['name' => 'Hamburguesa', 'price' => 100, 'category' => 'Hamburguesas', 'available' => 1, 'station' => 'cocina']);
        $pedido = [
            'items' => [['product_id' => $hamburguesa->id, 'quantity' => 1]],
            'customer_name' => 'Ana', 'customer_phone' => '1234567890', 'customer_address' => 'Calle 1',
            'payment_method' => 'efectivo', 'delivery_terms_accepted' => true,
        ];
        $conToken = ['Authorization' => 'Bearer ' . $token];

        $this->getJson('/api/v1/app/profile', $conToken)->assertJsonPath('data.legal_update_required', true);
        $this->postJson('/api/v1/app/checkout', $pedido, $conToken)->assertStatus(409)->assertJsonPath('error', 'legal_update_required');

        // Una app con el texto viejo no puede registrar como aceptado lo que no mostró
        $this->postJson('/api/v1/app/account/accept-legal', ['privacy_version' => '2026-11', 'terms_version' => '2026-11'], $conToken)
            ->assertStatus(409)->assertJsonPath('error', 'legal_version_mismatch');

        $this->postJson('/api/v1/app/account/accept-legal', [
            'privacy_version' => config('legal.privacy_version'), 'terms_version' => config('legal.terms_version'),
        ], $conToken)->assertOk();

        $this->postJson('/api/v1/app/checkout', $pedido, $conToken)->assertCreated();
        $this->assertSame(config('legal.terms_version'), Order::latest('id')->value('terms_version_accepted'));
    }

    public function test_la_app_puede_leer_el_texto_vigente(): void
    {
        $this->getJson('/api/v1/public/legal/privacy')
            ->assertOk()
            ->assertJsonPath('data.version', config('legal.privacy_version'))
            ->assertJsonPath('data.blocks.0.type', 'h2');

        $this->getJson('/api/v1/public/legal/staff')->assertNotFound();
    }

    public function test_el_cliente_desactiva_las_recomendaciones_desde_la_web(): void
    {
        $cliente = $this->cuenta();
        $this->actingAs($cliente)->post(route('configuracion.recomendaciones'))->assertRedirect();

        $this->assertFalse((bool) $cliente->fresh()->recomendaciones_personalizadas);
        $this->assertNotNull($cliente->fresh()->preferencias_privacidad_at);
    }
}
