<?php
namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use Carbon\Carbon;

class AchievementService
{
    public function check(User $user): void
    {
        $this->checkCompras($user);
        $this->checkReservaciones($user);
        $this->checkFidelidad($user);
        $this->checkVariedad($user);
        $this->checkEspecial($user);
    }

    private function unlock(User $user, string $slug): void
    {
        $achievement = Achievement::where('slug', $slug)->first();
        if (!$achievement) {
            return;
        }

        // Solo desbloquear si no lo tiene ya
        $exists = $user->achievements()->where('achievement_id', $achievement->id)->exists();
        if (!$exists) {
            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => Carbon::now(),
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
        }
    }

    private function checkCompras(User $user): void
    {
        $total = $user->orders()->count();

        if ($total >= 1)  { $this->unlock($user, 'primera_orden'); }
        if ($total >= 5)  { $this->unlock($user, '5_ordenes'); }
        if ($total >= 10) { $this->unlock($user, '10_ordenes'); }
        if ($total >= 25) { $this->unlock($user, '25_ordenes'); }
        if ($total >= 50) { $this->unlock($user, '50_ordenes'); }
    }

    private function checkReservaciones(User $user): void
    {
        $total = \App\Models\Reservation::where('correo_electronico', $user->email)
                    ->whereIn('status', ['confirmada','finalizada'])
                    ->count();

        if ($total >= 1)  { $this->unlock($user, 'primera_reserva'); }
        if ($total >= 5)  { $this->unlock($user, '5_reservas'); }
        if ($total >= 10) { $this->unlock($user, '10_reservas'); }
    }

    private function checkFidelidad(User $user): void
    {
        $meses = $user->created_at->diffInMonths(Carbon::now());

        if ($meses >= 1)  { $this->unlock($user, '1_mes'); }
        if ($meses >= 3)  { $this->unlock($user, '3_meses'); }
        if ($meses >= 6)  { $this->unlock($user, '6_meses'); }
        if ($meses >= 12) { $this->unlock($user, '1_anio'); }
        if ($meses >= 24) { $this->unlock($user, '2_anios'); }
    }

    private function checkVariedad(User $user): void
    {
        // Categorías ordenadas (requiere relación order_items -> product -> category)
        $categorias = \App\Models\OrderItem::whereHas('order', fn($q) => $q->where('user_id', $user->id))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->distinct()
            ->pluck('products.category')
            ->toArray();

        $hamburguesas = \App\Models\OrderItem::whereHas('order', fn($q) => $q->where('user_id', $user->id))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.category', 'Hamburguesas')
            ->count();

        $bar = \App\Models\OrderItem::whereHas('order', fn($q) => $q->where('user_id', $user->id))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('products.category', ['Cervezas','Coctelería','Destilados'])
            ->count();

        $alitas = \App\Models\OrderItem::whereHas('order', fn($q) => $q->where('user_id', $user->id))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.category', 'Alitas y Costillas')
            ->count();

        if ($hamburguesas >= 5)       { $this->unlock($user, 'fan_hamburguesas'); }
        if ($bar >= 5)                { $this->unlock($user, 'fan_bar'); }
        if ($alitas >= 5)             { $this->unlock($user, 'fan_alitas'); }
        if (count($categorias) >= 4)  { $this->unlock($user, 'explorador'); }
    }

    private function checkEspecial(User $user): void
    {
        // Viernes por la noche
        $nocheViernes = \App\Models\Order::where('user_id', $user->id)
            ->whereRaw('DAYOFWEEK(created_at) = 6')
            ->whereRaw('HOUR(created_at) >= 20')
            ->exists();
            
        if ($nocheViernes) {
            $this->unlock($user, 'noche_viernes');
        }

        // Grupo grande
        $grupoGrande = \App\Models\Reservation::where('correo_electronico', $user->email)
            ->where('cantidad_personas', '>=', 8)
            ->exists();
            
        if ($grupoGrande) {
            $this->unlock($user, 'grupo_grande');
        }

        // 100 puntos
        if (($user->points ?? 0) >= 100) {
            $this->unlock($user, 'puntos_100');
        }
    }
}
