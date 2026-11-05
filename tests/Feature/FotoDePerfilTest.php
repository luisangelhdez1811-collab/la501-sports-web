<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * B-16: la foto de perfil es la misma en la web y en la app, siempre con URL completa, con el mismo
 * nombre seguro generado por el servidor y la misma validación.
 */
class FotoDePerfilTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app/account/avatar';

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public_avatars');

        $this->ana = User::create([
            'name' => 'Ana Prueba', 'email' => 'ana@la501.test', 'password' => 'La501Sports!',
            'telefono' => '5512345678', 'role' => 'cliente', 'is_active' => 1,
        ]);
    }

    private function foto(string $nombre = 'mi foto.jpg', int $ancho = 200, int $alto = 200): UploadedFile
    {
        return UploadedFile::fake()->image($nombre, $ancho, $alto);
    }

    // ── App ──────────────────────────────────────────────────────────────────────────────────

    public function test_la_app_sube_la_foto_y_recibe_la_url_completa(): void
    {
        Sanctum::actingAs($this->ana);

        $r = $this->postJson(self::API, ['avatar' => $this->foto()])->assertOk();

        $url = $r->json('avatar');
        $this->assertMatchesRegularExpression('#^https?://.+/avatars/av_\d+_[A-Za-z0-9]{32}\.(jpg|jpeg)$#', $url);

        $ruta = $this->ana->fresh()->avatar;
        $this->assertStringStartsWith('avatars/av_', $ruta);
        Storage::disk('public_avatars')->assertExists(basename($ruta));
    }

    public function test_el_nombre_original_del_archivo_nunca_se_usa(): void
    {
        Sanctum::actingAs($this->ana);

        $this->postJson(self::API, ['avatar' => $this->foto('../../shell.php.jpg')])->assertOk();

        $ruta = $this->ana->fresh()->avatar;
        $this->assertStringNotContainsString('shell', $ruta);
        $this->assertStringNotContainsString('..', $ruta);
        $this->assertStringNotContainsString('.php', $ruta);
    }

    public function test_subir_otra_borra_la_anterior(): void
    {
        Sanctum::actingAs($this->ana);

        $this->postJson(self::API, ['avatar' => $this->foto()])->assertOk();
        $primera = basename($this->ana->fresh()->avatar);

        $this->postJson(self::API, ['avatar' => $this->foto('otra.png')])->assertOk();
        $segunda = basename($this->ana->fresh()->avatar);

        $this->assertNotSame($primera, $segunda);
        Storage::disk('public_avatars')->assertMissing($primera);
        Storage::disk('public_avatars')->assertExists($segunda);
    }

    public function test_un_pdf_renombrado_a_jpg_se_rechaza(): void
    {
        Sanctum::actingAs($this->ana);

        $falso = UploadedFile::fake()->createWithContent('truco.jpg', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

        $this->postJson(self::API, ['avatar' => $falso])->assertStatus(422)->assertJsonValidationErrors('avatar');

        $this->assertNull($this->ana->fresh()->avatar);
        $this->assertSame([], Storage::disk('public_avatars')->allFiles());
    }

    public function test_se_rechazan_gif_muy_pesadas_y_de_tamano_fuera_de_rango(): void
    {
        Sanctum::actingAs($this->ana);

        $casos = [
            'gif' => UploadedFile::fake()->image('a.gif', 200, 200),
            'pesada' => UploadedFile::fake()->image('b.jpg', 200, 200)->size(3000),
            'muy chica' => $this->foto('c.jpg', 63, 63),
            'muy grande' => $this->foto('d.jpg', 4001, 100),
        ];

        foreach ($casos as $cual => $archivo) {
            $this->postJson(self::API, ['avatar' => $archivo])->assertStatus(422, "debía rechazar: {$cual}");
        }

        $this->assertNull($this->ana->fresh()->avatar);
    }

    public function test_los_limites_exactos_si_se_aceptan(): void
    {
        Sanctum::actingAs($this->ana);

        $this->postJson(self::API, ['avatar' => $this->foto('a.png', 64, 64)])->assertOk();
        $this->postJson(self::API, ['avatar' => UploadedFile::fake()->image('b.webp', 4000, 64)])->assertOk();
    }

    public function test_sin_archivo_o_sin_sesion_no_se_puede(): void
    {
        $this->postJson(self::API, ['avatar' => $this->foto()])->assertUnauthorized();

        Sanctum::actingAs($this->ana);
        $this->postJson(self::API, [])->assertStatus(422)->assertJsonValidationErrors('avatar');
    }

    public function test_la_app_quita_la_foto(): void
    {
        Sanctum::actingAs($this->ana);
        $this->postJson(self::API, ['avatar' => $this->foto()])->assertOk();
        $archivo = basename($this->ana->fresh()->avatar);

        $this->deleteJson(self::API)->assertOk()->assertJsonPath('avatar', null);

        $this->assertNull($this->ana->fresh()->avatar);
        Storage::disk('public_avatars')->assertMissing($archivo);
    }

    public function test_quitar_la_foto_sin_tenerla_no_truena(): void
    {
        Sanctum::actingAs($this->ana);

        $this->deleteJson(self::API)->assertOk()->assertJsonPath('avatar', null);
    }

    public function test_el_perfil_y_el_login_de_la_app_mandan_la_misma_url_completa(): void
    {
        Sanctum::actingAs($this->ana);
        $url = $this->postJson(self::API, ['avatar' => $this->foto()])->json('avatar');

        $this->getJson('/api/v1/app/profile')->assertJsonPath('data.user.avatar', $url);
    }

    public function test_en_produccion_la_url_es_https(): void
    {
        config(['app.url' => 'http://lavender-ram-537394.hostingersite.com']);
        $this->app['env'] = 'production';
        Sanctum::actingAs($this->ana);

        $url = $this->postJson(self::API, ['avatar' => $this->foto()])->json('avatar');

        $this->assertStringStartsWith('https://lavender-ram-537394.hostingersite.com/avatars/av_', $url);
    }

    // ── Web ──────────────────────────────────────────────────────────────────────────────────

    public function test_la_web_usa_el_mismo_guardado_y_la_app_ve_la_misma_foto(): void
    {
        $this->actingAs($this->ana)->post(route('perfil.update'), ['avatar' => $this->foto('hacker.php.jpg')])
            ->assertSessionHasNoErrors();

        $ruta = $this->ana->fresh()->avatar;
        $this->assertMatchesRegularExpression('#^avatars/av_\d+_[A-Za-z0-9]{32}\.jpe?g$#', $ruta);
        Storage::disk('public_avatars')->assertExists(basename($ruta));

        Sanctum::actingAs($this->ana->fresh());
        $this->getJson('/api/v1/app/profile')->assertOk();
        $this->assertStringEndsWith('/' . $ruta, $this->getJson('/api/v1/app/profile')->json('data.user.avatar'));
    }

    public function test_la_web_rechaza_lo_mismo_que_la_app(): void
    {
        $falso = UploadedFile::fake()->createWithContent('truco.jpg', '%PDF-1.4 no soy una imagen');

        $this->actingAs($this->ana)->post(route('perfil.update'), ['avatar' => $falso])->assertSessionHasErrors('avatar');
        $this->actingAs($this->ana)->post(route('perfil.update'), ['avatar' => UploadedFile::fake()->image('a.gif', 200, 200)])->assertSessionHasErrors('avatar');
        $this->actingAs($this->ana)->post(route('perfil.update'), ['avatar' => $this->foto('chica.jpg', 10, 10)])->assertSessionHasErrors('avatar');

        $this->assertNull($this->ana->fresh()->avatar);
    }

    public function test_la_web_borra_la_foto_anterior_al_cambiarla(): void
    {
        $this->actingAs($this->ana)->post(route('perfil.update'), ['avatar' => $this->foto()]);
        $primera = basename($this->ana->fresh()->avatar);

        $this->actingAs($this->ana)->post(route('perfil.update'), ['avatar' => $this->foto('b.png')]);

        Storage::disk('public_avatars')->assertMissing($primera);
    }

    public function test_actualizar_el_perfil_sin_foto_conserva_la_que_tiene(): void
    {
        $this->actingAs($this->ana)->post(route('perfil.update'), ['avatar' => $this->foto()]);
        $ruta = $this->ana->fresh()->avatar;

        $this->actingAs($this->ana)->post(route('perfil.update'), [])->assertSessionHasNoErrors();

        $this->assertSame($ruta, $this->ana->fresh()->avatar);
    }
}
