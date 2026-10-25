<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mesa {{ $mesaId }} · Nuevo pedido</title>
    <script>
        if (localStorage.theme === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] } } } }</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="{{ asset('js/menu-opciones.js') }}?v={{ @filemtime(public_path('js/menu-opciones.js')) }}"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { -webkit-tap-highlight-color: transparent; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="font-sans antialiased bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 min-h-screen pb-28"
      x-data="carritoPedido(@js($menu))">

    {{-- CABECERA FIJA: mesa, Comidas/Bebidas y categorías --}}
    <div class="sticky top-0 z-50 bg-white/95 dark:bg-zinc-950/95 backdrop-blur border-b border-zinc-200 dark:border-zinc-800" x-ref="stickyBar">
        <header class="max-w-3xl mx-auto px-4 h-14 flex items-center gap-3">
            <a href="{{ route('mesero.mesas') }}" class="w-9 h-9 -ml-1 flex items-center justify-center rounded-lg text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition" aria-label="Volver a mesas">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div class="flex-grow leading-tight">
                <h1 class="text-base font-bold">Mesa {{ $mesaId }}</h1>
                <p class="text-xs text-zinc-500">Nuevo pedido</p>
            </div>
            <button type="button" id="theme-toggle-btn" class="w-9 h-9 flex items-center justify-center rounded-lg text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition" aria-label="Cambiar modo claro/oscuro" title="Cambiar modo claro/oscuro">
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </button>
        </header>

        <div class="max-w-3xl mx-auto px-4 pb-2.5">
            {{-- COMIDAS / BEBIDAS --}}
            <div class="grid grid-cols-2 p-1 bg-zinc-100 dark:bg-zinc-900 rounded-lg" role="tablist" aria-label="Sección del menú">
                <template x-for="sec in secciones" :key="sec.id">
                    <button type="button" role="tab" @click="cambiarSeccion(sec.id)" :aria-selected="seccion === sec.id"
                            :class="seccion === sec.id ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500'"
                            class="h-8 rounded-md text-sm font-semibold transition" x-text="sec.nombre"></button>
                </template>
            </div>
        </div>

        {{-- CATEGORÍAS --}}
        <nav class="overflow-x-auto no-scrollbar" aria-label="Categorías del menú">
            <div class="max-w-3xl mx-auto flex gap-1.5 px-4 pb-2.5">
                <template x-for="cat in categoriasVisibles()" :key="cat.nombre">
                    <button type="button" @click="scrollToCategory(cat.nombre)"
                            :class="activeCategory === cat.nombre
                                ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-white dark:text-zinc-900 dark:border-white'
                                : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-300'"
                            class="flex-shrink-0 h-8 px-3 rounded-md text-xs font-semibold border transition whitespace-nowrap"
                            x-text="cat.nombre"></button>
                </template>
            </div>
        </nav>
    </div>

    <main class="max-w-3xl mx-auto p-4 space-y-6">
        @if(session('error'))
            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 px-4 py-3 rounded text-sm font-semibold" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{-- BUSCADOR (busca en comidas y bebidas) --}}
        <label class="flex items-center gap-2 h-11 px-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg focus-within:border-zinc-400 dark:focus-within:border-zinc-600">
            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            <input type="text" x-model="searchQuery" placeholder="Buscar platillo o bebida" aria-label="Buscar platillo o bebida"
                   class="w-full bg-transparent border-0 outline-none text-sm placeholder-zinc-400 focus:ring-0 p-0">
            <button x-show="searchQuery" x-cloak @click="searchQuery = ''" type="button" aria-label="Limpiar búsqueda" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </label>

        {{-- CATEGORÍAS Y PRODUCTOS --}}
        <template x-for="cat in categoriasVisibles()" :key="cat.nombre">
            <section :id="'cat-' + cat.slug" data-category-section :data-category="cat.nombre">
                <h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500 mb-2" x-text="cat.nombre"></h2>
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl divide-y divide-zinc-100 dark:divide-zinc-800">
                    <template x-for="producto in cat.productos" :key="producto.id">
                        <div class="flex items-center gap-3 p-3">
                            <template x-if="producto.image">
                                <img :src="imagen(producto)" :alt="producto.name" x-on:error="producto.image = null" class="w-12 h-12 rounded-lg object-cover bg-zinc-100 dark:bg-zinc-800 flex-shrink-0" loading="lazy">
                            </template>
                            <template x-if="!producto.image">
                                <div class="w-12 h-12 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 flex items-center justify-center text-sm font-bold text-zinc-400" x-text="iniciales(producto.name)" aria-hidden="true"></div>
                            </template>

                            <div class="flex-grow min-w-0">
                                <h3 class="text-sm font-semibold leading-snug" x-text="producto.name"></h3>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-200" x-text="MenuOpciones.precioCarta(producto)"></span>
                                    <span x-show="producto.presentation" x-text="' · ' + producto.presentation"></span>
                                    <span x-text="' · ' + estacion(producto)"></span>
                                </p>
                            </div>

                            <button type="button" @click="abrirPersonalizar(producto)" :aria-label="'Opciones y nota para ' + producto.name" title="Opciones y nota"
                                    class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg>
                            </button>
                            <div class="flex items-center flex-shrink-0 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <button type="button" @click="restar(producto.id)" :aria-label="'Quitar uno de ' + producto.name" class="w-9 h-9 flex items-center justify-center text-zinc-500 hover:text-zinc-900 dark:hover:text-white disabled:opacity-30" :disabled="getCantidad(producto.id) === 0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14"/></svg>
                                </button>
                                <span class="w-6 text-center text-sm font-bold tabular-nums" x-text="getCantidad(producto.id)"></span>
                                <button type="button" @click="intentarAgregar(producto)" :aria-label="'Agregar uno de ' + producto.name" class="w-9 h-9 flex items-center justify-center text-orange-600 dark:text-orange-500 hover:text-orange-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </template>

        {{-- SIN RESULTADOS DE BÚSQUEDA --}}
        <p x-show="searchQuery && categoriasVisibles().length === 0" x-cloak class="text-center py-10 text-sm text-zinc-500">
            Sin resultados para «<span x-text="searchQuery"></span>».
        </p>

        {{-- RESUMEN DE LA ORDEN --}}
        <section x-show="total > 0" x-cloak>
            <h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500 mb-2">Resumen del pedido</h2>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl divide-y divide-zinc-100 dark:divide-zinc-800">
                <template x-for="(item, key) in items" :key="key">
                    <div class="flex items-start justify-between gap-4 p-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold" x-text="item.name"></p>
                            <p class="text-xs text-zinc-500 mt-0.5" x-show="item.resumen" x-text="item.resumen"></p>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-0.5" x-show="item.excluded_ingredients && item.excluded_ingredients.length" x-text="'Sin: ' + (item.excluded_ingredients || []).join(', ')"></p>
                            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5" x-show="item.notes" x-text="'Nota: ' + item.notes"></p>
                            <p class="text-xs text-zinc-500 mt-0.5 tabular-nums" x-text="MenuOpciones.formatoPrecio(item.price) + ' c/u'"></p>
                        </div>
                        <div class="flex items-center flex-shrink-0 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <button type="button" @click="restarPorKey(key)" aria-label="Quitar uno" class="w-8 h-8 flex items-center justify-center text-zinc-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14"/></svg>
                            </button>
                            <span class="w-5 text-center text-sm font-bold tabular-nums" x-text="item.cantidad"></span>
                            <button type="button" @click="sumarPorKey(key)" aria-label="Agregar uno" class="w-8 h-8 flex items-center justify-center text-zinc-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </main>

    {{-- BARRA INFERIOR: TOTAL Y ENVÍO --}}
    <div x-show="total > 0" x-cloak x-transition class="fixed bottom-0 inset-x-0 z-50 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-4">
            <div class="flex-grow">
                <p class="text-[11px] uppercase tracking-wider text-zinc-500" x-text="piezas() + (piezas() === 1 ? ' platillo' : ' platillos')"></p>
                <p class="text-xl font-bold tabular-nums" x-text="MenuOpciones.formatoPrecio(total)"></p>
            </div>

            <form action="{{ route('mesero.guardar_pedido', $mesaId) }}" method="POST" @submit="enviando = true">
                @csrf
                {{-- El servidor recalcula los precios --}}
                <input type="hidden" name="carrito" :value="JSON.stringify(Object.values(items).map(i => ({ id: i.id, variant_id: i.variant_id, options: i.options, cantidad: i.cantidad, excluded_ingredients: i.excluded_ingredients, notes: i.notes })))">
                <button type="submit" :disabled="enviando"
                        class="h-11 px-6 rounded-lg bg-orange-600 hover:bg-orange-700 disabled:opacity-60 text-white text-sm font-semibold transition"
                        x-text="enviando ? 'Enviando…' : 'Enviar pedido'">Enviar pedido</button>
            </form>
        </div>
    </div>

    {{-- OPCIONES DEL PLATILLO: presentación, extras, ingredientes y nota --}}
    <div x-show="showCustomizeModal" x-cloak class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center bg-black/60 sm:p-4">
        <div class="bg-white dark:bg-zinc-900 sm:border border-zinc-200 dark:border-zinc-800 rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg max-h-[92vh] flex flex-col" @click.away="showCustomizeModal = false">

            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="text-base font-bold" x-text="customizingProduct.name"></h3>
                    <p class="text-xs text-zinc-500 mt-1 leading-relaxed" x-show="customizingProduct.description" x-text="customizingProduct.description"></p>
                </div>
                <button type="button" class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800" @click="showCustomizeModal = false" aria-label="Cerrar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

            <div class="px-5 py-4 overflow-y-auto space-y-5" x-show="showCustomizeModal">
                {{-- Presentación (vaso / litro, copeo / botella) --}}
                <template x-if="(customizingProduct.variants || []).length > 0">
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-2">Presentación</legend>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="v in customizingProduct.variants" :key="v.id">
                                <button type="button" @click="seleccion.variantId = v.id" :aria-pressed="seleccion.variantId === v.id"
                                        :class="seleccion.variantId === v.id ? 'border-orange-500 ring-1 ring-orange-500' : 'border-zinc-200 dark:border-zinc-700'"
                                        class="border rounded-lg px-3 py-2.5 text-left transition">
                                    <span class="block text-sm font-semibold" x-text="v.name"></span>
                                    <span class="block text-xs text-zinc-500 tabular-nums" x-text="MenuOpciones.formatoPrecio(v.price)"></span>
                                </button>
                            </template>
                        </div>
                    </fieldset>
                </template>

                {{-- Salsas, papas, extras, sabores --}}
                <template x-for="grupo in (customizingProduct.option_groups || [])" :key="grupo.id">
                    <fieldset>
                        <div class="flex items-baseline justify-between gap-2 mb-2">
                            <legend class="text-xs font-semibold uppercase tracking-wider text-zinc-500" x-text="grupo.label"></legend>
                            <span class="text-[11px] font-medium"
                                  :class="MenuOpciones.totalGrupo(grupo, seleccion) < grupo.min ? 'text-red-600 dark:text-red-400' : 'text-zinc-400'"
                                  x-text="MenuOpciones.regla(grupo)"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="op in grupo.options" :key="op.id">
                                <div class="flex items-center border rounded-lg transition"
                                     :class="MenuOpciones.cantidad(seleccion, op) ? 'border-orange-500 ring-1 ring-orange-500' : 'border-zinc-200 dark:border-zinc-700'">
                                    <button type="button" @click="MenuOpciones.alternar(customizingProduct, grupo, op, seleccion)" :aria-pressed="MenuOpciones.cantidad(seleccion, op) > 0"
                                            class="flex-grow text-left px-3 py-2 min-w-0">
                                        <span class="block text-sm font-medium" x-text="op.name"></span>
                                        <span class="block text-[11px] text-zinc-500" x-show="op.detail || op.price > 0"
                                              x-text="[op.detail, op.price > 0 ? '+' + MenuOpciones.formatoPrecio(op.price) : ''].filter(Boolean).join(' · ')"></span>
                                    </button>
                                    {{-- Repetir la misma opción (especialidades: hasta 3 veces) --}}
                                    <template x-if="grupo.max_per_option > 1 && MenuOpciones.cantidad(seleccion, op) > 0">
                                        <div class="flex items-center gap-1 pr-2">
                                            <button type="button" @click="MenuOpciones.quitar(op, seleccion)" class="w-6 h-6 rounded border border-zinc-200 dark:border-zinc-700 text-xs font-bold" aria-label="Quitar una">−</button>
                                            <span class="w-3 text-center text-xs font-bold tabular-nums" x-text="MenuOpciones.cantidad(seleccion, op)"></span>
                                            <button type="button" @click="MenuOpciones.sumar(grupo, op, seleccion)" :disabled="!MenuOpciones.puedeSumar(grupo, op, seleccion)" class="w-6 h-6 rounded border border-zinc-200 dark:border-zinc-700 text-xs font-bold disabled:opacity-30" aria-label="Agregar otra">+</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </fieldset>
                </template>

                <fieldset x-show="customizingIngredients.length > 0">
                    <legend class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-2">Ingredientes (desmarca para quitar)</legend>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                        <template x-for="(ing, idx) in customizingIngredients" :key="idx">
                            <label class="flex items-center gap-2.5 text-sm cursor-pointer select-none">
                                <input type="checkbox" x-model="ing.included" class="w-4 h-4 rounded accent-orange-600">
                                <span :class="ing.included ? '' : 'line-through text-zinc-400'" x-text="ing.nombre"></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <div>
                    <label for="nota-platillo" class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-2">Nota para cocina</label>
                    <textarea id="nota-platillo" x-model="customizingComment" rows="2" maxlength="100"
                              placeholder="Instrucción especial (opcional)"
                              class="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm placeholder-zinc-400 outline-none focus:border-zinc-400 resize-none"></textarea>
                </div>
            </div>

            <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-800 space-y-2">
                <p class="text-xs font-medium text-red-600 dark:text-red-400" x-show="showCustomizeModal && pendientes().length" x-text="pendientes()[0]"></p>
                <p class="text-xs font-medium text-amber-700 dark:text-amber-400" x-show="showCustomizeModal && !pendientes().length && recordatorio()" x-text="recordatorio()"></p>
                <div class="flex gap-2">
                    <button type="button" class="h-11 px-5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-sm font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800" @click="showCustomizeModal = false">
                        Cancelar
                    </button>
                    <button type="button" :disabled="pendientes().length > 0" @click="confirmCustomization()"
                            class="flex-grow h-11 rounded-lg bg-orange-600 hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold transition tabular-nums"
                            x-text="'Agregar · ' + MenuOpciones.formatoPrecio(showCustomizeModal ? MenuOpciones.precio(customizingProduct, seleccion) : 0)">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const SECCIONES = [
            { id: 'comidas', nombre: 'Comidas' },
            { id: 'bebidas', nombre: 'Bebidas' },
        ];
        const ESTACIONES = { cocina: 'Cocina', barra: 'Barra', ninguna: 'Entrega directa' };

        function carritoPedido(allProducts) {
            return {
                productos: allProducts || [],
                secciones: SECCIONES,
                seccion: 'comidas',
                items: {},
                total: 0,
                searchQuery: '',
                activeCategory: null,
                enviando: false,

                // Opciones del platillo
                showCustomizeModal: false,
                customizingProduct: {},
                customizingIngredients: [],
                customizingComment: '',
                seleccion: { variantId: null, cantidades: {} },

                init() {
                    this.$watch('searchQuery', () => this.$nextTick(() => this.initScrollSpy()));
                    this.$nextTick(() => this.initScrollSpy());
                },

                cambiarSeccion(id) {
                    this.seccion = id;
                    window.scrollTo({ top: 0 });
                    this.$nextTick(() => this.initScrollSpy());
                },

                /** Con búsqueda se buscan comidas y bebidas a la vez; sin búsqueda, solo la sección elegida. */
                categoriasVisibles() {
                    const query = this.searchQuery.trim().toLowerCase();
                    const lista = this.productos.filter(p => query
                        ? p.name.toLowerCase().includes(query)
                        : (p.section || 'comidas') === this.seccion);

                    const categorias = [];
                    lista.forEach(p => {
                        let cat = categorias.find(c => c.nombre === p.category);
                        if (!cat) {
                            cat = { nombre: p.category, slug: categorias.length + '-' + p.section, productos: [] };
                            categorias.push(cat);
                        }
                        cat.productos.push(p);
                    });
                    return categorias;
                },

                initScrollSpy() {
                    if (this.scrollSpyObserver) this.scrollSpyObserver.disconnect();
                    const sections = document.querySelectorAll('[data-category-section]');
                    if (!sections.length || !('IntersectionObserver' in window)) return;
                    this.activeCategory = sections[0].dataset.category;
                    const barHeight = this.$refs.stickyBar ? this.$refs.stickyBar.offsetHeight : 0;
                    this.scrollSpyObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) this.activeCategory = entry.target.dataset.category;
                        });
                    }, { rootMargin: `-${barHeight + 10}px 0px -70% 0px`, threshold: 0 });
                    sections.forEach(s => this.scrollSpyObserver.observe(s));
                },

                scrollToCategory(nombre) {
                    this.activeCategory = nombre;
                    const el = document.querySelector(`[data-category="${CSS.escape(nombre)}"]`);
                    if (!el) return;
                    const offset = (this.$refs.stickyBar ? this.$refs.stickyBar.offsetHeight : 0) + 12;
                    const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                },

                imagen(producto) {
                    return /^https?:\/\//.test(producto.image) ? producto.image : '/storage/' + producto.image;
                },

                iniciales(nombre) {
                    return nombre.split(/\s+/).filter(p => p.length > 2).slice(0, 2).map(p => p[0]).join('').toUpperCase();
                },

                estacion(producto) {
                    return ESTACIONES[producto.station] || ESTACIONES.cocina;
                },

                pendientes() {
                    return MenuOpciones.pendientes(this.customizingProduct, this.seleccion);
                },

                recordatorio() {
                    const sinElegir = MenuOpciones.recordatorios(this.customizingProduct, this.seleccion);
                    return sinElegir.length ? 'Sin elegir: ' + sinElegir.join(', ') + '. Confirma con el cliente.' : '';
                },

                intentarAgregar(producto) {
                    if (MenuOpciones.necesitaElegir(producto)) {
                        this.abrirPersonalizar(producto);
                    } else {
                        this.agregar(producto, MenuOpciones.crear(producto), [], '');
                    }
                },

                abrirPersonalizar(producto) {
                    this.customizingProduct = producto;
                    this.seleccion = MenuOpciones.crear(producto);
                    this.customizingIngredients = (producto.ingredientes || []).map(ing => ({
                        nombre: ing.nombre,
                        included: true
                    }));
                    this.customizingComment = '';
                    this.showCustomizeModal = true;
                },

                confirmCustomization() {
                    if (this.pendientes().length) return;
                    const excluded = this.customizingIngredients
                        .filter(ing => !ing.included)
                        .map(ing => ing.nombre);

                    this.agregar(this.customizingProduct, this.seleccion, excluded, this.customizingComment.trim());
                    this.showCustomizeModal = false;
                },

                agregar(producto, seleccion, excludedIngredients, comment) {
                    const key = MenuOpciones.clave(producto, seleccion, excludedIngredients, comment);

                    if (this.items[key]) {
                        this.items[key].cantidad++;
                    } else {
                        const payload = MenuOpciones.payload(seleccion);
                        this.items[key] = {
                            id: producto.id,
                            name: MenuOpciones.nombre(producto, seleccion),
                            variant_id: payload.variant_id,
                            options: payload.options,
                            resumen: MenuOpciones.resumen(producto, seleccion),
                            price: MenuOpciones.precio(producto, seleccion),
                            cantidad: 1,
                            excluded_ingredients: excludedIngredients || [],
                            notes: comment || null
                        };
                    }
                    this.calcularTotal();
                },

                restar(id) {
                    const keys = Object.keys(this.items).filter(k => this.items[k].id === id);
                    if (keys.length > 0) this.restarPorKey(keys[keys.length - 1]);
                },

                sumarPorKey(key) {
                    if (this.items[key]) {
                        this.items[key].cantidad++;
                        this.calcularTotal();
                    }
                },

                restarPorKey(key) {
                    if (this.items[key]) {
                        this.items[key].cantidad--;
                        if (this.items[key].cantidad === 0) {
                            delete this.items[key];
                        }
                        this.calcularTotal();
                    }
                },

                getCantidad(id) {
                    return Object.values(this.items)
                        .filter(item => item.id === id)
                        .reduce((suma, item) => suma + item.cantidad, 0);
                },

                piezas() {
                    return Object.values(this.items).reduce((suma, item) => suma + item.cantidad, 0);
                },

                calcularTotal() {
                    this.total = Object.values(this.items).reduce((suma, item) => suma + item.price * item.cantidad, 0);
                }
            }
        }

        // Modo claro/oscuro
        document.getElementById('theme-toggle-btn').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        });
    </script>
</body>
</html>
