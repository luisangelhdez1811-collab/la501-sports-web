@extends('layouts.admin')

@section('content')
<div class="p-4 md:p-8" x-data="pantallaDomicilios(@js($domicilios))" x-init="init()">

    <div class="mb-6 flex justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-zinc-900 dark:text-white uppercase tracking-wider font-['Oswald']">Domicilios</h1>
            <p class="text-zinc-500 text-sm">Entrega los pedidos listos al mandadito e imprime su ticket. Un solo paso: al entregarlo, el cliente recibe el aviso de que va en camino</p>
        </div>
        <span class="flex-shrink-0 text-xs font-bold uppercase tracking-widest px-3 py-1.5 rounded-full border border-zinc-200 dark:border-white/10"
              :class="statusText === 'En vivo' ? 'text-green-500' : (statusText === 'Error de red' ? 'text-red-500' : 'text-zinc-500')"
              x-text="statusText"></span>
    </div>

    <template x-if="mensaje">
        <div class="mb-4 border-l-4 p-4 rounded shadow-sm"
             :class="mensajeError ? 'bg-red-100 border-red-500 text-red-700' : 'bg-green-100 border-green-500 text-green-700'">
            <p class="font-bold" x-text="mensaje"></p>
        </div>
    </template>

    {{-- ── POR ENTREGAR AL MANDADITO ── --}}
    <h2 class="text-sm font-black uppercase tracking-widest text-orange-500 mb-3">
        Por entregar al mandadito (<span x-text="porEntregar.length"></span>)
    </h2>

    <p x-show="porEntregar.length === 0" class="text-zinc-500 text-sm mb-8">
        No hay pedidos a domicilio listos. Cuando cocina termine uno, aparecerá aquí.
    </p>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 mb-8">
        <template x-for="o in porEntregar" :key="o.id">
            <div class="bg-white dark:bg-[#111111] border rounded-2xl p-5 transition-all duration-500"
                 :class="nuevos.includes(o.id) ? 'border-orange-500 ring-2 ring-orange-400' : 'border-orange-200 dark:border-orange-900'">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xl font-black text-zinc-900 dark:text-white" x-text="'#' + o.folio"></span>
                    <span class="text-xs font-bold uppercase text-orange-500">Listo</span>
                </div>
                <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100" x-text="o.customer_name"></p>
                <p class="text-sm text-zinc-500" x-text="o.customer_phone"></p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-2" x-text="o.customer_address"></p>
                <p x-show="o.has_location" class="text-xs text-green-600 dark:text-green-400 mt-1">Incluye ubicación GPS</p>
                <p class="text-sm text-zinc-500 mt-2" x-text="o.items_count + ' artículo(s) · $' + o.total.toFixed(2)"></p>
                <button type="button" @click="entregarAlMandadito(o)" :disabled="ocupado.includes(o.id)"
                        class="mt-4 w-full px-4 py-3 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white font-black rounded-xl uppercase tracking-wide text-sm">
                    <span x-text="ocupado.includes(o.id) ? 'Procesando…' : 'Entregado al mandadito'"></span>
                </button>
            </div>
        </template>
    </div>

    {{-- ── EN CAMINO ── --}}
    <h2 class="text-sm font-black uppercase tracking-widest text-zinc-500 mb-3">
        En camino · últimas 3 horas (<span x-text="enCamino.length"></span>)
    </h2>

    <p x-show="enCamino.length === 0" class="text-zinc-500 text-sm">No hay pedidos entregados al mandadito en las últimas 3 horas.</p>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="o in enCamino" :key="o.id">
            <div class="bg-white dark:bg-[#111111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xl font-black text-zinc-900 dark:text-white" x-text="'#' + o.folio"></span>
                    <span class="text-xs text-zinc-500" x-text="o.sent_at"></span>
                </div>
                <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100" x-text="o.customer_name"></p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300" x-text="o.customer_address"></p>
                <button type="button" @click="imprimirTicket(o.id)"
                        class="mt-4 w-full px-3 py-2.5 bg-zinc-100 dark:bg-white/10 hover:bg-zinc-200 dark:hover:bg-white/20 rounded-xl text-sm font-bold text-zinc-700 dark:text-zinc-200">
                    Imprimir ticket
                </button>
            </div>
        </template>
    </div>
</div>

<script>
    function pantallaDomicilios(inicial) {
        return {
            porEntregar: inicial.por_entregar,
            enCamino: inicial.en_camino,
            statusText: 'En vivo',
            mensaje: '',
            mensajeError: false,
            nuevos: [],
            ocupado: [],
            conocidos: inicial.por_entregar.map(o => o.id),
            mensajeTimeout: null,

            init() {
                setInterval(() => this.cargar(), 8000);
            },

            urlTicket(id) {
                return '{{ url('mesero/domicilios') }}/' + id + '/ticket';
            },

            // Imprime el ticket en un marco oculto de esta misma pantalla (sin abrir ventana nueva)
            imprimirTicket(id) {
                let marco = document.getElementById('marco-ticket');
                if (marco) marco.remove();
                marco = document.createElement('iframe');
                marco.id = 'marco-ticket';
                marco.setAttribute('aria-hidden', 'true');
                marco.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
                marco.onload = () => {
                    try {
                        marco.contentWindow.focus();
                        marco.contentWindow.print();
                    } catch (e) {
                        window.open(this.urlTicket(id), '_blank');
                    }
                };
                marco.src = this.urlTicket(id) + '?embed=1';
                document.body.appendChild(marco);
            },

            async cargar() {
                // No refrescar mientras hay una acción en curso, para evitar parpadeos
                if (this.ocupado.length) return;
                this.statusText = 'Actualizando...';
                try {
                    const res = await fetch('{{ route('mesero.api_domicilios') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) {
                        this.statusText = 'Error de red';
                        return;
                    }
                    const data = await res.json();
                    const aparecidos = data.por_entregar
                        .map(o => o.id)
                        .filter(id => !this.conocidos.includes(id));

                    this.porEntregar = data.por_entregar;
                    this.enCamino = data.en_camino;
                    this.conocidos = data.por_entregar.map(o => o.id);

                    if (aparecidos.length) {
                        this.nuevos = aparecidos;
                        setTimeout(() => { this.nuevos = []; }, 6000);
                    }
                    this.statusText = 'En vivo';
                } catch (e) {
                    this.statusText = 'Error de red';
                }
            },

            aviso(texto, error = false) {
                this.mensaje = texto;
                this.mensajeError = error;
                clearTimeout(this.mensajeTimeout);
                this.mensajeTimeout = setTimeout(() => { this.mensaje = ''; }, 5000);
            },

            async enviar(url) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json().catch(() => ({}));
                return { ok: res.ok, data };
            },

            async entregarAlMandadito(o) {
                this.ocupado.push(o.id);
                try {
                    const { ok, data } = await this.enviar('{{ url('mesero/domicilios') }}/' + o.id + '/mandadito');
                    if (ok && data.success) {
                        this.porEntregar = this.porEntregar.filter(x => x.id !== o.id);
                        this.conocidos = this.conocidos.filter(id => id !== o.id);
                        this.aviso(data.message + ' Ya puedes imprimir su ticket.');
                        this.imprimirTicket(o.id);
                    } else {
                        this.aviso('No se pudo entregar el pedido al mandadito.', true);
                    }
                } catch (e) {
                    this.aviso('Error de red. Intenta de nuevo.', true);
                } finally {
                    this.ocupado = this.ocupado.filter(id => id !== o.id);
                    this.cargar();
                }
            }
        }
    }
</script>
@endsection
