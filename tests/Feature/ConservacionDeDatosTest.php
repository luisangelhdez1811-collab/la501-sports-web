<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Borrado automático por plazos (B-31), origen de las imágenes (B-36) y alcohol fuera de las novedades
 * de Deportes (B-25).
 */
class ConservacionDeDatosTest extends TestCase
{
    use RefreshDatabase;

    private function pedido(string $status, int $diasAtras): Order
    {
        $pedido = Order::create([
            'customer_name' => 'Ana', 'customer_phone' => '1234567890', 'customer_address' => 'Calle 1',
            'total' => 100, 'status' => $status, 'payment_method' => 'efectivo',
            'delivery_lat' => 21.14, 'delivery_lng' => -98.42,
        ]);
        Order::whereKey($pedido->id)->update(['created_at' => now()->subDays($diasAtras), 'updated_at' => now()->subDays($diasAtras)]);

        return $pedido;
    }

    public function test_borra_la_ubicacion_de_entregas_viejas_pero_no_de_las_que_siguen_en_camino(): void
    {
        $entregado = $this->pedido('delivered', 31);
        $reciente = $this->pedido('delivered', 5);
        $confirmado = $this->pedido('paid', 31); // «Confirmado»: todavía puede ir en camino

        $this->artisan('privacy:purge')->assertSuccessful();

        $this->assertNull($entregado->fresh()->delivery_lat);
        $this->assertNotNull($reciente->fresh()->delivery_lat);
        $this->assertNotNull($confirmado->fresh()->delivery_lat);
    }

    public function test_borra_notas_reservaciones_mensajes_codigos_y_respaldos_vencidos(): void
    {
        $pedido = $this->pedido('delivered', 100);
        $item = OrderItem::create(['order_id' => $pedido->id, 'product_name' => 'Hamburguesa', 'quantity' => 1, 'price' => 100, 'subtotal' => 100, 'notes' => 'Alérgico al cacahuate', 'station' => 'cocina']);
        OrderItem::whereKey($item->id)->update(['created_at' => now()->subDays(91)]);

        Reservation::create(['nombre_completo' => 'Ana', 'telefono' => '1234567890', 'correo_electronico' => 'ana@la501.test',
            'fecha_reservacion' => now()->subDays(400)->toDateString(), 'hora_reservacion' => '20:00', 'cantidad_personas' => 2, 'zona' => 'general', 'status' => 'confirmada']);
        $mensaje = ContactMessage::create(['name' => 'Ana', 'email' => 'ana@la501.test', 'type' => 'sugerencia', 'subject' => 'Hola', 'message' => 'Hola', 'status' => 'pendiente']);
        ContactMessage::whereKey($mensaje->id)->update(['created_at' => now()->subDays(400)]);
        DB::table('password_reset_tokens')->insert(['email' => 'ana@la501.test', 'token' => 'x', 'created_at' => now()->subHours(2)]);

        Storage::fake('local');
        Storage::disk('local')->put('backups/viejo.sql', 'SELECT 1;');
        touch(Storage::disk('local')->path('backups/viejo.sql'), now()->subDays(31)->getTimestamp());
        Storage::disk('local')->put('backups/nuevo.sql', 'SELECT 1;');

        $this->artisan('privacy:purge')->assertSuccessful();

        $this->assertNull($item->fresh()->notes);
        $this->assertSame(0, Reservation::count());
        $this->assertSame(0, ContactMessage::count());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        $this->assertSame(['backups/nuevo.sql'], Storage::disk('local')->files('backups'));
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@la501.test', 'password' => 'La501Sports!', 'role' => 'admin']);
    }

    public function test_una_novedad_de_deportes_no_puede_hablar_de_alcohol(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.news.store'), ['title' => 'Final de la Liga', 'category' => 'Deportes', 'content' => 'Ven a ver la final con una cubeta de cerveza.'])
            ->assertSessionHasErrors('content');

        $this->post(route('admin.news.store'), ['title' => 'Final de la Liga', 'category' => 'Deportes', 'content' => 'Ven a ver la final con alitas y papas.'])
            ->assertSessionHasNoErrors();
    }

    public function test_una_imagen_nueva_pide_su_origen(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.news.store'), [
                'title' => 'Noche de karaoke', 'category' => 'Eventos', 'content' => 'Este viernes.',
                'image' => \Illuminate\Http\UploadedFile::fake()->image('karaoke.jpg'),
            ])
            ->assertSessionHasErrors('image_source');
    }
}
