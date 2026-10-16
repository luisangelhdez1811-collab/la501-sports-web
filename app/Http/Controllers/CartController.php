<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Promotion;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $cartChanged = false;
        $total = 0;

        foreach ($cart as $id => $details) {

            // Las líneas de promoción no son un Product real — se revalidan aparte.
            if (!empty($details['is_promotion'])) {
                $promotion = Promotion::find($details['promotion_id']);
                if (!$promotion || !$promotion->active || !$promotion->sellable_online) {
                    unset($cart[$id]);
                    $cartChanged = true;
                } else {
                    if ($cart[$id]['price'] != $promotion->price) {
                        $cart[$id]['price'] = $promotion->price;
                        $cartChanged = true;
                    }
                    $total += $cart[$id]['price'] * $cart[$id]['quantity'];
                }
                continue;
            }

            $productId = $details['id'] ?? $id;
            $product = Product::find($productId);

            if (!$product || $product->available == 0) {
                unset($cart[$id]);
                $cartChanged = true;
            }
            else {
                if ($cart[$id]['price'] != $product->price) {
                    $cart[$id]['price'] = $product->price;
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
        $recommendations = $mlService->getRecommendationsForCustomer(auth()->user(), $cartItemIds);

        return view('carrito', compact('cart', 'total', 'recommendations'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
            'image' => 'nullable|string',
            'excluded_ingredients' => 'nullable|array',
            'notes' => 'nullable|string|max:100'
        ]);

        $cart = session()->get('cart', []);
        $id = $request->id;

        $excluded = $request->input('excluded_ingredients', []);
        sort($excluded);
        $notes = trim((string) $request->input('notes', ''));

        $keyParts = [];
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
                "name" => $request->name,
                "quantity" => (int) $request->quantity,
                "price" => $request->price,
                "image" => $request->image,
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
     * Agrega el paquete de una promoción al carrito como una sola línea,
     * marcada con is_promotion para que no se cobre por separado y muestre
     * de dónde viene.
     */
    public function addPromotion(Request $request)
    {
        $request->validate([
            'promotion_id' => 'required|integer|exists:promotions,id',
        ]);

        $promotion = Promotion::with('products')->findOrFail($request->promotion_id);

        if (!$promotion->sellable_online) {
            $msg = 'Esta promoción solo está disponible en el local, no se puede pedir en línea.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        if (!$promotion->isActiveToday()) {
            $msg = 'Esta promoción solo está disponible: ' . $promotion->activeDaysLabel() . '.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $cart = session()->get('cart', []);
        $cartKey = 'promo_' . $promotion->id;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += 1;
        } else {
            $includedNames = $promotion->products->map(function ($p) {
                $qty = $p->pivot->quantity ?? 1;
                return ($qty > 1 ? $qty . 'x ' : '') . $p->name;
            })->implode(', ');

            $cart[$cartKey] = [
                "id" => $cartKey,
                "name" => $promotion->title,
                "quantity" => 1,
                "price" => (float) $promotion->price,
                "image" => $promotion->image,
                "excluded_ingredients" => [],
                "is_promotion" => true,
                "promotion_id" => $promotion->id,
                "promotion_includes" => $includedNames,
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