<?php

namespace Tests\Feature;

use App\Models\PromoSubscriber;
use App\Models\User;
use App\Services\MlEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * Derechos ARCO en la API de la app: Acceso y Rectificación en /profile, Cancelación en
 * DELETE /account y Oposición con las preferencias de privacidad.
 */
class ArcoPrivacidadTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app';

    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        $this->cliente = User::create([
            'name' => 'Cliente de prueba',
            'email' => 'cliente@la501.test',
            'password' => 'La501Sports!',
            'telefono' => '5512345678',
            'role' => 'cliente',
        ]);

        Sanctum::actingAs($this->cliente);
    }

    public function test_acceso_get_profile_devuelve_datos_y_preferencias_por_defecto(): void
    {
        $this->getJson(self::API . '/profile')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'cliente@la501.test')
            ->assertJsonPath('data.privacy.promos_correo', false)
            ->assertJsonPath('data.privacy.promos_sms', false)
            ->assertJsonPath('data.privacy.recomendaciones_personalizadas', true);
    }

    public function test_rectificacion_put_profile_corrige_el_nombre(): void
    {
        $this->putJson(self::API . '/profile', [
            'name' => 'Cliente Corregido',
            'email' => 'cliente@la501.test',
            'telefono' => '5512345678',
        ])->assertOk();

        $this->assertSame('Cliente Corregido', $this->cliente->fresh()->name);
    }

    public function test_oposicion_apaga_y_enciende_cada_preferencia_con_fecha(): void
    {
        $this->putJson(self::API . '/profile/privacy', [
            'recomendaciones_personalizadas' => false,
            'promos_sms' => true,
            'promos_correo' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.recomendaciones_personalizadas', false)
            ->assertJsonPath('data.promos_sms', true)
            ->assertJsonPath('data.promos_correo', true);

        $usuario = $this->cliente->fresh();
        $this->assertFalse($usuario->recomendaciones_personalizadas);
        $this->assertTrue($usuario->promos_sms);
        $this->assertNotNull($usuario->preferencias_privacidad_at);
        $this->assertTrue(PromoSubscriber::where('user_id', $usuario->id)->exists());

        $this->putJson(self::API . '/profile/privacy', ['promos_correo' => false])
            ->assertOk()
            ->assertJsonPath('data.promos_correo', false)
            // Lo que no se manda no cambia.
            ->assertJsonPath('data.promos_sms', true);
        $this->assertFalse(PromoSubscriber::where('user_id', $usuario->id)->exists());
    }

    public function test_oposicion_rechaza_peticiones_vacias_o_invalidas(): void
    {
        $this->putJson(self::API . '/profile/privacy', [])->assertStatus(422);
        $this->putJson(self::API . '/profile/privacy', ['promos_sms' => 'quizas'])->assertStatus(422);
    }

    public function test_recomendaciones_usan_el_historial_solo_si_el_cliente_lo_permite(): void
    {
        $recibido = [];
        $this->instance(MlEngineService::class, Mockery::mock(MlEngineService::class, function ($mock) use (&$recibido) {
            $mock->shouldReceive('getRecommendationsForCustomer')
                ->andReturnUsing(function ($usuario) use (&$recibido) {
                    $recibido[] = $usuario?->id;
                    return ['strategy' => 'popular', 'title' => 'Populares', 'products' => []];
                });
        }));

        $this->getJson(self::API . '/recommendations?cart_ids=')->assertOk();

        $this->cliente->update(['recomendaciones_personalizadas' => false]);
        $this->getJson(self::API . '/recommendations?cart_ids=')->assertOk();

        $this->assertSame([$this->cliente->id, null], $recibido);
    }

    public function test_cancelacion_delete_account_sigue_disponible(): void
    {
        $this->deleteJson(self::API . '/account', [])->assertStatus(422);
    }

    public function test_sin_sesion_no_se_puede_ver_ni_cambiar_el_perfil(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson(self::API . '/profile')->assertUnauthorized();
        $this->putJson(self::API . '/profile/privacy', ['promos_sms' => true])->assertUnauthorized();
    }
}
