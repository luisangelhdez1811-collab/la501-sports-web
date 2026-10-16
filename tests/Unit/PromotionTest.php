<?php

namespace Tests\Unit;

use App\Models\Promotion;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sin_dias_configurados_aplica_todos_los_dias(): void
    {
        $promo = new Promotion(['days_active' => null]);

        $this->assertTrue($promo->isActiveToday());
        $this->assertSame('Todos los días', $promo->activeDaysLabel());
    }

    public function test_solo_aplica_en_los_dias_indicados(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 15, 12)); // jueves

        $this->assertTrue((new Promotion(['days_active' => 'jueves']))->isActiveToday());
        $this->assertTrue((new Promotion(['days_active' => 'martes, jueves']))->isActiveToday());
        $this->assertFalse((new Promotion(['days_active' => 'lunes,martes']))->isActiveToday());
    }

    public function test_muestra_los_dias_en_texto_legible(): void
    {
        $this->assertSame('Miércoles', (new Promotion(['days_active' => 'miercoles']))->activeDaysLabel());
        $this->assertSame(
            'Viernes, Sábado y Domingo',
            (new Promotion(['days_active' => 'viernes,sabado,domingo']))->activeDaysLabel()
        );
    }
}
