<?php

namespace Tests\Unit;

use App\Http\Middleware\RaspSecurityMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReflectionMethod;
use Tests\TestCase;

class SeguridadMiddlewareTest extends TestCase
{
    private function esMalicioso(array $entrada): bool
    {
        $metodo = new ReflectionMethod(RaspSecurityMiddleware::class, 'containsMaliciousInput');
        $metodo->setAccessible(true);

        return $metodo->invoke(new RaspSecurityMiddleware(), $entrada, ['/select/i', '/union/i', '/<script>/i', '/\.\.\//', '/--/']);
    }

    public function test_rasp_permite_datos_normales(): void
    {
        $this->assertFalse($this->esMalicioso([
            'nombre' => 'Ana López',
            'telefono' => '7711234567',
            'excluded_ingredients' => ['cebolla', 'jalapeño'],
        ]));
    }

    public function test_rasp_detecta_inyeccion_sql_scripts_y_rutas(): void
    {
        $this->assertTrue($this->esMalicioso(['q' => "1 UNION SELECT password FROM users"]));
        $this->assertTrue($this->esMalicioso(['comentario' => '<script>alert(1)</script>']));
        $this->assertTrue($this->esMalicioso(['archivo' => '../../.env']));
        $this->assertTrue($this->esMalicioso(['notas' => ['ok', ["admin' --"]]]));
    }

    public function test_agrega_cabeceras_de_seguridad_a_la_respuesta(): void
    {
        $respuesta = (new SecurityHeaders())->handle(Request::create('/'), function () {
            $r = new Response('ok');
            $r->headers->set('X-Powered-By', 'PHP/8.3');

            return $r;
        });

        $this->assertSame('SAMEORIGIN', $respuesta->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $respuesta->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString("object-src 'none'", $respuesta->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('max-age=31536000', $respuesta->headers->get('Strict-Transport-Security'));
        $this->assertFalse($respuesta->headers->has('X-Powered-By'));
    }

    public function test_la_politica_permite_las_imagenes_de_cloudinary(): void
    {
        $respuesta = (new SecurityHeaders())->handle(Request::create('/'), fn () => new Response('ok'));
        $csp = $respuesta->headers->get('Content-Security-Policy');

        // Logo, fotos de platillos y carrusel se sirven desde Cloudinary
        $this->assertMatchesRegularExpression("/img-src[^;]*https:\/\/res\.cloudinary\.com/", $csp);
    }

    public function test_la_politica_permite_pusher_del_cluster_us2(): void
    {
        $respuesta = (new SecurityHeaders())->handle(Request::create('/'), fn () => new Response('ok'));
        $csp = $respuesta->headers->get('Content-Security-Policy');

        // El código usa new Pusher(..., { cluster: 'us2' }): sin esto se bloquea el tiempo real
        $this->assertMatchesRegularExpression("/connect-src[^;]*https:\/\/sockjs-us2\.pusher\.com/", $csp);
        $this->assertMatchesRegularExpression("/connect-src[^;]*wss:\/\/ws-us2\.pusher\.com/", $csp);
    }
}
