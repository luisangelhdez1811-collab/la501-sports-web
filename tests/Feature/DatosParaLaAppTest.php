<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B-17: lo que la app lee del servidor en lugar de tenerlo fijo en su código (cuánto se cobra el envío,
 * los documentos legales y qué platillos llevan alcohol) y B-14: la página pública para eliminar la cuenta.
 */
class DatosParaLaAppTest extends TestCase
{
    use RefreshDatabase;

    // ── /public/info ─────────────────────────────────────────────────────────────────────────

    public function test_info_trae_el_envio_y_los_enlaces_legales(): void
    {
        $this->getJson('/api/v1/public/info')
            ->assertOk()
            ->assertJsonPath('data.delivery.fee_included_in_total', false)
            ->assertJsonPath('data.delivery.paid_to', 'repartidor')
            ->assertJsonStructure(['data' => ['delivery' => ['fee_note'], 'legal' => ['privacy_url', 'terms_url', 'delete_account_url']]]);
    }

    public function test_el_texto_del_envio_de_info_es_el_del_panel(): void
    {
        $nuevo = 'Centro $30 · colonias $40 · comunidades cercanas $50.';
        Setting::updateOrCreate(['key' => 'delivery_fee_note'], ['value' => $nuevo]);

        $this->getJson('/api/v1/public/info')->assertJsonPath('data.delivery.fee_note', $nuevo);
    }

    public function test_los_enlaces_legales_son_urls_completas_a_paginas_que_cargan(): void
    {
        $legal = $this->getJson('/api/v1/public/info')->json('data.legal');

        $this->assertStringEndsWith('/aviso-de-privacidad', $legal['privacy_url']);
        $this->assertStringEndsWith('/terminos-y-condiciones', $legal['terms_url']);
        $this->assertStringEndsWith('/eliminar-cuenta', $legal['delete_account_url']);

        foreach ($legal as $url) {
            $this->assertMatchesRegularExpression('#^https?://#', $url);
        }
    }

    public function test_en_produccion_los_enlaces_son_https(): void
    {
        config(['app.url' => 'http://lavender-ram-537394.hostingersite.com']);
        $this->app['env'] = 'production';

        $legal = $this->getJson('/api/v1/public/info')->json('data.legal');

        foreach ($legal as $url) {
            $this->assertStringStartsWith('https://lavender-ram-537394.hostingersite.com/', $url);
        }
    }

    // ── alcoholic en productos y promociones ─────────────────────────────────────────────────

    private function productos(): array
    {
        return [
            'hamburguesa' => Product::create(['name' => 'Hamburguesa Clásica', 'price' => 195, 'category' => 'Hamburguesas', 'available' => 1, 'station' => 'cocina']),
            'cerveza' => Product::create(['name' => 'Cerveza Corona', 'price' => 45, 'category' => 'Cervecería', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna']),
            'cero' => Product::create(['name' => 'Corona Cero', 'price' => 45, 'category' => 'Cervecería', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna']),
        ];
    }

    public function test_los_productos_publicos_dicen_si_llevan_alcohol(): void
    {
        $this->productos();

        $productos = collect($this->getJson('/api/v1/public/products')->assertOk()->json('data'))->keyBy('name');

        $this->assertTrue($productos['Cerveza Corona']['alcoholic']);
        $this->assertFalse($productos['Corona Cero']['alcoholic']);
        $this->assertFalse($productos['Hamburguesa Clásica']['alcoholic']);
    }

    public function test_el_detalle_de_un_producto_de_la_app_dice_si_lleva_alcohol(): void
    {
        $p = $this->productos();

        $this->getJson('/api/v1/app/products/' . $p['cerveza']->id)->assertOk()->assertJsonPath('data.alcoholic', true);
        $this->getJson('/api/v1/app/products/' . $p['hamburguesa']->id)->assertOk()->assertJsonPath('data.alcoholic', false);
    }

    public function test_una_promocion_lleva_alcohol_si_alguno_de_sus_platillos_lo_lleva(): void
    {
        $p = $this->productos();

        $conCerveza = Promotion::create(['title' => 'Combo Cheve', 'description' => 'x', 'price_text' => '$200', 'price' => 200, 'active' => 1, 'sellable_online' => true]);
        $conCerveza->products()->attach([$p['hamburguesa']->id => ['quantity' => 1, 'slot' => 0], $p['cerveza']->id => ['quantity' => 1, 'slot' => 1]]);

        // Una de las opciones a elegir lleva alcohol: ya cuenta
        $aElegir = Promotion::create(['title' => 'Combo Elige', 'description' => 'x', 'price_text' => '$150', 'price' => 150, 'active' => 1, 'sellable_online' => true]);
        $aElegir->products()->attach([$p['cerveza']->id => ['quantity' => 1, 'slot' => 0], $p['cero']->id => ['quantity' => 1, 'slot' => 0]]);

        $sinAlcohol = Promotion::create(['title' => 'Combo Familiar', 'description' => 'x', 'price_text' => '$300', 'price' => 300, 'active' => 1, 'sellable_online' => true]);
        $sinAlcohol->products()->attach([$p['hamburguesa']->id => ['quantity' => 2, 'slot' => 0], $p['cero']->id => ['quantity' => 1, 'slot' => 1]]);

        $promos = collect($this->getJson('/api/v1/public/promotions')->assertOk()->json('data'))->keyBy('title');

        $this->assertTrue($promos['Combo Cheve']['alcoholic']);
        $this->assertTrue($promos['Combo Elige']['alcoholic']);
        $this->assertFalse($promos['Combo Familiar']['alcoholic']);

        // Dentro de cada renglón también se sabe cuál opción lleva alcohol
        $opciones = collect($promos['Combo Elige']['slots'][0]['products'])->keyBy('name');
        $this->assertTrue($opciones['Cerveza Corona']['alcoholic']);
        $this->assertFalse($opciones['Corona Cero']['alcoholic']);
    }

    // ── B-14: eliminar cuenta ────────────────────────────────────────────────────────────────

    public function test_eliminar_cuenta_es_publica_y_explica_los_tres_caminos(): void
    {
        $this->get(route('eliminar-cuenta'))
            ->assertOk()
            ->assertSee('Desde la app', false)
            ->assertSee('Desde el sitio web', false)
            ->assertSee('Por correo', false)
            ->assertSee('Equipolapapa501@gmail.com', false)
            ->assertSee('Qué se borra', false)
            ->assertSee('Qué se anonimiza', false)
            ->assertSee('Qué se conserva', false)
            ->assertSee('5 años', false);
    }

    public function test_los_terminos_enlazan_la_pagina_de_eliminar_cuenta(): void
    {
        $this->get(route('terminos-condiciones'))->assertOk()->assertSee(route('eliminar-cuenta'), false);
    }
}
