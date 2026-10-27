<?php

namespace App\Services;

use App\Exceptions\InvalidOrderLine;
use App\Models\MenuOption;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Arma una línea de pedido a partir de lo que eligió el cliente o el mesero: presentación
 * (vaso / litro) y opciones (salsas, papas, especialidades). El precio SIEMPRE sale de aquí,
 * nunca del navegador ni de la app.
 *
 * Las opciones llegan como [option_id, ...] o [['id' => option_id, 'quantity' => n], ...].
 * Con $options = null (apps que todavía no conocen las opciones) la línea se acepta sin
 * opciones salvo en modo estricto, donde se exigen los mínimos de cada grupo.
 */
class OrderLineBuilder
{
    public function build(Product $product, ?int $variantId = null, ?array $options = null, bool $strict = true): array
    {
        $product->loadMissing(['variants', 'optionGroups.options']);

        $variant = $this->resolveVariant($product, $variantId);
        $elegidas = $this->resolveOptions($product, $options, $strict);

        $precio = $variant ? (float) $variant->price : (float) $product->price;
        $precio += array_sum(array_map(fn ($o) => $o['price'] * $o['quantity'], $elegidas));

        return [
            'product_id' => $product->id,
            'product_name' => $variant ? $product->name . ' (' . $variant->name . ')' : $product->name,
            'variant_id' => $variant?->id,
            'station' => $product->station ?: Product::STATION_KITCHEN,
            'price' => round($precio, 2),
            'options' => $elegidas,
            'signature' => $this->signature($product->id, $variant?->id, $elegidas),
        ];
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if ($product->variants->isEmpty()) {
            return null;
        }

        // Sin presentación elegida (apps anteriores): la primera, que es la de menor precio
        if ($variantId === null) {
            return $product->variants->first();
        }

        $variant = $product->variants->firstWhere('id', $variantId);
        if (!$variant) {
            throw new InvalidOrderLine("La presentación elegida de «{$product->name}» ya no existe.");
        }

        return $variant;
    }

    private function resolveOptions(Product $product, ?array $options, bool $strict): array
    {
        if ($options === null && !$strict) {
            return [];
        }

        $cantidades = $this->normalize($options ?? []);
        $grupos = $product->optionGroups;
        $elegidas = [];

        foreach ($grupos as $grupo) {
            $enGrupo = $grupo->options->filter(fn (MenuOption $o) => isset($cantidades[$o->id]));
            $total = 0;

            foreach ($enGrupo as $opcion) {
                $cantidad = $cantidades[$opcion->id];
                unset($cantidades[$opcion->id]);

                if (!$opcion->available) {
                    throw new InvalidOrderLine("«{$opcion->name}» ya no está disponible.");
                }
                if ($cantidad > $grupo->max_per_option) {
                    throw new InvalidOrderLine("«{$opcion->name}» se puede pedir máximo {$grupo->max_per_option} " . ($grupo->max_per_option === 1 ? 'vez' : 'veces') . '.');
                }

                $total += $cantidad;
                $elegidas[] = [
                    'option_id' => $opcion->id,
                    'group' => $grupo->name,
                    'name' => $opcion->name,
                    'price' => (float) $opcion->price,
                    'quantity' => $cantidad,
                ];
            }

            $this->assertGroupLimits($product, $grupo, $total);
        }

        if ($cantidades !== []) {
            throw new InvalidOrderLine("Una de las opciones elegidas no corresponde a «{$product->name}».");
        }

        return $elegidas;
    }

    private function assertGroupLimits(Product $product, OptionGroup $grupo, int $total): void
    {
        $min = (int) $grupo->pivot->min_select;
        $max = (int) $grupo->pivot->max_select;

        if ($total < $min) {
            throw new InvalidOrderLine("Para «{$product->name}» elige " . ($min === 1 ? 'una opción' : "{$min} opciones") . " en «{$grupo->label}».");
        }
        if ($max > 0 && $total > $max) {
            throw new InvalidOrderLine("Para «{$product->name}» puedes elegir máximo {$max} en «{$grupo->label}».");
        }
    }

    /** [3, 3, 5] o [['id' => 3, 'quantity' => 2], ['id' => 5]] → [3 => 2, 5 => 1] */
    private function normalize(array $options): array
    {
        $cantidades = [];

        foreach ($options as $valor) {
            $id = is_array($valor) ? ($valor['id'] ?? $valor['option_id'] ?? null) : $valor;
            $cantidad = is_array($valor) ? (int) ($valor['quantity'] ?? 1) : 1;

            if (!is_numeric($id) || $cantidad < 1) {
                throw new InvalidOrderLine('Hay una opción inválida en el pedido.');
            }

            $cantidades[(int) $id] = ($cantidades[(int) $id] ?? 0) + $cantidad;
        }

        return $cantidades;
    }

    /** Identifica la combinación para juntar líneas iguales en el carrito. */
    private function signature(int $productId, ?int $variantId, array $elegidas): string
    {
        $partes = array_map(fn ($o) => $o['option_id'] . 'x' . $o['quantity'], $elegidas);
        sort($partes);

        return $productId . ($variantId ? 'v' . $variantId : '') . ($partes ? 'o' . implode('-', $partes) : '');
    }
}
