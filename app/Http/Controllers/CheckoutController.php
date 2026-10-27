<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart');

        if (!$cart || count($cart) == 0) {
            return redirect()->route('pedido')->with('error', 'Tu carrito está vacío.');
        }


        foreach ($cart as $id => $details) {
            if (!empty($details['is_promotion'])) {
                if (!$this->promotionAvailable($details)) {
                    return redirect()->route('cart.index')->with('error', '¡Ups! Una promoción que ibas a pedir ya no está disponible hoy. Por favor revisa tu pedido.');
                }
                continue;
            }
            if (!CartController::rebuildLine($details, $id)) {
                return redirect()->route('cart.index')->with('error', '¡Ups! Un platillo que ibas a pedir se acaba de agotar. Por favor revisa tu pedido.');
            }
        }

        $total = 0;
        foreach ($cart as $details) {
            $total += $details['price'] * $details['quantity'];
        }

        return view('checkout', compact('total'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'delivery_lat' => 'nullable|required_with:delivery_lng|numeric|between:-90,90',
            'delivery_lng' => 'nullable|required_with:delivery_lat|numeric|between:-180,180',
            'customer_email' => 'nullable|email|max:255',
            'payment_method' => 'required|in:efectivo,tarjeta',
        ]);

        $cart = session()->get('cart');
        
        // 1. Validar estado del carrito (Barrera de Seguridad 3)
        $cartValidation = $this->validateCart($cart);
        if ($cartValidation) {
            return $cartValidation;
        }

        // 2. Preparar los datos, crear Orden y Platillos
        $orderData = $this->createOrder($request, $cart);

        // 3. Procesar Pago y retornar la redirección final
        return $this->handlePaymentMethod(
            $request->payment_method,
            $orderData['order'],
            $orderData['total'],
            $orderData['mp_items']
        );
    }

    // =====================================================================
    // Funciones Privadas (Helpers) para reducir la Complejidad Cognitiva
    // =====================================================================

    private function validateCart($cart)
    {
        if (!$cart || count($cart) == 0) {
            return redirect()->route('pedido');
        }

        foreach ($cart as $id => $details) {
            if (!empty($details['is_promotion'])) {
                if (!$this->promotionAvailable($details)) {
                    return redirect()->route('cart.index')->with('error', 'La compra no pudo completarse porque una promoción ya no está disponible hoy.');
                }
                continue;
            }
            if (!CartController::rebuildLine($details, $id)) {
                return redirect()->route('cart.index')->with('error', 'La compra no pudo completarse porque un platillo ya no está disponible en inventario.');
            }
        }

        return null;
    }

    private function promotionAvailable(array $details): bool
    {
        $promotion = Promotion::find($details['promotion_id'] ?? null);

        return $promotion && $promotion->isActiveToday() && CartController::rebuildPromotionLine($details) !== null;
    }

    private function createOrder(Request $request, array $cart): array
    {
        $total = 0;
        $items_mercadopago = [];

        // Precio, nombre y opciones de cada platillo salen de la base de datos (validateCart
        // ya confirmó que todos se pueden pedir)
        foreach ($cart as $id => $details) {
            $linea = empty($details['is_promotion'])
                ? CartController::rebuildLine($details, $id)
                : CartController::rebuildPromotionLine($details);
            $cart[$id]['name'] = $linea['product_name'];
            $cart[$id]['price'] = $linea['price'];
            $cart[$id]['station'] = $linea['station'];
            $cart[$id]['options_snapshot'] = $linea['options'];
        }

        // Calcular totales y armar el arreglo para Mercado Pago
        foreach ($cart as $details) {
            $total += $details['price'] * $details['quantity'];
            
            $items_mercadopago[] = [
                'title' => $details['name'],
                'quantity' => (int) $details['quantity'],
                'unit_price' => (float) $details['price'],
                'currency_id' => 'MXN',
            ];
        }

        // Guardar la Orden Principal
        $orderData = [
            'user_id' => Auth::id(), // Auth::id() retorna null automáticamente si no hay sesión
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'customer_email' => $request->customer_email,
            'total' => $total,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
        ];

        // La ubicación GPS es opcional; solo se envía si el cliente la compartió
        if ($request->filled('delivery_lat') && $request->filled('delivery_lng')) {
            $orderData['delivery_lat'] = round((float) $request->delivery_lat, 7);
            $orderData['delivery_lng'] = round((float) $request->delivery_lng, 7);
        }

        $order = Order::create($orderData);

        // Guardar los Platillos (y las promociones, sin cobrarlas por separado)
        foreach ($cart as $id => $details) {
            $isPromotion = !empty($details['is_promotion']);

            // Lo que incluye la promoción viaja en las opciones ("Incluye: 2x Alitas 700 g")
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $isPromotion ? null : ($details['id'] ?? $id),
                'product_name' => $details['name'],
                'station' => $details['station'] ?? Product::STATION_KITCHEN,
                'quantity' => $details['quantity'],
                'price' => $details['price'],
                'subtotal' => $details['price'] * $details['quantity'],
                'excluded_ingredients' => $details['excluded_ingredients'] ?? [],
                'options' => $details['options_snapshot'] ?? [],
                'notes' => !empty($details['notes']) ? mb_substr($details['notes'], 0, 100) : null,
            ]);
        }

        return [
            'order' => $order,
            'total' => $total,
            'mp_items' => $items_mercadopago
        ];
    }

    private function handlePaymentMethod(string $method, Order $order, float $total, array $items_mercadopago)
    {
        // Lógica si es EFECTIVO
        if ($method === 'efectivo') {
            $order->status = 'paid';
            $order->save();

            session()->forget('cart');
            if (Auth::check()) {
                $user = \App\Models\User::find(Auth::id());
                $user->points += floor($total / 10);
                $user->save();

                (new \App\Services\AchievementService())->check($user);
            }
            session()->put('last_order_id', $order->id);
            return redirect()->route('payment.confirmation', $order->id)->with('success', '¡Pedido recibido! Lo prepararemos enseguida y pagarás al recibir.');
        }
        
        // Lógica si es TARJETA (Mercado Pago)
        $response = Http::withToken(env('MERCADOPAGO_ACCESS_TOKEN'))->post('https://api.mercadopago.com/checkout/preferences', [
            'items' => $items_mercadopago,
            'external_reference' => $order->id,
            'back_urls' => [
                'success' => route('payment.success'),
                'failure' => route('payment.failure'),
                'pending' => route('payment.failure'),
            ],
            'auto_return' => 'approved',
        ]);

        if ($response->successful()) {
            $datos = $response->json();
            if (isset($datos['init_point'])) {
                session()->forget('cart');
                return redirect()->away($datos['init_point']);
            }
        }

        \Illuminate\Support\Facades\Log::error('Fallo de Mercado Pago al crear preferencia de pago.', [
            'order_id' => $order->id,
            'response' => $response->json(),
        ]);

        return redirect()->route('cart.index')->with('error', 'No pudimos conectar con el sistema de pago con tarjeta. Intenta de nuevo o elige pago en efectivo.');
    }

    // =====================================================================
    // Callbacks de Mercado Pago
    // =====================================================================

    public function success(Request $request)
    {
        $order_id = $request->get('external_reference');
        $payment_id = $request->get('payment_id');

        if ($order_id) {
            $order = Order::find($order_id);
            if ($order) {
                $order->status = 'paid';
                $order->payment_id = $payment_id;
                $order->save();

                if ($order->user_id) {
                    $user = \App\Models\User::find($order->user_id);
                    $user->points += floor($order->total / 10);
                    $user->save();

                    (new \App\Services\AchievementService())->check($user);
                }
                session()->put('last_order_id', $order->id);
            }
        }

        if (isset($order)) {
            return redirect()->route('payment.confirmation', $order->id)->with('success', '¡Pago aprobado! 💳 Tu pedido ya se está preparando en cocina.');
        }

        return redirect()->route('pedido')->with('success', '¡Pago aprobado! 💳 Tu pedido ya se está preparando en cocina.');
    }

    public function failure()
    {
        return redirect()->route('pedido')->with('error', 'El pago fue rechazado o cancelado. Tu pedido no se ha procesado.');
    }

    public function confirmation($id)
    {
        $order = Order::with('items')->findOrFail($id);

        $isAuthorized = false;
        if (Auth::check()) {
            if ($order->user_id === Auth::id() || in_array(Auth::user()->role, ['admin', 'cocinero', 'empleado'])) {
                $isAuthorized = true;
            }
        }
        if (session('last_order_id') == $id) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            abort(403, 'No tienes permiso para ver este ticket.');
        }

        return redirect()->route('order.track', $order->trackingToken());
    }

    public function apiStatus($id)
    {
        $order = Order::findOrFail($id);

        $isAuthorized = false;
        if (Auth::check()) {
            if ($order->user_id === Auth::id() || in_array(Auth::user()->role, ['admin', 'cocinero', 'empleado'])) {
                $isAuthorized = true;
            }
        }
        if (session('last_order_id') == $id) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json(['status' => $order->status]);
    }
}