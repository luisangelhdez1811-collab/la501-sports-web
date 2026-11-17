<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\User;
use App\Services\AlcoholPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Venta y publicidad de alcohol (B-25, B-28, B-37) y promociones con vigencia y condiciones (B-27).
 */
class AlcoholYPromocionesTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app/checkout';

    private Product $cerveza;
    private Product $hamburguesa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cerveza = Product::create(['name' => 'Cerveza Corona', 'price' => 45, 'category' => 'Cervecería', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna']);
        $this->hamburguesa = Product::create(['name' => 'Hamburguesa Clásica', 'price' => 195, 'category' => 'Hamburguesas', 'available' => 1, 'station' => 'cocina']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pedido(Product $producto): array
    {
        return [
            'items' => [['product_id' => $producto->id, 'quantity' => 1]],
            'customer_name' => 'Ana Prueba', 'customer_phone' => '1234567890', 'customer_address' => 'Calle 1, Col. Centro',
            'payment_method' => 'efectivo', 'delivery_terms_accepted' => true, 'adult_confirmed' => true,
        ];
    }

    private function permitir(array $extra = []): void
    {
        foreach (['alcohol_delivery_allowed' => '1'] + $extra as $clave => $valor) {
            Setting::updateOrCreate(['key' => $clave], ['value' => $valor]);
        }
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@la501.test', 'password' => 'La501Sports!', 'role' => 'admin']);
    }

    // ---------------------------------------------------------------------
    // Venta de alcohol (B-28)
    // ---------------------------------------------------------------------

    public function test_sin_licencia_confirmada_no_se_vende_alcohol_pero_si_comida(): void
    {
        $this->postJson(self::API, $this->pedido($this->cerveza))
            ->assertStatus(422)
            ->assertJsonPath('error', 'alcohol_sales_closed');

        $this->postJson(self::API, $this->pedido($this->hamburguesa))->assertCreated();
    }

    public function test_ley_seca_cierra_la_venta_hasta_la_fecha_indicada(): void
    {
        $this->permitir(['alcohol_suspended_until' => now()->addDay()->format('Y-m-d H:i:s')]);

        $this->postJson(self::API, $this->pedido($this->cerveza))
            ->assertStatus(422)
            ->assertJsonPath('error', 'alcohol_sales_closed');

        Carbon::setTestNow(now()->addDays(2));
        $this->assertTrue(AlcoholPolicy::salesOpen());
    }

    public function test_fuera_del_horario_de_la_licencia_no_se_vende(): void
    {
        $this->permitir(['alcohol_hours' => json_encode(['jueves' => '13:00-22:00'])]);

        Carbon::setTestNow(Carbon::create(2026, 10, 15, 14, 0, 0, 'America/Mexico_City')); // jueves 2 p. m.
        $this->assertTrue(AlcoholPolicy::salesOpen());

        Carbon::setTestNow(Carbon::create(2026, 10, 15, 23, 0, 0, 'America/Mexico_City')); // jueves 11 p. m.
        $this->assertFalse(AlcoholPolicy::salesOpen());

        Carbon::setTestNow(Carbon::create(2026, 10, 16, 14, 0, 0, 'America/Mexico_City')); // viernes: no se capturó
        $this->assertFalse(AlcoholPolicy::salesOpen());
    }

    public function test_la_app_recibe_la_leyenda_y_si_hoy_se_vende(): void
    {
        $this->getJson('/api/v1/public/info')
            ->assertOk()
            ->assertJsonPath('data.alcohol.legend', AlcoholPolicy::DEFAULT_LEGEND)
            ->assertJsonPath('data.alcohol.sales_open', false);
    }

    public function test_el_admin_suspende_y_reanuda_la_venta(): void
    {
        $this->permitir();
        $this->actingAs($this->admin());

        $this->post(route('admin.settings.alcohol.suspend'), ['alcohol_suspended_until' => now()->addDays(2)->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();
        $this->assertFalse(AlcoholPolicy::salesOpen());

        $this->post(route('admin.settings.alcohol.resume'));
        $this->assertTrue(AlcoholPolicy::salesOpen());
    }

    // ---------------------------------------------------------------------
    // Promociones (B-25, B-27)
    // ---------------------------------------------------------------------

    private function datosPromo(array $extra = []): array
    {
        return array_merge([
            'title' => 'Cubeta de Corona', 'description' => '6 cervezas Corona de 355 ml.', 'price_text' => '$180', 'price' => 180,
            'end_date' => now()->addMonth()->toDateString(), 'conditions' => 'Solo en el restaurante. No acumulable.',
            'products' => [['id' => $this->cerveza->id, 'quantity' => 6, 'slot' => 0]],
        ], $extra);
    }

    public function test_una_promocion_con_alcohol_no_puede_invitar_a_beber_ni_hablar_de_deportes(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.promotions.store'), $this->datosPromo(['description' => '¡Pide tu cubeta para el partido!']))
            ->assertSessionHasErrors('description');

        $this->assertSame(0, Promotion::count());

        $this->post(route('admin.promotions.store'), $this->datosPromo())->assertSessionHasNoErrors();
        $this->assertSame(1, Promotion::count());
    }

    public function test_una_promocion_sin_vigencia_ni_condiciones_no_se_guarda(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.promotions.store'), $this->datosPromo(['end_date' => null, 'conditions' => '']))
            ->assertSessionHasErrors(['end_date', 'conditions']);
    }

    public function test_la_pagina_muestra_vigencia_condiciones_y_leyenda(): void
    {
        $promo = Promotion::create($this->datosPromo() + ['active' => 1, 'sellable_online' => true]);
        $promo->products()->attach($this->cerveza->id, ['quantity' => 6, 'slot' => 0]);

        $this->get(route('promociones'))
            ->assertOk()
            ->assertSee('Válida hasta el', false)
            ->assertSee('Solo en el restaurante. No acumulable.')
            ->assertSee(AlcoholPolicy::DEFAULT_LEGEND)
            ->assertSee(AlcoholPolicy::ADULTS_ONLY);
    }

    public function test_una_promocion_vencida_no_se_vende(): void
    {
        $promo = Promotion::create(['title' => 'Combo', 'description' => 'x', 'price_text' => '$100', 'price' => 100,
            'end_date' => now()->subDay()->toDateString(), 'active' => 1, 'sellable_online' => true]);
        $promo->products()->attach($this->hamburguesa->id, ['quantity' => 1, 'slot' => 0]);

        $this->assertFalse($promo->fresh()->isActiveToday());
        $this->postJson(self::API, array_merge($this->pedido($this->hamburguesa), ['items' => [['promotion_id' => $promo->id, 'quantity' => 1]]]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'item_unavailable');
    }

    // ---------------------------------------------------------------------
    // Correo del código de seguridad
    // ---------------------------------------------------------------------

    public function test_el_codigo_del_correo_va_en_un_solo_bloque_para_copiarlo_completo(): void
    {
        $html = view('emails.otp', ['codigo' => '12345678'])->render();
        $this->assertStringContainsString('>12345678</div>', $html);
        $this->assertStringContainsString('Plaza de la Revolución Mexicana #16', $html);
    }
}
