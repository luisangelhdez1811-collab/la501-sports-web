<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_optimiza_automaticamente_las_imagenes_de_cloudinary(): void
    {
        $producto = new Product(['image' => 'https://res.cloudinary.com/la501/image/upload/v1/alitas.jpg']);

        $this->assertSame('https://res.cloudinary.com/la501/image/upload/f_auto,q_auto/v1/alitas.jpg', $producto->image);
    }

    public function test_no_modifica_imagenes_ya_optimizadas_ni_externas(): void
    {
        $optimizada = 'https://res.cloudinary.com/la501/image/upload/f_auto,q_auto/v1/alitas.jpg';
        $externa = 'https://images.unsplash.com/photo-123.jpg';

        $this->assertSame($optimizada, (new Product(['image' => $optimizada]))->image);
        $this->assertSame($externa, (new Product(['image' => $externa]))->image);
        $this->assertNull((new Product(['image' => null]))->image);
    }
}
