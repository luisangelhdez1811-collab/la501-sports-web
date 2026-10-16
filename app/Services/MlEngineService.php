<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MlEngineService
{
    protected function runPythonBridge(string $command, array $payload): array
    {
        $pythonBin = env('PYTHON_ML_BIN', 'python3');
        $scriptPath = base_path('python_ml/ml_bridge.py');

        putenv('OPENBLAS_NUM_THREADS=1');
        putenv('OMP_NUM_THREADS=1');
        putenv('MKL_NUM_THREADS=1');
        putenv('NUMEXPR_NUM_THREADS=1');

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            [$pythonBin, $scriptPath, $command],
            $descriptorSpec,
            $pipes,
            base_path()
        );

        if (!is_resource($process)) {
            throw new RuntimeException("No se pudo iniciar el puente Python ({$pythonBin}).");
        }

        fwrite($pipes[0], json_encode($payload));
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException("El puente Python ({$command}) falló: " . trim($stderr . ' ' . $stdout));
        }

        $decoded = json_decode($stdout, true);

        if (!is_array($decoded)) {
            throw new RuntimeException("Respuesta inválida del puente Python ({$command}): {$stdout}");
        }

        if (isset($decoded['error'])) {
            throw new RuntimeException("Error del puente Python ({$command}): {$decoded['error']}");
        }

        return $decoded;
    }

    public function getCustomerAnalysis()
    {
        $users = User::whereNotIn('role', ['admin'])->get();

        $clientesPayload = [];
        $base = [];

        foreach ($users as $user) {
            $userOrders = Order::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('status', 'paid')->orWhere('status', 'ready')->orWhere('status', 'delivering')->orWhere('status', 'delivered');
                })->get();

            $pedidos = $userOrders->count();
            $gastoTotal = $userOrders->sum('total');
            $gastoPromedio = $pedidos > 0 ? $gastoTotal / $pedidos : 0;
            $antiguedadMeses = max(1, (int) Carbon::parse($user->created_at)->diffInMonths(now()));
            $puntos = $user->points ?? 0;

            $lastOrder = $userOrders->max('created_at');
            $recencyDays = $lastOrder ? (int) Carbon::parse($lastOrder)->diffInDays(now()) : 999;

            $regresaProxMes = ($pedidos >= 2 && $recencyDays <= 30) ? 1 : 0;
            $riesgoFuga = ($recencyDays > 30 || $pedidos <= 1) ? 'Alto Riesgo' : 'Bajo Riesgo';

            $frecuenciaMensual = $pedidos > 0 ? $pedidos / $antiguedadMeses : 0;

            $clientesPayload[] = [
                'id' => $user->id,
                'frecuencia' => $pedidos,
                'monetario' => round($gastoTotal, 2),
                'recencia_dias' => $recencyDays,
                'ticket_promedio' => round($gastoPromedio, 2),
                'frecuencia_mensual' => round($frecuenciaMensual, 3),
            ];

            $base[$user->id] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'antiguedad_meses' => $antiguedadMeses,
                'cantidad_pedidos' => $pedidos,
                'gasto' => round($gastoTotal, 2),
                'puntos' => $puntos,
                'recency_days' => $recencyDays,
                'regresa_prox_mes' => $regresaProxMes,
                'riesgo_fuga' => $riesgoFuga,
            ];
        }

        $counts = ['VIP' => 0, 'Casual' => 0, 'Inactivo' => 0, 'AltoRiesgo' => 0, 'BajoRiesgo' => 0];
        $analysis = [];

        if (count($clientesPayload) > 0) {
            $resultado = $this->runPythonBridge('segmentacion', ['clientes' => $clientesPayload]);
            foreach ($resultado['resultados'] as $r) {
                $fila = $base[$r['id']];
                $fila['segmento'] = $r['segmento'];
                $counts[$r['segmento']]++;
                $counts[$fila['riesgo_fuga'] === 'Alto Riesgo' ? 'AltoRiesgo' : 'BajoRiesgo']++;
                $analysis[] = $fila;
            }
        }

        return ['customers' => $analysis, 'summary' => $counts];
    }

    protected function calendarFeatures(Carbon $date): array
    {
        $dow = $date->dayOfWeekIso - 1;
        return [
            'dia_semana' => $dow,
            'es_fin_de_semana' => in_array($dow, [4, 5, 6]) ? 1 : 0,
            'dia_mes' => (int) $date->format('j'),
            'mes' => (int) $date->format('n'),
        ];
    }

    public function getDemandForecast()
    {
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $dailyUnits = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('orders.status', 'paid')->orWhere('orders.status', 'ready')->orWhere('orders.status', 'delivering')->orWhere('orders.status', 'delivered');
            })
            ->select(DB::raw('DATE(orders.created_at) as date'), DB::raw('SUM(order_items.quantity) as total_units'))
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->pluck('total_units', 'date');

        $diasHistoricos = [];
        for ($i = 30; $i >= 0; $i--) {
            $dateObj = Carbon::now()->subDays($i);
            $dateStr = $dateObj->format('Y-m-d');
            $diasHistoricos[] = array_merge(
                ['idx' => 30 - $i, 'dateObj' => $dateObj, 'dateStr' => $dateStr],
                $this->calendarFeatures($dateObj)
            );
        }

        $payloadHist = ['dias' => array_map(function ($d) {
            return ['idx' => $d['idx'], 'dia_semana' => $d['dia_semana'], 'es_fin_de_semana' => $d['es_fin_de_semana'], 'dia_mes' => $d['dia_mes'], 'mes' => $d['mes']];
        }, $diasHistoricos)];

        $prediccionesHist = $this->runPythonBridge('demanda_total', $payloadHist)['predicciones'];

        $labels = [];
        $realDemand = [];
        $predDemand = [];
        $historyData = [];

        foreach ($diasHistoricos as $i => $d) {
            $unidadesReales = (int) ($dailyUnits[$d['dateStr']] ?? 0);
            $labels[] = $d['dateObj']->format('d M');
            $realDemand[] = $unidadesReales;
            $predDemand[] = $prediccionesHist[$i];

            $historyData[] = [
                'date' => $d['dateStr'],
                'day_name' => $d['dateObj']->locale('es')->isoFormat('dddd'),
                'sporting_event' => $d['es_fin_de_semana'],
                'active_promos' => 0,
                'demanda' => $unidadesReales,
                'pred_demanda' => $prediccionesHist[$i],
            ];
        }

        $diasFuturos = [];
        for ($j = 1; $j <= 7; $j++) {
            $futureDate = Carbon::now()->addDays($j);
            $diasFuturos[] = array_merge(
                ['idx' => $j - 1, 'dateObj' => $futureDate],
                $this->calendarFeatures($futureDate)
            );
        }

        $payloadFut = ['dias' => array_map(function ($d) {
            return ['idx' => $d['idx'], 'dia_semana' => $d['dia_semana'], 'es_fin_de_semana' => $d['es_fin_de_semana'], 'dia_mes' => $d['dia_mes'], 'mes' => $d['mes']];
        }, $diasFuturos)];

        $prediccionesFut = $this->runPythonBridge('demanda_total', $payloadFut)['predicciones'];
        $productosFut = $this->runPythonBridge('demanda_productos', $payloadFut)['resultados'];

        $productIds = collect($productosFut)->flatten(1)->pluck('product_id')->unique();
        $productosCatalogo = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $ingredientesPorProducto = DB::table('ingredientes')
            ->join('inventories', 'ingredientes.inventory_id', '=', 'inventories.id')
            ->whereIn('ingredientes.product_id', $productIds)
            ->select('ingredientes.product_id', 'inventories.name', 'inventories.unit', 'ingredientes.cantidad_usada')
            ->get()
            ->groupBy('product_id');

        $next7Days = [];
        foreach ($diasFuturos as $i => $d) {
            $labels[] = $d['dateObj']->format('d M') . ' *';
            $realDemand[] = null;
            $predDemand[] = $prediccionesFut[$i];

            $topProductosDia = collect($productosFut[$i])
                ->sortByDesc('unidades')
                ->take(3)
                ->values();

            $productosDetalle = [];
            $insumosAcumulados = [];

            foreach ($topProductosDia as $tp) {
                $producto = $productosCatalogo->get($tp['product_id']);
                $nombre = $producto ? $producto->name : "Producto {$tp['product_id']}";
                $productosDetalle[] = ['nombre' => $nombre, 'unidades' => $tp['unidades']];

                $ingredientesProducto = $ingredientesPorProducto->get($tp['product_id']);
                if ($ingredientesProducto) {
                    foreach ($ingredientesProducto as $ing) {
                        $clave = $ing->name . '|' . $ing->unit;
                        $insumosAcumulados[$clave] = ($insumosAcumulados[$clave] ?? 0) + ($ing->cantidad_usada * $tp['unidades']);
                    }
                }
            }

            $insumosDetalle = [];
            foreach ($insumosAcumulados as $clave => $cantidad) {
                [$nombreInsumo, $unidad] = explode('|', $clave);
                $insumosDetalle[] = ['nombre' => $nombreInsumo, 'cantidad' => round($cantidad, 2), 'unidad' => $unidad];
            }

            $next7Days[] = [
                'date' => $d['dateObj']->format('d M Y'),
                'day_name' => ucfirst($d['dateObj']->locale('es')->isoFormat('dddd')),
                'sporting_event' => $d['es_fin_de_semana'] ? 'Sí' : 'No',
                'active_promos' => 'No',
                'pred_demanda' => $prediccionesFut[$i],
                'productos' => $productosDetalle,
                'insumos' => $insumosDetalle,
            ];
        }

        return [
            'chart' => [
                'labels' => $labels,
                'real' => $realDemand,
                'pred' => $predDemand,
            ],
            'history' => array_reverse($historyData),
            'forecast_next_7' => $next7Days,
        ];
    }

    public function getAssociationRules()
    {
        $csvPath = storage_path('ml_models/reglas_asociacion_apriori.csv');

        if (!file_exists($csvPath)) {
            return [];
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        $rules = [];

        while (($row = fgetcsv($handle)) !== false) {
            $fila = array_combine($header, $row);
            $rules[] = [
                'antecedent' => $fila['antecedents'],
                'consequent' => $fila['consequents'],
                'support' => round(((float) $fila['support']) * 100, 1) . '%',
                'confidence' => round(((float) $fila['confidence']) * 100, 1) . '%',
                'lift' => round((float) $fila['lift'], 2),
            ];
        }
        fclose($handle);

        usort($rules, function ($a, $b) {
            return $b['lift'] <=> $a['lift'];
        });

        return array_slice($rules, 0, 12);
    }

    public function getRecommendationsForCustomer(?User $user, array $cartItemIds = [])
    {
        if ($user) {
            $pastProductIds = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.user_id', $user->id)
                ->whereNotIn('order_items.product_id', $cartItemIds)
                ->groupBy('order_items.product_id')
                ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as total_qty'))
                ->orderBy('total_qty', 'desc')
                ->limit(4)
                ->pluck('product_id')
                ->toArray();

            $products = Product::with('ingredientes')->whereIn('id', $pastProductIds)->where('available', 1)->get();

            if ($products->count() > 0) {
                return [
                    'strategy' => 'user_history',
                    'title' => 'Basado en tus pedidos anteriores (Vuelve a pedir tu favorito)',
                    'badge' => 'Personalizado',
                    'products' => $products,
                ];
            }
        }

        $nombresSimilares = $this->runPythonBridge('recomendacion_similares', ['top_n' => 4])['productos'];

        $popularProducts = Product::with('ingredientes')
            ->whereIn('name', $nombresSimilares)
            ->whereNotIn('id', $cartItemIds)
            ->where('available', 1)
            ->get();

        return [
            'strategy' => 'cosine_similarity',
            'title' => 'Popular entre nuestros visitantes',
            'products' => $popularProducts,
        ];
    }
}
