<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffOrderController extends Controller
{
    private const ZONA_HORARIA = 'America/Mexico_City';

    private const ESTADOS_ACTIVOS_COCINA = [
        'pending',
        'paid',
        'preparing',
        'ready',
    ];

    private function rangoDeHoy(): array
    {
        $inicio = Carbon::now(self::ZONA_HORARIA)->startOfDay();
        $fin = $inicio->copy()->addDay();

        $tzApp = config('app.timezone', 'UTC');

        return [
            $inicio->copy()->setTimezone($tzApp),
            $fin->copy()->setTimezone($tzApp),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
        ]);

        [$inicioHoy, $finHoy] = $this->rangoDeHoy();

        $query = Order::query()
            ->with(['items:id,order_id,product_name,station,quantity,price,subtotal,excluded_ingredients,options,notes,prepared_at,served_at'])
            ->whereBetween('created_at', [$inicioHoy, $finHoy])
            ->orderBy('created_at', 'asc');

        if (!empty($validated['status'])) {
            $estados = array_filter(array_map('trim', explode(',', $validated['status'])));
            $query->whereIn('status', $estados);
        } else {
            $query->whereIn('status', self::ESTADOS_ACTIVOS_COCINA);
        }

        $orders = $query->get()->map(fn (Order $order) => $this->serializeOrder($order));

        return response()->json([
            'success' => true,
            'total' => $orders->count(),
            'data' => $orders,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load('items');

        return response()->json([
            'success' => true,
            'data' => $this->serializeOrder($order, true),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function updateStatus(Request $request, Order $order, OrderNotificationService $notifier): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                'string',
                Rule::in(['pending', 'paid', 'preparing', 'ready', 'delivering', 'delivered', 'cancelled']),
            ],
        ]);

        $estadoAnterior = $order->status;
        $siguiente = $validated['status'] ?? $this->siguienteEstado($order);

        if (!$siguiente) {
            return response()->json([
                'success' => false,
                'error' => 'no_next_status',
                'message' => "El pedido #{$order->id} ya está en estado '{$order->status}' y no tiene un siguiente paso automático.",
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $order->status = $siguiente;

        if ($siguiente === 'delivering' && $order->esADomicilio()) {
            $order->delivery_started_at = now();
        }

        $order->save();

        if (in_array($siguiente, ['ready', 'delivering'], true)) {
            $notifier->notificarEstado($order, $siguiente);
        }

        return response()->json([
            'success' => true,
            'message' => "Pedido #{$order->id} pasó de '{$estadoAnterior}' a '{$order->status}'.",
            'data' => $this->serializeOrder($order->fresh('items')),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Domicilio: preparing -> ready -> delivering -> delivered.
     * Mesa: preparing -> ready (el cobro lo hace el mesero, no esta API).
     */
    private function siguienteEstado(Order $order): ?string
    {
        if ($order->esADomicilio()) {
            return match ($order->status) {
                'pending', 'paid' => 'preparing',
                'preparing' => 'ready',
                'ready' => 'delivering',
                'delivering' => 'delivered',
                default => null,
            };
        }

        return match ($order->status) {
            'pending' => 'preparing',
            'preparing' => 'ready',
            default => null,
        };
    }

    public function cancel(Order $order): JsonResponse
    {
        if (in_array($order->status, ['delivered', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'cannot_cancel',
                'message' => "El pedido #{$order->id} ya está '{$order->status}' y no se puede cancelar.",
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Pedido #{$order->id} cancelado.",
            'data' => $this->serializeOrder($order->fresh('items')),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function updateItemQuantity(
        Request $request,
        Order $order,
        OrderItem $item
    ): JsonResponse {
        if ((int) $item->order_id !== (int) $order->id) {
            return response()->json([
                'success' => false,
                'error' => 'item_not_in_order',
                'message' => 'Ese platillo no pertenece a este pedido.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        if ((int) $validated['quantity'] === 0) {
            $item->delete();
        } else {
            $item->quantity = $validated['quantity'];
            $item->subtotal = round($item->price * $item->quantity, 2);
            $item->save();
        }

        $order->total = $order->items()->sum('subtotal');
        $order->save();

        return response()->json([
            'success' => true,
            'message' => (int) $validated['quantity'] === 0
                ? 'Platillo eliminado del pedido.'
                : 'Cantidad actualizada.',
            'data' => $this->serializeOrder($order->fresh('items')),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function serializeOrder(Order $order, bool $detallado = false): array
    {
        return [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'table_number' => $order->table_number,
            'address' => $order->customer_address,
            'maps_url' => $order->mapsUrl(),
            'status' => $order->status,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'created_at' => optional($order->created_at)->toISOString(),
            'minutes_waiting' => $order->created_at
                ? (int) round(abs($order->created_at->diffInMinutes(now())))
                : 0,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
                'excluded_ingredients' => $item->excluded_ingredients,
                'station' => $item->stationOrDefault(),
                'options' => $item->options ?? [],
                'options_summary' => $item->optionsSummary(),
                'notes' => $item->notes,
                'item_status' => $item->prepStatus(),
            ])->values(),
        ];
    }
}
