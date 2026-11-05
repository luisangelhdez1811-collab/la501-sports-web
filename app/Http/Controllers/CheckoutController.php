<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Payments\MercadoPago;
use App\Services\Payments\OrderPayments;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        // Folio de este intento de pedido: si el cliente da doble clic o reenvía el formulario,
        // el servidor reconoce el mismo folio y no crea otro pedido (B-03).
        $clientRef = 'web_' . Str::random(32);

        return view('checkout', compact('total', 'clientRef'));
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
            'client_ref' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
        ]);

        $clientRef = $request->input('client_ref');

        // Doble clic o reintento: el mismo folio lleva al mismo pedido (B-03)
        if ($clientRef && ($existente = Order::where('client_ref', $clientRef)->first())) {
            return $this->pedidoRepetido($existente, $request);
        }

        $cart = session()->get('cart');

        // 1. Validar estado del carrito (Barrera de Seguridad 3)
        $cartValidation = $this->validateCart($cart);
        if ($cartValidation) {
            return $cartValidation;
        }

        // 2. Preparar los datos, crear Orden y Platillos
        try {
            $orderData = $this->createOrder($request, $cart, $clientRef);
        } catch (UniqueConstraintViolationException $e) {
            // Dos envíos con el mismo folio llegaron al mismo tiempo: el segundo choca con el índice único
            if ($clientRef && ($existente = Order::where('client_ref', $clientRef)->first())) {
                return $this->pedidoRepetido($existente, $request);
            }

            throw $e;
        }

        // 3. Procesar Pago y retornar la redirección final
        return $this->handlePaymentMethod(
            $request->payment_method,
            $orderData['order'],
            $orderData['total']
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

    private function createOrder(Request $request, array $cart, ?string $clientRef = null): array
    {
        $total = 0;

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

        foreach ($cart as $details) {
            $total += $details['price'] * $details['quantity'];
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
            'client_ref' => $clientRef,
        ];

        // La ubicación GPS es opcional; solo se envía si el cliente la compartió
        if ($request->filled('delivery_lat') && $request->filled('delivery_lng')) {
            $orderData['delivery_lat'] = round((float) $request->delivery_lat, 7);
            $orderData['delivery_lng'] = round((float) $request->delivery_lng, 7);
        }

        // El pedido y sus platillos se guardan juntos o no se guarda nada
        $order = DB::transaction(function () use ($orderData, $cart) {
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

            return $order;
        });

        return [
            'order' => $order,
            'total' => $total,
        ];
    }

    private function handlePaymentMethod(string $method, Order $order, float $total)
    {
        // Lógica si es EFECTIVO
        if ($method === 'efectivo') {
            $order->status = 'paid';
            $order->save();

            session()->forget('cart');
            // Una sola vez por pedido (B-02); un invitado no tiene puntos que ganar.
            app(OrderPayments::class)->awardPoints($order);
            session()->put('last_order_id', $order->id);
            return redirect()->route('payment.confirmation', $order->id)->with('success', '¡Pedido recibido! Lo prepararemos enseguida y pagarás al recibir.');
        }

        // Lógica si es TARJETA (Mercado Pago): el pedido queda pendiente y NO llega a cocina hasta que
        // el pago se confirme contra la API de Mercado Pago (webhook o página de regreso).
        $url = app(MercadoPago::class)->createPreference($order);

        if ($url) {
            session()->forget('cart');
            session()->put('last_order_id', $order->id);
            return redirect()->away($url);
        }

        return redirect()->route('cart.index')->with('error', 'No pudimos conectar con el sistema de pago con tarjeta. Intenta de nuevo o elige pago en efectivo.');
    }

    /**
     * El folio ya existía. Si es del mismo cliente (o de un invitado con el mismo teléfono) se lleva a
     * ESE pedido, sin crear otro; si es de otra cuenta, se pide intentarlo de nuevo.
     */
    private function pedidoRepetido(Order $existente, Request $request)
    {
        $digitos = fn (?string $valor) => preg_replace('/\D+/', '', (string) $valor);

        $esDelMismoCliente = $existente->user_id
            ? Auth::check() && (int) $existente->user_id === (int) Auth::id()
            : !Auth::check() && $digitos($existente->customer_phone) === $digitos($request->input('customer_phone'));

        if (!$esDelMismoCliente) {
            return redirect()->route('checkout.index')->with('error', 'No pudimos procesar tu pedido porque ese envío ya se había usado. Revisa tu carrito e inténtalo de nuevo.');
        }

        session()->forget('cart');
        session()->put('last_order_id', $existente->id);

        // Tarjeta sin pagar: se ofrece un enlace de pago del mismo pedido, no se crea otro
        if ($existente->payment_method === 'tarjeta' && $existente->status === 'pending') {
            $url = app(MercadoPago::class)->createPreference($existente);
            if ($url) {
                return redirect()->away($url);
            }
        }

        return redirect()->route('payment.confirmation', $existente->id)->with('success', 'Tu pedido ya estaba registrado.');
    }

    // =====================================================================
    // Páginas de regreso de Mercado Pago
    //
    // Sirven solo para mostrarle una pantalla al cliente. Nunca son prueba de pago: el pedido solo
    // cambia de estado si el pago, consultado a la API de Mercado Pago, existe, es de este pedido y
    // coincide en monto y moneda (OrderPayments::syncCardPayment). Lo mismo hace el webhook,
    // así que el pedido se confirma aunque el cliente cierre la pestaña antes de regresar.
    // =====================================================================

    /** El pedido al que regresa el cliente, identificado por el token que viajó en la URL de regreso. */
    private function pedidoDeRegreso(Request $request): ?Order
    {
        $token = (string) $request->query('t');

        return strlen($token) >= 20 ? Order::where('tracking_token', $token)->first() : null;
    }

    public function success(Request $request)
    {
        $order = $this->pedidoDeRegreso($request);

        if ($order && $request->filled('payment_id')) {
            $order = app(OrderPayments::class)->syncCardPayment($request->query('payment_id'), $order) ?? $order->fresh();
        }

        if (!$order) {
            return redirect()->route('pedido')->with('success', 'Estamos confirmando tu pago. Si se aprueba, tu pedido aparecerá en «Mis pedidos».');
        }

        session()->put('last_order_id', $order->id);

        if (in_array($order->status, ['paid', 'preparing', 'ready', 'delivering', 'delivered'], true)) {
            return redirect()->route('payment.confirmation', $order->id)->with('success', '¡Pago aprobado! 💳 Tu pedido ya se está preparando en cocina.');
        }

        if ($order->status === 'failed') {
            return redirect()->route('payment.confirmation', $order->id)->with('error', 'El pago fue rechazado. Tu pedido no se ha procesado.');
        }

        return redirect()->route('payment.confirmation', $order->id)->with('success', 'Estamos confirmando tu pago. Te avisaremos en cuanto se apruebe.');
    }

    public function failure(Request $request)
    {
        $order = $this->pedidoDeRegreso($request);

        if ($order && $request->filled('payment_id')) {
            app(OrderPayments::class)->syncCardPayment($request->query('payment_id'), $order);
        }

        return redirect()->route('pedido')->with('error', 'El pago fue rechazado o cancelado. Tu pedido no se ha procesado.');
    }

    public function pending(Request $request)
    {
        $order = $this->pedidoDeRegreso($request);

        if ($order) {
            session()->put('last_order_id', $order->id);

            return redirect()->route('payment.confirmation', $order->id)->with('success', 'Estamos confirmando tu pago. Te avisaremos en cuanto se apruebe.');
        }

        return redirect()->route('pedido')->with('success', 'Estamos confirmando tu pago. Si se aprueba, tu pedido aparecerá en «Mis pedidos».');
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
