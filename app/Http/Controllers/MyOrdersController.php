<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Todos los pedidos del cliente con sesión. El perfil solo muestra los últimos; aquí están completos.
 * «Volver» regresa a donde estaba: al perfil si entró desde ahí, al menú si entró desde un ticket.
 */
class MyOrdersController extends Controller
{
    private const POR_PAGINA = 10;

    private const ORIGENES = [
        'perfil' => ['route' => 'perfil', 'label' => 'Volver a mi perfil'],
        'ticket' => ['route' => 'pedido', 'label' => 'Volver al menú'],
    ];

    public function index(Request $request)
    {
        $desde = $request->query('desde');
        $volver = self::ORIGENES[$desde] ?? self::ORIGENES['ticket'];

        $orders = Order::with('items:id,order_id,product_name,quantity')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('mis_pedidos', [
            'orders' => $orders,
            'volverUrl' => route($volver['route']),
            'volverLabel' => $volver['label'],
        ]);
    }
}
