<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Una opción dentro de un grupo: "Buffalo" (picor 2 de 5), "Con papas" (+$22)... */
class MenuOption extends Model
{
    protected $fillable = ['option_group_id', 'name', 'detail', 'price', 'available', 'sort_order'];

    protected $casts = [
        'price' => 'float',
        'available' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('la501_products_active'));
        static::deleted(fn () => Cache::forget('la501_products_active'));
    }

    public function group()
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id');
    }
}
