@extends('layouts.admin')

@section('content')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const MESAS_FIJAS = {{ $minMesas }};

    function mapaMesas(initialMesas) {
        return {
            showModal: false,
            mesaActual: 0,
            totalActual: 0,
            pagos: [],
            mesas: initialMesas,
            confirmandoPago: false,
            confirmTimeout: null,
            modoForzado: false,
            forcedReason: '',
            confirmPassword: '',

            abrirCobro(mesa, total, forzado = false) {
                this.mesaActual = mesa;
                this.totalActual = total;
                this.pagos = [
                    { metodo: 'efectivo', monto: Number(total).toFixed(2) }
                ];
                this.confirmandoPago = false;
                this.modoForzado = forzado;
                this.forcedReason = '';
                this.confirmPassword = '';
                clearTimeout(this.confirmTimeout);
                this.showModal = true;
            },
            agregarPago() {
                let restante = Math.max(0, this.totalActual - this.totalIngresado);
                this.pagos.push({
                    metodo: 'efectivo',
                    monto: restante > 0 ? Number(restante).toFixed(2) : ''
                });
                this.confirmandoPago = false;
                clearTimeout(this.confirmTimeout);
            },
            eliminarPago(index) {
                this.pagos.splice(index, 1);
                this.confirmandoPago = false;
                clearTimeout(this.confirmTimeout);
            },
            intentarConfirmarPago(event) {
                if (!this.isValido) return;
                if (!this.confirmandoPago) {
                    this.confirmandoPago = true;
                    clearTimeout(this.confirmTimeout);
                    this.confirmTimeout = setTimeout(() => { this.confirmandoPago = false; }, 6000);
                    return;
                }
                clearTimeout(this.confirmTimeout);
                event.target.submit();
            },
            get totalIngresado() {
                return this.pagos.reduce((sum, p) => sum + (parseFloat(p.monto) || 0), 0);
            },
            get isValido() {
                const pagoCubierto = this.totalIngresado >= this.totalActual - 0.009;
                if (!this.modoForzado) return pagoCubierto;
                return pagoCubierto && this.forcedReason.trim().length > 0 && this.confirmPassword.length > 0;
            },
            get formattedPaymentMethod() {
                if (this.pagos.length === 1) {
                    let p = this.pagos[0];
                    if (p.metodo === 'efectivo') return 'Efectivo';
                    if (p.metodo === 'tarjeta') return 'Tarjeta';
                    return p.metodo;
                }
                let parts = [];
                let totals = { efectivo: 0, tarjeta: 0 };
                this.pagos.forEach(p => {
                    let m = parseFloat(p.monto) || 0;
                    if (totals[p.metodo] !== undefined) {
                        totals[p.metodo] += m;
                    }
                });
                if (totals.efectivo > 0) parts.push('Efectivo: $' + totals.efectivo.toFixed(2));
                if (totals.tarjeta > 0) parts.push('Tarjeta: $' + totals.tarjeta.toFixed(2));
                return 'Mixto (' + parts.join(', ') + ')';
            },
            async actualizarMesas() {
                try {
                    const response = await fetch('{{ route('mesero.api_mesas') }}');
                    if (response.ok) {
                        const data = await response.json();
                        this.mesas = data;
                    }
                } catch (e) {
                    console.log('Error actualizando mesas:', e);
                }
            },
            async entregar(index, item) {
                const mesa = this.mesas[index];
                mesa.por_entregar = mesa.por_entregar.filter(i => i.id !== item.id);
                try {
                    const response = await fetch('{{ url('mesero/items') }}/' + item.id + '/entregado', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error();
                } catch (e) {
                    alert('No se pudo marcar como entregado. Revisa la conexión.');
                    this.actualizarMesas();
                }
            },
            async entregarTodo(index) {
                const mesa = this.mesas[index];
                const anteriores = mesa.por_entregar;
                mesa.por_entregar = [];
                try {
                    const response = await fetch('{{ url('mesero/mesas') }}/' + mesa.id + '/entregar-todo', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error();
                } catch (e) {
                    mesa.por_entregar = anteriores;
                    alert('No se pudo marcar como entregado. Revisa la conexión.');
                }
            },
            agregandoMesa: false,
            async agregarMesa() {
                if (this.agregandoMesa) return;
                this.agregandoMesa = true;
                try {
                    const response = await fetch('{{ route('mesero.mesas.agregar') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    if (response.ok) {
                        // Recargamos la página completa: las tarjetas de mesa
                        // se generan en el servidor, no alcanza con refrescar el arreglo.
                        window.location.reload();
                    } else {
                        alert('No se pudo agregar la mesa.');
                        this.agregandoMesa = false;
                    }
                } catch (e) {
                    alert('Error de red al agregar la mesa.');
                    this.agregandoMesa = false;
                }
            },
            quitandoMesa: false,
            // Solo la última mesa agregada, libre, y nunca de las mesas fijas
            puedeQuitar(index) {
                return index === this.mesas.length - 1 && this.mesas.length > MESAS_FIJAS && !this.mesas[index].ocupada;
            },
            async quitarMesa(index) {
                const mesa = this.mesas[index];
                if (this.quitandoMesa || !confirm('¿Quitar la mesa ' + mesa.id + '?')) return;
                this.quitandoMesa = true;
                try {
                    const response = await fetch('{{ url('mesero/mesas') }}/' + mesa.id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    });
                    if (response.ok) {
                        window.location.reload();
                        return;
                    }
                    const data = await response.json().catch(() => ({}));
                    alert(data.error || 'No se pudo quitar la mesa.');
                } catch (e) {
                    alert('Error de red al quitar la mesa.');
                }
                this.quitandoMesa = false;
            },
            init() {
                try {
                    const pusher = new Pusher('491d18da8b8b427e4969', { cluster: 'us2' });
                    const channel = pusher.subscribe('dashboard-channel');
                    channel.bind('dashboard.updated', () => {
                        this.actualizarMesas();
                    });
                } catch (e) {
                    console.log('Pusher initialization error:', e);
                }
                setInterval(() => {
                    this.actualizarMesas();
                }, 6000);
            }
        }
    }
</script>

<div x-data="mapaMesas({{ json_encode($mesas) }})">
    
    <div class="mb-6 flex flex-wrap justify-between items-end gap-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500">Servicio en salón</p>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Mesas</h1>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <a href="{{ route('mesero.domicilios') }}" x-show="$store.domicilios && $store.domicilios.listos > 0" x-cloak
               class="flex items-center gap-2.5 h-10 pl-3 pr-4 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/40 text-sm font-semibold text-amber-900 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition"
               :title="'Hay ' + $store.domicilios.listos + ' pedido(s) a domicilio listos para entregar al mandadito'">
                <span class="relative flex w-2.5 h-2.5" aria-hidden="true">
                    <span class="absolute inset-0 rounded-full bg-amber-500 animate-ping opacity-75"></span>
                    <span class="relative w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                </span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h1m6 0h1v-5l-3-4h-4v9m-6 0a2 2 0 11-4 0m10 0a2 2 0 11-4 0"/></svg>
                <span x-text="$store.domicilios.listos === 1 ? '1 domicilio listo para enviar' : $store.domicilios.listos + ' domicilios listos para enviar'"></span>
            </a>
            <dl class="hidden sm:flex gap-5 text-right">
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-zinc-500">Ocupadas</dt>
                    <dd class="text-lg font-bold text-zinc-900 dark:text-white tabular-nums" x-text="mesas.filter(m => m.ocupada).length + ' / ' + mesas.length"></dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-zinc-500">Por entregar</dt>
                    <dd class="text-lg font-bold text-emerald-600 dark:text-emerald-400 tabular-nums" x-text="mesas.reduce((s, m) => s + (m.por_entregar || []).reduce((t, i) => t + i.cantidad, 0), 0)"></dd>
                </div>
            </dl>
            <button type="button" @click="agregarMesa()" :disabled="agregandoMesa"
                    class="flex items-center gap-2 h-10 px-4 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-semibold text-zinc-700 dark:text-zinc-200 transition disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                <span x-text="agregandoMesa ? 'Agregando…' : 'Agregar mesa'">Agregar mesa</span>
            </button>
        </div>
    </div>

    {{-- Aviso breve (se oculta solo) --}}
    @if(session('success'))
        <div x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 3500)" x-show="visible" x-transition.opacity
             class="fixed top-5 right-5 z-[90] flex items-center gap-2.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-sm font-semibold px-4 py-3 rounded-lg shadow-xl" role="status">
            <svg class="w-4 h-4 text-emerald-400 dark:text-emerald-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 border-l-4 border-red-500 bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-300 px-4 py-3 rounded text-sm font-semibold" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Cuadrícula de Mesas (Blade + Alpine) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
        @foreach($mesas as $index => $mesa)
            <article class="rounded-xl border bg-white dark:bg-zinc-900/60 flex flex-col transition-colors"
                     :class="mesas[{{ $index }}].ocupada ? 'border-zinc-300 dark:border-zinc-700' : 'border-zinc-200 dark:border-zinc-800'">

                <header class="flex items-start justify-between gap-3 p-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Mesa</p>
                        <h2 class="text-3xl font-bold leading-none tabular-nums text-zinc-900 dark:text-white">{{ $mesa['id'] }}</h2>
                        @if($loop->last && count($mesas) > $minMesas)
                            <button type="button" x-show="puedeQuitar({{ $index }})" @click="quitarMesa({{ $index }})" :disabled="quitandoMesa"
                                    class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 hover:text-red-600 dark:hover:text-red-400 transition disabled:opacity-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0l-1 12a2 2 0 01-2 2H9a2 2 0 01-2-2L6 7"/></svg>
                                Quitar mesa
                            </button>
                        @endif
                    </div>
                    <div class="text-right">
                        {{-- Libre / En preparación / Lista para cobrar --}}
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider px-2 py-1 rounded-md"
                              :class="!mesas[{{ $index }}].ocupada
                                  ? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'
                                  : (mesas[{{ $index }}].tiene_pendientes ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300')">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="!mesas[{{ $index }}].ocupada ? 'bg-zinc-400' : (mesas[{{ $index }}].tiene_pendientes ? 'bg-amber-500' : 'bg-emerald-500')"></span>
                            <span x-text="!mesas[{{ $index }}].ocupada ? 'Libre' : (mesas[{{ $index }}].tiene_pendientes ? 'En preparación' : 'Lista para cobrar')">{{ $mesa['ocupada'] ? 'Ocupada' : 'Libre' }}</span>
                        </span>
                        <p class="mt-2 text-xl font-bold tabular-nums text-zinc-900 dark:text-white" x-show="mesas[{{ $index }}].ocupada" style="display: {{ $mesa['ocupada'] ? 'block' : 'none' }};"
                           x-text="'$' + Number(mesas[{{ $index }}].total).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">
                            ${{ number_format($mesa['total'], 2) }}
                        </p>
                    </div>
                </header>

                <div class="p-4 flex-1 space-y-3">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400" x-show="!mesas[{{ $index }}].ocupada" style="display: {{ !$mesa['ocupada'] ? 'block' : 'none' }};">Sin pedidos activos.</p>

                    <p x-show="mesas[{{ $index }}].en_preparacion > 0" style="display: none;"
                       class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                        <span><strong class="tabular-nums" x-text="mesas[{{ $index }}].en_preparacion"></strong> en preparación</span>
                    </p>

                    {{-- Lo que ya está listo (o no se prepara) y falta llevar a la mesa --}}
                    <div x-show="mesas[{{ $index }}].por_entregar && mesas[{{ $index }}].por_entregar.length > 0" style="display: none;">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Por entregar</p>
                            <button type="button" @click="entregarTodo({{ $index }})"
                                    class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                                Entregar todo
                            </button>
                        </div>
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg">
                            <template x-for="item in mesas[{{ $index }}].por_entregar" :key="item.id">
                                <li>
                                    <button type="button" @click="entregar({{ $index }}, item)"
                                            :aria-label="'Marcar como entregado: ' + item.nombre"
                                            class="group w-full flex items-start gap-3 text-left px-3 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition">
                                        <span class="mt-0.5 w-5 h-5 flex-shrink-0 rounded border-2 border-zinc-300 dark:border-zinc-600 group-hover:border-emerald-500 flex items-center justify-center">
                                            <svg class="w-3 h-3 text-emerald-500 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <span class="flex-grow min-w-0">
                                            <span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                                <span class="tabular-nums" x-text="item.cantidad + '×'"></span> <span x-text="item.nombre"></span>
                                            </span>
                                            <span class="block text-xs text-zinc-500" x-show="item.opciones" x-text="item.opciones"></span>
                                        </span>
                                        <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400"
                                              x-text="item.estacion === 'barra' ? 'Barra' : (item.estacion === 'ninguna' ? 'Directo' : 'Cocina')"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <footer class="p-4 pt-0">
                    <div class="grid grid-cols-2 gap-2" x-show="mesas[{{ $index }}].ocupada" style="display: {{ $mesa['ocupada'] ? 'grid' : 'none' }};">
                        <a :href="'{{ url('mesero/mesas') }}/' + mesas[{{ $index }}].id + '/pedido'"
                           class="h-10 flex items-center justify-center gap-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 text-sm font-semibold text-zinc-800 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                            Agregar
                        </a>
                        <button type="button" @click="abrirCobro(mesas[{{ $index }}].id, mesas[{{ $index }}].total)" :disabled="mesas[{{ $index }}].tiene_pendientes"
                                :title="mesas[{{ $index }}].tiene_pendientes ? 'Hay platillos en preparación' : 'Cobrar la mesa'"
                                class="h-10 rounded-lg text-sm font-semibold transition bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 disabled:bg-zinc-100 disabled:text-zinc-400 dark:disabled:bg-zinc-800 dark:disabled:text-zinc-500 disabled:cursor-not-allowed">
                            Cobrar
                        </button>
                        <button type="button" x-show="mesas[{{ $index }}].tiene_pendientes" @click="abrirCobro(mesas[{{ $index }}].id, mesas[{{ $index }}].total, true)"
                                class="col-span-2 text-xs font-medium text-zinc-500 hover:text-red-600 dark:hover:text-red-400 py-1 transition">
                            Forzar cobro (falla en cocina)
                        </button>
                    </div>

                    <a x-show="!mesas[{{ $index }}].ocupada" style="display: {{ !$mesa['ocupada'] ? 'flex' : 'none' }};"
                       :href="'{{ url('mesero/mesas') }}/' + mesas[{{ $index }}].id + '/pedido'"
                       class="h-10 flex items-center justify-center rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition">
                        Tomar pedido
                    </a>
                </footer>
            </article>
        @endforeach
    </div>


    {{-- MODAL DE COBRO (AlpineJS) --}}
    <div x-show="showModal" style="display: none;"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        
        <div @click.away="showModal = false; confirmandoPago = false; clearTimeout(confirmTimeout)"
             class="bg-white dark:bg-[#111111] border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
             :class="modoForzado ? 'border-red-500/40' : 'border-zinc-200 dark:border-white/10'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="px-6 py-4 border-b flex justify-between items-center"
                 :class="modoForzado ? 'border-red-500/20 bg-red-50 dark:bg-red-950/20' : 'border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-black'">
                <h3 class="text-lg font-bold "
                    :class="modoForzado ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white'">
                    <span x-show="modoForzado">Forzar cobro · Mesa </span>
                    <span x-show="!modoForzado">Cobrar · Mesa </span>
                    <span x-text="mesaActual" class="text-orange-500"></span>
                </h3>
                <button @click="showModal = false; confirmandoPago = false; clearTimeout(confirmTimeout)" class="text-zinc-400 hover:text-red-500 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Formulario dinámico que apunta a la ruta de la mesa actual --}}
            <form :action="'{{ url('mesero/mesas') }}/' + mesaActual + '/cobrar'" method="POST" @submit.prevent="intentarConfirmarPago($event)">
                @csrf
                @method('PUT')
                
                <input type="hidden" name="payment_method" :value="formattedPaymentMethod">
                <input type="hidden" name="forced" :value="modoForzado ? 1 : 0">
                <input type="hidden" name="forced_reason" :value="forcedReason">
                <input type="hidden" name="confirm_password" :value="confirmPassword">

                {{-- AVISO Y CAMPOS DE COBRO FORZADO --}}
                <div x-show="modoForzado" class="mx-6 mt-5 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900 rounded-xl space-y-3">
                    <p class="text-xs text-red-700 dark:text-red-400 font-semibold leading-relaxed">
                        Esta mesa aún tiene platillos en preparación. Solo usa esto si la cocina no responde o hubo una falla — quedará registrado con tu usuario para revisión del administrador.
                    </p>
                    <div>
                        <label class="block text-[11px] font-black text-red-700 dark:text-red-400 uppercase tracking-widest mb-1">Motivo (obligatorio)</label>
                        <textarea x-model="forcedReason" rows="2" :required="modoForzado"
                                  placeholder="Ej: la pantalla de cocina no responde, hay que cobrar de emergencia"
                                  class="w-full px-3 py-2 bg-white dark:bg-[#1A1A1A] border border-red-300 dark:border-red-800 text-zinc-900 dark:text-white rounded-lg outline-none focus:ring-2 focus:ring-red-500 transition text-sm resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black text-red-700 dark:text-red-400 uppercase tracking-widest mb-1">Confirma tu contraseña de cuenta</label>
                        <input type="password" x-model="confirmPassword" :required="modoForzado" autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full px-3 py-2 bg-white dark:bg-[#1A1A1A] border border-red-300 dark:border-red-800 text-zinc-900 dark:text-white rounded-lg outline-none focus:ring-2 focus:ring-red-500 transition text-sm">
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest ">
                            Forma de pago
                        </label>
                        <button type="button" @click="agregarPago()"
                                class="text-xs font-bold text-orange-500 hover:text-orange-600 flex items-center gap-1 transition">
                            + Agregar pago
                        </button>
                    </div>

                    {{-- Lista de Pagos Dinámica --}}
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                        <template x-for="(pago, index) in pagos" :key="index">
                            <div class="flex items-center gap-2 bg-zinc-50 dark:bg-black/25 border border-zinc-200 dark:border-white/5 rounded-xl p-2.5 transition">
                                {{-- Selector de Método --}}
                                <select x-model="pago.metodo"
                                        class="bg-white dark:bg-[#1A1A1A] border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white rounded-lg px-2.5 py-2 outline-none focus:ring-2 focus:ring-orange-500 transition text-sm font-bold flex-shrink-0">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                </select>

                                {{-- Entrada de Monto --}}
                                <div class="relative flex-1">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 font-bold text-sm">$</span>
                                    <input type="number" step="0.01" x-model="pago.monto" placeholder="0.00" required
                                           class="w-full pl-6 pr-3 py-2 bg-white dark:bg-[#1A1A1A] border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white rounded-lg outline-none focus:ring-2 focus:ring-orange-500 transition font-bold text-sm">
                                </div>

                                {{-- Botón Eliminar --}}
                                <button type="button" @click="eliminarPago(index)" x-show="pagos.length > 1"
                                        class="text-zinc-400 hover:text-red-500 p-2 rounded-lg hover:bg-zinc-200/50 dark:hover:bg-zinc-800 transition flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- Resumen de Cobro --}}
                    <div class="bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/5 rounded-xl p-4 space-y-2.5 mt-4">
                        <div class="flex justify-between text-xs font-bold text-zinc-500">
                            <span>Total a Pagar:</span>
                            <span class="text-zinc-800 dark:text-zinc-300" x-text="'$' + Number(totalActual).toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-xs font-bold text-zinc-500">
                            <span>Total Pagado:</span>
                            <span class="text-zinc-900 dark:text-white font-extrabold" x-text="'$' + totalIngresado.toFixed(2)"></span>
                        </div>

                        {{-- Faltante --}}
                        <div x-show="!isValido" class="bg-yellow-50 dark:bg-yellow-950/20 text-yellow-800 dark:text-yellow-400 text-xs font-bold px-3 py-2.5 rounded-lg border border-yellow-200 dark:border-yellow-900 flex items-center justify-between shadow-sm">
                            <span>Faltan por cubrir</span>
                            <span x-text="'$' + (totalActual - totalIngresado).toFixed(2)"></span>
                        </div>

                        {{-- Cambio --}}
                        <div x-show="isValido && totalIngresado > totalActual" class="bg-green-50 dark:bg-green-950/20 text-green-800 dark:text-green-400 text-sm font-extrabold px-3 py-2.5 rounded-lg border border-green-200 dark:border-green-900 flex items-center justify-between shadow-sm border-t pt-2 mt-2">
                            <span>Cambio</span>
                            <span x-text="'$' + (totalIngresado - totalActual).toFixed(2)"></span>
                        </div>

                        {{-- Pago Exacto --}}
                        <div x-show="isValido && Math.abs(totalIngresado - totalActual) < 0.01" class="bg-green-50 dark:bg-green-950/20 text-green-800 dark:text-green-400 text-xs font-bold px-3 py-2.5 rounded-lg border border-green-200 dark:border-green-900 flex items-center justify-center gap-1.5 shadow-sm">
                            <span>Monto cubierto exactamente</span>
                        </div>
                    </div>
                </div>

                <div class="px-6 pb-1" x-show="isValido">
                    <p class="text-[11px] text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-lg px-3 py-2 font-semibold flex items-start gap-1.5">
                        <span>Confirma solo si el cliente <strong>ya pagó</strong> el total. Esta acción no se puede deshacer.</span>
                    </p>
                </div>

                <div class="px-6 py-4 bg-zinc-50 dark:bg-black border-t border-zinc-200 dark:border-white/10 flex justify-end gap-3">
                    <button type="button" @click="showModal = false; confirmandoPago = false; clearTimeout(confirmTimeout)" class="px-5 py-2.5 rounded-lg text-sm font-bold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="!isValido"
                            :class="!isValido
                                ? 'bg-zinc-700 text-zinc-400 cursor-not-allowed opacity-50'
                                : (confirmandoPago ? 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-500/30' : (modoForzado ? 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-500/30' : 'bg-green-600 hover:bg-green-700 text-white shadow-lg shadow-green-500/30'))"
                            class="px-5 py-2.5 rounded-lg text-sm font-bold transition flex items-center gap-2">
                        <svg x-show="!confirmandoPago && !modoForzado" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        
                        <span x-text="confirmandoPago ? 'Toca de nuevo para confirmar' : (modoForzado ? 'Forzar cobro' : 'Confirmar pago')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
