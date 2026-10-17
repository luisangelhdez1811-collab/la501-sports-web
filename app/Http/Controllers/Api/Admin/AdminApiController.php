<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\NotificationProcessed;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\TableCharge;
use App\Services\AchievementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminApiController extends Controller
{
    public function salesSummary(): JsonResponse
    {
        $totalVentas = Order::vendidas()->sum('total');
        $pedidosHoy = Order::whereDate('created_at', Carbon::today())->count();

        return response()->json([
            'total_sales' => (float) $totalVentas,
            'orders_today' => (int) $pedidosHoy,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function salesReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:ganancias,rotacion,corte'],
            'period' => ['required_if:type,ganancias,rotacion', 'in:30d,months'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return match ($validated['type']) {
            'ganancias' => response()->json(['data' => $this->gananciasReport($validated['period'])], 200, [], JSON_UNESCAPED_UNICODE),
            'rotacion' => response()->json(['data' => $this->rotacionReport($validated['period'])], 200, [], JSON_UNESCAPED_UNICODE),
            'corte' => response()->json(['data' => $this->corteReport($validated['date'] ?? null)], 200, [], JSON_UNESCAPED_UNICODE),
        };
    }

    private function gananciasReport(string $period): array
    {
        $query = Order::vendidas();

        if ($period === '30d') {
            $start = Carbon::now()->subDays(29)->startOfDay();
            $orders = (clone $query)->where('created_at', '>=', $start)->get()
                ->groupBy(fn ($o) => Carbon::parse($o->created_at)->format('Y-m-d'));

            $data = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = Carbon::now()->subDays($i);
                $key = $day->format('Y-m-d');
                $data[] = [
                    'label' => $day->format('d M'),
                    'total' => isset($orders[$key]) ? (float) $orders[$key]->sum('total') : 0.0,
                ];
            }

            return $data;
        }

        $start = Carbon::now()->startOfYear();
        $orders = (clone $query)->where('created_at', '>=', $start)->get()
            ->groupBy(fn ($o) => Carbon::parse($o->created_at)->format('M Y'));

        $data = [];
        $currentMonth = Carbon::now()->month;
        for ($i = 1; $i <= $currentMonth; $i++) {
            $label = Carbon::create(null, $i, 1)->format('M Y');
            $data[] = [
                'label' => $label,
                'total' => isset($orders[$label]) ? (float) $orders[$label]->sum('total') : 0.0,
            ];
        }

        return $data;
    }

    private function rotacionReport(string $period): array
    {
        if ($period === '30d') {
            $start = Carbon::now()->subDays(29)->startOfDay();
            $end = Carbon::now()->endOfDay();
        } else {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfDay();
        }

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where(function ($q) {
                $q->where('orders.status', 'paid')
                    ->orWhere(function ($sq) {
                        $sq->whereNull('orders.table_number')
                            ->whereIn('orders.status', ['ready', 'delivering', 'delivered']);
                    });
            })
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'order_items.product_name as name',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.subtotal) as total')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('quantity')
            ->limit(20)
            ->get();

        return $rows->map(fn ($r) => [
            'name' => $r->name,
            'quantity' => (int) $r->quantity,
            'total' => (float) $r->total,
        ])->all();
    }

    private function corteReport(?string $date): array
    {
        $corteDate = $date ?? Carbon::today()->format('Y-m-d');

        $orders = Order::whereDate('created_at', $corteDate)->vendidas()->get();

        $cash = 0.0;
        $card = 0.0;
        foreach ($orders as $order) {
            [$efectivo, $tarjeta] = $this->splitPaymentMethod((string) ($order->payment_method ?: 'efectivo'), (float) $order->total);
            $cash += $efectivo;
            $card += $tarjeta;
        }

        return [
            'date' => $corteDate,
            'total' => (float) $orders->sum('total'),
            'orders' => $orders->count(),
            'cash' => round($cash, 2),
            'card' => round($card, 2),
        ];
    }

    private function splitPaymentMethod(string $method, float $total): array
    {
        $methodLower = strtolower(trim($method));
        $efectivo = 0.0;
        $tarjeta = 0.0;

        if (str_starts_with($methodLower, 'mixto')) {
            if (preg_match('/efectivo:\s*\$?\s*([\d.]+)/i', $method, $m)) {
                $efectivo = (float) $m[1];
            }
            if (preg_match('/tarjeta:\s*\$?\s*([\d.]+)/i', $method, $m)) {
                $tarjeta = (float) $m[1];
            }
        } elseif (str_contains($methodLower, 'efectivo')) {
            $efectivo = $total;
        } else {
            $tarjeta = $total;
        }

        return [$efectivo, $tarjeta];
    }

    private const RESERVATION_STATUSES = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];

    public function reservations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:' . implode(',', self::RESERVATION_STATUSES)],
        ]);
        $status = $validated['status'] ?? 'pendiente';

        Reservation::whereIn('status', ['pendiente', 'confirmada'])
            ->whereDate('fecha_reservacion', '<', Carbon::today())
            ->update(['status' => 'finalizada']);

        $counts = array_fill_keys(self::RESERVATION_STATUSES, 0);
        Reservation::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->each(function ($total, $status) use (&$counts) {
                if (isset($counts[$status])) {
                    $counts[$status] = (int) $total;
                }
            });

        $reservations = Reservation::where('status', $status)
            ->orderBy('fecha_reservacion', 'asc')
            ->orderBy('hora_reservacion', 'asc')
            ->get()
            ->map(fn (Reservation $r) => [
                'id' => $r->id,
                'nombre_completo' => $r->nombre_completo,
                'correo_electronico' => $r->correo_electronico,
                'telefono' => $r->telefono,
                'fecha_reservacion' => optional($r->fecha_reservacion)->format('Y-m-d'),
                'hora_reservacion' => $r->hora_reservacion,
                'cantidad_personas' => (int) $r->cantidad_personas,
                'zona' => $r->zona,
                'estado' => $r->status,
            ]);

        return response()->json([
            'counts' => $counts,
            'data' => $reservations,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function updateReservation(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'estado' => ['required', 'in:' . implode(',', self::RESERVATION_STATUSES)],
        ]);

        $reservation->update(['status' => $validated['estado']]);

        if ($validated['estado'] === 'confirmada') {
            $user = \App\Models\User::where('email', $reservation->correo_electronico)->first();
            if ($user) {
                (new AchievementService())->check($user);
            }
        }

        event(new NotificationProcessed(
            "La reservación de {$reservation->nombre_completo} ha sido marcada como: " . ucfirst($validated['estado']),
            $validated['estado'] === 'confirmada' ? 'success' : 'info'
        ));

        return response()->json(['success' => true], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private const MESSAGE_STATUS_DB_TO_APP = ['pendiente' => 'pendiente', 'respondido' => 'atendido'];
    private const MESSAGE_STATUS_APP_TO_DB = ['pendiente' => 'pendiente', 'atendido' => 'respondido'];

    public function messages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'in:todos,pendientes,atendidos,quejas,sugerencias'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $filter = $validated['filter'] ?? 'todos';

        $counts = [
            'total' => ContactMessage::count(),
            'pendientes' => ContactMessage::where('status', 'pendiente')->count(),
            'quejas' => ContactMessage::where('type', 'queja')->count(),
            'atendidos' => ContactMessage::where('status', 'respondido')->count(),
        ];

        $query = ContactMessage::query();

        match ($filter) {
            'pendientes' => $query->where('status', 'pendiente'),
            'atendidos' => $query->where('status', 'respondido'),
            'quejas' => $query->where('type', 'queja'),
            'sugerencias' => $query->where('type', 'sugerencia'),
            default => null,
        };

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%")
                    ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        $messages = $query->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (ContactMessage $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'subject' => $m->subject,
                'message' => $m->message,
                'type' => $m->type,
                'status' => self::MESSAGE_STATUS_DB_TO_APP[$m->status] ?? $m->status,
                'created_at' => optional($m->created_at)->toISOString(),
            ]);

        return response()->json([
            'counts' => $counts,
            'data' => $messages,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function updateMessage(Request $request, ContactMessage $message): JsonResponse
    {
        $validated = $request->validate([
            'estado' => ['required', 'in:pendiente,atendido'],
        ]);

        $message->update(['status' => self::MESSAGE_STATUS_APP_TO_DB[$validated['estado']]]);

        return response()->json(['success' => true], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private const ZONA_HORARIA = 'America/Mexico_City';

    public function waiterPerformance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:hoy,semana,mes,todo'],
        ]);
        $period = $validated['period'] ?? 'hoy';

        $ahora = Carbon::now(self::ZONA_HORARIA);
        $desde = match ($period) {
            'semana' => $ahora->copy()->startOfWeek(),
            'mes' => $ahora->copy()->startOfMonth(),
            'todo' => null,
            default => $ahora->copy()->startOfDay(),
        };

        $query = Order::with(['waiter:id,name', 'chargedBy:id,name', 'tableCharge:id,offline,status'])
            ->whereNotNull('table_number')
            ->where('status', 'paid');

        $desdeUtc = $desde?->copy()->setTimezone(config('app.timezone', 'UTC'));
        if ($desdeUtc) {
            $query->where('updated_at', '>=', $desdeUtc);
        }

        $orders = $query->orderByDesc('updated_at')->get();

        // Cobros de la app que el administrador debe revisar: hechos sin conexión con un
        // pago menor al total, o que llegaron cuando la mesa ya no tenía nada por cobrar.
        $issuesQuery = TableCharge::with('waiter:id,name')
            ->where('status', '!=', TableCharge::STATUS_APPLIED);
        if ($desdeUtc) {
            $issuesQuery->where('charged_at', '>=', $desdeUtc);
        }
        $issues = $issuesQuery->orderByDesc('charged_at')->get()->map(fn (TableCharge $c) => [
            'table' => (string) $c->table_number,
            'waiter' => $c->waiter->name ?? 'Sin asignar',
            'status' => $c->status,
            'offline' => $c->offline,
            'total_due' => $c->total_due,
            'amount_received' => $c->amount_received,
            'notes' => $c->notes,
            'charged_at' => optional($c->charged_at)->toISOString(),
        ]);

        $ranking = $orders->groupBy('waiter_id')
            ->map(fn ($grupo) => [
                'waiter' => $grupo->first()->waiter->name ?? 'Sin asignar',
                'tables' => $grupo->count(),
                'total' => (float) $grupo->sum('total'),
            ])
            ->sortByDesc('total')
            ->values();

        $tables = $orders->map(fn (Order $o) => [
            'table' => (string) $o->table_number,
            'waiter' => $o->waiter->name ?? 'Sin asignar',
            'charged_by' => $o->chargedBy->name ?? null,
            'total' => (float) $o->total,
            'forced' => (bool) $o->forced_checkout,
            'offline' => (bool) optional($o->tableCharge)->offline,
            'label' => $o->diner_label,
            'closed_at' => optional($o->updated_at)->toISOString(),
        ]);

        return response()->json([
            'summary' => [
                'total_sold' => (float) $orders->sum('total'),
                'tables_charged' => $orders->count(),
                'waiters_with_sales' => $orders->pluck('waiter_id')->unique()->filter()->count(),
                'forced_charges' => $orders->where('forced_checkout', true)->count(),
                'offline_charges' => $orders->filter(fn (Order $o) => (bool) optional($o->tableCharge)->offline)->count(),
                'charge_issues' => $issues->count(),
            ],
            'ranking' => $ranking,
            'tables' => $tables,
            'issues' => $issues,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
