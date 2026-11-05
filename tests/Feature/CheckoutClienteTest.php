<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkout de la app (POST /api/v1/app/checkout): folio contra pedidos duplicados (B-03),
 * ingredientes excluidos por nombre o id (B-06), pedidos ligados a la cuenta con JWT (B-09)
 * y texto normal que el filtro de seguridad no debe bloquear (B-05).
 */
class CheckoutClienteTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/app';

    private Product $hamburguesa;
    private Ingrediente $cebolla;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hamburguesa = Product::create(['name' => 'Hamburguesa Clásica', 'price' => 195, 'category' => 'Hamburguesas', 'available' => 1]);
        $this->cebolla = Ingrediente::create(['product_id' => $this->hamburguesa->id, 'nombre' => 'Cebolla']);
        Ingrediente::create(['product_id' => $this->hamburguesa->id, 'nombre' => 'Jitomate']);
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1]],
            'customer_name' => 'Ana Prueba',
            'customer_phone' => '5512345678',
            'customer_address' => 'Calle 1, Col. Centro',
            'payment_method' => 'efectivo',
        ], $extra);
    }

    private function cliente(string $correo = 'cliente@la501.test'): User
    {
        // La tabla users no tiene email_verified_at, por eso se crea sin la factory
        return User::create([
            'name' => 'Cliente', 'email' => $correo, 'password' => 'La501Sports!',
            'telefono' => '5512345678', 'role' => 'cliente', 'is_active' => 1,
        ]);
    }

    private function conSesionJwt(string $correo)
    {
        $token = $this->postJson(self::API . '/auth/login', ['email' => $correo, 'password' => 'La501Sports!'])
            ->assertOk()->json('access_token');

        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    // ── B-03: folio client_ref ───────────────────────────────────────────────────────────────

    public function test_el_mismo_folio_devuelve_el_mismo_pedido_y_no_crea_otro(): void
    {
        $datos = $this->datos(['client_ref' => 'app_folio_0001']);

        $primero = $this->postJson(self::API . '/checkout', $datos)->assertCreated();
        $this->assertNull($primero->json('duplicate'));

        $segundo = $this->postJson(self::API . '/checkout', $datos)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('duplicate', true);

        $this->assertSame($primero->json('order.id'), $segundo->json('order.id'));
        $this->assertSame($primero->json('order.tracking_token'), $segundo->json('order.tracking_token'));
        $this->assertSame(1, Order::count());
    }

    public function test_dos_pedidos_con_folios_distintos_si_se_crean(): void
    {
        $this->postJson(self::API . '/checkout', $this->datos(['client_ref' => 'app_folio_0001']))->assertCreated();
        $this->postJson(self::API . '/checkout', $this->datos(['client_ref' => 'app_folio_0002']))->assertCreated();

        $this->assertSame(2, Order::count());
    }

    public function test_un_folio_que_pertenece_a_otra_cuenta_responde_409(): void
    {
        $ana = $this->cliente('ana@la501.test');
        $beto = $this->cliente('beto@la501.test');

        $this->conSesionJwt('ana@la501.test')
            ->postJson(self::API . '/checkout', $this->datos(['client_ref' => 'app_folio_0001']))
            ->assertCreated();

        $this->conSesionJwt('beto@la501.test')
            ->postJson(self::API . '/checkout', $this->datos(['client_ref' => 'app_folio_0001']))
            ->assertStatus(409)
            ->assertJsonPath('error', 'duplicate_reference');

        $this->assertSame(1, Order::count());
        $this->assertSame($ana->id, Order::first()->user_id);
        $this->assertNotSame($beto->id, Order::first()->user_id);
    }

    public function test_un_invitado_con_otro_telefono_no_reutiliza_el_folio_de_alguien_mas(): void
    {
        $this->postJson(self::API . '/checkout', $this->datos(['client_ref' => 'app_folio_0001']))->assertCreated();

        $this->postJson(self::API . '/checkout', $this->datos(['client_ref' => 'app_folio_0001', 'customer_phone' => '7711112222']))
            ->assertStatus(409)
            ->assertJsonPath('error', 'duplicate_reference');

        // Mismo teléfono con otro formato: es el mismo invitado reintentando
        $this->postJson(self::API . '/checkout', $this->datos(['client_ref' => 'app_folio_0001', 'customer_phone' => '55 1234 5678']))
            ->assertOk()
            ->assertJsonPath('duplicate', true);
    }

    public function test_un_folio_con_formato_invalido_se_rechaza(): void
    {
        foreach (['corto', 'tiene espacios en medio', str_repeat('a', 65), 'caracter$raro_123'] as $folio) {
            $this->postJson(self::API . '/checkout', $this->datos(['client_ref' => $folio]))->assertStatus(422);
        }
        $this->assertSame(0, Order::count());
    }

    public function test_sin_folio_el_pedido_se_crea_como_siempre(): void
    {
        $this->postJson(self::API . '/checkout', $this->datos())->assertCreated();
        $this->postJson(self::API . '/checkout', $this->datos())->assertCreated();

        $this->assertSame(2, Order::count());
    }

    // ── B-06: ingredientes excluidos por nombre o por id ─────────────────────────────────────

    public function test_excluir_por_nombre_o_por_id_guarda_el_nombre(): void
    {
        $this->postJson(self::API . '/checkout', $this->datos([
            'client_ref' => 'app_folio_por_id',
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1, 'excluded_ingredients' => [$this->cebolla->id]]],
        ]))->assertCreated();

        $this->postJson(self::API . '/checkout', $this->datos([
            'client_ref' => 'app_folio_por_nombre',
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1, 'excluded_ingredients' => ['Cebolla']]],
        ]))->assertCreated();

        foreach (Order::with('items')->get() as $pedido) {
            $this->assertSame(['Cebolla'], $pedido->items->first()->excluded_ingredients);
        }
    }

    public function test_el_nombre_se_reconoce_sin_importar_mayusculas_y_se_normaliza(): void
    {
        $this->postJson(self::API . '/checkout', $this->datos([
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1, 'excluded_ingredients' => [' cebolla ', 'JITOMATE', 'Cebolla']]],
        ]))->assertCreated();

        $this->assertSame(['Cebolla', 'Jitomate'], Order::first()->items->first()->excluded_ingredients);
    }

    public function test_un_id_de_otro_producto_se_ignora_y_un_nombre_desconocido_se_conserva(): void
    {
        $otro = Product::create(['name' => 'Papas', 'price' => 50, 'category' => 'Entradas', 'available' => 1]);
        $ajeno = Ingrediente::create(['product_id' => $otro->id, 'nombre' => 'Sal']);

        $this->postJson(self::API . '/checkout', $this->datos([
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1, 'excluded_ingredients' => [$ajeno->id, 99999, 'Pepinillos']]],
        ]))->assertCreated();

        // El id ajeno y el inexistente no sirven; el nombre que no está en la lista se respeta (puede ser una alergia)
        $this->assertSame(['Pepinillos'], Order::first()->items->first()->excluded_ingredients);
    }

    // ── B-09: pedido con sesión JWT queda en la cuenta ───────────────────────────────────────

    public function test_un_pedido_de_la_app_con_jwt_queda_ligado_a_la_cuenta(): void
    {
        $cliente = $this->cliente();

        $this->conSesionJwt('cliente@la501.test')
            ->postJson(self::API . '/checkout', $this->datos())
            ->assertCreated();

        $pedido = Order::first();
        $this->assertSame($cliente->id, $pedido->user_id);
        // Y por eso gana los puntos del pedido (1 por cada $10)
        $this->assertSame(19, (int) $cliente->fresh()->points);
    }

    public function test_un_pedido_sin_sesion_sigue_siendo_de_invitado(): void
    {
        $this->postJson(self::API . '/checkout', $this->datos())->assertCreated();

        $this->assertNull(Order::first()->user_id);
    }

    // ── B-05: texto normal en la dirección y las notas ───────────────────────────────────────

    public function test_una_direccion_en_la_colonia_la_union_crea_el_pedido(): void
    {
        $this->postJson(self::API . '/checkout', $this->datos([
            'customer_address' => 'Calle Unión 12, Col. La Unión',
            'items' => [['product_id' => $this->hamburguesa->id, 'quantity' => 1, 'notes' => 'Selecciona la más grande']],
        ]))->assertCreated();

        $this->assertSame('Calle Unión 12, Col. La Unión', Order::first()->customer_address);
    }
}
