@extends('layouts.admin')

@section('content')

<style>
    /* Estilo para el contenedor del tooltip */
    .tooltip-container {
        position: relative;
        display: inline-block;
        cursor: help;
    }

    /* El texto que aparece al hacer hover */
    .tooltip-text {
        visibility: hidden;
        width: 200px;
        background-color: #1f2937; /* zinc-800 */
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 8px;
        position: absolute;
        z-index: 50;
        bottom: 125%; /* Aparece arriba del icono */
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 10px;
        line-height: 1.2;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        pointer-events: none;
    }

    /* Flechita del tooltip */
    .tooltip-text::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #1f2937 transparent transparent transparent;
    }

    /* Mostrar al hacer hover */
    .tooltip-container:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
</style>

<div class="max-w-5xl mx-auto">
    {{-- 1. Encabezado --}}
    <div class="mb-8">
        <a href="{{ route('admin.database') }}" class="inline-flex items-center gap-1 text-sm font-bold text-zinc-500 hover:text-zinc-800 dark:hover:text-white mb-4 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver a Gestión
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Monitoreo del Sistema Relacional
        </h1>
    </div>

    {{-- 2. Cuadrícula de Métricas Principales --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        
        {{-- Tarjeta 1: QPS --}}
        <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5 shadow-sm col-span-1 md:col-span-2 lg:col-span-1">
            <div class="text-xs font-bold text-zinc-500 uppercase mb-2 flex items-center gap-1">
                Carga del Servidor (QPS)
                <div class="tooltip-container">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.444 1.103m.444-1.103c1.103 1.103 0 2.206 0 2.206m0-4.412c-.542.104-.994.54-.444 1.103"/></svg>
                    <span class="tooltip-text">Mide la cantidad de consultas que el motor procesa por segundo. Ideal para detectar picos de tráfico.</span>
                </div>
            </div>
            <div class="flex items-end gap-2 mb-4">
                <div id="qps-value" class="text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ $qps }}</div>
                <div class="text-sm font-medium text-zinc-400 mb-1">consultas / seg</div>
            </div>
            <div class="h-32 w-full">
                <canvas id="qpsChart"></canvas>
            </div>
        </div>

        {{-- Tarjeta 2: Conexiones --}}
        <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-bold text-zinc-500 uppercase mb-2 flex items-center gap-1">
                Conexiones Activas
                <div class="tooltip-container">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.444 1.103m.444-1.103c1.103 1.103 0 2.206 0 2.206m0-4.412c-.542.104-.994.54-.444 1.103"/></svg>
                    <span class="tooltip-text">Usuarios o procesos conectados simultáneamente. Si llega al máximo, no se permitirán nuevas conexiones.</span>
                </div>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-end gap-2">
                    <div id="conn-value" class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ $connections }}</div>
                    <div class="text-sm font-medium text-zinc-400 mb-1">/ <span id="conn-max">{{ $maxConnections }}</span></div>
                </div>
                <div id="conn-status-dot" class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
            </div>
            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                @php $porcentaje = $maxConnections > 0 ? ($connections / $maxConnections) * 100 : 0; @endphp
                <div id="conn-bar" class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ $porcentaje }}%"></div>
            </div>
            <div class="mt-3 text-[10px] text-zinc-500 flex justify-between">
                <span>Uso del pool</span>
                <span id="conn-percent">{{ round($porcentaje, 1) }}%</span>
            </div>
        </div>

        {{-- Tarjeta 3: Cuellos de Botella --}}
        <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-bold text-zinc-500 uppercase mb-2 flex items-center gap-1">
                Cuellos de Botella
                <div class="tooltip-container">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.444 1.103m.444-1.103c1.103 1.103 0 2.206 0 2.206m0-4.412c-.542.104-.994.54-.444 1.103"/></svg>
                    <span class="tooltip-text">Detecta consultas lentas que tardan más de lo normal en ejecutarse. Útil para optimizar índices.</span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-end gap-2">
                        <div id="slow-value" class="text-3xl font-black text-zinc-700 dark:text-zinc-300">{{ $slowQueries }}</div>
                        <div class="text-sm font-medium text-zinc-400 mb-1">registradas</div>
                    </div>
                </div>
                <div id="slow-badge" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-500">Optimizado</div>
            </div>
            <div class="mt-4 flex gap-1 h-1">
                <div class="flex-1 rounded-full bg-emerald-500/20"></div>
                <div class="flex-1 rounded-full bg-emerald-500/20"></div>
                <div id="slow-indicator" class="flex-1 rounded-full bg-emerald-500"></div>
            </div>
        </div>

        {{-- Tarjeta 4: Almacenamiento --}}
        <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-bold text-zinc-500 uppercase mb-2 flex items-center gap-1">
                Almacenamiento Total
                <div class="tooltip-container">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.444 1.103m.444-1.103c1.103 1.103 0 2.206 0 2.206m0-4.412c-.542.104-.994.54-.444 1.103"/></svg>
                    <span class="tooltip-text">Muestra el peso total ocupado en disco, dividiendo los datos puros del peso de los índices.</span>
                </div>
            </div>
            <div class="flex items-end gap-2 mb-4">
                <div id="storage-value" class="text-3xl font-black text-orange-600 dark:text-orange-400">{{ $dbSize }}</div>
                <div class="text-sm font-medium text-zinc-400 mb-1">MB</div>
            </div>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-[10px] mb-1">
                        <span class="text-zinc-500">Datos Puros</span>
                        <span id="data-size-label" class="font-bold text-zinc-700 dark:text-zinc-300">...</span>
                    </div>
                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5">
                        <div id="data-bar" class="bg-orange-500 h-1.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-[10px] mb-1">
                        <span class="text-zinc-500">Índices</span>
                        <span id="index-size-label" class="font-bold text-zinc-700 dark:text-zinc-300">...</span>
                    </div>
                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5">
                        <div id="index-bar" class="bg-indigo-500 h-1.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjeta 5: Uptime --}}
        <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5 shadow-sm lg:col-span-2">
            <div class="text-xs font-bold text-zinc-500 uppercase mb-2 flex items-center gap-1">
                Disponibilidad del Sistema (Uptime)
                <div class="tooltip-container">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.444 1.103m.444-1.103c1.103 1.103 0 2.206 0 2.206m0-4.412c-.542.104-.994.54-.444 1.103"/></svg>
                    <span class="tooltip-text">Tiempo total que el motor de Base de Datos ha estado activo sin interrupciones ni reinicios.</span>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div>
                    <div id="uptime-value" class="text-3xl font-black text-zinc-800 dark:text-white">{{ $uptimeStr }}</div>
                    <p class="text-[10px] text-zinc-500 mt-1">Tiempo transcurrido sin caídas.</p>
                </div>
                <div class="flex-1 h-8 flex items-center gap-1">
                    @for ($i = 0; $i < 20; $i++)
                        <div class="flex-1 h-full bg-emerald-500/20 rounded-sm overflow-hidden relative">
                            <div class="absolute inset-0 bg-emerald-500 animate-pulse" style="animation-delay: {{ $i * 0.1 }}s"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Tabla de Estadísticas --}}
    <div class="mt-6 bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-zinc-100 dark:border-white/5 flex justify-between items-center">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-white flex items-center gap-2">Análisis de Objetos Relacionales</h3>
            <span class="text-[10px] text-emerald-500 font-mono animate-pulse">● LIVE</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-zinc-500 uppercase font-bold">
                    <tr>
                        <th class="px-5 py-3">Entidad</th>
                        <th class="px-5 py-3 text-right">Registros</th>
                        <th class="px-5 py-3 text-right">Datos</th>
                        <th class="px-5 py-3 text-right">Índices</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody id="table-stats-body" class="divide-y divide-zinc-100 dark:divide-white/5">
                    @foreach($tableStats as $table)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition">
                        <td class="px-5 py-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $table->name }}</td>
                        <td class="px-5 py-3 text-right font-mono" id="rows-{{ $table->name }}">{{ number_format($table->rows) }}</td>
                        <td class="px-5 py-3 text-right">{{ $table->size }} MB</td>
                        <td class="px-5 py-3 text-right">{{ $table->index_size }} MB</td>
                        <td class="px-5 py-3 text-right font-bold text-zinc-700 dark:text-zinc-300">{{ number_format($table->size + $table->index_size, 2) }} MB</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('qpsChart').getContext('2d');
    const qpsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array(10).fill(''),
            datasets: [{
                data: Array(10).fill({{ $qps }}),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { beginAtZero: true } }
        }
    });

    async function updateMetrics() {
        try {
            const response = await fetch('{{ route("admin.database.api.metrics") }}');
            const data = await response.json();
            
            // 1. Uptime
            const upSec = data.uptime;
            const d = Math.floor(upSec / 86400), h = Math.floor((upSec % 86400) / 3600), m = Math.floor((upSec % 3600) / 60);
            document.getElementById('uptime-value').innerText = `${d > 0 ? d+'d ' : ''}${h}h ${m}m`;

            // 2. QPS
            document.getElementById('qps-value').innerText = data.qps;
            qpsChart.data.datasets[0].data.push(data.qps);
            qpsChart.data.datasets[0].data.shift();
            qpsChart.update();

            // 3. Conexiones
            const p = (data.connections / data.maxConnections) * 100;
            document.getElementById('conn-value').innerText = data.connections;
            document.getElementById('conn-percent').innerText = p.toFixed(1) + '%';
            document.getElementById('conn-bar').style.width = p + '%';

            // 4. Consultas Lentas
            const sVal = data.slowQueries;
            document.getElementById('slow-value').innerText = sVal;
            const badge = document.getElementById('slow-badge');
            if(sVal > 0) {
                badge.className = "px-3 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-600";
                badge.innerText = "Atención";
            } else {
                badge.className = "px-3 py-1 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-500";
                badge.innerText = "Optimizado";
            }

            // 5. Almacenamiento
            const total = parseFloat(data.dbSize), idx = parseFloat(data.totalIndexSize), dat = total - idx;
            document.getElementById('storage-value').innerText = total.toFixed(2);
            document.getElementById('data-size-label').innerText = dat.toFixed(2) + ' MB';
            document.getElementById('index-size-label').innerText = idx.toFixed(2) + ' MB';
            document.getElementById('data-bar').style.width = (dat/total*100) + '%';
            document.getElementById('index-bar').style.width = (idx/total*100) + '%';

            // 6. Tabla
            data.tableStats.forEach(t => {
                const el = document.getElementById(`rows-${t.name}`);
                if(el) el.innerText = new Intl.NumberFormat().format(t.rows);
            });

        } catch (e) { console.error("Error:", e); }
    }

    setInterval(updateMetrics, 30000);
</script>
@endsection
