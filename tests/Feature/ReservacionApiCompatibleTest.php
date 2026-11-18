<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** La app ya publicada sigue pudiendo reservar aunque mande el teléfono con +52 o espacios. */
class ReservacionApiCompatibleTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre_completo' => 'Ana López',
            'telefono' => '+52 771 123 4567',
            'correo_electronico' => 'ana@example.com',
            'fecha_reservacion' => now()->addDays(2)->toDateString(),
            'hora_reservacion' => '19:00',
            'cantidad_personas' => 4,
            'zona' => 'Terraza',
        ], $cambios);
    }

    public function test_acepta_el_telefono_con_lada_y_lo_guarda_en_10_digitos(): void
    {
        Sanctum::actingAs(User::create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'La501Sports!', 'role' => 'cliente']));

        $this->postJson('/api/v1/app/reservations', $this->datos(['status' => 'confirmada']))->assertSuccessful();

        $reserva = Reservation::sole();
        $this->assertSame('7711234567', $reserva->telefono);
        $this->assertSame('pendiente', $reserva->status);
    }

    public function test_rechaza_una_zona_con_codigo(): void
    {
        Sanctum::actingAs(User::create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'La501Sports!', 'role' => 'cliente']));

        // El filtro de ataques lo corta antes (403); si no, la validación lo rechaza (422)
        $this->postJson('/api/v1/app/reservations', $this->datos(['zona' => '<script>x</script>']))
            ->assertStatus(403);
        $this->postJson('/api/v1/app/reservations', $this->datos(['zona' => 'Terraza; DROP']))
            ->assertUnprocessable();
        $this->assertSame(0, Reservation::count());
    }
}
