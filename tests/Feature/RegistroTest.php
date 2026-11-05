<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B-15: el registro exige declarar mayoría de edad y ya no pide ni guarda la pregunta secreta.
 */
class RegistroTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app/auth/register';

    private function datosApi(array $extra = []): array
    {
        return array_merge([
            'name' => 'Ana Prueba',
            'email' => 'ana@la501.test',
            'telefono' => '5512345678',
            'password' => 'La501Sports!',
            'password_confirmation' => 'La501Sports!',
            'acepta_aviso_privacidad' => true,
            'mayor_de_edad' => true,
        ], $extra);
    }

    private function datosWeb(array $extra = []): array
    {
        return array_merge([
            'name' => 'Ana Prueba',
            'email' => 'ana@la501.test',
            'telefono' => '5512345678',
            'password' => 'La501Sports!',
            'password_confirmation' => 'La501Sports!',
            'terms' => '1',
            'mayor_de_edad' => '1',
        ], $extra);
    }

    // ── API de la app ────────────────────────────────────────────────────────────────────────

    public function test_la_api_rechaza_el_registro_sin_declarar_mayoria_de_edad(): void
    {
        $this->postJson(self::API, array_diff_key($this->datosApi(), ['mayor_de_edad' => 1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('mayor_de_edad');

        $this->postJson(self::API, $this->datosApi(['mayor_de_edad' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('mayor_de_edad');

        $this->assertSame(0, User::count());
    }

    public function test_la_api_registra_sin_pregunta_secreta_y_no_la_guarda(): void
    {
        $this->postJson(self::API, $this->datosApi())->assertCreated();

        $usuario = User::firstOrFail();
        $this->assertNull($usuario->pregunta_secreta);
        $this->assertNull($usuario->respuesta_secreta);
        $this->assertNotNull($usuario->aviso_privacidad_aceptado_at);
        $this->assertSame('cliente', $usuario->role);
    }

    public function test_una_version_vieja_de_la_app_que_manda_la_pregunta_no_truena_y_se_ignora(): void
    {
        $this->postJson(self::API, $this->datosApi([
            'pregunta_secreta' => '¿Cuál es el nombre de tu primera mascota?',
            'respuesta_secreta' => 'Firulais',
        ]))->assertCreated();

        $usuario = User::firstOrFail();
        $this->assertNull($usuario->pregunta_secreta);
        $this->assertNull($usuario->respuesta_secreta);
    }

    // ── Web ──────────────────────────────────────────────────────────────────────────────────

    public function test_el_formulario_web_no_pide_pregunta_secreta_y_trae_la_casilla_de_edad_sin_premarcar(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('pregunta_secreta', false)
            ->assertDontSee('respuesta_secreta', false)
            ->assertSee('name="mayor_de_edad"', false)
            ->assertSee('Soy mayor de 18 años', false)
            ->assertDontSee('name="mayor_de_edad" value="1" required checked', false);
    }

    public function test_el_registro_web_sin_la_casilla_de_edad_se_rechaza(): void
    {
        $this->post('/registro', array_diff_key($this->datosWeb(), ['mayor_de_edad' => 1]))
            ->assertSessionHasErrors('mayor_de_edad');

        $this->assertSame(0, User::count());
    }

    public function test_el_registro_web_con_la_casilla_de_edad_y_sin_pregunta_secreta_funciona(): void
    {
        $this->post('/registro', $this->datosWeb())->assertRedirect(route('login'));

        $usuario = User::firstOrFail();
        $this->assertNull($usuario->pregunta_secreta);
        $this->assertNull($usuario->respuesta_secreta);
        $this->assertNotNull($usuario->aviso_privacidad_aceptado_at);
    }

    public function test_el_mensaje_de_error_de_edad_es_claro(): void
    {
        $respuesta = $this->post('/registro', array_diff_key($this->datosWeb(), ['mayor_de_edad' => 1]));

        $this->assertSame(
            'Debes confirmar que eres mayor de 18 años para registrarte.',
            session('errors')->first('mayor_de_edad')
        );
    }

    // ── Migración: se borran las respuestas ya guardadas ─────────────────────────────────────

    public function test_la_migracion_borra_las_preguntas_y_respuestas_guardadas_y_conserva_las_columnas(): void
    {
        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        $usuario = User::create(['name' => 'Ana', 'email' => 'ana@la501.test', 'password' => 'La501Sports!', 'role' => 'cliente']);
        DB::table('users')->where('id', $usuario->id)->update([
            'pregunta_secreta' => '¿Cuál es tu platillo favorito de La 501?',
            'respuesta_secreta' => 'hash-de-la-respuesta',
        ]);

        (require base_path('database/migrations/2026_11_05_000100_clear_secret_question_data_from_users.php'))->up();

        $fila = DB::table('users')->where('id', $usuario->id)->first();
        $this->assertNull($fila->pregunta_secreta);
        $this->assertNull($fila->respuesta_secreta);
        $this->assertSame('Ana', $fila->name, 'el resto de la cuenta no se toca');
    }
}
