<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cuenta de prueba con contraseña conocida («password»): solo en la computadora de desarrollo.
        // En producción sería una puerta abierta. El dueño se crea con «php artisan owner:create».
        if (app()->environment('local', 'testing')) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $this->call([
            IngredientInventorySeeder::class,
            CarouselSlideSeeder::class,
        ]);
    }
}
