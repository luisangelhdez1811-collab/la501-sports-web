<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoSubscriber;
use App\Models\Setting;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\ProviderInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * B-24 («Confirmado», no «Pagado»), B-26 (ningún logro premia el alcohol y las reglas de los puntos están
 * publicadas), B-29 (quién vende y cómo reclamar antes de pagar) y B-33 (sin permiso para SMS inexistentes
 * y baja de un clic en los correos).
 */
class ProveedorPuntosYPromosTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): User
    {
        return User::create([
            'name' => 'Ana Prueba', 'email' => 'ana@la501.test', 'password' => 'La501Sports!',
            'telefono' => '5512345678', 'role' => 'cliente', 'is_active' => 1,
        ]);
    }

    private function pedido(User $dueno, array $extra = []): Order
    {
        $pedido = Order::create(array_merge([
            'user_id' => $dueno->id, 'customer_name' => 'Ana', 'customer_phone' => '5512345678', 'customer_address' => 'Calle 1',
            'total' => 100, 'status' => 'paid', 'payment_method' => 'efectivo',
        ], $extra));

        return $pedido;
    }

    // ── B-24 ─────────────────────────────────────────────────────────────────────────────────

    public function test_un_pedido_en_efectivo_confirmado_se_ve_como_confirmado_no_como_pagado(): void
    {
        $ana = $this->cliente();
        $pedido = $this->pedido($ana);
        $pedido->items()->create(['product_name' => 'Hamburguesa', 'station' => 'cocina', 'quantity' => 1, 'price' => 100, 'subtotal' => 100]);

        $this->assertSame('Confirmado', $pedido->statusLabel());

        $perfil = $this->actingAs($ana)->get(route('perfil'))->assertOk();
        $perfil->assertSee('Confirmado', false);
        $this->assertStringNotContainsString('>Pagado<', $perfil->getContent());

        $this->withSession(['order_lookup_verified_phone' => '5512345678', 'order_lookup_verified_until' => now()->addMinutes(10)])
            ->get(route('order.lookup.results'))
            ->assertOk()
            ->assertSee('Confirmado', false)
            ->assertDontSee('Pagado', false);
    }

    // ── B-26 ─────────────────────────────────────────────────────────────────────────────────

    private function logros(): void
    {
        foreach (['fan_bar', 'explorador', 'fan_hamburguesas'] as $slug) {
            Achievement::create(['slug' => $slug, 'nombre' => $slug, 'descripcion' => 'x', 'icono' => 'x', 'categoria' => 'variedad', 'requisito' => 1]);
        }
    }

    private function cincoCervezas(User $ana): void
    {
        $cerveza = Product::create(['name' => 'Cerveza Corona', 'price' => 45, 'category' => 'Cervecería', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna']);
        $pedido = $this->pedido($ana);
        for ($i = 0; $i < 5; $i++) {
            $pedido->items()->create(['product_id' => $cerveza->id, 'product_name' => 'Cerveza Corona', 'station' => 'ninguna', 'quantity' => 1, 'price' => 45, 'subtotal' => 45]);
        }
    }

    public function test_pedir_cinco_cervezas_no_desbloquea_ningun_logro(): void
    {
        $this->logros();
        $ana = $this->cliente();
        $this->cincoCervezas($ana);

        (new AchievementService())->check($ana);

        $this->assertSame(0, $ana->achievements()->count());
    }

    public function test_las_bebidas_con_alcohol_no_cuentan_para_ser_explorador(): void
    {
        $this->logros();
        $ana = $this->cliente();
        $pedido = $this->pedido($ana);

        $categorias = [['Hamburguesas', false], ['Alitas', false], ['Cervecería', true], ['Coctelería', true], ['Destilados', true]];
        foreach ($categorias as $i => [$categoria, $alcohol]) {
            $producto = Product::create(['name' => "Producto {$i}", 'price' => 50, 'category' => $categoria, 'available' => 1, 'alcoholic' => $alcohol]);
            $pedido->items()->create(['product_id' => $producto->id, 'product_name' => "Producto {$i}", 'station' => 'cocina', 'quantity' => 1, 'price' => 50, 'subtotal' => 50]);
        }

        (new AchievementService())->check($ana);
        $this->assertFalse($ana->achievements()->where('slug', 'explorador')->exists(), 'solo 2 categorías sin alcohol');

        foreach ([['Postres', false], ['Bebidas sin alcohol', false]] as $i => [$categoria, $alcohol]) {
            $producto = Product::create(['name' => "Extra {$i}", 'price' => 30, 'category' => $categoria, 'available' => 1, 'alcoholic' => $alcohol]);
            $pedido->items()->create(['product_id' => $producto->id, 'product_name' => "Extra {$i}", 'station' => 'cocina', 'quantity' => 1, 'price' => 30, 'subtotal' => 30]);
        }

        (new AchievementService())->check($ana);
        $this->assertTrue($ana->achievements()->where('slug', 'explorador')->exists(), 'con 4 categorías sin alcohol sí');
    }

    public function test_la_migracion_borra_fan_bar_y_se_lo_quita_a_quien_lo_tenia(): void
    {
        $this->logros();
        $ana = $this->cliente();
        $fanBar = Achievement::where('slug', 'fan_bar')->first();
        $otro = Achievement::where('slug', 'explorador')->first();
        $ana->achievements()->attach([$fanBar->id => ['unlocked_at' => now()], $otro->id => ['unlocked_at' => now()]]);

        (require base_path('database/migrations/2026_11_05_000400_remove_fan_bar_achievement.php'))->up();

        $this->assertNull(Achievement::where('slug', 'fan_bar')->first());
        $this->assertSame(['explorador'], $ana->achievements()->pluck('slug')->all());
    }

    public function test_el_perfil_ya_no_describe_fan_bar(): void
    {
        $this->actingAs($this->cliente())->get(route('perfil'))->assertOk()->assertDontSee('Ordena del bar 5 veces', false);
    }

    public function test_info_dice_que_los_puntos_no_se_canjean_y_enlaza_las_reglas(): void
    {
        $loyalty = $this->getJson('/api/v1/public/info')->assertOk()->json('data.loyalty');

        $this->assertFalse($loyalty['redeemable']);
        $this->assertStringEndsWith('/terminos-y-condiciones#programa-de-puntos', $loyalty['rules_url']);
    }

    public function test_los_terminos_publican_las_reglas_de_los_puntos(): void
    {
        $this->get(route('terminos-condiciones'))
            ->assertOk()
            ->assertSee('id="programa-de-puntos"', false)
            ->assertSee('Programa de puntos y logros', false)
            ->assertSee('no se pueden canjear', false)
            ->assertSee('Se pierden al eliminar su cuenta', false)
            ->assertSee('se cancela o se devuelve', false);
    }

    // ── B-29 ─────────────────────────────────────────────────────────────────────────────────

    private function alCarrito(): void
    {
        $p = Product::create(['name' => 'Hamburguesa Clásica', 'price' => 195, 'category' => 'Hamburguesas', 'available' => 1, 'station' => 'cocina']);
        $this->postJson(route('cart.add'), ['id' => $p->id, 'quantity' => 1])->assertOk();
    }

    public function test_el_checkout_sin_sesion_muestra_quien_vende_y_como_quejarse(): void
    {
        $this->alCarrito();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Vende: La 501 Sports Restaurant (Andrés Rivera Jiménez)', false)
            ->assertSee('Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hgo.', false)
            ->assertSee('Tel. 771 109 7827', false)
            ->assertSee('Quejas: Equipolapapa501@gmail.com, de 1:00 p.m. a 10:00 p.m.', false)
            ->assertSee(route('terminos-condiciones'), false)
            ->assertSee(route('aviso-privacidad'), false);
    }

    public function test_los_datos_del_proveedor_salen_de_settings_y_el_rfc_aparece_cuando_se_agrega(): void
    {
        Setting::updateOrCreate(['key' => 'provider_phone'], ['value' => '771 000 1111']);
        Setting::updateOrCreate(['key' => 'provider_rfc'], ['value' => 'RIJA800101AB1']);
        $this->alCarrito();

        $this->get(route('checkout.index'))->assertSee('Tel. 771 000 1111 · RFC RIJA800101AB1', false);
        $this->assertSame('RIJA800101AB1', ProviderInfo::get()['rfc']);
    }

    public function test_sin_rfc_no_se_imprime_nada_de_rfc(): void
    {
        $this->assertNull(ProviderInfo::get()['rfc']);
        $this->assertStringNotContainsString('RFC', ProviderInfo::line());
    }

    public function test_info_trae_el_bloque_provider(): void
    {
        $this->getJson('/api/v1/public/info')
            ->assertOk()
            ->assertJsonPath('data.provider.name', 'La 501 Sports Restaurant')
            ->assertJsonPath('data.provider.owner', 'Andrés Rivera Jiménez')
            ->assertJsonPath('data.provider.phone', '771 109 7827')
            ->assertJsonPath('data.provider.email', 'Equipolapapa501@gmail.com')
            ->assertJsonPath('data.provider.rfc', null);
    }

    public function test_el_ticket_de_seguimiento_trae_los_datos_del_proveedor(): void
    {
        $pedido = $this->pedido($this->cliente());

        $this->get(route('order.track', $pedido->trackingToken()))->assertOk()->assertSee('Vende: La 501 Sports Restaurant', false);
    }

    public function test_la_migracion_siembra_los_datos_sin_pisar_los_que_ya_existen(): void
    {
        Setting::updateOrCreate(['key' => 'provider_phone'], ['value' => '771 000 1111']);
        DB::table('settings')->where('key', 'provider_email')->delete();

        (require base_path('database/migrations/2026_11_05_000300_seed_provider_info_settings.php'))->up();

        $this->assertSame('771 000 1111', Setting::get('provider_phone'));
        $this->assertSame('Equipolapapa501@gmail.com', Setting::get('provider_email'));
    }

    // ── B-33 ─────────────────────────────────────────────────────────────────────────────────

    public function test_la_api_ya_no_ofrece_ni_acepta_promos_sms(): void
    {
        $ana = $this->cliente();
        Sanctum::actingAs($ana);

        $this->getJson('/api/v1/app/profile/privacy')->assertOk()->assertJsonMissingPath('data.promos_sms');

        // Una app vieja que lo manda: se ignora, no truena y no se guarda
        $this->putJson('/api/v1/app/profile/privacy', ['promos_sms' => true, 'promos_correo' => true])
            ->assertOk()
            ->assertJsonMissingPath('data.promos_sms');
        $this->assertFalse((bool) $ana->fresh()->promos_sms);

        // Si solo manda lo que ya no existe, no hay nada que cambiar
        $this->putJson('/api/v1/app/profile/privacy', ['promos_sms' => true])->assertStatus(422);
    }

    public function test_la_migracion_apaga_los_promos_sms_guardados(): void
    {
        $ana = $this->cliente();
        DB::table('users')->where('id', $ana->id)->update(['promos_sms' => true]);

        (require base_path('database/migrations/2026_11_05_000500_clear_promos_sms_preference.php'))->up();

        $this->assertFalse((bool) $ana->fresh()->promos_sms);
    }

    public function test_el_enlace_de_baja_funciona_sin_iniciar_sesion_y_aplica_de_inmediato(): void
    {
        $suscriptor = PromoSubscriber::create(['email' => 'ana@la501.test', 'unsubscribe_token' => str_repeat('b', 48)]);

        $this->get(route('newsletter.unsubscribe', $suscriptor->unsubscribe_token))->assertOk();

        $this->assertNull(PromoSubscriber::find($suscriptor->id));
    }

    public function test_el_correo_de_promociones_trae_baja_y_el_domicilio_del_restaurante(): void
    {
        $html = view('emails.promotion_notification', $this->datosCorreo())->render();

        $this->assertStringContainsString(route('newsletter.unsubscribe', 'token-de-prueba'), $html);
        $this->assertStringContainsString('La 501 Sports Restaurant · Plaza de la Revolución Mexicana #16', $html);
    }

    private function datosCorreo(): array
    {
        $promo = new \App\Models\Promotion(['title' => 'Combo', 'description' => 'x', 'price_text' => '$100', 'price' => 100, 'active' => 1]);

        return ['promotion' => $promo, 'esNueva' => true, 'unsubscribeToken' => 'token-de-prueba'];
    }
}
