<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Categorías administrables de la carta y promociones con platillos a elegir.
 */
class CategoriasYPromocionesTest extends TestCase
{
    use RefreshDatabase;

    private Product $alitas;
    private Product $boneless;
    private Product $argentina;
    private Product $cerveza;
    private Promotion $promo;

    protected function setUp(): void
    {
        parent::setUp();

        MenuCategory::create(['name' => 'Hamburguesas', 'section' => 'comidas', 'sort_order' => 2]);
        MenuCategory::create(['name' => 'Alitas', 'section' => 'comidas', 'sort_order' => 1]);
        MenuCategory::create(['name' => 'Cervecería', 'section' => 'bebidas', 'sort_order' => 3]);

        $this->alitas = Product::create(['name' => 'Alitas 700 g', 'price' => 270, 'category' => 'Alitas', 'available' => 1, 'station' => 'cocina', 'sort_order' => 1]);
        $this->boneless = Product::create(['name' => 'Hamburguesa Boneless', 'price' => 115, 'category' => 'Hamburguesas', 'available' => 1, 'station' => 'cocina', 'sort_order' => 2]);
        $this->argentina = Product::create(['name' => 'Hamburguesa Argentina', 'price' => 130, 'category' => 'Hamburguesas', 'available' => 1, 'station' => 'cocina', 'sort_order' => 3]);
        $this->cerveza = Product::create(['name' => 'Cerveza Corona', 'price' => 35, 'category' => 'Cervecería', 'section' => 'bebidas', 'available' => 1, 'station' => 'ninguna', 'sort_order' => 4]);

        // 2x Alitas fijas + 1 hamburguesa a elegir entre Boneless y Argentina
        $this->promo = Promotion::create([
            'title' => 'Combo Jueves', 'description' => 'Alitas y hamburguesa', 'price_text' => '$350', 'price' => 350,
            'active' => 1, 'sellable_online' => true,
        ]);
        $this->promo->products()->attach([
            $this->alitas->id => ['quantity' => 2, 'slot' => 0],
            $this->boneless->id => ['quantity' => 1, 'slot' => 1],
            $this->argentina->id => ['quantity' => 1, 'slot' => 1],
        ]);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@la501.test', 'password' => 'La501Sports!', 'role' => 'admin']);
    }

    // ---------------------------------------------------------------------
    // Categorías
    // ---------------------------------------------------------------------

    public function test_la_carta_sigue_el_orden_de_las_categorias(): void
    {
        $this->assertSame(
            ['Alitas 700 g', 'Hamburguesa Boneless', 'Hamburguesa Argentina', 'Cerveza Corona'],
            Product::menuOrder()->pluck('name')->all()
        );

        MenuCategory::where('name', 'Hamburguesas')->update(['sort_order' => 0]);

        $this->assertSame('Hamburguesa Boneless', Product::menuOrder()->value('name'));
    }

    public function test_el_admin_agrega_una_categoria_y_un_platillo_toma_su_seccion(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.menu.categories.store'), ['name' => 'Mocktails', 'section' => 'bebidas'])
            ->assertSessionHasNoErrors();

        $this->post(route('admin.menu.store'), [
            'name' => 'Limonada de fresa', 'price' => 55, 'category' => 'Mocktails', 'station' => 'barra', 'available' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame('bebidas', Product::where('name', 'Limonada de fresa')->value('section'));

        // Una categoría que no existe no se acepta: se agrega primero en «Categorías»
        $this->post(route('admin.menu.store'), ['name' => 'X', 'price' => 1, 'category' => 'Inventada', 'station' => 'cocina'])
            ->assertSessionHasErrors('category');
    }

    public function test_renombrar_una_categoria_mueve_sus_platillos(): void
    {
        $categoria = MenuCategory::where('name', 'Hamburguesas')->first();

        $this->actingAs($this->admin())
            ->put(route('admin.menu.categories.update', $categoria), ['name' => 'Burgers', 'section' => 'comidas'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Product::where('category', 'Burgers')->count());
        $this->assertSame(0, Product::where('category', 'Hamburguesas')->count());
    }

    public function test_quitar_una_categoria_con_platillos_pide_a_donde_pasarlos(): void
    {
        $hamburguesas = MenuCategory::where('name', 'Hamburguesas')->first();
        $alitas = MenuCategory::where('name', 'Alitas')->first();
        $this->actingAs($this->admin());

        $this->delete(route('admin.menu.categories.destroy', $hamburguesas))->assertSessionHasErrors('move_to');
        $this->assertNotNull($hamburguesas->fresh());

        $this->delete(route('admin.menu.categories.destroy', $hamburguesas), ['move_to' => $alitas->id])->assertSessionHasNoErrors();
        $this->assertNull($hamburguesas->fresh());
        $this->assertSame('Alitas', $this->boneless->fresh()->category);
    }

    public function test_subir_una_categoria_cambia_su_lugar(): void
    {
        $hamburguesas = MenuCategory::where('name', 'Hamburguesas')->first();

        $this->actingAs($this->admin())
            ->post(route('admin.menu.categories.move', $hamburguesas), ['direction' => 'up']);

        $this->assertSame(['Hamburguesas', 'Alitas'], MenuCategory::where('section', 'comidas')->ordered()->pluck('name')->all());
    }

    // ---------------------------------------------------------------------
    // Promociones con platillos a elegir
    // ---------------------------------------------------------------------

    public function test_la_promocion_dice_que_incluye(): void
    {
        $this->assertTrue($this->promo->hasChoices());
        $this->assertSame('2x Alitas 700 g · 1 a elegir: Hamburguesa Boneless o Hamburguesa Argentina', $this->promo->includesLabel());

        $this->get(route('promociones'))
            ->assertOk()
            ->assertSee('1 a elegir:', false)
            ->assertSee('Hamburguesa Argentina');
    }

    public function test_sin_elegir_el_platillo_no_se_agrega_al_carrito(): void
    {
        $this->postJson(route('cart.addPromotion'), ['promotion_id' => $this->promo->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Dos hamburguesas cuando la promoción da una
        $this->postJson(route('cart.addPromotion'), [
            'promotion_id' => $this->promo->id,
            'choices' => [1 => [$this->boneless->id, $this->argentina->id]],
        ])->assertStatus(422);

        // Un platillo que no es parte de la promoción
        $this->postJson(route('cart.addPromotion'), [
            'promotion_id' => $this->promo->id,
            'choices' => [1 => [$this->cerveza->id]],
        ])->assertStatus(422);
    }

    public function test_la_eleccion_llega_al_carrito_y_a_la_comanda(): void
    {
        $this->postJson(route('cart.addPromotion'), ['promotion_id' => $this->promo->id, 'choices' => [1 => [$this->argentina->id]]])->assertOk();
        $this->postJson(route('cart.addPromotion'), ['promotion_id' => $this->promo->id, 'choices' => [1 => [$this->boneless->id]]])->assertOk();
        $this->postJson(route('cart.addPromotion'), ['promotion_id' => $this->promo->id, 'choices' => [1 => [$this->argentina->id]]])->assertOk();

        // Elecciones distintas = líneas distintas; la misma se suma
        $carrito = collect(session('cart'));
        $this->assertCount(2, $carrito);
        $argentina = $carrito->firstWhere('promotion_includes', '2x Alitas 700 g · Hamburguesa Argentina');
        $this->assertSame(2, $argentina['quantity']);

        $this->post(route('checkout.process'), [
            'customer_name' => 'Cliente', 'customer_phone' => '4440000000', 'customer_address' => 'Calle 1', 'payment_method' => 'efectivo',
        ])->assertRedirect()->assertSessionMissing('error');

        $linea = OrderItem::where('product_name', 'Combo Jueves')->where('quantity', 2)->firstOrFail();
        $this->assertSame('Incluye: 2x Alitas 700 g, Hamburguesa Argentina', $linea->optionsSummary());
        $this->assertSame('cocina', $linea->station);
        $this->assertEquals(350, $linea->price);
    }

    public function test_la_app_elige_con_choices_y_las_versiones_anteriores_siguen_funcionando(): void
    {
        $datos = ['customer_name' => 'Cliente', 'customer_phone' => '4440000000', 'customer_address' => 'Calle 1', 'payment_method' => 'efectivo'];

        $this->postJson('/api/v1/app/checkout', $datos + ['items' => [
            ['promotion_id' => $this->promo->id, 'quantity' => 1, 'choices' => [1 => [['id' => $this->boneless->id, 'quantity' => 1]]]],
        ]])->assertSuccessful();
        $this->assertSame('Incluye: 2x Alitas 700 g, Hamburguesa Boneless', OrderItem::latest('id')->first()->optionsSummary());

        $this->postJson('/api/v1/app/checkout', $datos + ['items' => [
            ['promotion_id' => $this->promo->id, 'quantity' => 1],
        ]])->assertSuccessful();
        $this->assertStringContainsString('confirmar con el cliente', OrderItem::latest('id')->first()->optionsSummary());

        $this->getJson('/api/v1/public/promotions')
            ->assertOk()
            ->assertJsonPath('data.0.slots.1.choice', true)
            ->assertJsonCount(2, 'data.0.slots.1.products');
    }

    public function test_una_promocion_de_puras_cervezas_va_directo_al_mesero(): void
    {
        $cubeta = Promotion::create(['title' => 'Cubeta', 'description' => '6 cervezas', 'price_text' => '$180', 'price' => 180, 'active' => 1, 'sellable_online' => true]);
        $cubeta->products()->attach($this->cerveza->id, ['quantity' => 6, 'slot' => 0]);

        $linea = app(\App\Services\PromotionLineBuilder::class)->build($cubeta->fresh(), []);

        $this->assertSame('ninguna', $linea['station']);
        $this->assertSame('6x Cerveza Corona', $linea['includes']);
    }

    public function test_el_admin_guarda_los_renglones_de_la_promocion(): void
    {
        $this->actingAs($this->admin())->put(route('admin.promotions.update', $this->promo->id), [
            'title' => 'Combo Jueves', 'description' => 'Alitas y hamburguesa', 'price_text' => '$350', 'price' => 350,
            'products' => [
                ['id' => $this->alitas->id, 'quantity' => 1, 'slot' => 0],
                ['id' => $this->boneless->id, 'quantity' => 2, 'slot' => 1],
                ['id' => $this->argentina->id, 'quantity' => 2, 'slot' => 1],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Alitas 700 g · 2 a elegir: Hamburguesa Boneless o Hamburguesa Argentina', $this->promo->fresh()->includesLabel());
    }
}
