<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    // Quién lo prepara. "ninguna" = se entrega tal cual (refresco, cerveza): no pasa por
    // cocina ni barra y le aparece directo al mesero como "por entregar".
    public const STATION_KITCHEN = 'cocina';
    public const STATION_BAR = 'barra';
    public const STATION_NONE = 'ninguna';
    public const STATIONS = [self::STATION_KITCHEN, self::STATION_BAR, self::STATION_NONE];

    public const SECTION_FOOD = 'comidas';
    public const SECTION_DRINKS = 'bebidas';
    public const SECTIONS = [self::SECTION_FOOD, self::SECTION_DRINKS];

    protected $fillable = [
        'name', 'description', 'price', 'category', 'subcategory', 'image', 'available',
        'section', 'station', 'presentation', 'sort_order', 'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('la501_products_active');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('la501_products_active');
        });
    }
    public function getImageAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        if (str_contains($value, 'res.cloudinary.com') && str_contains($value, '/upload/')) {
            if (!str_contains($value, 'q_auto')) {
                return str_replace('/upload/', '/upload/f_auto,q_auto/', $value);
            }
        }
        return $value;
    }

    /** Lo que se ofrece hoy: disponible y del menú vigente (no archivado). */
    public function scopeOnMenu(Builder $query): Builder
    {
        return $query->where('available', 1)->whereNull('archived_at');
    }

    /**
     * Orden de la carta: comidas primero, luego bebidas; dentro de cada una, por el orden de
     * sus categorías (las que no están registradas, al final) y luego como en el menú impreso.
     */
    public function scopeMenuOrder(Builder $query): Builder
    {
        return $query->orderByRaw("CASE WHEN products.section = 'bebidas' THEN 1 ELSE 0 END")
            ->orderByRaw('COALESCE((SELECT mc.sort_order FROM menu_categories mc WHERE mc.name = products.category LIMIT 1), 999999)')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function needsPreparation(): bool
    {
        return $this->station !== self::STATION_NONE;
    }

    public function ingredientes()
    {
        return $this->hasMany(Ingrediente::class, 'product_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function optionGroups()
    {
        return $this->belongsToMany(OptionGroup::class, 'product_option_group')
            ->withPivot(['min_select', 'max_select', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product')
            ->withPivot(['quantity', 'slot'])
            ->withTimestamps();
    }
}
