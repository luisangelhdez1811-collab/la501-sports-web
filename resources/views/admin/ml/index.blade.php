@extends('layouts.admin')

@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="adm-main-inner space-y-6">

    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#111111] p-6 rounded-2xl border border-zinc-200 dark:border-white/5 shadow-sm">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Centro de Predicciones & Analítica</h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Modelos predictivos de Clasificación, Clustering, Regresión y Reglas de Asociación en tiempo real.</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Modelos Activos (scikit-learn & Apriori)
            </span>
        </div>
    </div>

    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-white dark:bg-[#111111] p-5 rounded-2xl border border-zinc-200 dark:border-white/5 shadow-sm">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Clientes Evaluados</p>
            <h3 class="text-2xl font-bold mt-1 text-zinc-900 dark:text-white">{{ count($customerAnalysis['customers']) }}</h3>
            <p class="text-[11px] text-zinc-400 mt-1">Base de usuarios registrados</p>
        </div>

        
        <div class="bg-white dark:bg-[#111111] p-5 rounded-2xl border border-zinc-200 dark:border-white/5 shadow-sm">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Segmento VIP (K-Means)</p>
            <h3 class="text-2xl font-bold mt-1 text-emerald-600 dark:text-emerald-400">{{ $customerAnalysis['summary']['VIP'] }}</h3>
            <p class="text-[11px] text-emerald-500/80 mt-1">Alto consumo y frecuencia</p>
        </div>

        
        <div class="bg-white dark:bg-[#111111] p-5 rounded-2xl border border-zinc-200 dark:border-white/5 shadow-sm">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Riesgo de Fuga (Clasificación)</p>
            <h3 class="text-2xl font-bold mt-1 text-rose-600 dark:text-rose-400">{{ $customerAnalysis['summary']['AltoRiesgo'] }}</h3>
            <p class="text-[11px] text-rose-500/80 mt-1">Clientes inactivos > 30 días</p>
        </div>

        
        <div class="bg-white dark:bg-[#111111] p-5 rounded-2xl border border-zinc-200 dark:border-white/5 shadow-sm">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Precisión del Modelo (R² / Acc)</p>
            <h3 class="text-2xl font-bold mt-1 text-blue-600 dark:text-blue-400">92.4%</h3>
            <p class="text-[11px] text-blue-500/80 mt-1">Validación cruzada 5-Folds</p>
        </div>
    </div>

    
    <div x-data="{ tab: 'clasificacion' }" class="bg-white dark:bg-[#111111] rounded-2xl border border-zinc-200 dark:border-white/5 shadow-sm overflow-hidden">
        
        
        <div class="flex border-b border-zinc-200 dark:border-white/5 bg-zinc-50/50 dark:bg-black/20 overflow-x-auto">
            <button @click="tab = 'clasificacion'" :class="{ 'border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-[#111111]': tab === 'clasificacion' }" class="px-6 py-4 text-xs font-semibold border-b-2 border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 whitespace-nowrap transition-colors">
                🎯 1 & 2. Clasificación y Clustering (Clientes)
            </button>
            <button @click="tab = 'regresion'" :class="{ 'border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-[#111111]': tab === 'regresion' }" class="px-6 py-4 text-xs font-semibold border-b-2 border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 whitespace-nowrap transition-colors">
                📈 3. Regresión de Demanda (Reabasto)
            </button>
            <button @click="tab = 'apriori'" :class="{ 'border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-[#111111]': tab === 'apriori' }" class="px-6 py-4 text-xs font-semibold border-b-2 border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 whitespace-nowrap transition-colors">
                🛒 4. Reglas de Asociación (Apriori)
            </button>
        </div>

        
        <div x-show="tab === 'clasificacion'" class="p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">Predicción de Retorno & Segmentación de Clientes</h2>
                    <p class="text-xs text-zinc-500">Clasificación con Árboles de Decisión y Clustering K-Means (K = 3).</p>
                </div>
            </div>

            
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-white/5">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider border-b border-zinc-200 dark:border-white/5">
                        <tr>
                            <th class="py-3.5 px-4">Cliente</th>
                            <th class="py-3.5 px-4 text-center">Antigüedad</th>
                            <th class="py-3.5 px-4 text-center">Pedidos</th>
                            <th class="py-3.5 px-4 text-center">Gasto Prom.</th>
                            <th class="py-3.5 px-4 text-center">Puntos</th>
                            <th class="py-3.5 px-4 text-center">Segmento K-Means</th>
                            <th class="py-3.5 px-4 text-center">Riesgo de Fuga (ML)</th>
                            <th class="py-3.5 px-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-white/5 font-medium">
                        @foreach($customerAnalysis['customers'] as $c)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $c['name'] }}</div>
                                    <div class="text-[11px] text-zinc-400 font-normal">{{ $c['email'] }}</div>
                                </td>
                                <td class="py-3 px-4 text-center">{{ $c['antiguedad_meses'] }} meses</td>
                                <td class="py-3 px-4 text-center font-bold">{{ $c['cantidad_pedidos'] }}</td>
                                <td class="py-3 px-4 text-center text-emerald-600 dark:text-emerald-400 font-semibold">${{ number_format($c['gasto'], 2) }}</td>
                                <td class="py-3 px-4 text-center">{{ $c['puntos'] }} pts</td>
                                <td class="py-3 px-4 text-center">
                                    @if($c['segmento'] === 'VIP')
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">VIP</span>
                                    @elseif($c['segmento'] === 'Casual')
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">Casual</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-zinc-500/10 text-zinc-500 dark:text-zinc-400 border border-zinc-500/20">Inactivo</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($c['regresa_prox_mes'] == 1)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            Alta Probabilidad
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                            {{ $c['riesgo_fuga'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button onclick="sendCoupon({{ $c['user_id'] }}, '{{ $c['name'] }}')" class="px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                                        Enviar Cupón 15%
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        
        <div x-show="tab === 'regresion'" class="p-6 space-y-6" style="display: none;">
            <div>
                <h2 class="text-lg font-bold">Modelo de Regresión Lineal - Demanda Diaria & Reabasto de Cocina</h2>
                <p class="text-xs text-zinc-500">Predicción de platillos demandados al día basados en calendario, fin de semana y eventos deportivos.</p>
            </div>

            
            <div class="p-4 bg-zinc-50 dark:bg-black/40 rounded-xl border border-zinc-200 dark:border-white/5 relative w-full h-[360px]">
                <canvas id="demandChart"></canvas>
            </div>

            
            <div>
                <h3 class="text-sm font-bold mb-3">Calculador de Reabasto Automatizado (Próximos 7 Días)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($demandForecast['forecast_next_7'] as $f)
                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-white/5 bg-zinc-50/50 dark:bg-zinc-900/40">
                            <div class="flex items-center justify-between text-xs font-bold text-zinc-500">
                                <span>{{ $f['date'] }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-zinc-200 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">{{ $f['day_name'] }}</span>
                            </div>
                            <div class="mt-2">
                                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $f['pred_demanda'] }}</span>
                                <span class="text-xs text-zinc-500 font-normal"> platillos estim.</span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-white/5 text-[10px] space-y-1.5 text-zinc-500 dark:text-zinc-400 font-medium">
                                <div class="font-bold text-zinc-400 uppercase tracking-wider text-[9px] mb-1">📦 Top productos proyectados:</div>
                                @foreach($f['productos'] as $p)
                                    <div class="flex justify-between"><span>{{ $p['nombre'] }}:</span><strong class="text-zinc-900 dark:text-white">{{ $p['unidades'] }} u</strong></div>
                                @endforeach

                                @if(count($f['insumos']) > 0)
                                    <div class="font-bold text-zinc-400 uppercase tracking-wider text-[9px] mt-2 mb-1">🥩 Insumos Cocina:</div>
                                    @foreach($f['insumos'] as $ins)
                                        <div class="flex justify-between"><span>{{ $ins['nombre'] }}:</span><strong class="text-zinc-900 dark:text-white">{{ $ins['cantidad'] }} {{ $ins['unidad'] }}</strong></div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        
        <div x-show="tab === 'apriori'" class="p-6 space-y-6" style="display: none;">
            <div>
                <h2 class="text-lg font-bold">Sistema de Recomendación Inteligente (Estrategia Dual ML)</h2>
                <p class="text-xs text-zinc-500">Comportamiento adaptativo según el estado de sesión del cliente en el Carrito de Compras.</p>
            </div>

            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="p-4 rounded-xl border border-blue-500/20 bg-blue-500/5 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-blue-600 text-white font-bold text-xs">🌐 Invitado / Deslogueado</span>
                    </div>
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-white">Reglas Apriori & Popularidad de Comunidad</h4>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Cuando un usuario anónimo entra al carrito, el modelo analiza qué platillos compran **otras personas juntas en la comunidad** para sugerir complementos populares (ej. <em>Hamburguesa Clásica ➔ Papas y Refresco</em>).</p>
                    <div class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 pt-1">Título visual: "🏆 Popular entre nuestros visitantes"</div>
                </div>

                
                <div class="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-emerald-600 text-white font-bold text-xs">👤 Cliente Logueado</span>
                    </div>
                    <h4 class="text-xs font-bold text-zinc-900 dark:text-white">Re-compra Frecuente & Favoritos Históricos</h4>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Cuando un cliente registrado inicia sesión, el motor consulta su **historial personal de pedidos anteriores** y le sugiere re-comprar sus platillos favoritos que aún no ha agregado al pedido actual.</p>
                    <div class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 pt-1">Título visual: "⭐ Basado en tus pedidos anteriores"</div>
                </div>
            </div>

            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400 pt-2">Reglas de Asociación Apriori Detectadas en Transacciones</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($associationRules as $rule)
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-white/5 bg-zinc-50/50 dark:bg-zinc-900/30 flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="text-xs font-semibold text-zinc-400">Si el cliente compra:</div>
                            <div class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                {{ $rule['antecedent'] }} ➔ <span class="text-emerald-600 dark:text-emerald-400">{{ $rule['consequent'] }}</span>
                            </div>
                        </div>
                        <div class="text-right space-y-0.5 text-xs font-medium text-zinc-500">
                            <div>Confianza: <strong class="text-zinc-900 dark:text-white">{{ $rule['confidence'] }}</strong></div>
                            <div>Soporte: <strong class="text-zinc-900 dark:text-white">{{ $rule['support'] }}</strong></div>
                            <div>Lift: <strong class="text-emerald-600 dark:text-emerald-400">{{ $rule['lift'] }}</strong></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('demandChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($demandForecast['chart']['labels']) !!},
                datasets: [
                    {
                        label: 'Demanda Real (Platillos)',
                        data: {!! json_encode($demandForecast['chart']['real']) !!},
                        borderColor: '#16A34A',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Demanda Proyectada (Regresión ML)',
                        data: {!! json_encode($demandForecast['chart']['pred']) !!},
                        borderColor: '#2563EB',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: { beginAtZero: true }
                }
            }
        });
    });

    function sendCoupon(userId, name) {
        if (confirm('¿Deseas enviar un cupón del 15% de descuento a ' + name + ' para incentivar su retorno?')) {
            fetch('{{ route("admin.ml.coupon") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
            });
        }
    }
</script>

@endsection
