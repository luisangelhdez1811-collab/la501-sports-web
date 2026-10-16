<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarouselSlide;

class CarouselSlideSeeder extends Seeder
{
    public function run(): void
    {
        CarouselSlide::create([
            'image_path' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?q=80&w=1200&auto=format&fit=crop',
            'subtitle' => 'Gourmet & Grill',
            'title' => 'SABOR INIGUALABLE',
            'description' => 'Las mejores hamburguesas a la parrilla y cerveza artesanal bien fría.',
            'order' => 1,
            'is_active' => true,
        ]);

        CarouselSlide::create([
            'image_path' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1200&auto=format&fit=crop',
            'subtitle' => 'La emoción del juego',
            'title' => 'PASIÓN DEPORTIVA',
            'description' => 'El mejor ambiente familiar para disfrutar de tus deportes favoritos.',
            'order' => 2,
            'is_active' => true,
        ]);

        CarouselSlide::create([
            'image_path' => 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?q=80&w=1200&auto=format&fit=crop',
            'subtitle' => 'Para picar',
            'title' => 'MOMENTOS COMPARTIDOS',
            'description' => 'Deliciosas alitas y snacks perfectos para disfrutar con amigos.',
            'order' => 3,
            'is_active' => true,
        ]);
    }
}
