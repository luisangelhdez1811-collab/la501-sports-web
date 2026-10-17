<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TableCharge;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * API de la app del mesero: mapa de mesas, pedidos por comensal y cobro (junto o por
 * separado). La app puede trabajar sin conexión: guarda los pedidos y cobros y los envía
 * después con un identificador propio (client_ref), así que reenviar lo mismo nunca lo
 * registra dos veces. Los montos siempre se recalculan aquí desde Product.
 */
class WaiterApiController extends Controller
{
    private const ACTIVE_STATUSES = ['pending', 'preparing', 'ready'];
    private const KITCHEN_BUSY_STATUSES = ['pending', 'preparing'];
    private const TOTAL_MESAS_BASE = 13;
    private const TOTAL_MESAS_MAX = 40;
    private const CLIENT_REF_RULE = 'regex:/^[A-Za-z0-9_-]{8,64}$/';
    // Un cobro sin conexión puede llegar horas después, pero no se acepta una fecha absurda.
    private const OFFLINE_MAX_AGE_DAYS = 7;
    private const ZONA_HORARIA = 'America/Mexico_City';

    private function totalMesasActual(): int
    {
        return max(self::TOTAL_MESAS_BASE, (int) Setting::get('total_mesas', self::TOTAL_MESAS_BASE));
    }

    private function mesaExiste(int $table): bool
    {
        return $table >= 1 && $table <= $this->totalMesasActual();
    }

    private function error(int $status, string $code, string $message): JsonResponse
    {
        return response()->json(['success' => false, 'error' => $code, 'message' => $message], $status, [], JSON_UNESCAPED_UNICODE);
    }

    private function mesaNoEncontrada(): JsonResponse
    {
        return $this->error(404, 'table_not_found', 'Mesa no encontrada.');
    }

    private function activeOrdersForTable(int $tableId): Collection
    {
        return Order::where('table_number', $tableId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderBy('id')
            ->get();
    }

    private function tablePayload(int $tableId, ?Collection $orders = null): array
    {
        $orders ??= $this->activeOrdersForTable($tableId);

        return [
            'id' => $tableId,
            'number' => (string) $tableId,
            'status' => $orders->isNotEmpty() ? 'ocupada' : 'disponible',
            'total' => (float) $orders->sum('total'),
            'kitchen_pending' => $orders->contains(fn ($o) => in_array($o->status, self::KITCHEN_BUSY_STATUSES, true)),
            'orders_count' => $orders->count(),
        ];
    }

    private function orderPayload(Order $order, int $position): array
    {
        return [
            'id' => $order->id,
            'client_ref' => $order->client_ref,
            'label' => $order->diner_label ?: 'Pedido ' . $position,
            'status' => $order->status,
            'total' => (float) $order->total,
            'kitchen_pending' => in_array($order->status, self::KITCHEN_BUSY_STATUSES, true),
            'created_at' => optional($order->created_at)->toISOString(),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'notes' => $item->notes,
                'excluded_ingredients' => $item->excluded_ingredients ?? [],
            ])->values()->all(),
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

        return response()->json([
            'data' => $tables,
            'meta' => ['min_tables' => self::TOTAL_MESAS_BASE, 'max_tables' => self::TOTAL_MESAS_MAX],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(): JsonResponse
    {
        $actual = $this->totalMesasActual();
        if ($actual >= self::TOTAL_MESAS_MAX) {
            return $this->error(422, 'max_tables', 'Ya tienes el máximo de ' . self::TOTAL_MESAS_MAX . ' mesas.');
        }

        $nuevoTotal = $actual + 1;
        Setting::updateOrCreate(['key' => 'total_mesas'], ['value' => (string) $nuevoTotal]);

        return response()->json(['data' => $this->tablePayload($nuevoTotal, collect())], 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Quita la última mesa del mapa. Solo la última (las mesas son números consecutivos),
     * solo si está libre y nunca por debajo de las mesas base del restaurante.
     */
    public function destroy(int $table): JsonResponse
    {
        $actual = $this->totalMesasActual();

        if (!$this->mesaExiste($table)) {
            return $this->mesaNoEncontrada();
        }
        if ($table !== $actual) {
            return $this->error(422, 'only_last_table', "Solo se puede quitar la última mesa (la {$actual}).");
        }
        if ($actual <= self::TOTAL_MESAS_BASE) {
            return $this->error(422, 'min_tables', 'El restaurante tiene ' . self::TOTAL_MESAS_BASE . ' mesas fijas; no se pueden quitar.');
        }
        if ($this->activeOrdersForTable($table)->isNotEmpty()) {
            return $this->error(422, 'table_busy', "La mesa {$table} tiene pedidos sin cobrar.");
        }

        Setting::updateOrCreate(['key' => 'total_mesas'], ['value' => (string) ($actual - 1)]);

        return response()->json(['success' => true, 'data' => ['total_tables' => $actual - 1]], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function show(int $table): JsonResponse
    {
        if (!$this->mesaExiste($table)) {
            return $this->mesaNoEncontrada();
        }

        $orders = Order::with('items')->where('table_number', $table)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderBy('id')
            ->get();

        $payload = $this->tablePayload($table, $orders);
        $payload['orders'] = $orders->values()->map(fn (Order $order, int $i) => $this->orderPayload($order, $i + 1))->all();
        // Lista plana que usaban las versiones anteriores de la app.
        $payload['items'] = collect($payload['orders'])->flatMap(fn (array $order) => $order['items'])->values()->all();

        return response()->json(['data' => $payload], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function storeOrder(Request $request, int $table): JsonResponse
    {
        if (!$this->mesaExiste($table)) {
            return $this->mesaNoEncontrada();
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.excluded_ingredients' => ['nullable', 'array'],
            'items.*.notes' => ['nullable', 'string', 'max:300'],
            'client_ref' => ['nullable', 'string', self::CLIENT_REF_RULE],
            'label' => ['nullable', 'string', 'max:40'],
            'offline' => ['nullable', 'boolean'],
            'created_at' => ['nullable', 'date'],
        ]);

        $clientRef = $validated['client_ref'] ?? null;
        if ($clientRef && ($existente = Order::where('client_ref', $clientRef)->first())) {
            return $this->pedidoYaRegistrado($existente);
        }

        $offline = $request->boolean('offline');
        $label = trim((string) ($validated['label'] ?? ''));

        // Nunca confiamos en precios de la app: se recalculan desde Product.
        $lines = [];
        $total = 0.0;

        foreach ($validated['items'] as $item) {
            $product = Product::with('ingredientes')->find($item['product_id']);

            // Un pedido tomado sin conexión ya se sirvió: se registra aunque el platillo
            // se haya marcado como agotado mientras tanto, para que la cuenta cuadre.
            if (!$product || (!$product->available && !$offline)) {
                return $this->error(422, 'item_unavailable', 'Un platillo del pedido ya no está disponible.');
            }

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
                'excluded_ingredients' => $this->normalizeExcludedIngredients($item['excluded_ingredients'] ?? [], $product),
                'notes' => $notes !== '' ? mb_substr($notes, 0, 300) : null,
            ];
        }

        // Lo que ve la cocina en "Cliente:": la mesa, el comensal y si llegó tarde por falta de internet.
        $customerName = 'Mesa ' . $table . ($label !== '' ? ' · ' . $label : '');
        if ($offline) {
            $tomado = $this->fechaCliente($validated['created_at'] ?? null)->setTimezone(self::ZONA_HORARIA);
            $customerName .= ' (sin conexión, tomado ' . $tomado->format('H:i') . ')';
        }

        try {
            $order = DB::transaction(function () use ($request, $table, $total, $lines, $clientRef, $label, $customerName) {
                $order = Order::create([
                    'user_id' => null,
                    'waiter_id' => $request->user()->id,
                    'customer_name' => $customerName,
                    'customer_phone' => 'Local',
                    'customer_address' => 'Consumo en sucursal',
                    'table_number' => $table,
                    'diner_label' => $label !== '' ? $label : null,
                    'client_ref' => $clientRef,
                    'total' => $total,
                    'status' => 'pending',
                    'payment_method' => 'pendiente',
                ]);

                foreach ($lines as $line) {
                    OrderItem::create(array_merge(['order_id' => $order->id], $line));
                }

                return $order;
            });
        } catch (QueryException $e) {
            // Dos envíos del mismo pedido al mismo tiempo: el índice único deja pasar solo uno.
            if ($clientRef && ($existente = Order::where('client_ref', $clientRef)->first())) {
                return $this->pedidoYaRegistrado($existente);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'data' => ['order_id' => $order->id, 'client_ref' => $order->client_ref],
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    private function pedidoYaRegistrado(Order $order): JsonResponse
    {
        return response()->json([
            'success' => true,
            'duplicate' => true,
            'data' => ['order_id' => $order->id, 'client_ref' => $order->client_ref],
        ], 200, [], JSON_UNESCAPED_UNICODE);
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

    /**
     * Cobra la mesa completa ("junto") o solo algunos pedidos ("por separado", uno por
     * comensal). En línea se valida todo antes de cobrar. Un cobro hecho sin conexión ya
     * ocurrió (el cliente pagó y se fue), así que nunca se rechaza: se aplica y queda en la
     * bitácora marcado para que el administrador revise cualquier diferencia.
     */
    public function charge(Request $request, int $table): JsonResponse
    {
        if (!$this->mesaExiste($table)) {
            return $this->mesaNoEncontrada();
        }

        $validated = $request->validate([
            'payments' => ['required', 'array', 'min:1', 'max:5'],
            'payments.*.method' => ['required', 'in:efectivo,tarjeta,transferencia'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'order_ids' => ['nullable', 'array'],
            'order_ids.*' => ['integer'],
            'order_refs' => ['nullable', 'array'],
            'order_refs.*' => ['string', self::CLIENT_REF_RULE],
            'client_ref' => ['nullable', 'string', self::CLIENT_REF_RULE],
            'offline' => ['nullable', 'boolean'],
            'charged_at' => ['nullable', 'date'],
            'forced' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string'],
        ]);

        $chargeRef = $validated['client_ref'] ?? null;
        if ($chargeRef && ($existente = TableCharge::where('charge_ref', $chargeRef)->first())) {
            return $this->cobroRegistrado($existente, true);
        }

        $offline = $request->boolean('offline');
        $orders = $this->ordersToCharge($table, $validated);
        $total = round((float) $orders->sum('total'), 2);
        $pagado = round((float) collect($validated['payments'])->sum('amount'), 2);
        $kitchenPending = $orders->contains(fn ($o) => in_array($o->status, self::KITCHEN_BUSY_STATUSES, true));

        if (!$offline && ($rechazo = $this->validarCobroEnLinea($request, $validated, $table, $orders, $total, $pagado, $kitchenPending))) {
            return $rechazo;
        }

        $reason = $kitchenPending ? trim((string) ($validated['reason'] ?? '')) : null;
        if ($kitchenPending && $offline && $reason === '') {
            $reason = 'Cobro sin conexión con platillos en cocina';
        }

        [$status, $notes] = match (true) {
            $orders->isEmpty() => [
                TableCharge::STATUS_CONFLICT,
                'Al sincronizar, la mesa ya no tenía esos pedidos pendientes (pudo cobrarse dos veces).',
            ],
            $pagado < $total => [
                TableCharge::STATUS_DISCREPANCY,
                sprintf('Se registraron $%.2f de $%.2f.', $pagado, $total),
            ],
            default => [TableCharge::STATUS_APPLIED, null],
        };

        $waiterId = $request->user()->id;
        $paymentMethodLabel = $this->buildPaymentMethodLabel($validated['payments']);

        $charge = DB::transaction(function () use ($chargeRef, $table, $waiterId, $total, $pagado, $validated, $offline, $kitchenPending, $reason, $status, $notes, $orders, $paymentMethodLabel) {
            $charge = TableCharge::create([
                'charge_ref' => $chargeRef ?? (string) Str::uuid(),
                'table_number' => $table,
                'waiter_id' => $waiterId,
                'total_due' => $total,
                'amount_received' => $pagado,
                'payments' => array_map(fn (array $p) => [
                    'method' => $p['method'],
                    'amount' => round((float) $p['amount'], 2),
                ], $validated['payments']),
                'offline' => $offline,
                'forced' => $kitchenPending,
                'reason' => $reason,
                'status' => $status,
                'notes' => $notes,
                'charged_at' => $offline ? $this->fechaCliente($validated['charged_at'] ?? null) : Carbon::now(),
            ]);

            foreach ($orders as $order) {
                $order->status = 'paid';
                $order->payment_method = $paymentMethodLabel;
                $order->charged_by = $waiterId;
                $order->forced_checkout = $kitchenPending;
                $order->forced_checkout_reason = $reason;
                $order->table_charge_id = $charge->id;
                $order->save();
            }

            return $charge;
        });

        return $this->cobroRegistrado($charge, false);
    }

    /** Los pedidos activos de la mesa, o solo los indicados (cobro por separado). */
    private function ordersToCharge(int $table, array $validated): Collection
    {
        $orders = $this->activeOrdersForTable($table);
        $ids = $validated['order_ids'] ?? [];
        $refs = $validated['order_refs'] ?? [];

        if ($ids === [] && $refs === []) {
            return $orders;
        }

        return $orders->filter(fn (Order $o) => in_array($o->id, $ids, true)
            || ($o->client_ref !== null && in_array($o->client_ref, $refs, true)))->values();
    }

    /** Reglas del cobro en línea: con internet, el mesero puede corregir antes de cobrar. */
    private function validarCobroEnLinea(Request $request, array $validated, int $table, Collection $orders, float $total, float $pagado, bool $kitchenPending): ?JsonResponse
    {
        if ($orders->isEmpty()) {
            return $this->error(422, 'nothing_to_charge', "La mesa {$table} no tiene pedidos pendientes de cobro.");
        }
        if ($pagado < $total) {
            return $this->error(422, 'insufficient_payment', 'La suma de los pagos es menor al total por cobrar.');
        }
        if (!$kitchenPending) {
            return null;
        }
        if (!$request->boolean('forced')) {
            return $this->error(409, 'kitchen_pending', 'Hay platillos de esta mesa todavía en preparación.');
        }
        if (!Hash::check((string) ($validated['password'] ?? ''), $request->user()->password)) {
            return $this->error(422, 'invalid_password', 'Contraseña incorrecta. No se pudo forzar el cobro.');
        }
        if (mb_strlen(trim((string) ($validated['reason'] ?? ''))) < 5) {
            return $this->error(422, 'reason_too_short', 'El motivo del cobro forzado debe tener al menos 5 caracteres.');
        }

        return null;
    }

    private function cobroRegistrado(TableCharge $charge, bool $duplicate): JsonResponse
    {
        return response()->json([
            'success' => true,
            'duplicate' => $duplicate,
            'data' => [
                'charge_id' => $charge->id,
                'status' => $charge->status,
                'total_due' => (float) $charge->total_due,
                'amount_received' => (float) $charge->amount_received,
            ],
        ], $duplicate ? 200 : 201, [], JSON_UNESCAPED_UNICODE);
    }

    /** Hora que reporta la app para algo hecho sin conexión, limitada a un rango razonable. */
    private function fechaCliente(?string $valor): Carbon
    {
        $ahora = Carbon::now();
        if (!$valor) {
            return $ahora;
        }

        try {
            $fecha = Carbon::parse($valor);
        } catch (\Throwable) {
            return $ahora;
        }

        if ($fecha->greaterThan($ahora) || $fecha->lessThan($ahora->copy()->subDays(self::OFFLINE_MAX_AGE_DAYS))) {
            return $ahora;
        }

        return $fecha;
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
