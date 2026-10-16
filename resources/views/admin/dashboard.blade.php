@extends('layouts.admin')
@section('content')

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>


/* ── CORRECCIÓN DE TRANSPARENCIA EN MODALES ── */
.ds-overlay .ds-modal {
    background-color: var(--card) !important;
    /* Esta línea es la magia que evita que el fondo borroso traspase: */
    -webkit-transform: translate3d(0, 0, 0);
    transform: translate3d(0, 0, 0);
}
.ds-overlay .ds-modal-hd {
    background-color: var(--bg) !important;
}
.ds-overlay .ds-modal-body,
.ds-overlay .ds-table,
.ds-overlay .ds-table tbody tr,
.ds-overlay .ds-table td {
    background-color: var(--card) !important;
}
.ds-overlay .ds-table thead th {
    background-color: var(--bg) !important;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* ══════════════════════════════════════════
   VARIABLES — sistema admin La 501
══════════════════════════════════════════ */
:root {
    --ac:  #2563EB; --ac2: #1D4ED8;
    --gn:  #16A34A; --gn2: #22C55E;
    --or:  #EA580C; --am:  #D97706;
    --pu:  #7C3AED; --tl:  #0D9488;
    --rd:  #DC2626;
    --bg:  #F8F8F8; --card: #FFFFFF;
    --inp: #F4F4F5; --txt:  #18181B;
    --sub: #71717A; --bdr:  #E4E4E7;
}
.dark {
    --bg:  #0A0A0A; --card: #111111;
    --inp: #1C1C1C; --txt:  #FAFAFA;
    --sub: #71717A; --bdr:  rgba(255,255,255,.08);
}
.ds {
    --ac:  #2563EB; --ac2: #1D4ED8;
    --gn:  #16A34A; --gn2: #22C55E;
    --or:  #EA580C; --am:  #D97706;
    --pu:  #7C3AED; --tl:  #0D9488;
    --rd:  #DC2626;
    --bg:  #F8F8F8; --card: #FFFFFF;
    --inp: #F4F4F5; --txt:  #18181B;
    --sub: #71717A; --bdr:  #E4E4E7;
}
.dark .ds {
    --bg:  #0A0A0A; --card: #111111;
    --inp: #1C1C1C; --txt:  #FAFAFA;
    --sub: #71717A; --bdr:  rgba(255,255,255,.08);
}
.ds { background:var(--bg); min-height:100%; padding:28px 32px 60px; color:var(--txt); transition:background .2s,color .2s; }

/* ── HEADER ── */
.ds-hd { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.ds-hd h1 { font-size:20px; font-weight:700; color:var(--txt); margin:0 0 2px; }
.ds-hd p  { font-size:13px; color:var(--sub); margin:0; }

/* ── STAT CARDS ── */
.ds-stats { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-bottom:18px; }
.ds-stat {
    background:var(--card); border:1px solid var(--bdr);
    border-radius:10px; padding:14px 16px;
    position:relative; overflow:hidden;
    transition:box-shadow .15s,border-color .15s;
}
.ds-stat:hover { box-shadow:0 3px 16px rgba(0,0,0,.07); }
.ds-stat-bar { position:absolute; top:0; left:0; right:0; height:2px; }
.ds-stat-bar.gn { background:linear-gradient(90deg,var(--gn),var(--gn2)); }
.ds-stat-bar.or { background:linear-gradient(90deg,var(--or),#F97316); }
.ds-stat-bar.ac { background:linear-gradient(90deg,var(--ac),#60A5FA); }
.ds-stat-bar.am { background:linear-gradient(90deg,var(--am),#F59E0B); }
.ds-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.ds-stat-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--sub); }
.ds-stat-ico { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ds-stat-ico svg { width:13px; height:13px; }
.ds-stat-ico.gn { background:rgba(22,163,74,.1);  color:var(--gn); }
.ds-stat-ico.or { background:rgba(234,88,12,.1);  color:var(--or); }
.ds-stat-ico.ac { background:rgba(37,99,235,.1);  color:var(--ac); }
.ds-stat-ico.am { background:rgba(217,119,6,.1);  color:var(--am); }
.dark .ds-stat-ico.gn { color:#4ADE80; }
.dark .ds-stat-ico.or { color:#FB923C; }
.dark .ds-stat-ico.ac { color:#60A5FA; }
.dark .ds-stat-ico.am { color:#FCD34D; }
.ds-stat-val { font-size:24px; font-weight:800; color:var(--txt); line-height:1; margin:0; letter-spacing:-.5px; transition:color .2s; }
.ds-stat-flash { position:absolute; inset:0; background:rgba(34,197,94,.05); opacity:0; pointer-events:none; transition:opacity .5s; }
.ds-stat.flashing .ds-stat-flash { opacity:1; }

/* ── CHART CARD ── */
.ds-card {
    background: var(--card) !important; /* Fuerza el uso del color sólido de la variable */
    border: 1px solid var(--bdr);
    border-radius: 10px;
    overflow: hidden;
    transition: background .2s, border-color .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02); /* Sutil sombra para dar profundidad sin transparencia */
}
.ds-card-hd { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-bottom:1px solid var(--bdr); flex-wrap:wrap; gap:10px; }
.ds-card-ttl { display:flex; align-items:center; gap:8px; }
.ds-card-ttl-ico { width:26px; height:26px; border-radius:6px; background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.15); display:flex; align-items:center; justify-content:center; }
.ds-card-ttl-ico svg { width:13px; height:13px; color:var(--ac); }
.ds-card-ttl h2 { font-size:13px; font-weight:700; color:var(--txt); margin:0; transition:color .2s; }
.ds-card-body { padding:18px; }

/* ── CONTROLS ── */
.ds-controls { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.ds-pill-group { display:flex; background:var(--inp); border:1px solid var(--bdr); border-radius:7px; padding:2px; gap:1px; }
.ds-pill {
    padding:5px 12px; border-radius:5px; font-size:12px; font-weight:600;
    border:none; background:transparent; color:var(--sub);
    cursor:pointer; white-space:nowrap;
    transition:background .15s,color .15s,box-shadow .15s;
    display:inline-flex; align-items:center; gap:5px;
}
.ds-pill:hover { color:var(--txt); }
.ds-pill.on { background:var(--card); color:var(--txt); box-shadow:0 1px 3px rgba(0,0,0,.1); }
.dark .ds-pill.on { box-shadow:0 1px 3px rgba(0,0,0,.4); }
.ds-pill svg { width:12px; height:12px; }

/* export btn */
.ds-export-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 14px; border-radius:7px; background:var(--ac); color:#fff;
    border:none; font-size:12px; font-weight:700; cursor:pointer;
    transition:background .15s,transform .1s;
    box-shadow:0 1px 4px rgba(37,99,235,.25);
}
.ds-export-btn:hover { background:var(--ac2); transform:translateY(-1px); }
.ds-export-btn svg { width:13px; height:13px; }

/* canvas wrapper */
.ds-canvas-wrap { position:relative; height:260px; }

/* ── ESTADÍSTICAS INFERIORES ── */
.ds-bottom-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px; }
.ds-table { width:100%; border-collapse:collapse; font-size:12px; }
.ds-table th { padding:8px 10px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); border-bottom:1px solid var(--bdr); }
.ds-table td { padding:9px 10px; border-bottom:1px solid var(--bdr); color:var(--txt); transition:color .2s; }
.ds-table tr:last-child td { border-bottom:none; }
.ds-table tr:hover td { background:var(--bg); }
.ds-table .val-gn { color:var(--gn); font-weight:800; }
.ds-table .val-pu { color:var(--pu); font-weight:800; }
.ds-table .td-name { font-weight:600; }
.ds-table .td-sub  { color:var(--sub); font-size:11px; }

/* -- Tarjeta de Proyección -- */
.ds-proj-card {
    background: var(--card) !important;
    border: 1px solid var(--bdr);
    border-left: 3px solid var(--ac);
    border-radius: 10px;
    padding: 20px 24px;
    margin-top: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.ds-proj-val { font-size:30px; font-weight:800; color:var(--txt); margin:4px 0; letter-spacing:-1px; transition:color .2s; }
.ds-proj-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:var(--gn); }
.ds-proj-badge svg { width:13px; height:13px; }
.ds-proj-meta { background:var(--inp); border:1px solid var(--bdr); border-radius:8px; padding:12px 16px; font-size:12px; color:var(--sub); line-height:1.6; min-width:180px; }

/* ── MODAL ── */
.ds-overlay {
    display:none; position:fixed !important; inset:0 !important;
    z-index:9999999 !important; background:rgba(0,0,0,.55) !important;
    backdrop-filter:blur(6px) !important;
    align-items:center; justify-content:center; padding:16px;
}
/* -- Ventanas Modales (Ver todos) -- */
.ds-modal {
    background: var(--card) !important; /* Fondo sólido blanco o negro */
    border: 1px solid var(--bdr);
    border-radius: 12px;
    width: 100%;
    max-width: 550px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.3); /* Sombra pesada para que destaque sobre el fondo */
    overflow: hidden;
    opacity: 1 !important; /* Asegura opacidad total */
}
/* Ajuste para el encabezado del modal */
.ds-modal-hd {
    background: var(--bg) !important; /* Un tono ligeramente distinto para el título */
    border-bottom: 1px solid var(--bdr);
    padding: 16px 20px;
}
.ds-modal-hd h3 { font-size:15px; font-weight:700; color:var(--txt); margin:0 0 2px; transition:color .2s; }
.ds-modal-hd p  { font-size:12px; color:var(--sub); margin:0; }
.ds-modal-close { width:28px; height:28px; border-radius:7px; background:var(--inp); border:1px solid var(--bdr); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--sub); transition:background .15s; }
.ds-modal-close:hover { background:var(--bdr); }
.ds-modal-close svg { width:13px; height:13px; }
.ds-modal-body { padding:20px; display:flex; flex-direction:column; gap:14px; }
.ds-modal-foot { padding:14px 20px; border-top:1px solid var(--bdr); background:var(--bg); }
.ds-lbl { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); margin-bottom:5px; }
.ds-inp { width:100%; padding:9px 12px; background:var(--card); border:1px solid var(--bdr); border-radius:7px; font-size:13px; color:var(--txt); outline:none; font-family:inherit; transition:border-color .15s,box-shadow .15s; }
.ds-inp:focus { border-color:var(--ac); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.ds-btn-save { width:100%; padding:10px; border-radius:7px; background:var(--ac); color:#fff; border:none; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .15s; }
.ds-btn-save:hover { background:var(--ac2); }
.ds-btn-save svg { width:14px; height:14px; }

/* empty */
.ds-empty { padding:32px 20px; text-align:center; color:var(--sub); font-size:12px; }

@media(max-width:640px){ .ds { padding:20px 16px 40px; } .ds-bottom-grid { grid-template-columns:1fr; } }
</style>

<div class="ds" x-data="dashboardManager()" x-init="initDashboard()">

    {{-- HEADER --}}
    <div class="ds-hd">
        <div>
            <h1>Panel de Ventas</h1>
            <p>Gestión de La 501 en tiempo real</p>
        </div>
    </div>

    {{-- STATS --}}
    <div class="ds-stats">
        <div class="ds-stat" :class="isUpdating?'flashing':''">
            <div class="ds-stat-flash"></div>
            <div class="ds-stat-bar gn"></div>
            <div class="ds-stat-top">
                <span class="ds-stat-lbl">Ventas Totales</span>
                <div class="ds-stat-ico gn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="ds-stat-val" x-text="'$' + formatearDinero(stats.total_ventas)"></p>
        </div>

        <div class="ds-stat" :class="isUpdating?'flashing':''">
            <div class="ds-stat-flash"></div>
            <div class="ds-stat-bar or"></div>
            <div class="ds-stat-top">
                <span class="ds-stat-lbl">Pedidos Hoy</span>
                <div class="ds-stat-ico or">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
            </div>
            <p class="ds-stat-val" x-text="stats.pedidos_hoy"></p>
        </div>
    </div>

    {{-- CHART CARD --}}
    <div class="ds-card">
        <div class="ds-card-hd">
            <div class="ds-card-ttl">
                <div class="ds-card-ttl-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h2 id="ds-section-title">Reporte de Ventas</h2>
            </div>

            <div class="ds-controls">
                {{-- Tabs --}}
                <div class="ds-pill-group">
                    <button type="button" id="tab-ganancia" class="ds-pill on"
                            onclick="switchTab('ganancia')" style="display:flex;align-items:center;gap:5px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Ganancias
                    </button>
                    <button type="button" id="tab-categoria" class="ds-pill"
                            onclick="switchTab('categoria')" style="display:flex;align-items:center;gap:5px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        Rotación
                    </button>
                    <button type="button" id="tab-corte" class="ds-pill"
                            onclick="switchTab('corte')" style="display:flex;align-items:center;gap:5px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m16-2a4 4 0 01-4-4H7M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Corte Diario
                    </button>
                </div>

                {{-- Períodos --}}
                <div class="ds-pill-group" id="period-controls">
                    <button id="btn-day" class="ds-pill on" @click="changePeriod('day')">30 Días</button>
                    <button id="btn-month" class="ds-pill" @click="changePeriod('month')">Meses</button>
                </div>

                {{-- Controles Rotación (Mes y Año) --}}
                <div id="filter-rotacion" style="display:none;">
                    <form method="GET" action="" style="display:flex; gap:8px; margin:0; align-items:center;">
                        <input type="hidden" name="active_tab" value="categoria">
                        <label style="font-size:11px; color:var(--sub); font-weight:600;">Filtrar Mes:</label>
                        <input type="month" name="rotacion_date" value="{{ $rotacionDate ?? date('Y-m') }}" class="ds-inp" style="padding:4px 8px; font-size:12px; width:auto; height:28px;" onchange="this.form.submit()" onclick="this.showPicker()">
                    </form>
                </div>

                {{-- Controles Corte Diario --}}
                <div id="filter-corte" style="display:none;">
                    <form method="GET" action="" style="display:flex; gap:8px; margin:0; align-items:center;">
                        <input type="hidden" name="active_tab" value="corte">
                        <label style="font-size:11px; color:var(--sub); font-weight:600;">Fecha del Corte:</label>
                        <input type="date" name="corte_date" value="{{ $corteDate ?? date('Y-m-d') }}" class="ds-inp" style="padding:4px 8px; font-size:12px; width:auto; height:28px;" onchange="this.form.submit()" onclick="this.showPicker()">
                    </form>
                </div>
                
                {{-- Exportar --}}
                <button type="button" class="ds-export-btn" onclick="ejecutarExport()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exportar
                </button>
            </div>
        </div>

        <div class="ds-card-body">
            <div id="panel-ganancia" class="ds-canvas-wrap">
                <canvas id="salesChartCanvas"></canvas>
            </div>
            <div id="panel-categoria" style="display:none;">
                <div style="position:relative;height:240px;">
                    <canvas id="categoryChartCanvas"></canvas>
                </div>
            </div>
            <div id="panel-corte" style="display:none;" class="space-y-6">
                <!-- GRID DE RESUMEN (Tipo de la imagen) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    
                    <!-- Tarjeta 1: Ventas por Tipo de Servicio -->
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-white/5 rounded-xl p-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Ventas por Servicio</h3>
                                <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 font-bold px-2 py-0.5 rounded-full">Servicios</span>
                            </div>
                            <div class="space-y-2 mt-4">
                                @foreach($corteServicios as $tipo => $data)
                                    <div>
                                        <div class="flex justify-between text-xs font-semibold mb-1">
                                            <span class="text-zinc-700 dark:text-zinc-300">{{ $tipo }} ({{ $data['cantidad'] }})</span>
                                            <span class="text-zinc-900 dark:text-white font-bold">${{ number_format($data['monto'], 2) }}</span>
                                        </div>
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $corteTotal > 0 ? ($data['monto'] / $corteTotal * 100) : 0 }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Productos más vendidos -->
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-white/5 rounded-xl p-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Productos más vendidos</h3>
                                <span class="text-xs bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 font-bold px-2 py-0.5 rounded-full">Top 5</span>
                            </div>
                            <div class="space-y-2 mt-3">
                                @forelse($topProductsCorte as $prod)
                                    <div>
                                        <div class="flex justify-between text-xs font-semibold mb-1">
                                            <span class="text-zinc-700 dark:text-zinc-300 truncate max-w-[120px]">{{ $prod->name }}</span>
                                            <span class="text-zinc-900 dark:text-white font-bold">{{ $prod->total_vendido }} und.</span>
                                        </div>
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-orange-500 h-1.5 rounded-full" style="width: {{ $topProductsCorte->first() && $topProductsCorte->first()->total_vendido > 0 ? ($prod->total_vendido / $topProductsCorte->first()->total_vendido * 100) : 0 }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-4">Sin ventas registradas hoy.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Formas de pago más utilizadas -->
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-white/5 rounded-xl p-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Formas de Pago</h3>
                                <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 font-bold px-2 py-0.5 rounded-full">Métodos</span>
                            </div>
                            <div class="space-y-2 mt-4">
                                @foreach($totalsCorte as $metodo => $monto)
                                    <div>
                                        <div class="flex justify-between text-xs font-semibold mb-1">
                                            <span class="text-zinc-700 dark:text-zinc-300 capitalize">
                                                {{ $metodo }}
                                            </span>
                                            <span class="text-zinc-900 dark:text-white font-bold">${{ number_format($monto, 2) }}</span>
                                        </div>
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-green-600 h-1.5 rounded-full" style="width: {{ $corteTotal > 0 ? ($monto / $corteTotal * 100) : 0 }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 4: Pedidos Web vs Local -->
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-white/5 rounded-xl p-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Pedidos Web / Local</h3>
                                <span class="text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 font-bold px-2 py-0.5 rounded-full">Origen</span>
                            </div>
                            <div class="space-y-2 mt-4">
                                @foreach($corteWebLocal as $origen => $data)
                                    <div>
                                        <div class="flex justify-between text-xs font-semibold mb-1">
                                            <span class="text-zinc-700 dark:text-zinc-300">{{ $origen }} ({{ $data['cantidad'] }})</span>
                                            <span class="text-zinc-900 dark:text-white font-bold">${{ number_format($data['monto'], 2) }}</span>
                                        </div>
                                        <div class="w-full bg-zinc-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $corteTotal > 0 ? ($data['monto'] / $corteTotal * 100) : 0 }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>

                <!-- SEPARADOR Y TABLA DETALLADA TIPO BSALE (1783028191.xls) -->
                <div class="border-t border-dashed border-zinc-200 dark:border-white/10 pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-zinc-500 font-['Oswald']">
                            Reporte de Cierre de Caja (Formato Excel / Caja)
                        </h3>
                        <button type="button" onclick="window.print()" class="text-xs font-bold text-blue-500 hover:text-blue-600 flex items-center gap-1 transition">
                            🖨️ Imprimir Cierre
                        </button>
                    </div>

                    <div class="bg-white dark:bg-black border border-zinc-200 dark:border-white/5 rounded-xl p-6 shadow-sm font-mono text-xs text-zinc-800 dark:text-zinc-300 space-y-4 max-w-2xl mx-auto">
                        <div class="border-b border-zinc-200 dark:border-white/10 pb-4 text-center">
                            <p class="font-bold text-sm uppercase">La 501 Sports Bar</p>
                            <p class="text-zinc-500">Corte de Caja Diario</p>
                        </div>

                        <div class="space-y-1">
                            <p><b>Fecha de Cierre:</b> {{ date('d/m/Y', strtotime($corteDate)) }}</p>
                            <p><b>Hora Reg. Apertura:</b> {{ $corteFirstTime }}</p>
                            <p><b>Hora Reg. Cierre:</b> {{ $corteLastTime }}</p>
                        </div>

                        <div class="border-t border-b border-dashed border-zinc-300 dark:border-zinc-700 py-3 my-3">
                            <p class="font-bold text-center uppercase tracking-wider mb-2">Resumen de Ventas</p>

                            @php $hasVentas = false; @endphp
                            @foreach($detailedPayments as $metodo => $data)
                                @if($data['total'] > 0)
                                    @php $hasVentas = true; @endphp
                                    <div class="mt-3">
                                        <div class="flex justify-between font-bold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-white/5 pb-1">
                                            <span>{{ strtoupper($metodo) }} ({{ count($data['orders']) }})</span>
                                            <span>${{ number_format($data['total'], 2) }}</span>
                                        </div>
                                        <div class="pl-4 mt-1.5 space-y-1">
                                            <div class="grid grid-cols-3 text-zinc-400 font-semibold mb-1 text-[10px]">
                                                <span>Tipo Documento</span>
                                                <span class="text-center">Nº Documento</span>
                                                <span class="text-right">Monto</span>
                                            </div>
                                            @foreach($data['orders'] as $ord)
                                                <div class="grid grid-cols-3 text-zinc-600 dark:text-zinc-400">
                                                    <span class="truncate">TICKET DE VENTA T</span>
                                                    <span class="text-center">
                                                        <a href="{{ route('payment.confirmation', $ord['id']) }}" target="_blank" class="text-blue-500 hover:text-blue-600 underline font-bold transition-all">
                                                            #{{ str_pad($ord['id'], 4, '0', STR_PAD_LEFT) }}
                                                        </a>
                                                    </span>
                                                    <span class="text-right">${{ number_format($ord['monto'], 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            @if(!$hasVentas)
                                <p class="text-center text-zinc-400 dark:text-zinc-500 py-4">No se registraron ventas en la fecha seleccionada.</p>
                            @endif
                        </div>

                        <div class="flex justify-between font-bold text-sm text-zinc-950 dark:text-white pt-2">
                            <span>TOTAL GENERAL VENTAS</span>
                            <span>${{ number_format($corteTotal, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ESTADÍSTICAS INFERIORES (solo en tab Rotación) --}}
    <div id="panel-estadisticas" style="display:none;">

        <div class="ds-bottom-grid">
            {{-- Media aritmética --}}
            <div class="ds-card" style="margin-top:14px;">
                <div class="ds-card-hd">
                    <div class="ds-card-ttl">
                        <div class="ds-card-ttl-ico" style="background:rgba(13,148,136,.08);border-color:rgba(13,148,136,.18);">
                            <svg fill="none" stroke="#0D9488" viewBox="0 0 24 24" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <h2>Consumo Promedio</h2>
                    </div>
                    {{-- Botón Ver Todos (solo aparece si hay más de 5 registros) --}}
                    @if(count($mediaAritmetica) > 5)
                        <button type="button" onclick="abrirModal('modal-consumo')" style="background:none; border:none; font-size:11px; font-weight:700; color:var(--ac); cursor:pointer; padding:0;">Ver todos &rarr;</button>
                    @endif
                </div>
                <div class="ds-card-body" style="padding:0 0 4px;">
                    <table class="ds-table">
                        <thead>
                            <tr>
                                <th style="text-align:left">Platillo</th>
                                <th style="text-align:right">Total</th>
                                <th style="text-align:right">Media/día</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Limitamos a 5 visualmente en la tarjeta --}}
                            @forelse($mediaAritmetica->take(5) as $item)
                            <tr>
                                <td class="td-name">{{ $item->name }}</td>
                                <td style="text-align:right" class="td-sub">{{ $item->total_vendido }} und.</td>
                                <td style="text-align:right" class="val-gn">{{ $item->media_diaria }}/día</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="ds-empty">Sin datos suficientes este mes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Moda --}}
            <div class="ds-card" style="margin-top:14px;">
                <div class="ds-card-hd">
                    <div class="ds-card-ttl">
                        <div class="ds-card-ttl-ico" style="background:rgba(124,58,237,.08);border-color:rgba(124,58,237,.18);">
                            <svg fill="none" stroke="#7C3AED" viewBox="0 0 24 24" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <h2>Alta Rotación</h2>
                    </div>
                    {{-- Botón Ver Todos --}}
                    @if(count($modaMatematica) > 5)
                        <button type="button" onclick="abrirModal('modal-rotacion')" style="background:none; border:none; font-size:11px; font-weight:700; color:var(--ac); cursor:pointer; padding:0;">Ver todos &rarr;</button>
                    @endif
                </div>
                <div class="ds-card-body" style="padding:0 0 4px;">
                    <table class="ds-table">
                        <thead>
                            <tr>
                                <th style="text-align:left">Platillo</th>
                                <th style="text-align:right">Pedidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Limitamos a 5 visualmente en la tarjeta --}}
                            @forelse($modaMatematica->take(5) as $item)
                            <tr>
                                <td class="td-name">{{ $item->name }}</td>
                                <td style="text-align:right" class="val-pu">{{ $item->frecuencia }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="ds-empty">Sin datos de frecuencia.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Proyección interactiva, gráfica y comprobación --}}
        <div id="modulo-prediccion" style="background:var(--card); border:1px solid var(--bdr); border-radius:10px; margin-top:14px; overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid var(--bdr); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 style="margin:0; font-size:15px; color:var(--ac);">Módulo de Predicción Matemática</h3>
                    <p style="margin:0; font-size:12px; color:var(--sub);">Ecuación Diferencial: P(t) = P₀eᵏᵗ (Evaluación por {{ $granularidad }}s)</p>
                </div>
                
                {{-- Formulario de Fechas Modificado para AJAX --}}
                <form id="form-prediccion" onsubmit="calcularPrediccionAJAX(event)" style="display:flex; gap:8px; align-items:flex-end;">
                    <div>
                        <label style="font-size:10px; color:var(--sub); display:block;">Desde</label>
                        <input type="date" name="start_date" id="pred_start" value="{{ $fechaInicioPred }}" class="ds-inp" style="padding:5px 8px; font-size:12px;" onclick="this.showPicker()">
                    </div>
                    <div>
                        <label style="font-size:10px; color:var(--sub); display:block;">Hasta</label>
                        <input type="date" name="end_date" id="pred_end" value="{{ $fechaFinPred }}" class="ds-inp" style="padding:5px 8px; font-size:12px;" onclick="this.showPicker()">
                    </div>
                    <button type="submit" id="btn-calc-pred" class="ds-pill on" style="padding:6px 12px; background:var(--ac); color:#fff; border-radius:5px; transition:0.2s;">Calcular</button>
                </form>
            </div>

            <div style="padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                {{-- Panel de Resultados --}}
                <div>
                    <p style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--sub); margin:0 0 4px;">Ganancia Proyectada (Próxim{{ $granularidad == 'Semana' ? 'a' : 'o' }} {{ $granularidad }})</p>
                    <p style="font-size:32px; font-weight:800; color:var(--txt); margin:0 0 10px;">${{ number_format($gananciaProyectada, 2) }}</p>
                    
                    @if($k_constante > 0)
                        <span style="display:inline-block; padding:4px 8px; background:rgba(22,163,74,.1); color:var(--gn); border-radius:4px; font-size:11px; font-weight:700;">
                            + Crecimiento Exponencial (k = {{ round($k_constante, 4) }})
                        </span>
                    @else
                        <span style="display:inline-block; padding:4px 8px; background:rgba(220,38,38,.1); color:var(--rd); border-radius:4px; font-size:11px; font-weight:700;">
                            - Decrecimiento o Datos Insuficientes
                        </span>
                    @endif
                </div>

                {{-- Tabla de Comprobación Manual --}}
                <div style="background:var(--bg); border:1px solid var(--bdr); border-radius:8px; padding:12px;">
                    <p style="margin:0 0 8px; font-size:12px; font-weight:700;">Comprobación manual extraída de BD:</p>
                    <table style="width:100%; font-size:11px; color:var(--sub); border-collapse:collapse;">
                        <tr style="border-bottom:1px solid var(--bdr);">
                            <th style="text-align:left; padding:4px 0;">t ({{ $granularidad }})</th>
                            <th style="text-align:right; padding:4px 0;">Ingreso Registrado P(t)</th>
                        </tr>
                        @foreach($historialSintetizado as $index => $item)
                        <tr style="border-bottom:1px solid rgba(0,0,0,0.05);">
                            <td style="padding:4px 0;">t = {{ $index }} <span style="opacity:0.6;">({{ $item->etiqueta }})</span></td>
                            <td style="text-align:right; padding:4px 0; color:var(--txt);">${{ number_format($item->total_ganado, 2) }}</td>
                        </tr>
                        @endforeach
                    </table>
                    <p style="margin:8px 0 0; font-size:10px; color:var(--sub);">
                        <b>Fórmula k:</b> ln({{ round($p_last,2) }} / {{ round($p0,2) }}) / {{ $historialSintetizado->count() > 1 ? $historialSintetizado->count()-1 : 1 }} = {{ round($k_constante, 4) }}<br>
                        <b>Fórmula Final:</b> {{ round($p0,2) }} * e^({{ round($k_constante, 4) }} * {{ $historialSintetizado->count() }}) = {{ round($gananciaProyectada, 2) }}
                    </p>
                </div>

                {{-- Gráfica de Tendencia --}}
                <div style="grid-column: 1 / -1; margin-top:10px; padding-top:20px; border-top:1px dashed var(--bdr);">
                    <p style="font-size:13px; font-weight:700; margin:0 0 15px; color:var(--txt);">Curva de Crecimiento (Modelo Matemático vs. Realidad)</p>
                    <div style="position:relative; height: 260px;">
                        {{-- Guardamos los datos en la etiqueta para leerlos con JS sin recargar --}}
                        <canvas id="prediccionEcuacionChart" 
                                data-labels="{{ $fechasChartJson ?? '[]' }}"
                                data-reales="{{ $realesChartJson ?? '[]' }}"
                                data-prediccion="{{ $prediccionChartJson ?? '[]' }}"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- MODAL EXPORTAR --}}
<div id="modal-export" class="ds-overlay">
    <div class="ds-modal">
        <div class="ds-modal-hd">
            <div>
                <h3>Exportar Reporte</h3>
                <p>Selecciona el rango de fechas</p>
            </div>
            <button type="button" class="ds-modal-close" onclick="cerrarModal('modal-export')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="export-form" action="{{ route('admin.export.ganancias') }}" method="GET" target="_blank"
              onsubmit="setTimeout(()=>cerrarModal('modal-export'),500)">
            <div class="ds-modal-body">
                <div>
                    <label class="ds-lbl">Fecha de inicio</label>
                    <input type="date" name="start_date" class="ds-inp" required
                           value="{{ $primeraFecha ?? date('Y-m-d') }}">
                </div>
                <div>
                    <label class="ds-lbl">Fecha final</label>
                    <input type="date" name="end_date" class="ds-inp" required
                           value="{{ $ultimaFecha ?? date('Y-m-d') }}">
                </div>
            </div>
            <div class="ds-modal-foot">
                <button type="submit" class="ds-btn-save">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Descargar Excel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL CONSUMO PROMEDIO COMPLETO --}}
<div id="modal-consumo" class="ds-overlay">
    <div class="ds-modal" style="max-width:600px;">
        <div class="ds-modal-hd">
            <div>
                <h3>Consumo Promedio (Ranking Completo)</h3>
                <p>Todos los platillos del periodo seleccionado</p>
            </div>
            <button type="button" class="ds-modal-close" onclick="cerrarModal('modal-consumo')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="ds-modal-body" style="padding:0; max-height:65vh; overflow-y:auto;">
            <table class="ds-table">
                <thead>
                    <tr>
                        <th style="text-align:center; width:40px;">#</th>
                        <th style="text-align:left">Platillo</th>
                        <th style="text-align:right">Total Vendido</th>
                        <th style="text-align:right">Media/día</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mediaAritmetica as $index => $item)
                    <tr>
                        <td style="text-align:center; color:var(--sub); font-size:11px; font-weight:700;">{{ $index + 1 }}</td>
                        <td class="td-name">{{ $item->name }}</td>
                        <td style="text-align:right" class="td-sub">{{ $item->total_vendido }} und.</td>
                        <td style="text-align:right" class="val-gn">{{ $item->media_diaria }} / día</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL ALTA ROTACION COMPLETO --}}
<div id="modal-rotacion" class="ds-overlay">
    <div class="ds-modal" style="max-width:550px;">
        <div class="ds-modal-hd">
            <div>
                <h3>Alta Rotación (Ranking Completo)</h3>
                <p>Frecuencia de pedidos por platillo en el mes</p>
            </div>
            <button type="button" class="ds-modal-close" onclick="cerrarModal('modal-rotacion')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="ds-modal-body" style="padding:0; max-height:65vh; overflow-y:auto;">
            <table class="ds-table">
                <thead>
                    <tr>
                        <th style="text-align:center; width:40px;">#</th>
                        <th style="text-align:left">Platillo</th>
                        <th style="text-align:right">Total de Pedidos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modaMatematica as $index => $item)
                    <tr>
                        <td style="text-align:center; color:var(--sub); font-size:11px; font-weight:700;">{{ $index + 1 }}</td>
                        <td class="td-name">{{ $item->name }}</td>
                        <td style="text-align:right" class="val-pu">{{ $item->frecuencia }} veces</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>

document.addEventListener("DOMContentLoaded", function() {
            const ctxP = document.getElementById('prediccionEcuacionChart');
            if(ctxP) {
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
                const tickColor = isDark ? '#71717A' : '#9CA3AF';
                const textColor = isDark ? '#FAFAFA' : '#18181B';

                new Chart(ctxP, {
                    type: 'line',
                    data: {
                        labels: {!! $fechasChartJson ?? '[]' !!},
                        datasets: [
                            {
                                label: 'Ingresos Reales',
                                data: {!! $realesChartJson ?? '[]' !!},
                                borderColor: '#2563EB',
                                backgroundColor: 'rgba(37,99,235,0.15)',
                                borderWidth: 2,
                                pointRadius: 4,
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Curva del Modelo Exponencial (P₀eᵏᵗ)',
                                data: {!! $prediccionChartJson ?? '[]' !!},
                                borderColor: '#EA580C',
                                borderDash: [5, 5],
                                borderWidth: 2,
                                pointBackgroundColor: '#EA580C',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                fill: false,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: textColor, font: { family: 'inherit', size: 12 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) { label += ': '; }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: { color: tickColor, callback: function(value) { return '$' + value.toLocaleString(); } },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: tickColor },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });



/* ── MODAL ── */
function abrirModal(id)  { document.getElementById(id).style.setProperty('display','flex','important'); }
function cerrarModal(id) { document.getElementById(id).style.setProperty('display','none','important'); }

/* ── CHART INSTANCES ── */
let salesChart    = null;
let categoryChart = null;
let currentTab    = 'ganancia';
let currentPeriod = 'day';

/* ── ALPINE COMPONENT ── */
function dashboardManager() {
    return {
        period: 'day',
        isUpdating: false,
        stats: {
            total_ventas: parseFloat('{{ str_replace(',','', $stats['total_ventas'] ?? 0) }}'),
            pedidos_hoy:  parseInt('{{ $stats['pedidos_hoy'] ?? 0 }}'),
            en_proceso:   parseInt('{{ $stats['en_proceso'] ?? 0 }}'),
        },

        initDashboard() {
            this.renderSalesChart();
            const pusher  = new Pusher('491d18da8b8b427e4969', { cluster: 'us2' });
            const channel = pusher.subscribe('dashboard-channel');
            channel.bind('dashboard.updated', () => {
                this.fetchStats();
                if (currentTab === 'ganancia') this.renderSalesChart(true);
            });

            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('active_tab') === 'categoria' || urlParams.get('rotacion_date')) {
                setTimeout(() => switchTab('categoria'), 100);
            } else if(urlParams.get('active_tab') === 'corte' || urlParams.get('corte_date')) {
                setTimeout(() => switchTab('corte'), 100);
            }
        },

        changePeriod(p) {
            this.period = p;
            currentPeriod = p;
            document.getElementById('btn-day').classList.toggle('on', p === 'day');
            document.getElementById('btn-month').classList.toggle('on', p === 'month');
            if (currentTab === 'ganancia') this.renderSalesChart();
            else initCategoryChart(p);
        },

        formatearDinero(v) { return Number(v).toLocaleString('en-US',{minimumFractionDigits:2}); },

        async fetchStats() {
            try {
                const r = await fetch("{{ route('admin.api.stats') }}");
                if (r.ok) this.stats = await r.json();
            } catch(e) {}
        },

        async renderSalesChart(silent = false) {
            try {
                const r    = await fetch(`{{ route('admin.api.sales') }}?period=${this.period}&t=${Date.now()}`);
                const json = await r.json();
                const ctx  = document.getElementById('salesChartCanvas').getContext('2d');

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
                const tickColor = isDark ? '#71717A' : '#9CA3AF';

                if (!salesChart) {
                    salesChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: json.labels,
                            datasets: [{
                                label: 'Ventas',
                                data: json.data,
                                borderColor: '#2563EB',
                                backgroundColor: 'rgba(37,99,235,0.08)',
                                tension: 0.4, fill: true,
                                pointBackgroundColor: '#2563EB',
                                pointRadius: 3, pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { color: gridColor }, ticks: { color: tickColor, font:{ size:11 } } },
                                y: { grid: { color: gridColor }, ticks: { color: tickColor, font:{ size:11 }, callback: v => '$'+v.toLocaleString() } }
                            }
                        }
                    });
                } else {
                    salesChart.data.labels = json.labels;
                    salesChart.data.datasets[0].data = json.data;
                    salesChart.update();
                }
            } catch(e) { console.error('Chart error:', e); }
        }
    }
}

let prediccionChartInst = null;

        // Función encargada de dibujar la gráfica leyendo los datos incrustados
        function renderPrediccionChart() {
            const canvas = document.getElementById('prediccionEcuacionChart');
            if(!canvas) return;

            const ctxP = canvas.getContext('2d');
            const labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
            const reales = JSON.parse(canvas.getAttribute('data-reales') || '[]');
            const prediccion = JSON.parse(canvas.getAttribute('data-prediccion') || '[]');

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
            const tickColor = isDark ? '#71717A' : '#9CA3AF';
            const textColor = isDark ? '#FAFAFA' : '#18181B';

            if(prediccionChartInst) {
                prediccionChartInst.destroy(); // Eliminar gráfica anterior si existe
            }

            prediccionChartInst = new Chart(ctxP, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Ingresos Reales',
                            data: reales,
                            borderColor: '#2563EB',
                            backgroundColor: 'rgba(37,99,235,0.15)',
                            borderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Curva del Modelo Exponencial (P₀eᵏᵗ)',
                            data: prediccion,
                            borderColor: '#EA580C',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            pointBackgroundColor: '#EA580C',
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: textColor, font: { family: 'inherit', size: 12 } } },
                        tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y); } } }
                    },
                    scales: {
                        y: { ticks: { color: tickColor, callback: function(value) { return '$' + value.toLocaleString(); } }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor }, grid: { display: false } }
                    }
                }
            });
        }

        // 1. Dibujar gráfica al cargar la página por primera vez
        document.addEventListener("DOMContentLoaded", renderPrediccionChart);

        // 2. Función AJAX asíncrona para interceptar el botón Calcular
        async function calcularPrediccionAJAX(e) {
            e.preventDefault(); // Detiene la recarga de la página

            const form = e.target;
            const btn = document.getElementById('btn-calc-pred');
            const start = document.getElementById('pred_start').value;
            const end = document.getElementById('pred_end').value;

            // Efecto visual de carga
            const originalText = btn.innerText;
            btn.innerText = 'Calculando...';
            btn.style.opacity = '0.7';
            btn.disabled = true;

            try {
                // Preparamos la URL con las fechas en el fondo
                const url = new URL(window.location.href);
                url.searchParams.set('start_date', start);
                url.searchParams.set('end_date', end);

                // Fetch invisible al servidor PHP
                const response = await fetch(url);
                const htmlText = await response.text();

                // Extraemos solo el código de la tarjeta de predicción
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlText, 'text/html');
                const nuevoModulo = doc.getElementById('modulo-prediccion');

                // Inyectamos el nuevo HTML sin tocar el resto de la página
                document.getElementById('modulo-prediccion').innerHTML = nuevoModulo.innerHTML;

                // Redibujamos la gráfica con los nuevos datos
                renderPrediccionChart();

            } catch (error) {
                console.error('Error al calcular:', error);
                alert('Hubo un error al calcular la predicción.');
            } finally {
                // Restauramos el botón
                const newBtn = document.getElementById('btn-calc-pred');
                if(newBtn) {
                    newBtn.innerText = 'Calcular';
                    newBtn.style.opacity = '1';
                    newBtn.disabled = false;
                }
            }
        }

/* ── SWITCH TABS ── */
function switchTab(tab) {
    currentTab = tab;
    const formExport = document.getElementById('export-form');
    const panelG = document.getElementById('panel-ganancia');
    const panelC = document.getElementById('panel-categoria');
    const panelE = document.getElementById('panel-estadisticas');
    const panelCorte = document.getElementById('panel-corte');
    const btnG   = document.getElementById('tab-ganancia');
    const btnC   = document.getElementById('tab-categoria');
    const btnCorte = document.getElementById('tab-corte');
    const title  = document.getElementById('ds-section-title');
    
    // Contenedores de botones
    const periodControls = document.getElementById('period-controls');
    const filterRotacion = document.getElementById('filter-rotacion');
    const filterCorte = document.getElementById('filter-corte');

    if (tab === 'ganancia') {
        panelG.style.display = 'block';
        panelC.style.display = 'none';
        if (panelE) panelE.style.display = 'none';
        if (panelCorte) panelCorte.style.display = 'none';
        btnG.classList.add('on'); btnC.classList.remove('on'); if(btnCorte) btnCorte.classList.remove('on');
        title.textContent = 'Reporte de Ventas';
        
        if (periodControls) periodControls.style.display = 'flex';
        if (filterRotacion) filterRotacion.style.display = 'none';
        if (filterCorte) filterCorte.style.display = 'none';
        
        const alpine = document.querySelector('[x-data]').__x.$data;
        alpine.renderSalesChart();
        if (formExport) formExport.action = "{{ route('admin.export.ganancias') }}";
    } else if (tab === 'categoria') {
        panelG.style.display = 'none';
        panelC.style.display = 'block';
        if (panelE) panelE.style.display = 'block';
        if (panelCorte) panelCorte.style.display = 'none';
        btnG.classList.remove('on'); btnC.classList.add('on'); if(btnCorte) btnCorte.classList.remove('on');
        title.textContent = 'Rotación por Categoría';
        
        if (periodControls) periodControls.style.display = 'none';
        if (filterRotacion) filterRotacion.style.display = 'flex';
        if (filterCorte) filterCorte.style.display = 'none';
        
        currentPeriod = 'month';
        const rotacionInput = document.querySelector('input[name="rotacion_date"]');
        const rDate = rotacionInput ? rotacionInput.value : '';
        setTimeout(() => initCategoryChart(rDate), 50);
        if (formExport) formExport.action = "{{ route('admin.export.rotacion') }}";
    } else if (tab === 'corte') {
        panelG.style.display = 'none';
        panelC.style.display = 'none';
        if (panelE) panelE.style.display = 'none';
        if (panelCorte) panelCorte.style.display = 'block';
        btnG.classList.remove('on'); btnC.classList.remove('on'); if(btnCorte) btnCorte.classList.add('on');
        title.textContent = 'Corte de Caja Diario';
        
        if (periodControls) periodControls.style.display = 'none';
        if (filterRotacion) filterRotacion.style.display = 'none';
        if (filterCorte) filterCorte.style.display = 'flex';
    }
}

function ejecutarExport() {
    if (currentTab === 'corte') {
        const corteDate = document.querySelector('input[name="corte_date"]').value;
        window.location.href = `{{ route('admin.export.cortediario') }}?corte_date=${corteDate}`;
    } else {
        abrirModal('modal-export');
    }
}

/* ── CATEGORY CHART ── */
async function initCategoryChart(rotacionDate = '') {
    try {
        const r    = await fetch(`{{ route('admin.api.category_sales') }}?rotacion_date=${rotacionDate}`);
        const json = await r.json();
        const ctx  = document.getElementById('categoryChartCanvas').getContext('2d');

        const isDark = document.documentElement.classList.contains('dark');
        const tickColor = isDark ? '#71717A' : '#9CA3AF';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';

        if (categoryChart) {
            categoryChart.data.labels = json.labels;
            categoryChart.data.datasets[0].data = json.data;
            categoryChart.data.datasets[0].backgroundColor = json.backgroundColors;
            categoryChart.update();
        } else {
            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: json.labels,
                    datasets: [{
                        label: '% de Participación',
                        data: json.data,
                        backgroundColor: json.backgroundColors,
                        borderRadius: 6, borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: c => ` ${c.parsed.y}%` } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: tickColor, font:{ size:11, weight:'600' } } },
                        y: { max:100, beginAtZero:true, grid:{ color:gridColor }, ticks:{ color:tickColor, callback: v => v+'%', font:{ size:11 } } }
                    }
                }
            });
        }
    } catch(e) { console.error('Category chart error:', e); }
}
</script>

@endsection
