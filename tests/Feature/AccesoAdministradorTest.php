<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesoAdministradorTest extends TestCase
{
    use RefreshDatabase;

    public static function rutasDeAdministrador(): array
    {
        return [
            'reservaciones' => ['get', '/admin/reservaciones'],
            'exportar ganancias' => ['get', '/admin/export/ganancias'],
            'exportar corte diario' => ['get', '/admin/export/cortediario'],
            'métricas de base de datos' => ['get', '/admin/database/api/metrics'],
            'limpiar base de datos' => ['post', '/admin/sistema/base-de-datos/no9'],
            'optimizar base de datos' => ['post', '/admin/sistema/base-de-datos/optimizar'],
        ];
    }

    #[DataProvider('rutasDeAdministrador')]
    public function test_un_visitante_sin_sesion_es_enviado_al_login(string $metodo, string $ruta): void
    {
        $this->{$metodo}($ruta)->assertRedirect(route('login'));
    }

    #[DataProvider('rutasDeAdministrador')]
    public function test_un_cliente_no_puede_entrar_al_panel_de_administrador(string $metodo, string $ruta): void
    {
        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        $cliente = User::create([
            'name' => 'Cliente de prueba',
            'email' => 'cliente@la501.test',
            'password' => 'La501Sports!',
            'role' => 'cliente',
        ]);

        $this->actingAs($cliente)->{$metodo}($ruta)->assertRedirect('/');
    }
}
