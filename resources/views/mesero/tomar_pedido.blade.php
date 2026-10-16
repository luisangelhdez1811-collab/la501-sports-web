<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tomar Pedido - Mesa {{ $mesaId }}</title>
    <script>
        if (localStorage.theme === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { -webkit-tap-highlight-color: transparent; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-zinc-100 dark:bg-[#0a0a0a] text-zinc-900 dark:text-white min-h-screen pb-32"
      x-data="carritoPedido({{ json_encode($menu->flatten()) }}, {{ json_encode($menu->map(fn($productos) => $productos->pluck('id')->values()->all())->values()->all()) }})">

    {{-- CABECERA FIJA: HEADER + NAV DE CATEGORÍAS (siempre visibles al hacer scroll) --}}
    <div class="sticky top-0 z-50" x-ref="stickyBar">
        {{-- HEADER CON BOTÓN DE REGRESAR --}}
        <header class="bg-white/95 dark:bg-black/95 backdrop-blur-md border-b border-zinc-200 dark:border-white/5 px-4 py-3 flex items-center shadow-sm gap-4">
            <a href="{{ route('mesero.mesas') }}" class="p-2.5 bg-zinc-100 dark:bg-white/10 rounded-full hover:bg-zinc-200 dark:hover:bg-white/20 transition focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Volver al mapa de mesas">
                🔙
            </a>
            <div class="flex-grow">
                <h1 class="font-black text-lg leading-none">Mesa {{ $mesaId }}</h1>
                <p class="text-[10px] text-zinc-500 uppercase font-bold tracking-wider">Tomando Orden</p>
            </div>
            <button type="button" id="theme-toggle-btn" class="p-2.5 bg-zinc-100 dark:bg-white/10 rounded-full hover:bg-zinc-200 dark:hover:bg-white/20 transition focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Cambiar modo claro/oscuro" title="Cambiar modo claro/oscuro">
                <span id="theme-toggle-icon">🌙</span>
            </button>
        </header>

        {{-- NAV RÁPIDA DE CATEGORÍAS (sin imágenes, para saltar directo sin saberse el menú) --}}
        <nav class="bg-white/95 dark:bg-black/95 backdrop-blur-md border-b border-zinc-200 dark:border-white/5 overflow-x-auto no-scrollbar" aria-label="Categorías del menú">
            <div class="flex gap-2 px-4 py-2.5 max-w-md sm:max-w-2xl mx-auto">
                @foreach($menu as $categoria => $productos)
                    <button type="button"
                            @click="scrollToCategory({{ $loop->index }})"
                            x-show="categoryHasMatches({{ json_encode($productos->pluck('id')->toArray()) }})"
                            :class="activeCategory === {{ $loop->index }}
                                ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'bg-zinc-100 dark:bg-white/5 border-zinc-200 dark:border-white/10 text-zinc-600 dark:text-zinc-300 hover:border-blue-400'"
                            class="flex-shrink-0 px-4 py-2 rounded-full text-xs font-black uppercase tracking-wide border transition-colors whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ $categoria }}
                    </button>
                @endforeach
            </div>
        </nav>
    </div>

    <main class="p-4 max-w-md sm:max-w-2xl mx-auto space-y-8 mt-2">
        {{-- BUSCADOR EN VIVO --}}
        <div class="bg-white dark:bg-[#1a1612] border border-zinc-200 dark:border-white/5 rounded-[24px] p-3 shadow-sm">
            <div class="relative flex items-center bg-zinc-50 dark:bg-black/40 rounded-2xl border border-zinc-100 dark:border-white/5 px-3 py-2.5">
                <span class="text-zinc-400 select-none mr-2">🔍</span>
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Buscar platillo o bebida..."
                       aria-label="Buscar platillo o bebida"
                       class="w-full bg-transparent border-none outline-none text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-0 p-0">
                <button x-show="searchQuery"
                        @click="searchQuery = ''"
                        type="button"
                        aria-label="Limpiar búsqueda"
                        class="text-zinc-400 hover:text-red-500 font-bold text-lg leading-none pl-2 select-none">
                    &times;
                </button>
            </div>
        </div>

        {{-- BUCLE DE CATEGORÍAS Y PRODUCTOS --}}
        @foreach($menu as $categoria => $productos)
            <div id="cat-{{ $loop->index }}" data-category-section data-category-index="{{ $loop->index }}"
                 x-show="categoryHasMatches({{ json_encode($productos->pluck('id')->toArray()) }})" x-transition>
                <h2 class="text-sm font-black text-zinc-500 uppercase tracking-widest mb-4 pl-2 border-l-4 border-blue-500">{{ $categoria }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($productos as $producto)
                        <div x-show="productMatches({{ $producto->id }})" x-transition class="bg-white dark:bg-[#1a1612] border border-zinc-200 dark:border-white/5 rounded-[24px] p-3 shadow-sm flex flex-col gap-3">
                            <div class="flex items-center gap-4">
                                {{-- IMAGEN DEL PRODUCTO --}}
                                @php
                                    $imagenSrc = $producto->image ? ((str_starts_with($producto->image, 'http://') || str_starts_with($producto->image, 'https://')) ? $producto->image : asset('storage/' . $producto->image)) : 'https://cdn-icons-png.flaticon.com/512/3075/3075977.png';
                                @endphp
                                <img src="{{ $imagenSrc }}" alt="{{ $producto->name }}" class="w-16 h-16 rounded-2xl object-cover bg-zinc-50 flex-shrink-0">

                                {{-- NOMBRE Y PRECIO (completos, sin cortar) --}}
                                <div class="flex-grow min-w-0">
                                    <h3 class="font-bold text-sm leading-snug">{{ $producto->name }}</h3>
                                    <p class="text-green-500 font-black text-sm">${{ number_format($producto->price, 2) }}</p>
                                </div>
                            </div>

                            {{-- ACCIONES: NOTA/EXTRA + SUMAR/RESTAR --}}
                            <div class="flex items-center gap-2">
                                <button @click="abrirComentario({{ $producto->id }})" aria-label="Agregar comentario o extra a {{ $producto->name }}" title="Comentario / Extra" class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-zinc-100 dark:bg-black text-zinc-500 dark:text-zinc-300 rounded-xl font-bold shadow-sm active:scale-90 transition-transform focus:outline-none focus:ring-2 focus:ring-blue-500">📝</button>

                                <div class="flex items-center gap-3 bg-zinc-100 dark:bg-black p-1 rounded-2xl flex-shrink-0 ml-auto">
                                    <button @click="restar({{ $producto->id }})" aria-label="Quitar uno de {{ $producto->name }}" class="w-10 h-10 flex items-center justify-center bg-white dark:bg-[#1a1612] rounded-xl font-bold shadow-sm active:scale-90 transition-transform focus:outline-none focus:ring-2 focus:ring-blue-500">-</button>
                                    <span class="font-black w-4 text-center text-sm" x-text="getCantidad({{ $producto->id }})"></span>
                                    <button @click="intentarAgregar({{ $producto->id }})" aria-label="Agregar uno de {{ $producto->name }}" class="w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-xl font-bold shadow-sm active:scale-90 transition-transform focus:outline-none focus:ring-2 focus:ring-blue-500">+</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- SIN RESULTADOS DE BÚSQUEDA --}}
        <div x-show="searchQuery && !anyMatches()" x-cloak class="text-center py-10 text-zinc-400">
            <p class="text-3xl mb-2">🔎</p>
            <p class="text-sm font-bold">Sin resultados para "<span x-text="searchQuery"></span>"</p>
        </div>

        {{-- RESUMEN DE LA ORDEN (CON EXCLUSIONES) --}}
        <div x-show="total > 0" x-cloak class="bg-white dark:bg-[#1a1612] border border-zinc-200 dark:border-white/5 rounded-[24px] p-4 shadow-sm space-y-3">
            <h3 class="font-black text-xs uppercase tracking-wider text-zinc-400 pl-1">Resumen de la Orden</h3>
            <div class="space-y-3 divide-y divide-zinc-100 dark:divide-white/5">
                <template x-for="(item, key) in items" :key="key">
                    <div class="flex items-center justify-between gap-4 pt-3 first:pt-0">
                        <div class="flex-grow">
                            <span class="font-bold text-sm text-zinc-800 dark:text-zinc-200" x-text="item.name"></span>
                            <template x-if="item.excluded_ingredients && item.excluded_ingredients.length > 0">
                                <p class="text-[11px] text-red-500 font-semibold mt-0.5">
                                    Sin: <span x-text="item.excluded_ingredients.join(', ')"></span>
                                </p>
                            </template>
                            <template x-if="item.notes">
                                <p class="text-[11px] text-blue-500 font-semibold mt-0.5">
                                    📝 <span x-text="item.notes"></span>
                                </p>
                            </template>
                            <p class="text-xs text-zinc-500 mt-0.5" x-text="'$' + item.price.toFixed(2) + ' c/u'"></p>
                        </div>

                        <div class="flex items-center gap-2 bg-zinc-100 dark:bg-black p-1 rounded-xl flex-shrink-0">
                            <button type="button" @click="restarPorKey(key)" aria-label="Quitar uno" class="w-8 h-8 flex items-center justify-center bg-white dark:bg-[#1a1612] rounded-lg font-bold shadow-sm text-sm active:scale-90 transition-transform">-</button>
                            <span class="font-black text-xs w-4 text-center" x-text="item.cantidad"></span>
                            <button type="button" @click="sumarPorKey(key)" aria-label="Agregar uno" class="w-8 h-8 flex items-center justify-center bg-white dark:bg-[#1a1612] rounded-lg font-bold shadow-sm text-sm active:scale-90 transition-transform">+</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>

    {{-- BARRA FLOTANTE INFERIOR (ENVIAR ORDEN) --}}
    <div x-show="total > 0" x-cloak x-transition class="fixed bottom-0 left-0 w-full bg-white dark:bg-[#1a1612] border-t border-zinc-200 dark:border-white/10 p-4 shadow-[0_-10px_40px_rgba(0,0,0,0.1)] z-50">
        <div class="max-w-md sm:max-w-2xl mx-auto flex gap-4 items-center">
            <div class="flex-grow">
                <p class="text-[10px] text-zinc-500 uppercase font-bold tracking-widest">Total a mandar</p>
                <p class="text-2xl font-black text-green-500" x-text="'$' + total.toFixed(2)"></p>
            </div>

            <form action="{{ route('mesero.guardar_pedido', $mesaId) }}" method="POST" class="flex-grow flex-grow-2">
                @csrf
                {{-- Aquí mandamos todo el carrito en formato texto oculto al servidor --}}
                <input type="hidden" name="carrito" :value="JSON.stringify(Object.values(items))">
                <input type="hidden" name="total" :value="total">

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 px-6 rounded-2xl shadow-xl shadow-blue-500/30 active:scale-95 transition-transform flex justify-center items-center gap-2 text-sm uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-blue-400">
                    🍽️ ENVIAR A COCINA
                </button>
            </form>
        </div>
    </div>

    {{-- MODAL DE PERSONALIZACIÓN DE INGREDIENTES --}}
    <div x-show="showCustomizeModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-[#1a1612] border border-zinc-200 dark:border-white/5 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden max-h-[90vh] flex flex-col" @click.away="showCustomizeModal = false">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-white/5 flex justify-between items-center bg-zinc-50 dark:bg-black/50">
                <h3 class="text-sm font-black uppercase tracking-wider text-zinc-900 dark:text-white" x-text="'Personalizar ' + customizingProduct.name"></h3>
                <button type="button" class="text-zinc-400 hover:text-red-500 font-bold text-2xl transition" @click="showCustomizeModal = false" aria-label="Cerrar">&times;</button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto space-y-6">
                <div class="flex gap-4 items-center">
                    <div class="w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-black flex items-center justify-center text-3xl overflow-hidden flex-shrink-0">
                        <template x-if="customizingProduct.image">
                            <img :src="customizingProduct.image.startsWith('http') ? customizingProduct.image : '/storage/' + customizingProduct.image" :alt="customizingProduct.name" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!customizingProduct.image">
                            <span>🍔</span>
                        </template>
                    </div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed flex-1" x-text="customizingProduct.description || 'Personaliza este platillo.'"></div>
                </div>

                <div x-show="customizingIngredients.length > 0">
                    <h4 class="text-xs font-black uppercase tracking-widest text-zinc-400 mb-3">Ingredientes a incluir:</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="(ing, idx) in customizingIngredients" :key="idx">
                            <label class="flex items-center gap-3 bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/5 rounded-2xl p-3 cursor-pointer select-none hover:border-blue-500 transition-colors">
                                <input type="checkbox" x-model="ing.included" class="w-4 h-4 accent-blue-500 cursor-pointer">
                                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200" x-text="ing.nombre"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-black uppercase tracking-widest text-zinc-400 mb-3">Comentarios / Extra:</h4>
                    <textarea x-model="customizingComment" rows="3" maxlength="100"
                              placeholder="Ej: papas extra, salsa extra, más picante, sin sal…"
                              class="w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/5 rounded-2xl p-3 text-xs text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 dark:placeholder-zinc-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 resize-none"></textarea>
                    <p class="text-[11px] text-zinc-400 mt-1.5">Cualquier instrucción especial o algo extra que pida el cliente.</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-zinc-50 dark:bg-black/50 border-t border-zinc-200 dark:border-white/5 flex gap-3">
                <button type="button" class="flex-1 py-3 bg-zinc-200 dark:bg-white/10 hover:bg-zinc-300 dark:hover:bg-white/20 text-zinc-800 dark:text-white text-xs font-black uppercase tracking-wider rounded-xl transition" @click="showCustomizeModal = false">
                    Cancelar
                </button>
                <button type="button" class="flex-[2] py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-blue-500/25 transition" @click="confirmCustomization()">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    {{-- Lógica de Alpine.js para el carrito --}}
    <script>
        function carritoPedido(allProducts, categoryProductIds) {
            return {
                productosMap: {},
                categoryProductIds: categoryProductIds || [],
                items: {},
                total: 0,
                searchQuery: '',
                activeCategory: 0,
                scrollSpyObserver: null,

                // Customization Modal State
                showCustomizeModal: false,
                customizingProduct: {},
                customizingIngredients: [],
                customizingComment: '',

                init() {
                    allProducts.forEach(p => {
                        this.productosMap[p.id] = p;
                    });
                    this.$nextTick(() => this.initScrollSpy());
                },

                initScrollSpy() {
                    const sections = document.querySelectorAll('[data-category-section]');
                    if (!sections.length || !('IntersectionObserver' in window)) return;
                    const barHeight = this.$refs.stickyBar ? this.$refs.stickyBar.offsetHeight : 0;
                    this.scrollSpyObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.activeCategory = parseInt(entry.target.dataset.categoryIndex, 10);
                            }
                        });
                    }, { rootMargin: `-${barHeight + 10}px 0px -70% 0px`, threshold: 0 });
                    sections.forEach(s => this.scrollSpyObserver.observe(s));
                },

                scrollToCategory(index) {
                    this.activeCategory = index;
                    const el = document.getElementById('cat-' + index);
                    if (!el) return;
                    const offset = (this.$refs.stickyBar ? this.$refs.stickyBar.offsetHeight : 0) + 12;
                    const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                },

                productMatches(id) {
                    if (!this.searchQuery) return true;
                    const p = this.productosMap[id];
                    return p && p.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                },

                categoryHasMatches(ids) {
                    if (!this.searchQuery) return true;
                    const query = this.searchQuery.toLowerCase();
                    return ids.some(id => {
                        const p = this.productosMap[id];
                        return p && p.name.toLowerCase().includes(query);
                    });
                },

                anyMatches() {
                    return this.categoryProductIds.some(ids => this.categoryHasMatches(ids));
                },

                intentarAgregar(id) {
                    const prod = this.productosMap[id];
                    if (prod && prod.ingredientes && prod.ingredientes.length > 0) {
                        this.customizingProduct = prod;
                        this.customizingIngredients = prod.ingredientes.map(ing => ({
                            nombre: ing.nombre,
                            included: true
                        }));
                        this.customizingComment = '';
                        this.showCustomizeModal = true;
                    } else {
                        this.agregarDirecto(id, prod.name, prod.price, [], '');
                    }
                },

                abrirComentario(id) {
                    const prod = this.productosMap[id];
                    if (!prod) return;
                    this.customizingProduct = prod;
                    this.customizingIngredients = (prod.ingredientes || []).map(ing => ({
                        nombre: ing.nombre,
                        included: true
                    }));
                    this.customizingComment = '';
                    this.showCustomizeModal = true;
                },

                confirmCustomization() {
                    const excluded = this.customizingIngredients
                        .filter(ing => !ing.included)
                        .map(ing => ing.nombre);
                    const comment = this.customizingComment.trim();

                    this.agregarDirecto(this.customizingProduct.id, this.customizingProduct.name, this.customizingProduct.price, excluded, comment);
                    this.showCustomizeModal = false;
                },

                agregarDirecto(id, name, price, excludedIngredients, comment) {
                    let key = id;
                    const partes = [];
                    if (excludedIngredients && excludedIngredients.length > 0) {
                        partes.push('ex:' + excludedIngredients.join(','));
                    }
                    if (comment) {
                        partes.push('nt:' + comment);
                    }
                    if (partes.length > 0) {
                        key = id + '_' + partes.join('|');
                    }

                    if (this.items[key]) {
                        this.items[key].cantidad++;
                    } else {
                        this.items[key] = {
                            id: id,
                            name: name,
                            price: price,
                            cantidad: 1,
                            excluded_ingredients: excludedIngredients || [],
                            notes: comment || null
                        };
                    }
                    this.calcularTotal();
                },

                restar(id) {
                    const keys = Object.keys(this.items).filter(k => k == id || k.startsWith(id + '_'));
                    if (keys.length > 0) {
                        const keyToDec = keys[keys.length - 1];
                        this.items[keyToDec].cantidad--;
                        if (this.items[keyToDec].cantidad === 0) {
                            delete this.items[keyToDec];
                        }
                        this.calcularTotal();
                    }
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
                    let count = 0;
                    for (let key in this.items) {
                        if (key == id || key.startsWith(id + '_')) {
                            count += this.items[key].cantidad;
                        }
                    }
                    return count;
                },

                calcularTotal() {
                    let suma = 0;
                    for (let key in this.items) {
                        suma += this.items[key].price * this.items[key].cantidad;
                    }
                    this.total = suma;
                }
            }
        }

        // Botón de modo claro/oscuro
        const themeBtn = document.getElementById('theme-toggle-btn');
        const themeIcon = document.getElementById('theme-toggle-icon');
        const isDarkNow = () => document.documentElement.classList.contains('dark');
        const syncThemeIcon = () => { themeIcon.textContent = isDarkNow() ? '🌙' : '☀️'; };
        syncThemeIcon();
        themeBtn.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.theme = isDarkNow() ? 'dark' : 'light';
            syncThemeIcon();
        });
    </script>
</body>
</html>
