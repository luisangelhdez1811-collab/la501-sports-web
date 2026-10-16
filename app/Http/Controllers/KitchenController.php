<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Auth;

class KitchenController extends Controller
{
    private function checkAccess()
    {
        return Auth::check() && Auth::user()->role === 'cocinero';
    }

    public function index()
    {
        if (!$this->checkAccess()) {
            return abort(403);
        }

        $orders = $this->activeOrdersQuery()->get();

        return view('cocina.index', compact('orders'));
    }

    public function markAsReady(Request $request, $id, OrderNotificationService $notifier)
    {
        if (!$this->checkAccess()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $order = Order::findOrFail($id);

        if ($order->esADomicilio()) {
            // A domicilio queda "listo" esperando al mandadito; el correo "va en camino"
            // sale cuando el empleado lo entrega (WaiterController::entregarAlMandadito).
            $order->status = 'ready';
            $order->save();
        } else {
            $order->status = 'ready';
            $order->save();

            $notifier->notificarEstado($order, 'ready');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('kitchen.index')->with('success', 'Pedido marcado como listo');
    }

    public function apiActiveOrders()
    {
        if (!$this->checkAccess()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json($this->activeOrdersQuery()->get());
    }

    private function activeOrdersQuery()
    {
        return Order::with('items')
            ->where(function ($query) {
                $query->whereNotNull('table_number')
                      ->whereIn('status', ['pending', 'preparing']);
            })
            ->orWhere(function ($query) {
                $query->whereNull('table_number')
                      ->whereIn('status', ['paid', 'preparing']);
            })
            ->orderBy('created_at', 'asc');
    }
}
