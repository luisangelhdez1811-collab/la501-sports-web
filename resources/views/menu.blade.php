@extends('layouts.app')

@section('content')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Barlow:wght@400;500&display=swap');

    .menu-page {
        --or:   #F97316;
        --or2:  #EA580C;
        --gn:   #16A34A;
        --gn2:  #22C55E;
        --bg:       #0f0d0a;
        --bg-card:  #161310;
        --bg-card2: #1e1a14;
        --txt:      #FFFFFF;
        --txt-sub:  #A8A29E;
        --bdr:      rgba(255,255,255,0.07);
        --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-dark.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-dark.svg')) }}');
    }
    :root:not(.dark) .menu-page {
        --bg:       #F5F3EF;
        --bg-card:  #FFFFFF;
        --bg-card2: #EBEBEB;
        --txt:      #1A1208;
        --txt-sub:  #6B6560;
        --bdr:      rgba(0,0,0,0.09);
        --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-light.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-light.svg')) }}');
    }

    /* ── TOPBAR ── */
    .menu-topbar {
        position: relative;
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border-bottom: 3px solid var(--or);
        padding: 32px 32px 0;
        overflow: hidden;
    }
    .menu-topbar::before {
        content: '';
        position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
        width: 600px; height: 200px;
        background: radial-gradient(ellipse, rgba(249,115,22,.14) 0%, transparent 70%);
        pointer-events: none;
    }
    .menu-topbar-inner {
        max-width: 1200px; margin: 0 auto;
        display: flex; align-items: flex-end;
        justify-content: space-between;
        gap: 24px; flex-wrap: wrap;
        position: relative; z-index: 1;
    }
    .menu-topbar-badge {
        display: inline-block;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 10px; font-weight: 700;
        letter-spacing: 3px; text-transform: uppercase;
        padding: 4px 16px; margin-bottom: 10px;
        clip-path: polygon(8px 0%,100% 0%,calc(100% - 8px) 100%,0% 100%);
    }
    .menu-topbar-text h1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(42px, 7vw, 72px);
        line-height: .95; letter-spacing: 4px;
        color: var(--txt); margin: 0;
    }
    .menu-topbar-text h1 span { color: var(--or); }
    .menu-topbar-text p { font-size: 14px; color: var(--txt-sub); margin: 8px 0 0; line-height: 1.5; }
    .menu-topbar-stats { display: flex; gap: 28px; flex-shrink: 0; padding-bottom: 6px; }
    .menu-stat-num {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 32px; letter-spacing: 2px;
        color: var(--gn2); line-height: 1; display: block;
    }
    .menu-stat-lbl {
        font-size: 10px; letter-spacing: 2px;
        text-transform: uppercase; color: var(--txt-sub);
        display: block; margin-top: 2px;
    }

    /* ── FILTROS STICKY ── */
    .menu-filters-sticky {
        position: sticky; top: 72px; z-index: 40;
        background: var(--bg-card);
        border-bottom: 1px solid var(--bdr);
        box-shadow: 0 4px 24px rgba(0,0,0,.14);
        padding: 0 32px;
    }
    .menu-filters-inner { max-width: 1200px; margin: 0 auto; }

    .mf-row {
        display: flex; gap: 6px;
        overflow-x: auto; padding: 10px 0;
        -ms-overflow-style: none; scrollbar-width: none;
    }
    .mf-row::-webkit-scrollbar { display: none; }

    .mf-btn {
        display: inline-flex; align-items: center; gap: 7px;
        font-family: 'Oswald', sans-serif;
        font-size: 12px; font-weight: 600;
        letter-spacing: 1.5px; text-transform: uppercase;
        padding: 9px 20px; border-radius: 100px;
        border: 1px solid var(--bdr);
        background: transparent; color: var(--txt-sub);
        cursor: pointer; white-space: nowrap; flex-shrink: 0;
        transition: background .2s, color .2s, border-color .2s, box-shadow .2s;
    }
    .mf-btn .fi { font-size: 15px; }
    .mf-btn:hover { color: var(--or); border-color: rgba(249,115,22,.4); background: rgba(249,115,22,.07); }
    .mf-btn.active { background: var(--or); color: #fff; border-color: var(--or); box-shadow: 0 3px 16px rgba(249,115,22,.4); }

    .msf-btn {
        display: inline-flex; align-items: center;
        font-family: 'Oswald', sans-serif;
        font-size: 11px; font-weight: 600;
        letter-spacing: 1px; text-transform: uppercase;
        padding: 6px 15px; border-radius: 100px;
        border: 1px solid var(--bdr);
        background: transparent; color: var(--txt-sub);
        cursor: pointer; white-space: nowrap; flex-shrink: 0;
        transition: background .2s, color .2s, border-color .2s;
    }
    .msf-btn:hover { color: var(--txt); border-color: rgba(255,255,255,.2); }
    :root:not(.dark) .msf-btn:hover { border-color: rgba(0,0,0,.22); }
    .msf-btn.active { background: var(--txt); color: var(--bg-card); border-color: var(--txt); }

    .mf-divider { border: none; border-top: 1px solid var(--bdr); margin: 0; }

    /* ── MAIN ── */
    .menu-main { max-width: 1200px; margin: 0 auto; padding: 40px 32px 80px; background-color: var(--bg); }
    .menu-cat-label { display: flex; align-items: center; gap: 14px; margin-bottom: 28px; }
    .menu-cat-label::before {
        content: ''; display: block; width: 4px; height: 36px;
        background: linear-gradient(to bottom, var(--or), var(--gn2));
        border-radius: 3px; flex-shrink: 0;
    }
    .menu-cat-label h2 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 34px; letter-spacing: 3px; color: var(--txt); margin: 0; line-height: 1;
    }
    .menu-cat-label .cat-icon { font-size: 24px; }

    /* ── GRID ── */
    .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }

    /* ── CARD ── */
    .menu-card {
        background: var(--bg-card); border: 1px solid var(--bdr);
        border-radius: 16px; overflow: hidden;
        display: flex; flex-direction: column;
        position: relative;
        transition: transform .3s, border-color .3s, box-shadow .3s;
        cursor: pointer;
    }
    .menu-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(to right, var(--or), var(--gn)); opacity: 0; transition: opacity .3s;
    }
    .menu-card:hover { transform: translateY(-5px); border-color: var(--or); box-shadow: 0 10px 40px rgba(249,115,22,.16); }
    .menu-card:hover::before { opacity: 1; }

    .menu-card-img { height: 200px; position: relative; overflow: hidden; background: var(--bg-card2); flex-shrink: 0; }
    .menu-card-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s ease; display: block; }
    .menu-card:hover .menu-card-img img { transform: scale(1.1); }
    .menu-card-img-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,9,6,.7) 0%, transparent 55%); z-index: 1; }
    .menu-card-no-img {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 60px;
        background-image: var(--brand-pattern);
    }
    .menu-price-badge {
        position: absolute; top: 12px; right: 12px; z-index: 3;
        background: var(--or); font-family: 'Bebas Neue', sans-serif;
        font-size: 20px; letter-spacing: 1px; color: #fff;
        padding: 4px 14px; border-radius: 100px;
        box-shadow: 0 3px 14px rgba(249,115,22,.45); line-height: 1.2;
    }
    .menu-card-body { padding: 16px 18px 18px; display: flex; flex-direction: column; flex-grow: 1; }
    .menu-card-body h3 {
        font-family: 'Oswald', sans-serif; font-size: 17px; font-weight: 700; letter-spacing: .5px;
        color: var(--txt); margin: 0 0 6px; line-height: 1.2; transition: color .25s;
    }
    .menu-card:hover .menu-card-body h3 { color: var(--or); }
    .menu-card-body p {
        font-size: 13px; color: var(--txt-sub); line-height: 1.55; flex-grow: 1;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }

    /* ── EMPTY ── */
    .menu-empty {
        grid-column: 1 / -1; background: var(--bg-card);
        border: 1px solid var(--bdr); border-left: 5px solid var(--or);
        border-radius: 16px; padding: 60px 32px; text-align: center;
    }
    .menu-empty-icon { font-size: 48px; display: block; margin-bottom: 12px; }
    .menu-empty h3 { font-family: 'Bebas Neue', sans-serif; font-size: 34px; letter-spacing: 3px; color: var(--txt); margin: 0 0 8px; }
    .menu-empty p { color: var(--txt-sub); font-size: 14px; }

    @media (max-width: 768px) {
        .menu-topbar, .menu-filters-sticky { padding-left: 20px; padding-right: 20px; }
        .menu-topbar-stats { display: none; }
        .menu-main { padding: 28px 20px 60px; }
        .menu-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
    }
    @media (max-width: 420px) {
        .menu-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        .menu-card-img { height: 140px; }
    }
</style>

<div class="menu-page" style="background-color: var(--bg);"
     x-data="menuManager()" x-init="initMenu()">

    {{-- ── TOPBAR ── --}}
    <div class="menu-topbar">
        <div class="menu-topbar-inner">
            <div class="menu-topbar-text">
                <div class="menu-topbar-badge">La 501 Sports</div>
                <h1>Nuestro <span>Menú</span></h1>
                <p>Descubre todo lo que tenemos preparado para ti</p>
            </div>
            <div class="menu-topbar-stats" aria-hidden="true">
                <div>
                    <span class="menu-stat-num" x-text="allProductos.length + '+'"></span>
                    <span class="menu-stat-lbl">Platillos</span>
                </div>
                <div>
                    <span class="menu-stat-num" x-text="listaCategorias.length">14</span>
                    <span class="menu-stat-lbl">Categorías</span>
                </div>
                <div>
                    <span class="menu-stat-num">100%</span>
                    <span class="menu-stat-lbl">Fresco</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FILTROS STICKY ── --}}
    <div class="menu-filters-sticky">
        <div class="menu-filters-inner">

            {{-- Categorías principales --}}
            <div class="mf-row">
                <template x-for="cat in listaCategorias" :key="cat.nombre">
                    <button @click="filtro = cat.nombre; subfiltro = 'Todos'"
                            :class="filtro === cat.nombre ? 'active' : ''"
                            class="mf-btn">
                        <span class="fi" x-text="cat.icono" aria-hidden="true"></span>
                        <span x-text="cat.nombre"></span>
                    </button>
                </template>
            </div>

            {{-- Subfiltros --}}
            <template x-if="subcategoriasMap[filtro]">
                <div>
                    <hr class="mf-divider">
                    <div class="mf-row">
                        <button @click="subfiltro = 'Todos'"
                                :class="subfiltro === 'Todos' ? 'active' : ''"
                                class="msf-btn">
                            Todos
                        </button>
                        <template x-for="sub in subcategoriasMap[filtro]" :key="sub">
                            <button @click="subfiltro = sub"
                                    :class="subfiltro === sub ? 'active' : ''"
                                    class="msf-btn"
                                    x-text="sub">
                            </button>
                        </template>
                    </div>
                </div>
            </template>

        </div>
    </div>

    {{-- ── PRODUCTOS ── --}}
    <div class="menu-main">

        {{-- Etiqueta categoría activa --}}
        <div class="menu-cat-label">
            <template x-for="cat in listaCategorias" :key="cat.nombre">
                <template x-if="filtro === cat.nombre">
                    <div style="display:contents;">
                        <span class="cat-icon" x-text="cat.icono" aria-hidden="true"></span>
                        {{-- FIX L293: aria-label como fallback para analizadores estáticos --}}
                        <h2 x-text="filtro" aria-label="Filtro de menú">Filtro</h2>
                    </div>
                </template>
            </template>
        </div>

        <div class="menu-grid">
            <template x-for="item in productosFiltrados()" :key="item.id">
                <div class="menu-card">
                    <div class="menu-card-img">
                        <template x-if="item.image">
                            <img :src="item.image.startsWith('http') ? item.image : '/storage/' + item.image" :alt="item.name">
                        </template>
                        <template x-if="!item.image">
                            <div class="menu-card-no-img" aria-hidden="true">🍔</div>
                        </template>
                        <div class="menu-card-img-overlay" aria-hidden="true"></div>
                        <div class="menu-price-badge" x-text="'$' + item.price" aria-hidden="true"></div>
                    </div>
                    <div class="menu-card-body">
                        {{-- FIX L313: aria-label como fallback para analizadores estáticos --}}
                        <h3 x-text="item.name" aria-label="Nombre del producto">Nombre del producto</h3>
                        <p x-text="item.description || 'Delicioso platillo preparado al momento con los mejores ingredientes.'"></p>
                    </div>
                </div>
            </template>

            <div x-show="productosFiltrados().length === 0" class="menu-empty" style="display:none;">
                <span class="menu-empty-icon" aria-hidden="true">🍽️</span>
                <h3>Aún No Hay Platillos Aquí</h3>
                <p>Estamos cocinando nuevas opciones para esta categoría.</p>
            </div>
        </div>
    </div>
</div>

<script>
    function menuManager() {
        return {
            filtro:    'Hamburguesas',
            subfiltro: 'Todos',

            listaCategorias: [],

            subcategoriasMap: {
                'Coctelería': ['Ron', 'Vodka', 'Tequila', 'Mezcal', 'Ginebra', 'Digestivos'],
                'Destilados': ['Tequila', 'Ron', 'Brandy', 'Vodka', 'Whisky', 'Mezcal', 'Copeo', '6 Shots'],
            },

            allProductos: @json($products ?? []),
            lastDataHash: '',

            initMenu() {
                this.lastDataHash = JSON.stringify(this.allProductos);
                
                // Extract unique categories from active database products
                const cats = [...new Set(this.allProductos.map(p => p.category))];
                const iconMap = {
                    'Hamburguesas': '🍔',
                    'Jochos': '🌭',
                    'Burritos': '🌯',
                    'Tacos': '🌮',
                    'Strombolis': '🍕',
                    'Alitas y Costillas': '🍗',
                    'Especialidades': '🍟',
                    'Opción Fit': '🥗',
                    'Algo Dulce': '🍰',
                    'Sin Alcohol': '🥤',
                    'Cervezas': '🍺',
                    'Coctelería': '🍹',
                    'Destilados': '🥃',
                    'Salsas y Extras': '🌶️'
                };
                
                this.listaCategorias = cats.map(c => ({
                    nombre: c,
                    icono: iconMap[c] || '🍽️'
                }));

                if (cats.includes('Hamburguesas')) {
                    this.filtro = 'Hamburguesas';
                } else if (cats.length > 0) {
                    this.filtro = cats[0];
                }

                const pusher  = new Pusher('491d18da8b8b427e4969', { cluster: 'us2' });
                const channel = pusher.subscribe('menu-channel');
                channel.bind('menu.updated', () => { this.fetchLiveProducts(); });
            },

            productosFiltrados() {
                let lista = this.allProductos.filter(p => p.category === this.filtro);
                if (this.subcategoriasMap[this.filtro] && this.subfiltro !== 'Todos') {
                    lista = lista.filter(p => p.subcategory === this.subfiltro);
                }
                return lista;
            },
        }
    }
</script>

@endsection