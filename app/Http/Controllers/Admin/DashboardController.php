<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    private const DATE_FORMAT_DMY  = 'd/m/Y';
    private const NUMBER_FORMAT_MX = '"$"#,##0.00_-';

    public function index(Request $request)
    {
        $totalVentas  = Order::vendidas()->sum('total');
        $totalPedidos = Order::vendidas()->count();
        $ticketPromedio = $totalPedidos > 0 ? ($totalVentas / $totalPedidos) : 0;
        $pedidosHoy   = Order::whereDate('created_at', Carbon::today())->count();
        $enProceso    = Order::where(function ($query) {
            $query->whereNotNull('table_number')
                  ->whereIn('status', ['pending', 'preparing', 'ready']);
        })->orWhere(function ($query) {
            $query->whereNull('table_number')
                  ->whereIn('status', ['paid', 'preparing']);
        })->count();

        $stats = [
            'total_ventas'    => $totalVentas,
            'pedidos_hoy'     => $pedidosHoy,
            'en_proceso'      => $enProceso,
            'ticket_promedio' => $ticketPromedio,
        ];

        $primeraVenta = Order::min('created_at');
        $primeraFecha = $primeraVenta ? Carbon::parse($primeraVenta)->format('Y-m-d') : date('Y-m-d');
        $ultimaFecha  = date('Y-m-d');

        try {
            $topProducts = DB::table('v_top_productos')
                ->select('producto as name', 'cantidad_vendida as total_vendido')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $topProducts = [];
        }

        $rotacionDate = $request->input('rotacion_date', date('Y-m'));
        try {
            $fechaRotacion = Carbon::createFromFormat('Y-m', $rotacionDate);
        } catch (\Exception $e) {
            $fechaRotacion = Carbon::today();
            $rotacionDate = $fechaRotacion->format('Y-m');
        }

        $inicioMes = $fechaRotacion->copy()->startOfMonth();
        
        if ($fechaRotacion->isCurrentMonth()) {
            $finMes = Carbon::today()->endOfDay();
            $diasEvaluados = max(1, Carbon::today()->day);
        } else {
            $finMes = $fechaRotacion->copy()->endOfMonth()->endOfDay();
            $diasEvaluados = $fechaRotacion->daysInMonth;
        }

        $rawTotalVendido = 'SUM(order_items.quantity) as total_vendido';

        // MEDIA ARITMETICA
        $mediaAritmetica = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where(function ($q) {
                $q->where('orders.status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('orders.table_number')
                         ->whereIn('orders.status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->whereBetween('orders.created_at', [$inicioMes, $finMes])
            ->select('products.name', DB::raw($rawTotalVendido))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_vendido')
            ->get()
            ->map(function ($item) use ($diasEvaluados) {
                $item->media_diaria = round($item->total_vendido / $diasEvaluados, 2);
                return $item;
            });
        
        // CALCULO DE MODA MATEMATICA
        $modaMatematica = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where(function ($q) {
                $q->where('orders.status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('orders.table_number')
                         ->whereIn('orders.status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->whereBetween('orders.created_at', [$inicioMes, $finMes])
            ->select('products.name', DB::raw('COUNT(*) as frecuencia'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('frecuencia')
            ->get();

        // MODELO PREDICTIVO
        $fechaInicioPred = $request->input('start_date', Carbon::now()->subMonths(2)->startOfMonth()->format('Y-m-d'));
        $fechaFinPred = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $startCarbon = Carbon::parse($fechaInicioPred)->startOfDay();
        $endCarbon = Carbon::parse($fechaFinPred)->endOfDay();
        
        $diffDays = $startCarbon->diffInDays($endCarbon) + 1;

        $ordersQuery = Order::vendidas()
            ->whereBetween('created_at', [$startCarbon, $endCarbon])
            ->orderBy('created_at', 'asc')
            ->get();

        $groupedTemp = collect();

        if ($diffDays <= 14) {
            $granularidad = 'Día';
            $labelFormato = 'd M Y';
            $groupedTemp = $ordersQuery->groupBy(fn($q) => Carbon::parse($q->created_at)->format('Y-m-d'));
            
        } elseif ($diffDays <= 60) {
            $granularidad = 'Semana';
            $labelFormato = 'd M';
            $semanasCompletas = floor($diffDays / 7);
            $diasCompletos = $semanasCompletas * 7;
            
            $fechaFinValida = $startCarbon->copy()->addDays($diasCompletos)->subSecond();
            
            $groupedTemp = $ordersQuery->where('created_at', '<=', $fechaFinValida)
                ->groupBy(function($q) use ($startCarbon) {
                    $daysDiff = $startCarbon->diffInDays(Carbon::parse($q->created_at));
                    $weekNum = floor($daysDiff / 7);
                    return $startCarbon->copy()->addDays($weekNum * 7)->format('Y-m-d');
                });
                
        } else {
            $granularidad = 'Mes';
            $labelFormato = 'M Y';
            $groupedTemp = $ordersQuery->groupBy(fn($q) => Carbon::parse($q->created_at)->startOfMonth()->format('Y-m-d'));
        }

        $historialSintetizado = collect();
        foreach ($groupedTemp as $key => $group) {
            $historialSintetizado->push((object)[
                'etiqueta' => Carbon::parse($key)->format($labelFormato),
                'total_ganado' => $group->sum('total')
            ]);
        }

        $n = $historialSintetizado->count();
        $gananciaProyectada = 0;
        $k_constante = 0;
        $p0 = 0;
        $p_last = 0;

        $fechasChart = [];
        $realesChart = [];
        $prediccionChart = [];

        if ($n > 1) {
            $p0 = $historialSintetizado->first()->total_ganado;
            $p_last = $historialSintetizado->last()->total_ganado;
            $t_last = $n - 1;

            if ($p0 > 0 && $p_last > 0) {
                $k_raw = log($p_last / $p0) / $t_last;
                $k_constante = round($k_raw, 4);

                $t_next = $n;
                $gananciaProyectada = $p0 * exp($k_constante * $t_next);

                foreach ($historialSintetizado as $index => $item) {
                    $fechasChart[] = $item->etiqueta;
                    $realesChart[] = round($item->total_ganado, 2);
                    $prediccionChart[] = round($p0 * exp($k_constante * $index), 2);
                }
                
                $articulo = ($granularidad == 'Semana') ? 'Próxima' : 'Próximo';
                
                $fechasChart[] = "Proyección (" . $articulo . " " . $granularidad . ")";
                $realesChart[] = null;
                $prediccionChart[] = round($gananciaProyectada, 2);

            } else {
                $gananciaProyectada = $p_last;
            }
        } else {
            $gananciaProyectada = $n > 0 ? $historialSintetizado->first()->total_ganado * 1.10 : 0;
        }

        $fechasChartJson = json_encode($fechasChart);
        $realesChartJson = json_encode($realesChart);
        $prediccionChartJson = json_encode($prediccionChart);

        // --- DIARIO CORTE DE CAJA ---
        $corteDate = $request->input('corte_date', date('Y-m-d'));
        
        $ordersCorte = Order::whereDate('created_at', $corteDate)
            ->vendidas()
            ->get();

        $corteTotal = $ordersCorte->sum('total');
        $corteCount = $ordersCorte->count();
        $corteFirstTime = $ordersCorte->min('created_at') ? Carbon::parse($ordersCorte->min('created_at'))->format('h:i A') : 'N/A';
        $corteLastTime = $ordersCorte->max('created_at') ? Carbon::parse($ordersCorte->max('created_at'))->format('h:i A') : 'N/A';

        // Ventas por tipo de servicio (Mesa vs A Domicilio)
        $ventasMesa = $ordersCorte->whereNotNull('table_number')->sum('total');
        $ventasDelivery = $ordersCorte->whereNull('table_number')->sum('total');
        $cantMesa = $ordersCorte->whereNotNull('table_number')->count();
        $cantDelivery = $ordersCorte->whereNull('table_number')->count();

        $corteServicios = [
            'Mesa' => ['monto' => $ventasMesa, 'cantidad' => $cantMesa],
            'A Domicilio' => ['monto' => $ventasDelivery, 'cantidad' => $cantDelivery]
        ];

        // Productos más vendidos del día
        $topProductsCorte = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $corteDate)
            ->where(function ($q) {
                $q->where('orders.status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('orders.table_number')
                         ->whereIn('orders.status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->select('order_items.product_name as name', DB::raw('SUM(order_items.quantity) as total_vendido'), DB::raw('SUM(order_items.subtotal) as ingresos'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('ingresos')
            ->limit(5)
            ->get();

        // Formas de pago más utilizadas del día
        $totalsCorte = ['efectivo' => 0.0, 'tarjeta' => 0.0];
        $detailedPayments = [
            'Efectivo' => ['orders' => [], 'total' => 0.0],
            'Tarjeta' => ['orders' => [], 'total' => 0.0],
        ];

        foreach ($ordersCorte as $order) {
            $method = $order->payment_method ?: 'Efectivo';
            $parsed = self::parsePaymentMethodAmounts($method, (float) $order->total);

            $totalsCorte['efectivo'] += $parsed['efectivo'];
            $totalsCorte['tarjeta'] += $parsed['tarjeta'];

            if ($parsed['efectivo'] > 0) {
                $detailedPayments['Efectivo']['orders'][] = [
                    'id' => $order->id,
                    'monto' => $parsed['efectivo'],
                    'customer_name' => $order->customer_name ?: ('Mesa ' . $order->table_number),
                    'created_at' => $order->created_at->format('h:i A')
                ];
                $detailedPayments['Efectivo']['total'] += $parsed['efectivo'];
            }
            if ($parsed['tarjeta'] > 0) {
                $detailedPayments['Tarjeta']['orders'][] = [
                    'id' => $order->id,
                    'monto' => $parsed['tarjeta'],
                    'customer_name' => $order->customer_name ?: ('Mesa ' . $order->table_number),
                    'created_at' => $order->created_at->format('h:i A')
                ];
                $detailedPayments['Tarjeta']['total'] += $parsed['tarjeta'];
            }
        }

        // Pedidos Web vs Local
        $ventasWeb = $ordersCorte->whereNotNull('user_id')->sum('total');
        $cantWeb = $ordersCorte->whereNotNull('user_id')->count();
        $ventasLocal = $ordersCorte->whereNull('user_id')->sum('total');
        $cantLocal = $ordersCorte->whereNull('user_id')->count();

        $corteWebLocal = [
            'Web' => ['monto' => $ventasWeb, 'cantidad' => $cantWeb],
            'Local / Mesas' => ['monto' => $ventasLocal, 'cantidad' => $cantLocal]
        ];

        return view('admin.dashboard', compact(
            'stats', 'primeraFecha', 'ultimaFecha', 'topProducts',
            'mediaAritmetica', 'modaMatematica', 'gananciaProyectada',
            'historialSintetizado', 'fechaInicioPred', 'fechaFinPred', 'k_constante', 'p0', 'p_last',
            'fechasChartJson', 'realesChartJson', 'prediccionChartJson', 'granularidad', 'rotacionDate',
            'corteDate', 'corteTotal', 'corteCount', 'corteFirstTime', 'corteLastTime', 'corteServicios',
            'topProductsCorte', 'totalsCorte', 'detailedPayments', 'corteWebLocal'
        ));
    }

    private static function parsePaymentMethodAmounts(string $method, float $total)
    {
        $efectivo = 0.0;
        $tarjeta = 0.0;

        $methodLower = strtolower(trim($method));

        if (str_starts_with($methodLower, 'mixto')) {
            if (preg_match('/efectivo:\s*\$?\s*([\d\.]+)/i', $method, $m)) {
                $efectivo = (float) $m[1];
            }
            if (preg_match('/tarjeta:\s*\$?\s*([\d\.]+)/i', $method, $m)) {
                $tarjeta = (float) $m[1];
            }
        } elseif (str_contains($methodLower, 'efectivo')) {
            $efectivo = $total;
        } else {
            // Tarjeta, terminal, o cualquier otro método (ej. registros viejos de Mercado Pago).
            $tarjeta = $total;
        }

        return [
            'efectivo' => $efectivo,
            'tarjeta' => $tarjeta,
        ];
    }

    public function apiStats()
    {
        $totalVentas = Order::vendidas()->sum('total');
        $pedidosHoy  = Order::whereDate('created_at', Carbon::today())->count();
        $enProceso   = Order::where(function ($query) {
            $query->whereNotNull('table_number')
                  ->whereIn('status', ['pending', 'preparing', 'ready']);
        })->orWhere(function ($query) {
            $query->whereNull('table_number')
                  ->whereIn('status', ['paid', 'preparing']);
        })->count();

        return response()->json([
            'total_ventas' => (float) $totalVentas,
            'pedidos_hoy'  => (int) $pedidosHoy,
            'en_proceso'   => (int) $enProceso,
        ]);
    }

    public function apiSales(Request $request)
    {
        $period = $request->get('period', 'day');
        $query  = Order::vendidas();

        $result = match ($period) {
            'month' => $this->buildMonthlyData($query),
            'year'  => $this->buildYearlyData($query),
            default => $this->buildDailyData($query),
        };

        return response()->json($result);
    }

    private function buildDailyData($query): array
    {
        $start  = Carbon::now()->subDays(29)->startOfDay();
        $orders = clone $query->where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn($d) => Carbon::parse($d->created_at)->format('d M'));

        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $label    = Carbon::now()->subDays($i)->format('d M');
            $labels[] = $label;
            $data[]   = isset($orders[$label]) ? $orders[$label]->sum('total') : 0;
        }

        return compact('labels', 'data');
    }

    private function buildMonthlyData($query): array
    {
        $start  = Carbon::now()->startOfYear();
        $orders = clone $query->where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn($d) => Carbon::parse($d->created_at)->format('M Y'));

        $labels       = [];
        $data         = [];
        $currentMonth = Carbon::now()->month;

        for ($i = 1; $i <= $currentMonth; $i++) {
            $label    = Carbon::create(null, $i, 1)->format('M Y');
            $labels[] = $label;
            $data[]   = isset($orders[$label]) ? $orders[$label]->sum('total') : 0;
        }

        return compact('labels', 'data');
    }

    private function buildYearlyData($query): array
    {
        $start  = Carbon::now()->subYears(4)->startOfYear();
        $orders = clone $query->where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn($d) => Carbon::parse($d->created_at)->format('Y'));

        $labels = [];
        $data   = [];

        for ($i = 4; $i >= 0; $i--) {
            $label    = Carbon::now()->subYears($i)->format('Y');
            $labels[] = $label;
            $data[]   = isset($orders[$label]) ? $orders[$label]->sum('total') : 0;
        }

        return compact('labels', 'data');
    }

    public function apiCategoryRotation(Request $request)
    {
        $rotacionDate = $request->input('rotacion_date', date('Y-m'));
        try {
            $fechaRotacion = Carbon::createFromFormat('Y-m', $rotacionDate);
        } catch (\Exception $e) {
            $fechaRotacion = Carbon::today();
        }

        $inicioMes = $fechaRotacion->copy()->startOfMonth();
        $finMes = $fechaRotacion->isCurrentMonth() ? Carbon::today()->endOfDay() : $fechaRotacion->copy()->endOfMonth()->endOfDay();

        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where(function ($q) {
                $q->where('orders.status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('orders.table_number')
                         ->whereIn('orders.status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->whereBetween('orders.created_at', [$inicioMes, $finMes]);

        // 3. Agrupamos por categoría y preparamos los datos
        $ventasPorCategoria = $query->select('products.category', DB::raw('SUM(order_items.quantity) as total_vendido'))
            ->groupBy('products.category')
            ->orderByDesc('total_vendido')
            ->get();

        $ventasTotales = $ventasPorCategoria->sum('total_vendido');

        $labels = [];
        $data = [];
        $backgrounds = [];

        $colores = [
            'Bebidas'      => 'rgba(37, 99, 235, 0.85)',
            'Snacks'       => 'rgba(124, 58, 237, 0.85)',
            'Hamburguesas' => 'rgba(234, 88, 12, 0.85)',
            'Tacos'        => 'rgba(22, 163, 74, 0.85)',
            'Jochos'       => 'rgba(220, 38, 38, 0.85)',
            'Burritos'     => 'rgba(234, 179, 8, 0.85)'
        ];

        foreach ($ventasPorCategoria as $item) {
            $labels[] = $item->category;
            $porcentaje = $ventasTotales > 0 ? round(($item->total_vendido / $ventasTotales) * 100, 2) : 0;
            $data[] = $porcentaje;
            $backgrounds[] = $colores[$item->category] ?? 'rgba('.rand(100,200).','.rand(100,200).','.rand(100,200).', 0.85)';
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'backgroundColors' => $backgrounds
        ]);
    }
}
