<?php

namespace Tests\Feature;

use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sesiones de la app móvil con JWT Bearer: inicio de sesión, rutas protegidas, refresco
 * con rotación del refresh token y cierre de sesión.
 */
class JwtAuthTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app';
    private const PASSWORD = 'La501Sports!';

    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        $this->cliente = User::create([
            'name' => 'Cliente JWT',
            'email' => 'jwt@la501.test',
            'password' => self::PASSWORD,
            'telefono' => '5512345678',
            'role' => 'cliente',
            'is_active' => 1,
        ]);
    }

    private function iniciarSesion(): array
    {
        return $this->postJson(self::API . '/auth/login', [
            'email' => 'jwt@la501.test',
            'password' => self::PASSWORD,
        ])->assertOk()->json();
    }

    private function conToken(string $token)
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_el_inicio_de_sesion_entrega_un_jwt_y_un_refresh_token(): void
    {
        $sesion = $this->iniciarSesion();

        $this->assertSame('Bearer', $sesion['token_type']);
        $this->assertSame(15 * 60, $sesion['expires_in']);
        $this->assertCount(3, explode('.', $sesion['access_token']));
        $this->assertSame($sesion['access_token'], $sesion['token']);
        $this->assertSame(64, strlen($sesion['refresh_token']));

        // En la base solo queda el hash del refresh token.
        $guardado = RefreshToken::first();
        $this->assertSame(hash('sha256', $sesion['refresh_token']), $guardado->token_hash);
        $this->assertNotSame($sesion['refresh_token'], $guardado->token_hash);
    }

    public function test_el_jwt_abre_las_rutas_protegidas(): void
    {
        $sesion = $this->iniciarSesion();

        $this->conToken($sesion['access_token'])
            ->getJson(self::API . '/auth/me')
            ->assertOk();
    }

    public function test_un_jwt_alterado_vencido_o_de_otro_emisor_se_rechaza(): void
    {
        $sesion = $this->iniciarSesion();
        [$cabecera, $datos, $firma] = explode('.', $sesion['access_token']);

        $alterado = $cabecera . '.' . $datos . '.' . strrev($firma);
        $this->conToken($alterado)->getJson(self::API . '/auth/me')->assertUnauthorized();

        $this->travel(16)->minutes();
        $this->conToken($sesion['access_token'])->getJson(self::API . '/auth/me')->assertUnauthorized();
        $this->travelBack();

        $ajeno = JWT::encode(['sub' => (string) $this->cliente->id, 'typ' => 'access', 'exp' => time() + 600], str_repeat('x', 32), 'HS256');
        $this->conToken($ajeno)->getJson(self::API . '/auth/me')->assertUnauthorized();
    }

    public function test_una_cuenta_suspendida_pierde_el_acceso_aunque_su_jwt_siga_vigente(): void
    {
        $sesion = $this->iniciarSesion();
        $this->cliente->update(['is_active' => 0]);

        $this->conToken($sesion['access_token'])->getJson(self::API . '/auth/me')->assertUnauthorized();
    }

    public function test_el_refresh_token_se_cambia_por_un_par_nuevo_y_solo_sirve_una_vez(): void
    {
        $sesion = $this->iniciarSesion();

        $nueva = $this->postJson(self::API . '/auth/refresh', ['refresh_token' => $sesion['refresh_token']])
            ->assertOk()
            ->json();

        $this->assertNotSame($sesion['refresh_token'], $nueva['refresh_token']);
        $this->conToken($nueva['access_token'])->getJson(self::API . '/auth/me')->assertOk();

        // Reusar el refresh token anterior = robo: se cierra toda la sesión.
        $this->postJson(self::API . '/auth/refresh', ['refresh_token' => $sesion['refresh_token']])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'invalid_refresh_token');
        $this->postJson(self::API . '/auth/refresh', ['refresh_token' => $nueva['refresh_token']])
            ->assertUnauthorized();
    }

    public function test_un_refresh_token_vencido_o_inventado_no_sirve(): void
    {
        $sesion = $this->iniciarSesion();

        $this->postJson(self::API . '/auth/refresh', ['refresh_token' => str_repeat('a', 64)])->assertUnauthorized();

        $this->travel(31)->days();
        $this->postJson(self::API . '/auth/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
    }

    public function test_cerrar_sesion_revoca_el_refresh_token(): void
    {
        $sesion = $this->iniciarSesion();

        $this->conToken($sesion['access_token'])
            ->postJson(self::API . '/auth/logout', ['refresh_token' => $sesion['refresh_token']])
            ->assertOk();

        $this->postJson(self::API . '/auth/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
    }

    public function test_los_tokens_de_sanctum_siguen_funcionando_durante_la_migracion(): void
    {
        $sanctum = $this->cliente->createToken('react-native-app')->plainTextToken;

        $this->conToken($sanctum)->getJson(self::API . '/auth/me')->assertOk();
    }

    public function test_un_cliente_con_jwt_no_entra_a_la_api_del_mesero(): void
    {
        $sesion = $this->iniciarSesion();

        $this->conToken($sesion['access_token'])->getJson(self::API . '/waiter/tables')->assertForbidden();
    }
}
