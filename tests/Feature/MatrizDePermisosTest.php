<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireRecentPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Prueba de seguridad que recorre TODAS las rutas de la web con cada rol: visitante, cliente, cocinero,
 * mesero, administrador y dueño. Comprueba que nadie entra a lo que no le toca (que los roles no se
 * mezclan) y que ninguna página truena con un error 500. Si se agrega una ruta nueva sin protección,
 * esta prueba la detecta sola.
 */
class MatrizDePermisosTest extends TestCase
{
    use RefreshDatabase;

    /** Rutas que dependen de servicios externos o archivos que no hay en las pruebas (modelos de IA). */
    private const SIN_REVISAR_500 = ['admin/ia'];

    /** @return array<string, User|null> */
    private function roles(): array
    {
        $crear = fn (string $rol, string $correo) => User::create([
            'name' => ucfirst($rol), 'email' => $correo, 'password' => 'La501Sports!', 'role' => $rol, 'is_active' => 1,
        ]);

        $duenio = $crear('admin', 'dueno@la501.test');
        $duenio->forceFill(['is_owner' => true])->save();

        return [
            'visitante' => null,
            'cliente' => $crear('cliente', 'cliente@la501.test'),
            'cocinero' => $crear('cocinero', 'cocina@la501.test'),
            'mesero' => $crear('empleado', 'mesero@la501.test'),
            'admin' => $crear('admin', 'admin@la501.test'),
            'dueno' => $duenio,
        ];
    }

    /** Rutas GET de la web (sin la API ni las de depuración), con valores de ejemplo en sus parámetros. */
    private function rutasGet(): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => in_array('GET', $r->methods(), true))
            ->reject(fn (Route $r) => str_starts_with($r->uri(), 'api/v1') || str_starts_with($r->uri(), '_') || in_array($r->uri(), ['up', 'sanctum/csrf-cookie', 'storage/{path}'], true))
            ->map(fn (Route $r) => preg_replace('/\{[^}]+\}/', '1', $r->uri()))
            ->unique()
            ->values()
            ->all();
    }

    private function pedir(?User $user, string $metodo, string $uri)
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        if ($user) {
            $this->actingAs($user)->withSession([RequireRecentPassword::SESSION_KEY => time()]);
        }

        return $this->call($metodo, '/' . ltrim($uri, '/'));
    }

    public function test_ninguna_pagina_truena_con_ningun_rol(): void
    {
        $roles = $this->roles();
        $errores = [];

        foreach ($this->rutasGet() as $uri) {
            if (in_array($uri, self::SIN_REVISAR_500, true)) {
                continue;
            }
            foreach ($roles as $nombre => $user) {
                $estado = $this->pedir($user, 'GET', $uri)->getStatusCode();
                if ($estado >= 500) {
                    $errores[] = "{$nombre} GET /{$uri} → {$estado}";
                }
            }
        }

        $this->assertSame([], $errores, "Páginas con error 500:\n" . implode("\n", $errores));
    }

    public function test_cada_rol_solo_entra_a_lo_suyo(): void
    {
        $roles = $this->roles();
        $fugas = [];

        // Qué roles pueden ver (200) las rutas de cada zona
        $zonas = [
            'admin/usuarios' => ['dueno'],
            'admin/sistema' => ['dueno'],
            'admin/' => ['admin', 'dueno'],
            'mesero/' => ['mesero', 'admin', 'dueno'],
            'cocina' => ['cocinero'],
            'api/cocina' => ['cocinero'],
            'configuracion' => ['cliente', 'cocinero', 'mesero', 'admin', 'dueno'],
            'perfil' => ['cliente', 'cocinero', 'mesero', 'admin', 'dueno'],
            'mis-pedidos' => ['cliente', 'cocinero', 'mesero', 'admin', 'dueno'],
        ];

        foreach ($this->rutasGet() as $uri) {
            $permitidos = null;
            foreach ($zonas as $prefijo => $quienes) {
                if (str_starts_with($uri . '/', $prefijo) || $uri === rtrim($prefijo, '/')) {
                    $permitidos = $quienes;
                    break;
                }
            }
            if ($permitidos === null || in_array($uri, self::SIN_REVISAR_500, true)) {
                continue; // rutas públicas
            }

            foreach ($roles as $nombre => $user) {
                if (in_array($nombre, $permitidos, true)) {
                    continue;
                }
                $estado = $this->pedir($user, 'GET', $uri)->getStatusCode();
                if ($estado >= 200 && $estado < 300) {
                    $fugas[] = "{$nombre} pudo ver /{$uri} ({$estado})";
                }
            }
        }

        $this->assertSame([], $fugas, "Accesos indebidos:\n" . implode("\n", $fugas));
    }

    public function test_las_acciones_del_panel_y_del_personal_no_las_hace_otro_rol(): void
    {
        $roles = $this->roles();
        $fugas = [];

        $acciones = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $r->methods()))
            ->filter(fn (Route $r) => preg_match('#^(admin|mesero|cocina)/#', $r->uri()))
            ->map(fn (Route $r) => [array_values(array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $r->methods()))[0], preg_replace('/\{[^}]+\}/', '1', $r->uri())]);

        foreach ($acciones as [$metodo, $uri]) {
            $prohibidos = match (true) {
                str_starts_with($uri, 'admin/') => ['visitante', 'cliente', 'cocinero', 'mesero'],
                str_starts_with($uri, 'mesero/') => ['visitante', 'cliente', 'cocinero'],
                default => ['visitante', 'cliente', 'mesero', 'admin'],
            };

            foreach ($prohibidos as $nombre) {
                $estado = $this->pedir($roles[$nombre], $metodo, $uri)->getStatusCode();
                // Permitido: redirigir (al login o al inicio), 401, 403, 404 o 405. Nunca 2xx.
                if ($estado >= 200 && $estado < 300) {
                    $fugas[] = "{$nombre} {$metodo} /{$uri} → {$estado}";
                }
            }
        }

        $this->assertSame([], $fugas, "Acciones hechas por un rol sin permiso:\n" . implode("\n", $fugas));
    }

    public function test_en_la_api_un_cliente_no_usa_las_rutas_del_personal_ni_del_admin(): void
    {
        $roles = $this->roles();
        $fugas = [];

        $rutas = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => preg_match('#^api/v1/(app/admin|app/waiter|staff)/#', $r->uri()))
            ->reject(fn (Route $r) => $r->uri() === 'api/v1/staff/login')
            ->map(fn (Route $r) => [$r->methods()[0], preg_replace('/\{[^}]+\}/', '1', $r->uri())]);

        $this->assertNotEmpty($rutas);

        foreach (['cliente', 'cocinero'] as $nombre) {
            $token = $roles[$nombre]->createToken('app')->plainTextToken;

            foreach ($rutas as [$metodo, $uri]) {
                if ($nombre === 'cocinero' && str_starts_with($uri, 'api/v1/staff/') && !str_contains($uri, 'dashboard')) {
                    continue; // la cocina sí usa los pedidos del personal (con su propio token)
                }
                $this->app['auth']->forgetGuards();
                $estado = $this->json($metodo, '/' . $uri, [], ['Authorization' => 'Bearer ' . $token])->getStatusCode();
                if ($estado < 300) {
                    $fugas[] = "{$nombre} {$metodo} /{$uri} → {$estado}";
                }
            }
        }

        $this->assertSame([], $fugas, "La API dejó pasar:\n" . implode("\n", $fugas));
    }

    public function test_un_admin_que_no_es_dueno_no_hace_acciones_del_dueno(): void
    {
        $admin = $this->roles()['admin'];
        $fugas = [];

        $acciones = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => in_array('owner', $r->gatherMiddleware(), true))
            ->map(fn (Route $r) => [$r->methods()[0], preg_replace('/\{[^}]+\}/', '1', $r->uri())]);

        $this->assertNotEmpty($acciones);

        foreach ($acciones as [$metodo, $uri]) {
            $respuesta = $this->pedir($admin, $metodo, $uri);
            if ($respuesta->getStatusCode() < 300) {
                $fugas[] = "{$metodo} /{$uri}";
            }
        }

        $this->assertSame([], $fugas, "Un admin que no es dueño pudo:\n" . implode("\n", $fugas));
    }
}
