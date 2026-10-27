<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
class Promotion extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'image',
        'tag',
        'price_text',
        'price',
        'icon',
        'color_gradient',
        'end_date',
        'days_active',
        'active',
        'sellable_online'
    ];

    protected $casts = [
        'sellable_online' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_product')
            ->withPivot(['quantity', 'slot'])
            ->withTimestamps();
    }

    /**
     * Renglones de la promoción, en orden. Un renglón con un solo platillo es fijo
     * ("2x Alitas 700 g"); con varios, el cliente elige cuál ("1 hamburguesa a elegir").
     *
     * @return \Illuminate\Support\Collection<int, array{slot: int, quantity: int, choice: bool, products: \Illuminate\Support\Collection}>
     */
    public function slots(): Collection
    {
        return $this->products
            ->sortBy(fn (Product $p) => [$p->pivot->slot, $p->pivot->id ?? 0])
            ->groupBy(fn (Product $p) => (int) $p->pivot->slot)
            ->map(fn (Collection $productos, int $slot) => [
                'slot' => $slot,
                'quantity' => max(1, (int) $productos->first()->pivot->quantity),
                'choice' => $productos->count() > 1,
                'products' => $productos->values(),
            ])
            ->values();
    }

    public function hasChoices(): bool
    {
        return $this->slots()->contains('choice', true);
    }

    /**
     * Renglones para que el cliente (web o app) elija. En los renglones "a elegir" solo
     * aparecen los platillos que hoy se pueden pedir.
     */
    public function slotsPayload(): array
    {
        return $this->slots()->map(function (array $renglon) {
            $productos = $renglon['choice']
                ? $renglon['products']->filter(fn (Product $p) => $p->available && !$p->archived_at)
                : $renglon['products'];

            return [
                'slot' => $renglon['slot'],
                'quantity' => $renglon['quantity'],
                'choice' => $renglon['choice'],
                'products' => $productos->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'image' => $p->image,
                ])->values()->all(),
            ];
        })->all();
    }

    /** "2x Alitas 700 g · 1 a elegir: Boneless o Argentina" — para la tarjeta de la promoción. */
    public function includesLabel(): string
    {
        return $this->slots()->map(function (array $renglon) {
            $prefijo = $renglon['quantity'] > 1 ? $renglon['quantity'] . 'x ' : '';
            if (!$renglon['choice']) {
                return $prefijo . $renglon['products']->first()->name;
            }
            $nombres = $renglon['products']->pluck('name');
            return $renglon['quantity'] . ' a elegir: ' . $nombres->slice(0, -1)->implode(', ') . ' o ' . $nombres->last();
        })->implode(' · ');
    }

    /**
     * ¿Esta promoción se puede aprovechar hoy?
     * Si days_active está vacío, se interpreta como "todos los días".
     */
    public function isActiveToday(): bool
    {
        if (empty($this->days_active)) {
            return true;
        }
        $dayKeys = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $todayKey = $dayKeys[now()->dayOfWeek];
        $activeDays = array_map('trim', explode(',', $this->days_active));
        return in_array($todayKey, $activeDays, true);
    }

    /**
     * Texto legible de los días en que aplica, ej. "Jueves" o "Viernes, Sábado y Domingo".
     */
    public function activeDaysLabel(): string
    {
        if (empty($this->days_active)) {
            return 'Todos los días';
        }
        $labels = [
            'domingo' => 'Domingo', 'lunes' => 'Lunes', 'martes' => 'Martes',
            'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado',
        ];
        $days = array_map('trim', explode(',', $this->days_active));
        $names = array_map(fn($d) => $labels[$d] ?? $d, $days);
        if (count($names) === 1) {
            return $names[0];
        }
        $last = array_pop($names);
        return implode(', ', $names) . ' y ' . $last;
    }
}