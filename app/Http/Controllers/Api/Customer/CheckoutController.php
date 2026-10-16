<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.promotion_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.excluded_ingredients' => ['nullable', 'array'],
            'items.*.excluded_ingredients.*' => ['string'],
            'items.*.notes' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_address' => ['required', 'string'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', 'in:efectivo,tarjeta'],
        ]);

        [$lines, $error] = $this->buildLinesFromDb($validated['items']);
        if ($error) {
            return response()->json(['success' => false, 'error' => 'item_unavailable', 'message' => $error], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $total = array_sum(array_column($lines, 'subtotal'));
        $user = $request->user('sanctum');

        $order = Order::create([
            'user_id' => $user?->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_address' => $validated['customer_address'],
            'customer_email' => $validated['customer_email'] ?? null,
            'total' => $total,
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
        ]);

        foreach ($lines as $line) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $line['product_id'],
                'product_name' => $line['name'],
                'quantity' => $line['quantity'],
                'price' => $line['price'],
                'subtotal' => $line['subtotal'],
                'excluded_ingredients' => $line['excluded_ingredients'],
                'notes' => $line['notes'],
            ]);
        }

        return $this->handlePaymentMethod($validated['payment_method'], $order, $total, $lines, $user);
    }

    /**
     * Recalcula cada línea del pedido contra la base de datos (nunca confía
     * en el precio/nombre que mande el cliente). Devuelve [$lineas, $error].
     */
    private function buildLinesFromDb(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];

            if (!empty($item['promotion_id'])) {
                $promotion = Promotion::with('products')->find($item['promotion_id']);

                if (!$promotion || !$promotion->active || !$promotion->sellable_online || !$promotion->isActiveToday()) {
                    return [[], 'Una promoción de tu pedido ya no está disponible. Actualiza tu carrito.'];
                }

                $includedNames = $promotion->products->map(function ($p) {
                    $qty = $p->pivot->quantity ?? 1;
                    return ($qty > 1 ? $qty . 'x ' : '') . $p->name;
                })->implode(', ');

                $name = $promotion->title . ($includedNames ? ' (Promoción — incluye: ' . $includedNames . ')' : '');
                $price = (float) $promotion->price;

                $lines[] = [
                    'product_id' => null,
                    'name' => $name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $price * $quantity,
                    'excluded_ingredients' => [],
                    'notes' => null,
                ];

                continue;
            }

            $product = Product::find($item['product_id'] ?? null);

            if (!$product || !$product->available) {
                return [[], 'Un platillo de tu pedido ya no está disponible.'];
            }

            $excluded = $item['excluded_ingredients'] ?? [];
            sort($excluded);
            $notes = trim((string) ($item['notes'] ?? ''));
            $price = (float) $product->price;

            $lines[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
                'excluded_ingredients' => $excluded,
                'notes' => $notes !== '' ? mb_substr($notes, 0, 100) : null,
            ];
        }

        return [$lines, null];
    }

    private function handlePaymentMethod(string $method, Order $order, float $total, array $lines, $user): JsonResponse
    {
        if ($method === 'efectivo') {
            $order->status = 'paid';
            $order->save();

            if ($user) {
                $user->points += floor($total / 10);
                $user->save();
                (new AchievementService())->check($user);
            }

            return response()->json([
                'success' => true,
                'payment_method' => 'efectivo',
                'message' => '¡Pedido recibido! Lo prepararemos enseguida y pagarás al recibir.',
                'order' => [
                    'id' => $order->id,
                    'tracking_token' => $order->trackingToken(),
                    'status' => $order->status,
                    'total' => $total,
                ],
            ], 201, [], JSON_UNESCAPED_UNICODE);
        }

        // Tarjeta: se crea la preferencia de Mercado Pago y la app abre
        // "init_point" en un WebView. Mercado Pago redirige de vuelta a las
        // mismas rutas web de siempre (payment.success/failure), que ya
        // actualizan el pedido — no hace falta tocar nada de eso.
        $mpItems = array_map(fn ($line) => [
            'title' => $line['name'],
            'quantity' => (int) $line['quantity'],
            'unit_price' => (float) $line['price'],
            'currency_id' => 'MXN',
        ], $lines);

        $response = Http::withToken(env('MERCADOPAGO_ACCESS_TOKEN'))->post('https://api.mercadopago.com/checkout/preferences', [
            'items' => $mpItems,
            'external_reference' => $order->id,
            'back_urls' => [
                'success' => route('payment.success'),
                'failure' => route('payment.failure'),
                'pending' => route('payment.failure'),
            ],
            'auto_return' => 'approved',
        ]);

        if ($response->successful() && isset($response->json()['init_point'])) {
            return response()->json([
                'success' => true,
                'payment_method' => 'tarjeta',
                'checkout_url' => $response->json()['init_point'],
                'order' => [
                    'id' => $order->id,
                    'tracking_token' => $order->trackingToken(),
                    'status' => $order->status,
                    'total' => $total,
                ],
            ], 201, [], JSON_UNESCAPED_UNICODE);
        }

        Log::error('Fallo de Mercado Pago al crear preferencia de pago (app).', [
            'order_id' => $order->id,
            'response' => $response->json(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'mercadopago_unavailable',
            'message' => 'No pudimos conectar con el sistema de pago con tarjeta. Intenta de nuevo o elige pago en efectivo.',
            'order' => ['id' => $order->id],
        ], 502, [], JSON_UNESCAPED_UNICODE);
    }
}
