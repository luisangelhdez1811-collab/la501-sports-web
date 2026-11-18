<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Analytics;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * robots.txt, sitemap.xml, llms.txt, Search Console, datos del negocio para Google, Google Analytics solo
 * con permiso, CORS restringido y el modo debug apagado en producción.
 */
class SeoYAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    // ── Buscadores ──────────────────────────────────────────────────────────────────────────

    public function test_robots_fuera_de_produccion_no_deja_indexar_nada(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee("Disallow: /\n", false);
    }

    public function test_robots_en_produccion_oculta_las_zonas_privadas_y_enlaza_el_sitemap(): void
    {
        $this->app['env'] = 'production';
        $robots = Seo::robots();

        foreach (['/admin', '/cocina', '/mesero', '/api/', '/perfil', '/checkout', '/pedido/'] as $privada) {
            $this->assertStringContainsString("Disallow: {$privada}\n", $robots);
        }
        $this->assertStringContainsString('Sitemap: ' . url('sitemap.xml'), $robots);
        $this->assertStringNotContainsString("Disallow: /\n", $robots);
    }

    public function test_sitemap_lista_solo_paginas_publicas(): void
    {
        $xml = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'El sitemap no es XML válido');
        $this->assertStringContainsString('<loc>' . route('menu') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . route('reservaciones') . '</loc>', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertStringNotContainsString('/perfil', $xml);
    }

    public function test_llms_txt_resume_el_negocio_con_sus_enlaces(): void
    {
        Setting::updateOrCreate(['key' => 'schedule_lunes'], ['value' => '1:00 PM – 10:30 PM']);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('# La 501 Sports Restaurant', false)
            ->assertSee('771 109 7827')
            ->assertSee('(' . route('menu') . ')', false)
            ->assertSee('Lunes: 1:00 PM – 10:30 PM')
            ->assertDontSee('/admin');
    }

    public function test_datos_del_negocio_para_google_en_inicio_con_horario(): void
    {
        foreach (['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'] as $dia) {
            Setting::updateOrCreate(['key' => "schedule_{$dia}"], ['value' => '1:00 PM – 10:30 PM']);
        }

        $schema = Seo::restaurantSchema();
        $this->assertSame('Restaurant', $schema['@type']);
        $this->assertSame('13:00', $schema['openingHoursSpecification'][0]['opens']);
        $this->assertSame('22:30', $schema['openingHoursSpecification'][0]['closes']);
        $this->assertCount(7, $schema['openingHoursSpecification'][0]['dayOfWeek']);

        $this->get('/')->assertOk()->assertSee('application/ld+json', false)->assertSee('"@type":"Restaurant"', false);
    }

    public function test_verificacion_de_search_console_solo_si_esta_configurada_y_escapada(): void
    {
        $this->get('/contacto')->assertDontSee('google-site-verification', false);

        config(['services.google.site_verification' => 'abc123"><script>alert(1)</script>']);
        $this->get('/contacto')
            ->assertSee('name="google-site-verification"', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ── Google Analytics solo con permiso ────────────────────────────────────────────────────

    public function test_sin_analytics_el_aviso_es_informativo_y_la_csp_no_abre_google(): void
    {
        $r = $this->get('/menu')->assertOk();

        $r->assertSee('data-cookies="entendido"', false)->assertDontSee('data-cookies="aceptar"', false);
        $r->assertDontSee('data-abrir-cookies class', false);
        $this->assertStringNotContainsString('googletagmanager', $r->headers->get('Content-Security-Policy'));
    }

    public function test_con_analytics_pide_aceptar_o_rechazar_y_no_carga_nada_antes(): void
    {
        config(['services.google.analytics_id' => 'G-ABC123XYZ']);

        $r = $this->get('/menu')->assertOk();

        $r->assertSee('data-cookies="aceptar"', false)
            ->assertSee('data-cookies="rechazar"', false)
            ->assertSee('data-abrir-cookies class', false);
        // gtag.js nunca va en el HTML: el script lo agrega solo después de «Aceptar»
        $r->assertDontSee('<script async src="https://www.googletagmanager.com', false);
        $this->assertStringContainsString('https://www.googletagmanager.com', $r->headers->get('Content-Security-Policy'));
    }

    public function test_un_id_de_analytics_con_formato_raro_se_ignora(): void
    {
        config(['services.google.analytics_id' => 'G-1234"></script><script>alert(1)//']);
        $this->assertNull(Analytics::id());

        config(['services.google.analytics_id' => 'UA-12345-1']);
        $this->assertNull(Analytics::id());
    }

    public function test_analytics_nunca_mide_el_panel_ni_al_personal_ni_paginas_con_datos(): void
    {
        config(['services.google.analytics_id' => 'G-ABC123XYZ']);

        $this->assertTrue(Analytics::allowedOn(Request::create('/menu')));
        foreach (['/admin', '/cocina', '/mesero/mesas', '/pedido/15', '/perfil', '/checkout', '/newsletter/confirmar/x'] as $ruta) {
            $this->assertFalse(Analytics::allowedOn(Request::create($ruta)), $ruta);
        }

        $mesero = User::create(['name' => 'Mesero', 'email' => 'm@la501.test', 'password' => 'La501Sports!', 'role' => 'empleado']);
        $peticion = Request::create('/menu');
        $peticion->setUserResolver(fn () => $mesero);
        $this->assertFalse(Analytics::allowedOn($peticion));
    }

    // ── CORS y modo debug ────────────────────────────────────────────────────────────────────

    public function test_cors_no_acepta_cualquier_sitio(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));

        config(['cors.allowed_origins' => ['https://la501.test']]);

        $ajeno = $this->call('OPTIONS', '/api/v1/public/info', [], [], [], [
            'HTTP_ORIGIN' => 'https://sitio-malicioso.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        $this->assertNotSame('https://sitio-malicioso.example', $ajeno->headers->get('Access-Control-Allow-Origin'), json_encode($ajeno->headers->all()));
        $this->assertNotSame('*', $ajeno->headers->get('Access-Control-Allow-Origin'));

        $propio = $this->call('OPTIONS', '/api/v1/public/info', [], [], [], [
            'HTTP_ORIGIN' => 'https://la501.test',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        $this->assertSame('https://la501.test', $propio->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_en_produccion_el_modo_debug_se_apaga_aunque_el_env_lo_encienda(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);

        $metodo = new \ReflectionMethod(AppServiceProvider::class, 'configureProduction');
        $metodo->invoke(new AppServiceProvider($this->app));

        $this->assertFalse(config('app.debug'));
    }
}
