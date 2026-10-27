<?php

namespace App\Services;

use App\Models\MenuOption;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * El menú vigente con todo lo que necesitan las pantallas para tomar un pedido:
 * presentaciones con su precio y grupos de opciones con sus límites para cada platillo.
 */
class MenuCatalog
{
    public function products(): Collection
    {
        return Product::onMenu()
            ->menuOrder()
            ->with(['ingredientes', 'variants', 'optionGroups.options'])
            ->get()
            ->map(fn (Product $product) => $this->productPayload($product));
    }

    public function productPayload(Product $product): array
    {
        $product->loadMissing(['ingredientes', 'variants', 'optionGroups.options']);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'category' => $product->category,
            'subcategory' => $product->subcategory,
            'section' => $product->section ?: Product::SECTION_FOOD,
            'station' => $product->station ?: Product::STATION_KITCHEN,
            'presentation' => $product->presentation,
            'image' => $product->image,
            'available' => (int) $product->available,
            'ingredientes' => $product->ingredientes->map(fn ($i) => ['id' => $i->id, 'nombre' => $i->nombre])->values(),
            'variants' => $product->variants->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'price' => (float) $v->price,
            ])->values(),
            'option_groups' => $product->optionGroups->map(fn (OptionGroup $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'label' => $g->label,
                'min' => (int) $g->pivot->min_select,
                'max' => (int) $g->pivot->max_select,
                'max_per_option' => (int) $g->max_per_option,
                'options' => $g->options->where('available', true)->map(fn (MenuOption $o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'detail' => $o->detail,
                    'price' => (float) $o->price,
                ])->values(),
            ])->values(),
        ];
    }
}
