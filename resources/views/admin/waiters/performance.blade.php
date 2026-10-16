@extends('layouts.admin')

@section('content')
<style>
    .wp {
        --accent:    #2563EB;
        --danger:    #EF4444;
        --success:   #16A34A;
        --warning:   #D97706;

        --bg:        #F8F8F8;
        --bg-card:   #FFFFFF;
        --bg-input:  #F4F4F5;
        --txt:       #18181B;
        --txt-sub:   #71717A;
        --bdr:       #E4E4E7;
    }
    .dark .wp {
        --bg:        #0A0A0A;
        --bg-card:   #111111;
        --bg-input:  #1C1C1C;
        --txt:       #FAFAFA;
        --txt-sub:   #71717A;
        --bdr:       rgba(255,255,255,0.08);
    }
    .wp { background: var(--bg); min-height: 100%; }

    .wp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
    .wp-header h1 { font-size: 20px; font-weight: 700; color: var(--txt); margin: 0 0 2px; }
    .wp-header p { font-size: 13px; color: var(--txt-sub); margin: 0; }

    .wp-filters { display: flex; gap: 6px; flex-wrap: wrap; }
    .wp-filter { padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; border: 1px solid var(--bdr); color: var(--txt-sub); background: var(--bg-card); transition: background .15s, color .15s; }
    .wp-filter:hover { color: var(--txt); }
    .wp-filter.on { background: var(--accent); border-color: var(--accent); color: #fff; }

    .wp-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .wp-card { background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 12px; padding: 16px 18px; }
    .wp-card span { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--txt-sub); margin-bottom: 6px; }
    .wp-card strong { font-size: 24px; font-weight: 800; color: var(--txt); }
    .wp-card strong.danger { color: var(--danger); }

    .wp-section-title { font-size: 14px; font-weight: 800; color: var(--txt); margin: 28px 0 12px; display: flex; align-items: center; gap: 8px; }

    .wp-table-wrap { background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 12px; overflow-x: auto; }
    table.wp-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 640px; }
    table.wp-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: var(--txt-sub); font-weight: 700; padding: 12px 16px; border-bottom: 1px solid var(--bdr); white-space: nowrap; }
    table.wp-table td { padding: 12px 16px; border-bottom: 1px solid var(--bdr); color: var(--txt); vertical-align: top; }
    table.wp-table tr:last-child td { border-bottom: none; }
    table.wp-table tbody tr:hover { background: var(--bg-input); }

    .wp-badge { display: inline-block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; padding: 3px 8px; border-radius: 100px; }
    .wp-badge.forced { background: rgba(239,68,68,.12); color: var(--danger); }
    .wp-badge.ok { background: rgba(22,163,74,.12); color: var(--success); }

    .wp-empty { text-align: center; padding: 40px 20px; color: var(--txt-sub); font-size: 13px; }
</style>

<div class="wp">
    <div class="wp-header">
        <div>
            <h1>Desempeño de Meseros</h1>
            <p>Ventas por mesero, quién atendió y cobró cada mesa, y auditoría de cobros forzados.</p>
        </div>
        <div class="wp-filters">
            <a href="{{ route('admin.waiters.performance', ['rango' => 'hoy']) }}" class="wp-filter {{ $rango === 'hoy' ? 'on' : '' }}">Hoy</a>
            <a href="{{ route('admin.waiters.performance', ['rango' => 'semana']) }}" class="wp-filter {{ $rango === 'semana' ? 'on' : '' }}">Esta semana</a>
            <a href="{{ route('admin.waiters.performance', ['rango' => 'mes']) }}" class="wp-filter {{ $rango === 'mes' ? 'on' : '' }}">Este mes</a>
            <a href="{{ route('admin.waiters.performance', ['rango' => 'todo']) }}" class="wp-filter {{ $rango === 'todo' ? 'on' : '' }}">Todo</a>
        </div>
    </div>

    {{-- RESUMEN --}}
    <div class="wp-cards">
        <div class="wp-card">
            <span>Total Vendido</span>
            <strong>${{ number_format($orders->sum('total'), 2) }}</strong>
        </div>
        <div class="wp-card">
            <span>Mesas Cobradas</span>
            <strong>{{ $orders->count() }}</strong>
        </div>
        <div class="wp-card">
            <span>Meseros con Ventas</span>
            <strong>{{ $ranking->count() }}</strong>
        </div>
        <div class="wp-card">
            <span>Cobros Forzados</span>
            <strong class="{{ $forzados->count() > 0 ? 'danger' : '' }}">{{ $forzados->count() }}</strong>
        </div>
    </div>

    {{-- RANKING POR MESERO --}}
    <h2 class="wp-section-title">🏆 Ranking por Mesero</h2>
    <div class="wp-table-wrap">
        @if($ranking->isEmpty())
            <div class="wp-empty">No hay cobros registrados en este periodo.</div>
        @else
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>Mesero</th>
                        <th>Mesas Atendidas</th>
                        <th>Total Vendido</th>
                        <th>Cobros Forzados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ranking as $r)
                        <tr>
                            <td style="font-weight:700;">{{ $r['nombre'] }}</td>
                            <td>{{ $r['mesas_atendidas'] }}</td>
                            <td style="font-weight:700; color: var(--success);">${{ number_format($r['total_vendido'], 2) }}</td>
                            <td>
                                @if($r['cobros_forzados'] > 0)
                                    <span class="wp-badge forced">{{ $r['cobros_forzados'] }} forzado{{ $r['cobros_forzados'] > 1 ? 's' : '' }}</span>
                                @else
                                    <span class="wp-badge ok">0</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- COBROS FORZADOS (PARA REVISAR / CORREGIR) --}}
    @if($forzados->isNotEmpty())
        <h2 class="wp-section-title">🔓 Cobros Forzados — Revisar</h2>
        <div class="wp-table-wrap">
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>Mesa</th>
                        <th>Atendió</th>
                        <th>Forzó el cobro</th>
                        <th>Motivo</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($forzados as $order)
                        <tr>
                            <td style="font-weight:700;">#{{ $order->table_number }}</td>
                            <td>{{ $order->waiter->name ?? 'Sin asignar' }}</td>
                            <td style="color: var(--danger); font-weight:700;">{{ $order->chargedBy->name ?? 'Desconocido' }}</td>
                            <td style="max-width: 260px;">{{ $order->forced_checkout_reason ?: '—' }}</td>
                            <td style="font-weight:700;">${{ number_format($order->total, 2) }}</td>
                            <td style="color: var(--txt-sub); white-space:nowrap;">{{ $order->updated_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- DETALLE DE MESAS COBRADAS --}}
    <h2 class="wp-section-title">📋 Detalle de Mesas Cobradas</h2>
    <div class="wp-table-wrap">
        @if($orders->isEmpty())
            <div class="wp-empty">No hay cobros registrados en este periodo.</div>
        @else
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>Mesa</th>
                        <th>Atendió</th>
                        <th>Cobró</th>
                        <th>Método</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td style="font-weight:700;">#{{ $order->table_number }}</td>
                            <td>{{ $order->waiter->name ?? 'Sin asignar' }}</td>
                            <td>{{ $order->chargedBy->name ?? '—' }}</td>
                            <td>{{ $order->payment_method }}</td>
                            <td style="font-weight:700;">${{ number_format($order->total, 2) }}</td>
                            <td>
                                @if($order->forced_checkout)
                                    <span class="wp-badge forced">Forzado</span>
                                @else
                                    <span class="wp-badge ok">Normal</span>
                                @endif
                            </td>
                            <td style="color: var(--txt-sub); white-space:nowrap;">{{ $order->updated_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
