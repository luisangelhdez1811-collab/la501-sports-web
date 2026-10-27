<?php

namespace App\Services;

use App\Exceptions\InvalidOrderLine;
use App\Models\Product;
use App\Models\Promotion;

/**
 * Arma la línea de pedido de una promoción con los platillos que incluye. En los renglones
 * "a elegir" el cliente manda cuáles quiere:
 *   [slot => [product_id, product_id]] o [slot => [['id' => product_id, 'quantity' => 2]]]
 * La cantidad elegida en cada renglón debe ser exactamente la que da la promoción.
 *
 * Con $choices = null (apps que todavía no conocen la elección) se acepta sin elegir salvo
 * en modo estricto; la comanda dice entonces "a elegir, confirmar con el cliente".
 */
class PromotionLineBuilder
{
    public const GROUP = 'Incluye';

    public function build(Promotion $promotion, ?array $choices = null, bool $strict = true): array
    {
        $promotion->loadMissing('products');

        if ($choices === null && $strict && $promotion->hasChoices()) {
            throw new InvalidOrderLine("Elige los platillos de la promoción «{$promotion->title}».");
        }

        $incluye = [];
        $elegidos = [];

        foreach ($promotion->slots() as $renglon) {
            if (!$renglon['choice']) {
                $incluye[] = $this->entry($renglon['products']->first(), $renglon['quantity']);
                continue;
            }

            if ($choices === null) {
                $incluye[] = [
                    'group' => self::GROUP,
                    'name' => $renglon['products']->pluck('name')->implode(' / ') . ' (a elegir, confirmar con el cliente)',
                    'price' => 0.0,
                    'quantity' => $renglon['quantity'],
                ];
                continue;
            }

            $cantidades = $this->normalize($choices[$renglon['slot']] ?? []);
            $elegidos[$renglon['slot']] = $cantidades;

            if (array_sum($cantidades) !== $renglon['quantity']) {
                $cuantos = $renglon['quantity'] === 1 ? 'un platillo' : "{$renglon['quantity']} platillos";
                throw new InvalidOrderLine("En «{$promotion->title}» elige {$cuantos} de: " . $renglon['products']->pluck('name')->implode(', ') . '.');
            }

            foreach ($cantidades as $productId => $cantidad) {
                $producto = $renglon['products']->firstWhere('id', $productId);
                if (!$producto) {
                    throw new InvalidOrderLine("Uno de los platillos elegidos no es parte de «{$promotion->title}».");
                }
                if (!$producto->available || $producto->archived_at) {
                    throw new InvalidOrderLine("«{$producto->name}» de la promoción «{$promotion->title}» ya no está disponible.");
                }
                $incluye[] = $this->entry($producto, $cantidad);
            }
        }

        return [
            'promotion_id' => $promotion->id,
            'product_name' => $promotion->title,
            'station' => $this->station($promotion, $incluye),
            'price' => round((float) $promotion->price, 2),
            'options' => $incluye,
            'includes' => collect($incluye)->map(fn ($o) => ($o['quantity'] > 1 ? $o['quantity'] . 'x ' : '') . $o['name'])->implode(' · '),
            // Mismo formato que recibe build(), para volver a armarla desde el carrito
            'choices' => array_map(
                fn (array $cantidades) => array_map(fn ($id, $cantidad) => ['id' => $id, 'quantity' => $cantidad], array_keys($cantidades), $cantidades),
                $elegidos
            ),
            'signature' => $this->signature($promotion->id, $elegidos),
        ];
    }

    private function entry(Product $producto, int $cantidad): array
    {
        return [
            'product_id' => $producto->id,
            'group' => self::GROUP,
            'name' => $producto->name,
            'price' => 0.0,
            'quantity' => $cantidad,
        ];
    }

    /**
     * Una sola comanda por promoción: va a cocina si incluye algo de cocina, si no a barra,
     * y si todo se entrega tal cual (cervezas), directo al mesero.
     */
    private function station(Promotion $promotion, array $incluye): string
    {
        $estaciones = collect($incluye)
            ->map(fn ($o) => isset($o['product_id']) ? $promotion->products->firstWhere('id', $o['product_id'])?->station : Product::STATION_KITCHEN)
            ->filter();

        foreach ([Product::STATION_KITCHEN, Product::STATION_BAR] as $estacion) {
            if ($estaciones->contains($estacion)) {
                return $estacion;
            }
        }

        return $estaciones->isEmpty() ? Product::STATION_KITCHEN : Product::STATION_NONE;
    }

    /** [3, 3, 5] o [['id' => 3, 'quantity' => 2], ['id' => 5]] → [3 => 2, 5 => 1] */
    private function normalize(mixed $elegidos): array
    {
        if (!is_array($elegidos)) {
            throw new InvalidOrderLine('Hay un platillo inválido en la promoción.');
        }

        $cantidades = [];
        foreach ($elegidos as $valor) {
            $id = is_array($valor) ? ($valor['id'] ?? $valor['product_id'] ?? null) : $valor;
            $cantidad = is_array($valor) ? (int) ($valor['quantity'] ?? 1) : 1;

            if (!is_numeric($id) || $cantidad < 1) {
                throw new InvalidOrderLine('Hay un platillo inválido en la promoción.');
            }

            $cantidades[(int) $id] = ($cantidades[(int) $id] ?? 0) + $cantidad;
        }
        ksort($cantidades);

        return $cantidades;
    }

    /** Identifica la combinación para juntar líneas iguales en el carrito. */
    private function signature(int $promotionId, array $elegidos): string
    {
        ksort($elegidos);
        $partes = [];
        foreach ($elegidos as $slot => $cantidades) {
            foreach ($cantidades as $id => $cantidad) {
                $partes[] = $slot . ':' . $id . 'x' . $cantidad;
            }
        }

        return 'promo_' . $promotionId . ($partes ? '_' . implode('-', $partes) : '');
    }
}
