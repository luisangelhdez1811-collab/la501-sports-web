<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantalla de Cocina - La 501 Sports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#0c0a09] text-zinc-100 min-h-screen flex flex-col overflow-x-hidden"
      x-data="pantallaCocina()" x-init="init()">

    {{-- HEADER --}}
    <header class="sticky top-0 z-50 bg-[#161310]/95 backdrop-blur-md border-b border-white/5 px-6 py-4 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-3xl">🍳</span>
            <div>
                <h1 class="text-xl font-extrabold tracking-wider text-white uppercase font-['Bebas_Neue']">Cocina Digital</h1>
                <p class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">La 501 Sports</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            {{-- Indicador de Carga --}}
            <div class="flex items-center gap-2 bg-[#1c1815] border border-white/5 px-3 py-1.5 rounded-full text-xs font-semibold">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-zinc-400" x-text="statusText"></span>
            </div>
            
            <button @click="cargarOrdenes()" 
                    class="p-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all active:scale-95 text-sm"
                    title="Actualizar ahora">
                🔄
            </button>
            
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="py-2 px-4 bg-red-600/10 hover:bg-red-600/20 text-red-500 border border-red-500/20 text-xs font-bold uppercase rounded-xl transition">
                    Salir
                </button>
            </form>
        </div>
    </header>

    {{-- CUERPO PRINCIPAL --}}
    <main class="flex-grow p-6">
        
        {{-- Mensaje de sin órdenes --}}
        <div x-show="ordenes.length === 0" x-cloak class="max-w-md mx-auto text-center py-20 space-y-4">
            <span class="text-6xl block">🎉</span>
            <h2 class="text-2xl font-black text-white font-['Bebas_Neue'] tracking-wider uppercase">¡Cocina al día!</h2>
            <p class="text-zinc-400 text-sm">No hay pedidos pendientes en este momento. Buen trabajo.</p>
        </div>

        {{-- Grid de Comandas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <template x-for="(orden, index) in ordenes" :key="orden.id">
                <div class="bg-[#161310] border border-white/5 rounded-3xl overflow-hidden flex flex-col justify-between shadow-2xl relative transition-all duration-300 hover:border-orange-500/30">
                    
                    {{-- Barra superior de estado --}}
                    <div class="h-1.5 w-full bg-orange-500" :class="orden.status === 'preparing' ? 'bg-orange-500' : 'bg-yellow-500'"></div>

                    {{-- Encabezado de la comanda --}}
                    <div class="p-5 border-b border-white/5 bg-white/[0.01]">
                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <h3 class="text-2xl font-black tracking-wider text-white font-['Bebas_Neue'] uppercase"
                                    x-text="orden.table_number ? 'Mesa #' + orden.table_number : 'Para Llevar'">
                                </h3>
                                <p class="text-xs text-zinc-400 mt-0.5" x-text="'Cliente: ' + (orden.customer_name || 'Sin Nombre')"></p>
                            </div>
                            <div class="bg-white/5 border border-white/10 px-3 py-1.5 rounded-2xl text-right">
                                <span class="text-[10px] text-zinc-400 font-bold uppercase block">Ordenado hace</span>
                                <span class="text-xs font-black text-orange-500" x-text="calcularTiempo(orden.created_at)"></span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Lista de platillos --}}
                    <div class="p-5 flex-grow space-y-4">
                        <template x-for="item in orden.items" :key="item.id">
                            <div class="pb-3 border-b border-white/5 last:border-0 last:pb-0">
                                <div class="flex items-start gap-3">
                                    <span class="bg-orange-500/10 text-orange-500 text-sm font-black w-7 h-7 flex items-center justify-center rounded-lg flex-shrink-0"
                                          x-text="item.quantity + 'x'">
                                    </span>
                                    <div class="flex-grow">
                                        <h4 class="font-extrabold text-sm text-zinc-200" x-text="item.product_name"></h4>
                                        
                                        {{-- Exclusiones --}}
                                        <template x-if="item.excluded_ingredients && item.excluded_ingredients.length > 0">
                                            <div class="mt-1.5 flex flex-wrap gap-1 items-center">
                                                <span class="text-[9px] font-extrabold uppercase bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded border border-red-500/10">SIN</span>
                                                <span class="text-xs text-red-400 font-bold" x-text="item.excluded_ingredients.join(', ')"></span>
                                            </div>
                                        </template>

                                        {{-- Comentarios / Extras --}}
                                        <template x-if="item.notes">
                                            <div class="mt-1.5">
                                                <span class="text-[9px] font-extrabold uppercase bg-yellow-500/20 text-yellow-400 px-1.5 py-0.5 rounded border border-yellow-500/10 inline-block mb-1">NOTA</span>
                                                <p class="text-xs text-yellow-400 font-bold break-all whitespace-pre-wrap" x-text="item.notes"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    {{-- Botón de Listo/Despachar --}}
                    <div class="p-4 bg-white/[0.01] border-t border-white/5">
                        <button @click="despachar(orden.id)"
                                class="w-full py-4 bg-green-600 hover:bg-green-700 active:scale-95 text-white font-bold text-xs uppercase tracking-widest rounded-2xl shadow-lg shadow-green-600/20 transition-all flex items-center justify-center gap-2">
                            <span></span> MARCAR COMO LISTO
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </main>

    {{-- LOGIC DE ALPINE.JS --}}
    <script>
        function pantallaCocina() {
            return {
                ordenes: [],
                statusText: 'Cargando...',
                intervalId: null,

                init() {
                    this.cargarOrdenes();
                    
                    // Configurar polling cada 10 segundos
                    this.intervalId = setInterval(() => {
                        this.cargarOrdenes();
                    }, 10000);
                },

                async cargarOrdenes() {
                    this.statusText = 'Actualizando...';
                    try {
                        const res = await fetch('/api/cocina/ordenes');
                        if (res.ok) {
                            const data = await res.json();
                            this.ordenes = data;
                            this.statusText = 'En vivo';
                        } else {
                            this.statusText = 'Error de red';
                        }
                    } catch (e) {
                        this.statusText = 'Error de red';
                    }
                },

                async despachar(id) {
                    try {
                        const res = await fetch(`/cocina/orden/${id}/ready`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (res.ok) {
                            // Remover la orden localmente para un efecto visual instantáneo
                            this.ordenes = this.ordenes.filter(o => o.id !== id);
                            this.statusText = 'Orden despachada';
                        } else {
                            alert('No se pudo despachar la orden.');
                        }
                    } catch (e) {
                        alert('Error de red al despachar la orden.');
                    }
                },

                calcularTiempo(timestamp) {
                    const creacion = new Date(timestamp);
                    const ahora = new Date();
                    const diffMs = ahora - creacion;
                    const diffMins = Math.floor(diffMs / 1000 / 60);
                    
                    if (diffMins < 1) return 'Hace un momento';
                    return `${diffMins} min`;
                }
            }
        }
    </script>
</body>
</html>
