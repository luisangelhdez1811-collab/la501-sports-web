<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Presentación de un producto con precio propio: "Vaso" / "Litro" en coctelería,
 * "Copeo 1.5 oz" / "Botella 750 ml" en destilados.
 */
class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'price', 'sort_order'];

    protected $casts = [
        'price' => 'float',
    ];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('la501_products_active'));
        static::deleted(fn () => Cache::forget('la501_products_active'));
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
