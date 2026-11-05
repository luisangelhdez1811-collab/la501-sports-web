<?php

namespace App\Http\Controllers\Api\Customer;

use App\Exceptions\InvalidOrderLine;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\AchievementService;
use App\Services\OrderLineBuilder;
use App\Services\PromotionLineBuilder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // B-06: acepta el nombre o el id del ingrediente; se guarda siempre el nombre
            'items.*.excluded_ingredients.*' => ['max:100', function ($attribute, $value, $fail) {
                if (!is_scalar($value)) {
                    $fail('El ingrediente excluido no es válido.');
                }
            }],
            'items.*.notes' => ['nullable', 'string', 'max:100'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.options' => ['nullable', 'array'],
            'items.*.choices' => ['nullable', 'array'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_address' => ['required', 'string'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', 'in:efectivo,tarjeta'],
            // B-03: folio que genera el cliente; un reintento o doble clic no crea otro pedido
            'client_ref' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
        ]);

        // La ruta es pública (invitados), así que el usuario se lee a mano. 'sanctum' reconoce también
        // las sesiones JWT de la app porque config/sanctum.php incluye 'jwt' en 'guard' (B-09).
        $user = $request->user('sanctum');
        $clientRef = $validated['client_ref'] ?? null;

        // Reintento: el mismo folio devuelve el mismo pedido
        if ($clientRef && ($existente = Order::where('client_ref', $clientRef)->first())) {
            return $this->pedidoRepetido($existente, $user, $validated['customer_phone']);
        }

        [$lines, $error] = $this->buildLinesFromDb($validated['items']);
        if ($error) {
            return response()->json(['success' => false, 'error' => 'item_unavailable', 'message' => $error], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $total = array_sum(array_column($lines, 'subtotal'));

        try {
            $order = DB::transaction(function () use ($user, $validated, $total, $lines, $clientRef) {
                $order = Order::create([
                    'user_id' => $user?->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_address' => $validated['customer_address'],
                    'customer_email' => $validated['customer_email'] ?? null,
                    'total' => $total,
                    'status' => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'client_ref' => $clientRef,
                ]);

                foreach ($lines as $line) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $line['product_id'],
                        'product_name' => $line['name'],
                        'station' => $line['station'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                        'excluded_ingredients' => $line['excluded_ingredients'],
                        'options' => $line['options'],
                        'notes' => $line['notes'],
                    ]);
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Dos peticiones con el mismo folio llegaron al mismo tiempo: la segunda choca con el índice único
            if ($clientRef && ($existente = Order::where('client_ref', $clientRef)->first())) {
                return $this->pedidoRepetido($existente, $user, $validated['customer_phone']);
            }

            throw $e;
        }

        return $this->handlePaymentMethod($validated['payment_method'], $order, $total, $lines, $user);
    }

    /**
     * El folio ya existía. Si es del mismo cliente (o de un invitado con el mismo teléfono) se devuelve
     * ESE pedido y no es un error; si es de otra cuenta, se pide generar un folio nuevo.
     */
    private function pedidoRepetido(Order $existente, $user, string $telefono): JsonResponse
    {
        $esDelMismoCliente = $existente->user_id
            ? $user && (int) $existente->user_id === (int) $user->id
            : !$user && $this->soloDigitos($existente->customer_phone) === $this->soloDigitos($telefono);

        if (!$esDelMismoCliente) {
            return response()->json([
                'success' => false,
                'error' => 'duplicate_reference',
                'message' => 'Ese folio pertenece a otra cuenta. Genera un folio nuevo e inténtalo de nuevo.',
            ], 409, [], JSON_UNESCAPED_UNICODE);
        }

        $respuesta = [
            'success' => true,
            'duplicate' => true,
            'payment_method' => $existente->payment_method,
            'order' => [
                'id' => $existente->id,
                'tracking_token' => $existente->trackingToken(),
                'status' => $existente->status,
                'total' => (float) $existente->total,
            ],
        ];

        // Tarjeta sin pagar: se devuelve un enlace de pago del mismo pedido, no se crea otro pedido
        if ($existente->payment_method === 'tarjeta' && $existente->status === 'pending') {
            $url = $this->enlaceDePago($existente);
            if ($url) {
                $respuesta['checkout_url'] = $url;
            }
        }

        return response()->json($respuesta, 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function soloDigitos(?string $valor): string
    {
        return preg_replace('/\D+/', '', (string) $valor);
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

                // Versiones de la app que no eligen platillos: no mandan "choices" y la comanda
                // pide confirmarlos con el cliente
                try {
                    $promo = app(PromotionLineBuilder::class)->build(
                        $promotion,
                        $item['choices'] ?? null,
                        isset($item['choices'])
                    );
                } catch (InvalidOrderLine $e) {
                    return [[], $e->getMessage()];
                }

                $lines[] = [
                    'product_id' => null,
                    'name' => $promo['product_name'],
                    'station' => $promo['station'],
                    'quantity' => $quantity,
                    'price' => $promo['price'],
                    'subtotal' => $promo['price'] * $quantity,
                    'excluded_ingredients' => [],
                    'options' => $promo['options'],
                    'notes' => null,
                ];

                continue;
            }

            $product = Product::find($item['product_id'] ?? null);

            if (!$product || !$product->available || $product->archived_at) {
                return [[], 'Un platillo de tu pedido ya no está disponible.'];
            }

            // Versiones de la app sin opciones: no mandan "options" y se acepta sin ellas
            try {
                $linea = app(OrderLineBuilder::class)->build(
                    $product,
                    isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                    $item['options'] ?? null,
                    isset($item['options'])
                );
            } catch (InvalidOrderLine $e) {
                return [[], $e->getMessage()];
            }

            $excluded = $this->nombresExcluidos($product, $item['excluded_ingredients'] ?? []);
            $notes = trim((string) ($item['notes'] ?? ''));
            $price = $linea['price'];

            $lines[] = [
                'product_id' => $product->id,
                'name' => $linea['product_name'],
                'station' => $linea['station'],
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
                'excluded_ingredients' => $excluded,
                'options' => $linea['options'],
                'notes' => $notes !== '' ? mb_substr($notes, 0, 100) : null,
            ];
        }

        return [$lines, null];
    }

    /**
     * B-06: la app puede mandar el nombre o el id del ingrediente. En la comanda siempre se lee el
     * nombre. Un id que no es de este producto se ignora (no hay forma de saber qué era); un nombre
     * que no coincide se conserva tal cual para no perder una exclusión que puede ser una alergia.
     *
     * @param  array<int, int|string>  $valores
     * @return array<int, string>
     */
    private function nombresExcluidos(Product $product, array $valores): array
    {
        $ingredientes = $product->ingredientes()->get(['id', 'nombre']);
        $porId = $ingredientes->keyBy('id');
        $porNombre = $ingredientes->keyBy(fn ($i) => mb_strtolower(trim($i->nombre)));

        $nombres = [];
        foreach ($valores as $valor) {
            $valor = trim((string) $valor);
            if ($valor === '') {
                continue;
            }

            if (ctype_digit($valor)) {
                $ingrediente = $porId->get((int) $valor);
                if ($ingrediente) {
                    $nombres[] = $ingrediente->nombre;
                }
                continue;
            }

            $nombres[] = $porNombre->get(mb_strtolower($valor))->nombre ?? $valor;
        }

        $nombres = array_values(array_unique($nombres));
        sort($nombres);

        return $nombres;
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

        // Tarjeta: se crea la preferencia de Mercado Pago y la app abre "init_point" en un WebView.
        $url = $this->enlaceDePago($order);

        if ($url) {
            return response()->json([
                'success' => true,
                'payment_method' => 'tarjeta',
                'checkout_url' => $url,
                'order' => [
                    'id' => $order->id,
                    'tracking_token' => $order->trackingToken(),
                    'status' => $order->status,
                    'total' => $total,
                ],
            ], 201, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'success' => false,
            'error' => 'mercadopago_unavailable',
            'message' => 'No pudimos conectar con el sistema de pago con tarjeta. Intenta de nuevo o elige pago en efectivo.',
            'order' => ['id' => $order->id],
        ], 502, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Crea la preferencia de Mercado Pago de un pedido (con los renglones ya guardados) y devuelve el
     * enlace de pago, o null si Mercado Pago no respondió. Sirve igual para un pedido nuevo que para
     * el reintento de uno que sigue sin pagar. Mercado Pago redirige de vuelta a las rutas web
     * payment.success/failure.
     */
    private function enlaceDePago(Order $order): ?string
    {
        $mpItems = $order->items->map(fn ($item) => [
            'title' => $item->product_name,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->price,
            'currency_id' => 'MXN',
        ])->all();

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
            return $response->json()['init_point'];
        }

        Log::error('Fallo de Mercado Pago al crear preferencia de pago (app).', [
            'order_id' => $order->id,
            'response' => $response->json(),
        ]);

        return null;
    }
}
