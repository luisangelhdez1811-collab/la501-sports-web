<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaginasPublicasTest extends TestCase
{
    use RefreshDatabase;

    public static function paginas(): array
    {
        return [
            'inicio (quiénes somos)' => ['/'],
            'contacto' => ['/contacto'],
            'ubicación' => ['/ubicacion'],
            'reservaciones' => ['/reservaciones'],
            'inicio de sesión' => ['/login'],
            'aviso de privacidad' => ['/aviso-de-privacidad'],
        ];
    }

    #[DataProvider('paginas')]
    public function test_la_pagina_publica_carga_correctamente(string $ruta): void
    {
        $this->get($ruta)->assertOk();
    }

    public function test_las_paginas_incluyen_cabeceras_de_seguridad(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
