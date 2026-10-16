<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WaiterApiController extends Controller
{
    private const ACTIVE_STATUSES = ['pending', 'preparing', 'ready'];
    private const KITCHEN_BUSY_STATUSES = ['pending', 'preparing'];
    private const TOTAL_MESAS_BASE = 13;
    private const TOTAL_MESAS_MAX = 40;

    private function totalMesasActual(): int
    {
        return max(self::TOTAL_MESAS_BASE, (int) Setting::get('total_mesas', self::TOTAL_MESAS_BASE));
    }

    private function activeOrdersForTable(int $tableId)
    {
        return Order::where('table_number', $tableId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->get();
    }

    private function tablePayload(int $tableId, $orders = null): array
    {
        $orders ??= $this->activeOrdersForTable($tableId);

        return [
            'id' => $tableId,
            'number' => (string) $tableId,
            'status' => $orders->isNotEmpty() ? 'ocupada' : 'disponible',
            'total' => (float) $orders->sum('total'),
            'kitchen_pending' => $orders->contains(fn ($o) => in_array($o->status, self::KITCHEN_BUSY_STATUSES, true)),
        ];
    }

    public function index(): JsonResponse
    {
        $total = $this->totalMesasActual();

        $ordenesActivas = Order::whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('table_number')
            ->get()
            ->groupBy(fn ($o) => (int) $o->table_number);

        $tables = [];
        for ($i = 1; $i <= $total; $i++) {
            $tables[] = $this->tablePayload($i, $ordenesActivas->get($i, collect()));
        }

        return response()->json(['data' => $tables], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(): JsonResponse
    {
        $nuevoTotal = min(self::TOTAL_MESAS_MAX, $this->totalMesasActual() + 1);
        Setting::updateOrCreate(['key' => 'total_mesas'], ['value' => (string) $nuevoTotal]);

        return response()->json(['data' => $this->tablePayload($nuevoTotal, collect())], 201, [], JSON_UNESCAPED_UNICODE);
    }

    public function show(int $table): JsonResponse
    {
        if ($table < 1 || $table > $this->totalMesasActual()) {
            return response()->json(['success' => false, 'message' => 'Mesa no encontrada.'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $orders = Order::with('items')->where('table_number', $table)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->get();

        $payload = $this->tablePayload($table, $orders);
        $payload['items'] = $orders->flatMap->items->map(fn (OrderItem $item) => [
            'name' => $item->product_name,
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
            'notes' => $item->notes,
            'excluded_ingredients' => $item->excluded_ingredients ?? [],
        ])->values();

        return response()->json(['data' => $payload], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function storeOrder(Request $request, int $table): JsonResponse
    {
        if ($table < 1 || $table > $this->totalMesasActual()) {
            return response()->json(['success' => false, 'message' => 'Mesa no encontrada.'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.excluded_ingredients' => ['nullable', 'array'],
            'items.*.notes' => ['nullable', 'string', 'max:100'],
        ]);

        // Nunca confiamos en precios de la app: se recalculan desde Product.
        $lines = [];
        $total = 0.0;

        foreach ($validated['items'] as $item) {
            $product = Product::with('ingredientes')->find($item['product_id']);

            if (!$product || !$product->available) {
                return response()->json([
                    'success' => false,
                    'error' => 'item_unavailable',
                    'message' => "Un platillo del pedido ya no está disponible.",
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $excluded = $this->normalizeExcludedIngredients($item['excluded_ingredients'] ?? [], $product);
            $notes = trim((string) ($item['notes'] ?? ''));
            $price = (float) $product->price;
            $subtotal = $price * (int) $item['quantity'];
            $total += $subtotal;

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => (int) $item['quantity'],
                'price' => $price,
                'subtotal' => $subtotal,
                'excluded_ingredients' => $excluded,
                'notes' => $notes !== '' ? mb_substr($notes, 0, 100) : null,
            ];
        }

        $order = Order::create([
            'user_id' => null,
            'waiter_id' => $request->user()->id,
            'customer_name' => 'Mesa ' . $table,
            'customer_phone' => 'Local',
            'customer_address' => 'Consumo en sucursal',
            'table_number' => $table,
            'total' => $total,
            'status' => 'pending',
            'payment_method' => 'pendiente',
        ]);

        foreach ($lines as $line) {
            OrderItem::create(array_merge(['order_id' => $order->id], $line));
        }

        return response()->json(['success' => true], 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Acepta tanto IDs de ingrediente (int) como nombres (string) desde la
     * app, pero SIEMPRE guarda nombres — es lo único que entienden cocina
     * (Order::discountInventoryStock) y el resto del sistema. Un ID que no
     * pertenece al producto, o que no existe, se descarta en vez de
     * guardarse "a ciegas".
     */
    private function normalizeExcludedIngredients(array $excluded, Product $product): array
    {
        $porId = $product->ingredientes->keyBy('id');

        $nombres = collect($excluded)->map(function ($valor) use ($porId) {
            if (is_numeric($valor)) {
                return optional($porId->get((int) $valor))->nombre;
            }

            return trim((string) $valor) !== '' ? trim((string) $valor) : null;
        })->filter()->unique()->values()->all();

        sort($nombres);

        return $nombres;
    }

    public function charge(Request $request, int $table): JsonResponse
    {
        if ($table < 1 || $table > $this->totalMesasActual()) {
            return response()->json(['success' => false, 'message' => 'Mesa no encontrada.'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $validated = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:efectivo,tarjeta,transferencia'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'forced' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $orders = $this->activeOrdersForTable($table);

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'nothing_to_charge',
                'message' => "La mesa {$table} no tiene pedidos pendientes de cobro.",
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $total = (float) $orders->sum('total');
        $pagado = (float) collect($validated['payments'])->sum('amount');

        if (round($pagado, 2) < round($total, 2)) {
            return response()->json([
                'success' => false,
                'error' => 'insufficient_payment',
                'message' => 'La suma de los pagos es menor al total de la mesa.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $forced = $request->boolean('forced');
        $kitchenPending = $orders->contains(fn ($o) => in_array($o->status, self::KITCHEN_BUSY_STATUSES, true));

        if ($kitchenPending && !$forced) {
            return response()->json([
                'success' => false,
                'error' => 'kitchen_pending',
                'message' => 'Hay platillos de esta mesa todavía en preparación.',
            ], 409, [], JSON_UNESCAPED_UNICODE);
        }

        $reason = null;
        if ($forced) {
            if (!Hash::check((string) $validated['password'], $request->user()->password)) {
                return response()->json([
                    'success' => false,
                    'error' => 'invalid_password',
                    'message' => 'Contraseña incorrecta. No se pudo forzar el cobro.',
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $reason = trim((string) ($validated['reason'] ?? ''));
            if (mb_strlen($reason) < 5) {
                return response()->json([
                    'success' => false,
                    'error' => 'reason_too_short',
                    'message' => 'El motivo del cobro forzado debe tener al menos 5 caracteres.',
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }
        }

        $paymentMethodLabel = $this->buildPaymentMethodLabel($validated['payments']);

        foreach ($orders as $order) {
            $order->status = 'paid';
            $order->payment_method = $paymentMethodLabel;
            $order->charged_by = $request->user()->id;
            $order->forced_checkout = $kitchenPending;
            $order->forced_checkout_reason = $kitchenPending ? $reason : null;
            $order->save();
        }

        return response()->json(['success' => true], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Arma el texto que ya sabe leer DashboardController::parsePaymentMethodAmounts
     * (y su copia en AdminApiController): "efectivo", "tarjeta", o
     * "mixto: efectivo: $X tarjeta: $Y" cuando se combinan métodos.
     * "transferencia" se agrupa con "tarjeta" para el corte de caja — ese
     * reporte solo distingue efectivo vs. no-efectivo.
     */
    private function buildPaymentMethodLabel(array $payments): string
    {
        if (count($payments) === 1) {
            return $payments[0]['method'];
        }

        $efectivo = 0.0;
        $tarjeta = 0.0;
        foreach ($payments as $p) {
            if ($p['method'] === 'efectivo') {
                $efectivo += (float) $p['amount'];
            } else {
                $tarjeta += (float) $p['amount'];
            }
        }

        return sprintf('mixto: efectivo: $%.2f tarjeta: $%.2f', $efectivo, $tarjeta);
    }
}
