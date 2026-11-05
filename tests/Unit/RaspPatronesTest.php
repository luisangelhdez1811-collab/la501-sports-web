<?php

namespace Tests\Unit;

use App\Http\Middleware\RaspSecurityMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * B-05: el filtro de seguridad debe frenar ataques reales sin bloquear texto normal
 * (la colonia «La Unión», «Selecciona la salsa», contraseñas con «--»).
 */
class RaspPatronesTest extends TestCase
{
    private function esMalicioso(array $entrada): bool
    {
        $metodo = new ReflectionMethod(RaspSecurityMiddleware::class, 'containsMaliciousInput');
        $metodo->setAccessible(true);

        return $metodo->invoke(new RaspSecurityMiddleware(), $entrada, RaspSecurityMiddleware::PATTERNS);
    }

    public static function textosLegitimos(): array
    {
        return [
            'colonia La Unión' => ['Col. La Unión'],
            'calle Unión' => ['Calle Unión 12'],
            'selecciona' => ['Selecciona la salsa'],
            'seleccion de carnes' => ['Selección de carnes frías'],
            'reunión' => ['Reunión de equipo a las 8'],
            'guion doble en texto' => ['Sin cebolla -- gracias'],
            'direccion con diagonal' => ['Av. Hidalgo 5/B, entre calle 2 y calle 3'],
            'nota normal' => ['Bien doradas y sin sal, por favor'],
        ];
    }

    #[DataProvider('textosLegitimos')]
    public function test_permite_texto_legitimo(string $texto): void
    {
        $this->assertFalse($this->esMalicioso(['customer_address' => $texto]));
        $this->assertFalse($this->esMalicioso(['items' => [['notes' => $texto]]]));
    }

    public static function ataques(): array
    {
        return [
            'union select' => ['1 UNION SELECT password FROM users'],
            'union all select' => ['x UNION ALL SELECT 1,2,3'],
            'drop table' => ['1; DROP TABLE users'],
            'script' => ['<script>alert(1)</script>'],
            'script sin cerrar' => ['<script src=//evil.example/x.js'],
            'or 1=1' => ['x OR 1=1'],
            'comentario sql' => ["admin' --"],
            'recorrido de rutas' => ['../../.env'],
        ];
    }

    #[DataProvider('ataques')]
    public function test_bloquea_ataques_reales(string $ataque): void
    {
        $this->assertTrue($this->esMalicioso(['q' => $ataque]));
        // También dentro de arreglos anidados (como los ingredientes excluidos o las notas)
        $this->assertTrue($this->esMalicioso(['items' => [['notes' => $ataque]]]));
    }

    public function test_no_revisa_contrasenas_ni_el_token_csrf(): void
    {
        // Una contraseña válida puede llevar cualquier carácter; se guarda con hash, no va a SQL.
        $this->assertFalse($this->esMalicioso([
            'email' => 'cliente@la501.test',
            'password' => "Reunion--Select1' --",
            'password_confirmation' => "Reunion--Select1' --",
            '_token' => 'abc--union select',
        ]));

        // Pero el mismo texto en otro campo sí se revisa
        $this->assertTrue($this->esMalicioso(['comentario' => "Reunion--Select1' --"]));
    }

    public function test_los_patrones_son_expresiones_validas(): void
    {
        foreach (RaspSecurityMiddleware::PATTERNS as $patron) {
            $this->assertNotFalse(@preg_match($patron, 'texto'), "Patrón inválido: {$patron}");
        }
    }
}
