<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Pantalla de preparación. La misma pantalla sirve a la cocina (comida) y a la barra
 * (bebidas preparadas): cada tableta elige su estación y solo ve lo suyo. Cada platillo
 * se marca por separado; el pedido queda "listo" cuando las dos estaciones terminaron.
 */
class KitchenController extends Controller
{
    private const PREP_STATIONS = [Product::STATION_KITCHEN, Product::STATION_BAR];

    private function checkAccess()
    {
        return Auth::check() && Auth::user()->role === 'cocinero';
    }

    public function index()
    {
        if (!$this->checkAccess()) {
            return abort(403);
        }

        return view('cocina.index');
    }

    public function apiActiveOrders(Request $request)
    {
        if (!$this->checkAccess()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $station = $this->station($request);

        $orders = $this->activeOrdersQuery($station)->get()->map(function (Order $order) use ($station) {
            $propios = $order->items->filter(fn (OrderItem $i) => $i->stationOrDefault() === $station);
            $otraEstacion = $order->items->filter(fn (OrderItem $i) => $i->stationOrDefault() !== $station
                && $i->stationOrDefault() !== Product::STATION_NONE && !$i->prepared_at);

            return [
                'id' => $order->id,
                'folio' => str_pad($order->id, 5, '0', STR_PAD_LEFT),
                'table_number' => $order->table_number,
                'customer_name' => $order->customer_name,
                // Mesa: comensal o mesero. Domicilio: el cliente.
                'detail' => $order->table_number
                    ? trim(($order->diner_label ? $order->diner_label . ' · ' : '') . ($order->waiter?->name ? 'Mesero: ' . $order->waiter->name : ''), ' ·')
                    : $order->customer_name,
                'status' => $order->status,
                'created_at' => optional($order->created_at)->toISOString(),
                'other_station_pending' => $otraEstacion->sum('quantity'),
                'items' => $propios->map(fn (OrderItem $i) => [
                    'id' => $i->id,
                    'product_name' => $i->product_name,
                    'quantity' => (int) $i->quantity,
                    'excluded_ingredients' => $i->excluded_ingredients ?? [],
                    'options' => $i->optionsSummary(),
                    'notes' => $i->notes,
                    'prepared' => $i->prepared_at !== null,
                ])->values(),
            ];
        });

        return response()->json($orders);
    }

    /** Marca (o desmarca, si se tocó por error) un platillo como listo. */
    public function markItemReady(Request $request, $itemId, OrderNotificationService $notifier)
    {
        if (!$this->checkAccess()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $item = OrderItem::with('order')->findOrFail($itemId);
        $order = $item->order;

        if (!in_array($order->status, $order->preparationStatuses(), true)) {
            return response()->json(['error' => 'Este pedido ya no está en preparación.'], 422);
        }

        if ($request->boolean('listo', true)) {
            $item->prepared_at ??= now();
        } elseif (!$item->served_at) {
            $item->prepared_at = null;
        }
        $item->save();

        $this->finishIfComplete($order, $notifier);

        return response()->json(['success' => true, 'order_status' => $order->status]);
    }

    /**
     * Marca todo lo de una estación como listo. Sin estación (pantallas o apps anteriores)
     * marca el pedido completo, como antes.
     */
    public function markAsReady(Request $request, $id, OrderNotificationService $notifier)
    {
        if (!$this->checkAccess()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $order = Order::findOrFail($id);
        $station = $request->input('estacion');

        if (in_array($station, self::PREP_STATIONS, true)) {
            $this->pendingItems($order->items(), $station)->update(['prepared_at' => now()]);
            $this->finishIfComplete($order, $notifier);
        } else {
            $order->status = 'ready';
            $order->save();
            $this->notifyReady($order, $notifier);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'order_status' => $order->status]);
        }

        return redirect()->route('kitchen.index')->with('success', 'Pedido marcado como listo');
    }

    private function finishIfComplete(Order $order, OrderNotificationService $notifier): void
    {
        if ($order->syncPreparationStatus()) {
            $this->notifyReady($order, $notifier);
        }
    }

    private function notifyReady(Order $order, OrderNotificationService $notifier): void
    {
        // A domicilio queda "listo" esperando al mandadito; el correo "va en camino"
        // sale cuando el empleado lo entrega (WaiterController::entregarAlMandadito).
        if (!$order->esADomicilio()) {
            $notifier->notificarEstado($order, 'ready');
        }
    }

    private function station(Request $request): string
    {
        $station = $request->query('estacion', Product::STATION_KITCHEN);

        return in_array($station, self::PREP_STATIONS, true) ? $station : Product::STATION_KITCHEN;
    }

    /** Las líneas anteriores a la barra no tienen estación: eran de cocina. */
    private function pendingItems($query, string $station)
    {
        return $query->whereNull('prepared_at')->where(function ($q) use ($station) {
            $q->where('station', $station);
            if ($station === Product::STATION_KITCHEN) {
                $q->orWhereNull('station');
            }
        });
    }

    private function activeOrdersQuery(string $station): Builder
    {
        return Order::with(['items', 'waiter:id,name'])
            ->whereHas('items', fn ($q) => $this->pendingItems($q, $station))
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('table_number')->whereIn('status', ['pending', 'preparing']);
                })->orWhere(function ($q) {
                    $q->whereNull('table_number')->whereIn('status', ['paid', 'preparing']);
                });
            })
            ->orderBy('created_at', 'asc');
    }
}
