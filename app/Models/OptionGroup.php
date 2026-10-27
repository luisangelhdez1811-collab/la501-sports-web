<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Lista de opciones que se reutiliza entre platillos: salsas, papas, sabores de malteada,
 * especialidades de "Arma tus Papas"... Cada platillo la usa con su propio mínimo y máximo
 * (pivote product_option_group): las alitas de 350 g llevan 1 salsa y las de 700 g, 2.
 */
class OptionGroup extends Model
{
    protected $fillable = ['name', 'label', 'min_select', 'max_select', 'max_per_option', 'sort_order'];

    protected $casts = [
        'min_select' => 'integer',
        'max_select' => 'integer',
        'max_per_option' => 'integer',
    ];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('la501_products_active'));
        static::deleted(fn () => Cache::forget('la501_products_active'));
    }

    public function options()
    {
        return $this->hasMany(MenuOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_option_group')
            ->withPivot(['min_select', 'max_select', 'sort_order']);
    }
}
