<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingrediente extends Model
{
    protected $table = 'ingredientes';
    protected $fillable = ['product_id', 'nombre', 'inventory_id', 'cantidad_usada'];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('la501_products_active');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('la501_products_active');
        });
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
