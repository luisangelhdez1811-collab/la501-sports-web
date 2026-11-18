<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * La fila de la pantalla de cocina y de barra. Cada estación ve sus pedidos del más antiguo al más
 * nuevo; los primeros ON_SCREEN salen en grande (son los que se están cocinando) y el resto queda
 * «en cola» abajo. Un pedido que ya salió en grande en alguna estación se considera «en cocina»
 * y el cliente ya no lo puede cancelar.
 */
class KitchenQueue
{
    /** Órdenes que la pantalla muestra en grande. Debe coincidir con ORDENES_EN_PRIORIDAD de la vista. */
    public const ON_SCREEN = 3;

    public const STATIONS = [Product::STATION_KITCHEN, Product::STATION_BAR];

    /** Pedidos con algo pendiente en la estación, en el orden en que la pantalla los muestra. */
    public static function query(string $station): Builder
    {
        return Order::query()
            ->whereHas('items', fn ($q) => self::pendingItems($q, $station))
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('table_number')->whereIn('status', ['pending', 'preparing']);
                })->orWhere(function ($q) {
                    $q->whereNull('table_number')->whereIn('status', ['paid', 'preparing']);
                });
            })
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /** Las líneas anteriores a la barra no tienen estación: eran de cocina. */
    public static function pendingItems($query, string $station)
    {
        return $query->whereNull('prepared_at')->where(function ($q) use ($station) {
            $q->where('station', $station);
            if ($station === Product::STATION_KITCHEN) {
                $q->orWhereNull('station');
            }
        });
    }

    /** ¿El pedido está ahora mismo entre los que la cocina o la barra ven en grande? */
    public static function isOnScreen(Order $order): bool
    {
        foreach (self::STATIONS as $station) {
            $enEstaFila = self::query($station)->whereKey($order->getKey())->exists();
            if (!$enEstaFila) {
                continue;
            }

            $antesQueEl = self::query($station)
                ->where(function ($q) use ($order) {
                    $q->where('created_at', '<', $order->created_at)
                      ->orWhere(fn ($q) => $q->where('created_at', $order->created_at)->where('id', '<', $order->id));
                })
                ->count();

            if ($antesQueEl < self::ON_SCREEN) {
                return true;
            }
        }

        return false;
    }

    /** Anota la hora en que cada pedido apareció en grande por primera vez. */
    public static function markShown(iterable $orders): void
    {
        $ids = collect($orders)->take(self::ON_SCREEN)->filter(fn (Order $o) => $o->kitchen_shown_at === null)->pluck('id');

        if ($ids->isNotEmpty()) {
            Order::whereIn('id', $ids)->whereNull('kitchen_shown_at')->update(['kitchen_shown_at' => now()]);
        }
    }
}
