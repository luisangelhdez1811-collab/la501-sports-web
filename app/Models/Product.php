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

    // Categorías del menú que son bebidas alcohólicas. Solo sirven para sugerir el valor de un
    // producto NUEVO (importador); lo que manda al vender es la columna `alcoholic` de cada producto.
    public const ALCOHOLIC_CATEGORIES = ['6 Shots', 'Coctelería', 'Destilados', 'Digestivos', 'Cervecería', 'Cervezas'];

    protected $fillable = [
        'name', 'description', 'price', 'category', 'subcategory', 'image', 'available',
        'section', 'station', 'presentation', 'sort_order', 'archived_at', 'alcoholic',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'alcoholic' => 'boolean',
    ];

    /**
     * ¿Un producto nuevo con esta categoría lleva alcohol? La cerveza 0.0 (nombre con «Cero») y los
     * «Preparados» (solo la mezcla, sin cerveza) no. El panel permite corregir cada producto.
     */
    public static function defaultAlcoholic(?string $category, ?string $subcategory, ?string $name): bool
    {
        $limpiar = fn (?string $texto) => mb_strtolower(\Illuminate\Support\Str::ascii(trim((string) $texto)));

        $categorias = array_map($limpiar, self::ALCOHOLIC_CATEGORIES);
        if (!in_array($limpiar($category), $categorias, true)) {
            return false;
        }

        return $limpiar($subcategory) !== 'preparados'
            && !str_contains($limpiar($name), 'cero')
            && !str_starts_with($limpiar($name), 'preparado');
    }

    protected static function booted()
    {
        // Un producto NUEVO de una categoría de alcohol nace marcado «lleva alcohol» si nadie dijo otra cosa
        // (importador de Excel, CSV, seeders, panel). Así no se vende cerveza sin pedir la mayoría de edad
        // solo porque quien lo dio de alta olvidó la casilla. Si el alta trae `alcoholic` explícito, se respeta.
        static::creating(function (Product $product) {
            if (!array_key_exists('alcoholic', $product->getAttributes())) {
                $product->alcoholic = self::defaultAlcoholic($product->category, $product->subcategory, $product->name);
            }
        });
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
