<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\InvalidOrderLine;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\OrderLineBuilder;
use App\Services\PromotionLineBuilder;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $cartChanged = false;
        $total = 0;

        foreach ($cart as $id => $details) {

            // El precio se recalcula con la presentación y las opciones elegidas (o, en una
            // promoción, con los platillos que eligió el cliente)
            $linea = !empty($details['is_promotion'])
                ? $this->rebuildPromotionLine($details)
                : $this->rebuildLine($details, $id);

            if (!$linea) {
                unset($cart[$id]);
                $cartChanged = true;
            }
            else {
                if ($cart[$id]['price'] != $linea['price']) {
                    $cart[$id]['price'] = $linea['price'];
                    $cartChanged = true;
                }
                $total += $cart[$id]['price'] * $cart[$id]['quantity'];
            }
        }

        if ($cartChanged) {
            session()->put('cart', $cart);
            session()->flash('error', 'Algunos platillos de tu carrito se agotaron o cambiaron de precio y fueron actualizados automáticamente.');
        }

        // Obtener recomendaciones mediante el motor ML (Adaptativo: logueados vs deslogueados)
        $mlService = app(\App\Services\MlEngineService::class);
        $cartItemIds = array_filter(array_map(function($item) {
            return $item['id'] ?? null;
        }, $cart));
        // Las sugerencias son un extra: si el motor de ML no responde, el carrito se muestra sin ellas
        try {
            $recommendations = $mlService->getRecommendationsForCustomer(auth()->user(), $cartItemIds);
        } catch (\Throwable $e) {
            report($e);
            $recommendations = ['products' => collect()];
        }

        return view('carrito', compact('cart', 'total', 'recommendations'));
    }

    public function add(Request $request, OrderLineBuilder $builder)
    {
        $request->validate([
            'id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|integer',
            'options' => 'nullable|array',
            'excluded_ingredients' => 'nullable|array',
            'notes' => 'nullable|string|max:100'
        ]);

        // Nombre y precio salen de la base de datos, nunca del navegador
        $product = Product::find($request->id);
        if (!$product || !$product->available || $product->archived_at) {
            return $this->addError($request, 'Este platillo ya no está disponible.');
        }

        try {
            $linea = $builder->build($product, $request->filled('variant_id') ? (int) $request->variant_id : null, $request->input('options', []));
        } catch (InvalidOrderLine $e) {
            return $this->addError($request, $e->getMessage());
        }

        $cart = session()->get('cart', []);
        $id = $product->id;

        $excluded = $request->input('excluded_ingredients', []);
        sort($excluded);
        $notes = trim((string) $request->input('notes', ''));

        // Misma combinación (presentación, opciones, exclusiones y nota) = misma línea
        $keyParts = [];
        if ($linea['signature'] !== (string) $id) {
            $keyParts[] = md5($linea['signature']);
        }
        if (!empty($excluded)) {
            $keyParts[] = md5(json_encode($excluded));
        }
        if ($notes !== '') {
            $keyParts[] = md5($notes);
        }
        $cartKey = empty($keyParts) ? $id : $id . '_' . implode('_', $keyParts);

        if(isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += (int) $request->quantity;
        } else {
            $cart[$cartKey] = [
                "id" => (int)$id,
                "name" => $linea['product_name'],
                "quantity" => (int) $request->quantity,
                "price" => $linea['price'],
                "image" => $product->image,
                "variant_id" => $linea['variant_id'],
                "options" => array_map(fn ($o) => ['id' => $o['option_id'], 'quantity' => $o['quantity']], $linea['options']),
                "options_summary" => $this->optionsSummary($linea['options']),
                "excluded_ingredients" => $excluded,
                "notes" => $notes !== '' ? $notes : null
            ];
        }

        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'total_items' => count((array)$cart),
                'message' => '¡Platillo agregado al carrito!'
            ]);
        }

        return back()->with('success', '¡Platillo agregado al carrito!');
    }

    /**
     * Agrega una promoción al carrito como una sola línea con lo que incluye. Si tiene
     * renglones "a elegir", el cliente manda cuáles platillos quiere (choices[slot][]).
     * Misma promoción con la misma elección = misma línea.
     */
    public function addPromotion(Request $request, PromotionLineBuilder $builder)
    {
        $request->validate([
            'promotion_id' => 'required|integer|exists:promotions,id',
            'choices' => 'nullable|array',
        ]);

        $promotion = Promotion::with('products')->findOrFail($request->promotion_id);

        if (!$promotion->sellable_online) {
            return $this->addError($request, 'Esta promoción solo está disponible en el local, no se puede pedir en línea.');
        }

        if (!$promotion->isActiveToday()) {
            return $this->addError($request, 'Esta promoción solo está disponible: ' . $promotion->activeDaysLabel() . '.');
        }

        try {
            $linea = $builder->build($promotion, $request->input('choices', []));
        } catch (InvalidOrderLine $e) {
            return $this->addError($request, $e->getMessage());
        }

        $cart = session()->get('cart', []);
        $cartKey = $linea['signature'];

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += 1;
        } else {
            $cart[$cartKey] = [
                "id" => $cartKey,
                "name" => $promotion->title,
                "quantity" => 1,
                "price" => $linea['price'],
                "image" => $promotion->image,
                "excluded_ingredients" => [],
                "is_promotion" => true,
                "promotion_id" => $promotion->id,
                "promotion_choices" => $linea['choices'],
                "promotion_includes" => $linea['includes'],
            ];
        }

        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'total_items' => count((array) $cart),
                'message' => '¡Promoción agregada al carrito!'
            ]);
        }

        return back()->with('success', '¡Promoción agregada a tu carrito!');
    }

    /**
     * Vuelve a armar una promoción del carrito: null si ya no se puede pedir en línea o
     * si un platillo elegido se agotó. Los carritos de antes de poder elegir no traen
     * promotion_choices y se aceptan como estaban.
     */
    public static function rebuildPromotionLine(array $details): ?array
    {
        $promotion = Promotion::with('products')->find($details['promotion_id'] ?? null);
        if (!$promotion || !$promotion->active || !$promotion->sellable_online) {
            return null;
        }

        try {
            return app(PromotionLineBuilder::class)->build(
                $promotion,
                $details['promotion_choices'] ?? null,
                array_key_exists('promotion_choices', $details)
            );
        } catch (InvalidOrderLine) {
            return null;
        }
    }

    /**
     * Vuelve a armar una línea del carrito contra la base de datos (precio, opciones).
     * Null si el platillo ya no se puede pedir. Los carritos guardados antes de que
     * existieran las opciones no las traen: se aceptan sin ellas.
     */
    public static function rebuildLine(array $details, $key): ?array
    {
        $product = Product::find($details['id'] ?? $key);
        if (!$product || !$product->available || $product->archived_at) {
            return null;
        }

        try {
            return app(OrderLineBuilder::class)->build(
                $product,
                $details['variant_id'] ?? null,
                $details['options'] ?? null,
                array_key_exists('options', $details)
            );
        } catch (InvalidOrderLine) {
            return null;
        }
    }

    private function optionsSummary(array $opciones): ?string
    {
        $resumen = (new \App\Models\OrderItem(['options' => $opciones]))->optionsSummary();

        return $resumen !== '' ? $resumen : null;
    }

    private function addError(Request $request, string $mensaje)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $mensaje], 422);
        }

        return back()->with('error', $mensaje);
    }

    public function remove(Request $request)
    {
        if($request->id) {
            $cart = session()->get('cart');
            if(isset($cart[$request->id])) {
                unset($cart[$request->id]);
                session()->put('cart', $cart);
            }
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'cart' => $cart,
                    'total_items' => count((array)$cart),
                    'message' => 'Platillo eliminado del carrito.'
                ]);
            }
            return back()->with('success', 'Platillo eliminado del carrito.');
        }
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'ID no proporcionado.'
            ], 400);
        }
    }

    public function clear()
    {
        session()->forget('cart');
        return back()->with('success', 'Carrito vaciado.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'quantity' => 'required|numeric'
        ]);

        $cart = session()->get('cart');

        if(isset($cart[$request->id])) {
            if($request->quantity <= 0) {
                unset($cart[$request->id]);
            } else {
                $cart[$request->id]['quantity'] = (int) $request->quantity;
            }
            session()->put('cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'total_items' => count((array)$cart),
                'message' => 'Carrito actualizado.'
            ]);
        }

        return back();
    }
}