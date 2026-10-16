<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            ->withPivot('quantity')
            ->withTimestamps();
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