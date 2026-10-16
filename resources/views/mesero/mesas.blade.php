@extends('layouts.admin')

@section('content')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
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
    
    <div class="mb-6 flex justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 dark:text-white uppercase tracking-wider font-['Oswald']">Mapa de Mesas</h1>
            <p class="text-zinc-500 text-sm">Gestiona los pedidos de los comensales</p>
        </div>
        <button type="button" @click="agregarMesa()" :disabled="agregandoMesa"
                class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 bg-zinc-100 dark:bg-white/10 hover:bg-zinc-200 dark:hover:bg-white/20 border border-zinc-200 dark:border-white/10 rounded-xl text-sm font-bold text-zinc-700 dark:text-zinc-200 transition disabled:opacity-50">
            <span x-text="agregandoMesa ? 'Agregando…' : '➕ Agregar Mesa'"></span>
        </button>
    </div>

    {{-- Alertas de Éxito --}}
    @if(session('success'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
            <p class="font-bold">¡Éxito!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    {{-- Cuadrícula de Mesas (Blade + Alpine) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($mesas as $index => $mesa)
            <div class="rounded-xl border shadow-sm transition-all duration-300 flex flex-col justify-between p-6"
                 :class="mesas[{{ $index }}].ocupada ? 'bg-orange-50/50 border-orange-200 dark:bg-orange-950/20 dark:border-orange-900' : 'bg-white border-zinc-200 dark:bg-[#111111] dark:border-white/10'">
                
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-4xl font-black font-['Oswald']"
                            :class="mesas[{{ $index }}].ocupada ? 'text-orange-600 dark:text-orange-500' : 'text-zinc-800 dark:text-white'">
                            #{{ $mesa['id'] }}
                        </h2>
                        <p class="text-xs font-bold uppercase tracking-widest mt-1"
                           :class="mesas[{{ $index }}].ocupada ? 'text-orange-500' : 'text-green-500'"
                           x-text="mesas[{{ $index }}].ocupada ? 'Ocupada' : 'Disponible'">
                            {{ $mesa['ocupada'] ? 'Ocupada' : 'Disponible' }}
                        </p>
                    </div>
                    
                    <div class="text-right" x-show="mesas[{{ $index }}].ocupada" style="display: {{ $mesa['ocupada'] ? 'block' : 'none' }};">
                        <p class="text-xs text-zinc-500 uppercase font-bold tracking-wider mb-1">Total</p>
                        <p class="text-2xl font-black text-zinc-900 dark:text-white"
                           x-text="'$' + Number(mesas[{{ $index }}].total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">
                            ${{ number_format($mesa['total'], 2) }}
                        </p>
                    </div>
                </div>

                <div>
                    <div class="flex flex-col gap-2" x-show="mesas[{{ $index }}].ocupada" style="display: {{ $mesa['ocupada'] ? 'flex' : 'none' }};">
                        <a :href="'{{ url('mesero/mesas') }}/' + mesas[{{ $index }}].id + '/pedido'"
                           class="block text-center w-full bg-blue-600 hover:bg-blue-700 text-white font-bold uppercase tracking-wider py-2.5 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5 text-sm">
                            ➕ Agregar Más
                        </a>
                        
                        <div x-show="mesas[{{ $index }}].tiene_pendientes" style="display: {{ ($mesa['ocupada'] && $mesa['tiene_pendientes']) ? 'block' : 'none' }};">
                            <button type="button" disabled
                                    class="w-full bg-zinc-700 text-zinc-400 font-bold uppercase tracking-wider py-2.5 px-4 rounded-lg cursor-not-allowed opacity-60 text-sm flex items-center justify-center gap-2 border border-white/5">
                                🍳 Cocinando...
                            </button>
                            <button type="button" @click="abrirCobro(mesas[{{ $index }}].id, mesas[{{ $index }}].total, true)"
                                    class="w-full mt-1.5 text-[11px] text-zinc-400 hover:text-red-500 font-bold uppercase tracking-wider py-1 flex items-center justify-center gap-1 transition">
                                🔓 ¿Falla en cocina? Forzar cobro
                            </button>
                        </div>

                        <button type="button" @click="abrirCobro(mesas[{{ $index }}].id, mesas[{{ $index }}].total)" x-show="!mesas[{{ $index }}].tiene_pendientes" style="display: {{ ($mesa['ocupada'] && !$mesa['tiene_pendientes']) ? 'flex' : 'none' }};"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold uppercase tracking-wider py-2.5 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2">
                            💰 Cobrar Mesa (Listo)
                        </button>
                    </div>
                    
                    <div x-show="!mesas[{{ $index }}].ocupada" style="display: {{ !$mesa['ocupada'] ? 'block' : 'none' }};">
                        <a :href="'{{ url('mesero/mesas') }}/' + mesas[{{ $index }}].id + '/pedido'"
                           class="block text-center w-full bg-green-600 hover:bg-green-700 text-white font-bold uppercase tracking-wider py-3 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
                            📝 Tomar Pedido
                        </a>
                    </div>
                </div>
            </div>
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
                <h3 class="text-lg font-black uppercase tracking-wider font-['Oswald']"
                    :class="modoForzado ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white'">
                    <span x-show="modoForzado">🔓 Forzar Cobro — Mesa </span>
                    <span x-show="!modoForzado">Cobrar Mesa </span>
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
                        🔒 Esta mesa aún tiene platillos en preparación. Solo usa esto si la cocina no responde o hubo una falla — quedará registrado con tu usuario para revisión del administrador.
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
                        <label class="block text-xs font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest font-['Oswald']">
                            Distribución de Pagos
                        </label>
                        <button type="button" @click="agregarPago()"
                                class="text-xs font-bold text-orange-500 hover:text-orange-600 flex items-center gap-1 transition">
                            ➕ Agregar Pago
                        </button>
                    </div>

                    {{-- Lista de Pagos Dinámica --}}
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                        <template x-for="(pago, index) in pagos" :key="index">
                            <div class="flex items-center gap-2 bg-zinc-50 dark:bg-black/25 border border-zinc-200 dark:border-white/5 rounded-xl p-2.5 transition">
                                {{-- Selector de Método --}}
                                <select x-model="pago.metodo"
                                        class="bg-white dark:bg-[#1A1A1A] border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white rounded-lg px-2.5 py-2 outline-none focus:ring-2 focus:ring-orange-500 transition text-sm font-bold flex-shrink-0">
                                    <option value="efectivo">💵 Efectivo</option>
                                    <option value="tarjeta">💳 Tarjeta</option>
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
                            <span>⚠️ Faltan por cubrir:</span>
                            <span x-text="'$' + (totalActual - totalIngresado).toFixed(2)"></span>
                        </div>

                        {{-- Cambio --}}
                        <div x-show="isValido && totalIngresado > totalActual" class="bg-green-50 dark:bg-green-950/20 text-green-800 dark:text-green-400 text-sm font-extrabold px-3 py-2.5 rounded-lg border border-green-200 dark:border-green-900 flex items-center justify-between shadow-sm border-t pt-2 mt-2">
                            <span>💵 Cambio:</span>
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
                        <span>⚠️</span>
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
                                : (confirmandoPago ? 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-500/30 animate-pulse' : (modoForzado ? 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-500/30' : 'bg-green-600 hover:bg-green-700 text-white shadow-lg shadow-green-500/30'))"
                            class="px-5 py-2.5 rounded-lg text-sm font-bold transition flex items-center gap-2">
                        <svg x-show="!confirmandoPago && !modoForzado" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-show="confirmandoPago || modoForzado">⚠️</span>
                        <span x-text="confirmandoPago ? '¿Seguro? Toca de nuevo' : (modoForzado ? 'Forzar Cobro' : 'Confirmar Pago')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
