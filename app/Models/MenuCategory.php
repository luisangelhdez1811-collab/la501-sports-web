<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Categoría de la carta (Hamburguesas, Coctelería...). Los platillos guardan el nombre en
 * products.category; esta tabla define cuáles existen, a qué sección pertenecen y su orden.
 */
class MenuCategory extends Model
{
    protected $fillable = ['name', 'section', 'sort_order'];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('la501_products_active'));
        static::deleted(fn () => Cache::forget('la501_products_active'));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw("CASE WHEN section = 'bebidas' THEN 1 ELSE 0 END")
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** Platillos del menú vigente en esta categoría (lo archivado no cuenta). */
    public function products()
    {
        return $this->hasMany(Product::class, 'category', 'name')->whereNull('archived_at');
    }

    /** La registra si no existe (importaciones y platillos de categorías nuevas). */
    public static function ensure(string $name, string $section): self
    {
        return static::firstOrCreate(
            ['name' => $name],
            ['section' => $section, 'sort_order' => (int) static::max('sort_order') + 1]
        );
    }
}
