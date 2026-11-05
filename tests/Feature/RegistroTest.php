<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El registro exige declarar mayoría de edad y pide y guarda la pregunta secreta.
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
            'pregunta_secreta' => '¿Cuál es el nombre de tu primera mascota?',
            'respuesta_secreta' => 'Firulais',
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
            'pregunta_secreta' => '¿Cuál es el nombre de tu primera mascota?',
            'respuesta_secreta' => 'Firulais',
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

    public function test_la_api_registra_y_guarda_la_pregunta_secreta(): void
    {
        $this->postJson(self::API, $this->datosApi())->assertCreated();

        $usuario = User::firstOrFail();
        $this->assertSame('¿Cuál es el nombre de tu primera mascota?', $usuario->pregunta_secreta);
        $this->assertNotSame('Firulais', $usuario->respuesta_secreta, 'la respuesta se guarda cifrada');
        $this->assertTrue(Hash::check('Firulais', $usuario->respuesta_secreta));
        $this->assertNotNull($usuario->aviso_privacidad_aceptado_at);
        $this->assertSame('cliente', $usuario->role);
    }

    public function test_la_api_rechaza_el_registro_sin_pregunta_o_sin_respuesta_secreta(): void
    {
        $this->postJson(self::API, array_diff_key($this->datosApi(), ['pregunta_secreta' => 1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('pregunta_secreta');

        $this->postJson(self::API, array_diff_key($this->datosApi(), ['respuesta_secreta' => 1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('respuesta_secreta');

        $this->assertSame(0, User::count());
    }

    // ── Web ──────────────────────────────────────────────────────────────────────────────────

    public function test_el_formulario_web_pide_pregunta_secreta_y_trae_la_casilla_de_edad_sin_premarcar(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="pregunta_secreta"', false)
            ->assertSee('name="respuesta_secreta"', false)
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

    public function test_el_registro_web_con_la_casilla_de_edad_y_la_pregunta_secreta_funciona(): void
    {
        $this->post('/registro', $this->datosWeb())->assertRedirect(route('login'));

        $usuario = User::firstOrFail();
        $this->assertSame('¿Cuál es el nombre de tu primera mascota?', $usuario->pregunta_secreta);
        $this->assertTrue(Hash::check('Firulais', $usuario->respuesta_secreta));
        $this->assertNotNull($usuario->aviso_privacidad_aceptado_at);
    }

    public function test_el_registro_web_sin_pregunta_secreta_se_rechaza(): void
    {
        $this->post('/registro', array_diff_key($this->datosWeb(), ['pregunta_secreta' => 1, 'respuesta_secreta' => 1]))
            ->assertSessionHasErrors(['pregunta_secreta', 'respuesta_secreta']);

        $this->assertSame(0, User::count());
    }

    public function test_el_mensaje_de_error_de_edad_es_claro(): void
    {
        $respuesta = $this->post('/registro', array_diff_key($this->datosWeb(), ['mayor_de_edad' => 1]));

        $this->assertSame(
            'Debes confirmar que eres mayor de 18 años para registrarte.',
            session('errors')->first('mayor_de_edad')
        );
    }

    // ── Migración: ya no borra las respuestas guardadas ──────────────────────────────────────

    public function test_la_migracion_conserva_las_preguntas_y_respuestas_guardadas(): void
    {
        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        $usuario = User::create(['name' => 'Ana', 'email' => 'ana@la501.test', 'password' => 'La501Sports!', 'role' => 'cliente']);
        DB::table('users')->where('id', $usuario->id)->update([
            'pregunta_secreta' => '¿Cuál es tu platillo favorito de La 501?',
            'respuesta_secreta' => 'hash-de-la-respuesta',
        ]);

        (require base_path('database/migrations/2026_11_05_000100_clear_secret_question_data_from_users.php'))->up();

        $fila = DB::table('users')->where('id', $usuario->id)->first();
        $this->assertSame('¿Cuál es tu platillo favorito de La 501?', $fila->pregunta_secreta);
        $this->assertSame('hash-de-la-respuesta', $fila->respuesta_secreta);
        $this->assertSame('Ana', $fila->name);
    }
}
