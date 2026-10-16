<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WaiterPerformanceController extends Controller
{
    private const ZONA_HORARIA = 'America/Mexico_City';

    public function index(Request $request)
    {
        $rango = $request->get('rango', 'hoy');
        $ahora = Carbon::now(self::ZONA_HORARIA);

        $desde = match ($rango) {
            'semana' => $ahora->copy()->startOfWeek(),
            'mes'    => $ahora->copy()->startOfMonth(),
            'todo'   => null,
            default  => $ahora->copy()->startOfDay(),
        };

        $query = Order::with(['waiter:id,name', 'chargedBy:id,name'])
            ->whereNotNull('table_number')
            ->where('status', 'paid');

        if ($desde) {
            $query->where('updated_at', '>=', $desde->copy()->setTimezone(config('app.timezone', 'UTC')));
        }

        $orders = $query->orderByDesc('updated_at')->get();

        $ranking = $orders->groupBy('waiter_id')
            ->map(function ($grupo, $waiterId) {
                $waiter = $grupo->first()->waiter;
                return [
                    'waiter_id'       => $waiterId,
                    'nombre'          => $waiter->name ?? 'Sin asignar',
                    'total_vendido'   => $grupo->sum('total'),
                    'mesas_atendidas' => $grupo->count(),
                    'cobros_forzados' => $grupo->where('forced_checkout', true)->count(),
                ];
            })
            ->sortByDesc('total_vendido')
            ->values();

        $forzados = $orders->where('forced_checkout', true)->values();

        return view('admin.waiters.performance', compact('orders', 'ranking', 'forzados', 'rango'));
    }
}
