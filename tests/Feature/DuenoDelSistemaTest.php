<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireRecentPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pasar la propiedad del sistema (por ejemplo, del desarrollador al dueño del restaurante), en el panel
 * o con «php artisan owner:transfer». Siempre queda exactamente un dueño.
 */
class DuenoDelSistemaTest extends TestCase
{
    use RefreshDatabase;

    private function cuenta(string $correo, string $rol = 'admin', bool $duenio = false): User
    {
        $user = User::create(['name' => ucfirst(strtok($correo, '@')), 'email' => $correo, 'password' => 'La501Sports!', 'role' => $rol, 'is_active' => 1]);
        if ($duenio) {
            $user->forceFill(['is_owner' => true])->save();
        }

        return $user;
    }

    private function comoDuenio(User $user): static
    {
        return $this->actingAs($user)->withSession([RequireRecentPassword::SESSION_KEY => time()]);
    }

    public function test_el_duenio_pasa_la_propiedad_a_otro_administrador(): void
    {
        $desarrollador = $this->cuenta('dev@la501.test', duenio: true);
        $restaurante = $this->cuenta('dueno@la501.test');

        $this->comoDuenio($desarrollador)
            ->post(route('admin.users.transfer-ownership', $restaurante->id), ['current_password' => 'La501Sports!'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue($restaurante->fresh()->is_owner);
        $this->assertFalse($desarrollador->fresh()->is_owner);
        $this->assertSame('admin', $desarrollador->fresh()->role);
        $this->assertSame(1, User::where('is_owner', true)->count());

        // El anterior ya no entra a Personal; el nuevo sí, y ahora puede pausar al anterior
        $this->comoDuenio($desarrollador->fresh())->get(route('admin.users.index'))->assertRedirect(route('admin.dashboard'));
        $this->comoDuenio($restaurante->fresh())->get(route('admin.users.index'))->assertOk();
        $this->comoDuenio($restaurante->fresh())->put(route('admin.users.toggle', $desarrollador->id))->assertSessionHas('success');
        $this->assertFalse((bool) $desarrollador->fresh()->is_active);
    }

    public function test_sin_la_contrasena_correcta_no_cambia_nada(): void
    {
        $duenio = $this->cuenta('dev@la501.test', duenio: true);
        $otro = $this->cuenta('dueno@la501.test');

        $this->comoDuenio($duenio)
            ->post(route('admin.users.transfer-ownership', $otro->id), ['current_password' => 'Equivocada#1'])
            ->assertSessionHas('error');

        $this->assertTrue($duenio->fresh()->is_owner);
        $this->assertFalse($otro->fresh()->is_owner);
    }

    public function test_solo_se_pasa_a_un_administrador_activo(): void
    {
        $duenio = $this->cuenta('dev@la501.test', duenio: true);
        $cocinero = $this->cuenta('cocina@la501.test', 'cocinero');
        $pausado = $this->cuenta('pausado@la501.test');
        $pausado->forceFill(['is_active' => 0])->save();
        $cliente = $this->cuenta('cliente@la501.test', 'cliente');

        $this->comoDuenio($duenio)->post(route('admin.users.transfer-ownership', $cocinero->id), ['current_password' => 'La501Sports!'])->assertSessionHas('error');
        $this->comoDuenio($duenio)->post(route('admin.users.transfer-ownership', $pausado->id), ['current_password' => 'La501Sports!'])->assertSessionHas('error');
        $this->comoDuenio($duenio)->post(route('admin.users.transfer-ownership', $cliente->id), ['current_password' => 'La501Sports!'])->assertNotFound();

        $this->assertSame([$duenio->id], User::where('is_owner', true)->pluck('id')->all());
    }

    public function test_un_administrador_que_no_es_el_duenio_no_puede_quedarse_con_la_propiedad(): void
    {
        $this->cuenta('dev@la501.test', duenio: true);
        $admin = $this->cuenta('admin@la501.test');

        $this->comoDuenio($admin)
            ->post(route('admin.users.transfer-ownership', $admin->id), ['current_password' => 'La501Sports!'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertFalse($admin->fresh()->is_owner);
    }

    public function test_la_pantalla_de_personal_ofrece_hacer_dueno_y_la_clave_va_a_la_ruta_correcta(): void
    {
        $duenio = $this->cuenta('dev@la501.test', duenio: true);
        $this->cuenta('dueno@la501.test');
        $this->cuenta('cocina@la501.test', 'cocinero');

        $html = $this->comoDuenio($duenio)->get(route('admin.users.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'Hacer dueño'), 'Solo el otro administrador puede recibir la propiedad');
        $this->assertStringContainsString('/hacer-dueno', $html);
        $this->assertStringNotContainsString("'/usuarios/' + passUserId", $html);
    }

    // ── Base nueva ───────────────────────────────────────────────────────────────────────────

    public function test_en_una_base_nueva_registrarse_primero_no_da_ningun_poder(): void
    {
        $this->post('/registro', [
            'name' => 'Primero', 'email' => 'primero@la501.test', 'telefono' => '1234567890',
            'password' => 'Fuerte#2026', 'password_confirmation' => 'Fuerte#2026',
            'mayor_de_edad' => '1', 'terms' => '1',
        ])->assertSessionHasNoErrors();

        $primero = User::where('email', 'primero@la501.test')->firstOrFail();
        $this->assertSame('cliente', $primero->role);
        $this->assertFalse($primero->is_owner);
        $this->assertSame(0, User::where('is_owner', true)->count());
    }

    public function test_el_dueno_de_una_base_nueva_se_crea_desde_el_servidor(): void
    {
        $this->artisan('owner:create', ['email' => 'Dueno@La501.test', '--name' => 'Dueño del Restaurante'])
            ->expectsQuestion('Contraseña (8 o más, con mayúscula, minúscula, número y símbolo)', 'Fuerte#2026')
            ->expectsQuestion('Escribe la contraseña otra vez', 'Fuerte#2026')
            ->assertSuccessful();

        $duenio = User::where('email', 'dueno@la501.test')->firstOrFail();
        $this->assertSame('admin', $duenio->role);
        $this->assertTrue($duenio->is_owner);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Fuerte#2026', $duenio->password));

        // Si ya hay dueño, no crea otro
        $this->artisan('owner:create', ['email' => 'otro@la501.test', '--name' => 'Otro'])->assertFailed();
        $this->assertSame(1, User::where('is_owner', true)->count());
    }

    public function test_owner_create_no_acepta_una_contrasena_debil_ni_distinta(): void
    {
        $this->artisan('owner:create', ['email' => 'dueno@la501.test', '--name' => 'Dueño'])
            ->expectsQuestion('Contraseña (8 o más, con mayúscula, minúscula, número y símbolo)', '123456')
            ->expectsQuestion('Escribe la contraseña otra vez', '123456')
            ->assertFailed();

        $this->artisan('owner:create', ['email' => 'dueno@la501.test', '--name' => 'Dueño'])
            ->expectsQuestion('Contraseña (8 o más, con mayúscula, minúscula, número y símbolo)', 'Fuerte#2026')
            ->expectsQuestion('Escribe la contraseña otra vez', 'Fuerte#2027')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    // ── Desde el servidor ────────────────────────────────────────────────────────────────────

    public function test_el_comando_cambia_al_dueno_desde_el_servidor(): void
    {
        $duenio = $this->cuenta('dev@la501.test', duenio: true);
        $nuevo = $this->cuenta('dueno@la501.test');

        $this->artisan('owner:transfer', ['email' => 'Dueno@La501.test', '--force' => true])->assertSuccessful();

        $this->assertTrue($nuevo->fresh()->is_owner);
        $this->assertFalse($duenio->fresh()->is_owner);
    }

    public function test_el_comando_no_hace_dueno_a_quien_no_es_administrador_sin_permiso_explicito(): void
    {
        $this->cuenta('dev@la501.test', duenio: true);
        $mesero = $this->cuenta('mesero@la501.test', 'empleado');

        $this->artisan('owner:transfer', ['email' => 'mesero@la501.test', '--force' => true])->assertFailed();
        $this->assertFalse($mesero->fresh()->is_owner);

        $this->artisan('owner:transfer', ['email' => 'mesero@la501.test', '--force' => true, '--make-admin' => true])->assertSuccessful();
        $this->assertTrue($mesero->fresh()->is_owner);
        $this->assertSame('admin', $mesero->fresh()->role);

        $this->artisan('owner:transfer', ['email' => 'nadie@la501.test', '--force' => true])->assertFailed();
    }
}
