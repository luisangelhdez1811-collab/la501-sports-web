<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La API ligera de api/ (usada por la skill de Alexa) abre su propia conexión mysqli,
 * por eso estas pruebas necesitan MySQL real (el CI lo tiene) y datos ya confirmados.
 */
class ApiLigeraInyeccionTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Requiere MySQL (se ejecuta en GitHub Actions).');
        }

        DB::table('products')->insert([
            ['name' => 'Alitas BBQ', 'price' => 139, 'category' => 'Botanas', 'available' => 1],
            ['name' => 'Hamburguesa 501', 'price' => 159, 'category' => 'Hamburguesas', 'available' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        // DatabaseTruncation limpia al INICIAR cada prueba; aquí se limpia también al terminar
        // para que las pruebas que usan RefreshDatabase no hereden estos platillos.
        if (config('database.default') === 'mysql') {
            DB::table('products')->truncate();
        }

        parent::tearDown();
    }

    private function ejecutar(string $archivo, array $parametros): array
    {
        $_GET = $parametros;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        include base_path('api/' . $archivo);
        $salida = ob_get_clean();

        return json_decode($salida, true);
    }

    public function test_la_busqueda_encuentra_platillos_reales(): void
    {
        $respuesta = $this->ejecutar('busqueda.php', ['q' => 'alitas']);

        $this->assertTrue($respuesta['encontrado']);
        $this->assertStringContainsString('Alitas BBQ', $respuesta['respuesta']);
    }

    public function test_la_busqueda_no_permite_inyeccion_sql(): void
    {
        foreach (["' OR '1'='1", "x' UNION SELECT password, 1, 1 FROM users -- ", "%' OR 1=1 #"] as $ataque) {
            $respuesta = $this->ejecutar('busqueda.php', ['q' => $ataque]);
            $this->assertFalse($respuesta['encontrado'], "La búsqueda respondió con datos para: $ataque");
        }
    }

    public function test_los_comodines_de_like_se_buscan_como_texto(): void
    {
        $this->assertFalse($this->ejecutar('busqueda.php', ['q' => '%'])['encontrado']);
        $this->assertFalse($this->ejecutar('busqueda.php', ['q' => '_'])['encontrado']);
    }

    public function test_el_menu_por_categoria_no_permite_inyeccion_sql(): void
    {
        $this->assertSame(1, $this->ejecutar('menu.php', ['categoria' => 'Botanas'])['total']);
        $this->assertSame(0, $this->ejecutar('menu.php', ['categoria' => "Botanas' OR '1'='1"])['total']);
    }

    public function test_reservaciones_ignora_fechas_invalidas(): void
    {
        $respuesta = $this->ejecutar('reservaciones.php', ['fecha' => "2026-10-22' OR '1'='1"]);

        $this->assertSame(date('Y-m-d'), $respuesta['fecha']);
        $this->assertSame('2026-12-24', $this->ejecutar('reservaciones.php', ['fecha' => '2026-12-24'])['fecha']);
    }
}
