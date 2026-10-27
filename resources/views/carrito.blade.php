@extends('layouts.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Barlow:wght@400;500&display=swap');

    .cart-page {
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
    }
    :root:not(.dark) .cart-page {
        --bg:       #F5F3EF;
        --bg-card:  #FFFFFF;
        --bg-card2: #EBEBEB;
        --txt:      #1A1208;
        --txt-sub:  #6B6560;
        --bdr:      rgba(0,0,0,0.09);
    }

    /* ── TOPBAR ── */
    .cart-topbar {
        position: relative;
        background: var(--bg-card);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='98'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23F97316' fill-opacity='0.04'%3E%3Cpath d='M28 66L0 49V16L28 0l28 16v33L28 66zm0-2l26-15V18L28 2 2 18v31l26 15zM56 98L28 81 0 98V81L0 66l28-16 28 16v15L28 98l28-17V98z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        border-bottom: 3px solid var(--or);
        padding: 32px 32px 28px;
        overflow: hidden;
    }
    .cart-topbar::before {
        content: '';
        position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
        width: 500px; height: 180px;
        background: radial-gradient(ellipse, rgba(249,115,22,.14) 0%, transparent 70%);
        pointer-events: none;
    }
    .cart-topbar-inner {
        max-width: 860px; margin: 0 auto;
        display: flex; align-items: flex-end;
        justify-content: space-between; gap: 16px;
        position: relative; z-index: 1;
    }
    .cart-topbar-badge {
        display: inline-block;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 10px; font-weight: 700;
        letter-spacing: 3px; text-transform: uppercase;
        padding: 4px 16px; margin-bottom: 10px;
        clip-path: polygon(8px 0%,100% 0%,calc(100% - 8px) 100%,0% 100%);
    }
    .cart-topbar-text h1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(42px, 7vw, 68px);
        line-height: .95; letter-spacing: 4px;
        color: var(--txt); margin: 0;
    }
    .cart-topbar-text h1 span { color: var(--or); }
    .cart-topbar-text p {
        font-size: 14px; color: var(--txt-sub); margin: 8px 0 0;
    }
    .cart-count-badge {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 48px; letter-spacing: 2px;
        color: var(--gn2); line-height: 1;
        flex-shrink: 0;
    }
    .cart-count-badge span {
        font-family: 'Oswald', sans-serif;
        font-size: 11px; letter-spacing: 2px;
        color: var(--txt-sub); display: block; margin-top: 2px;
        text-transform: uppercase;
    }

    /* ── MAIN ── */
    .cart-main {
        max-width: 860px; margin: 0 auto;
        padding: 36px 32px 80px;
        background-color: var(--bg);
    }

    /* alerts */
    .cart-alert {
        border-radius: 10px; padding: 12px 16px;
        font-size: 13px; font-weight: 600;
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 20px;
    }
    .cart-alert.success {
        background: rgba(22,163,74,.1); border: 1px solid rgba(34,197,94,.25);
        border-left: 4px solid var(--gn2); color: var(--gn2);
    }
    .cart-alert.error {
        background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);
        border-left: 4px solid #F87171; color: #F87171;
    }

    /* ── CART CARD ── */
    .cart-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 14px;
        overflow: hidden;
    }
    .cart-card::before {
        content: '';
        display: block; height: 3px;
        background: linear-gradient(to right, var(--or), var(--gn));
    }

    /* items list */
    .cart-items { padding: 0 20px; }
    .cart-item {
        display: flex; align-items: center;
        gap: 14px;
        padding: 16px 0;
        border-bottom: 1px solid var(--bdr);
        transition: background .15s;
    }
    .cart-item:last-child { border-bottom: none; }
    .cart-item.promo { background: rgba(22,163,74,.04); }

    /* thumbnail */
    .cart-item-img {
        width: 60px; height: 60px; border-radius: 9px;
        overflow: hidden; background: var(--bg-card2);
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 28px;
    }
    .cart-item-img { position: relative; }
    .cart-item-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }

    /* info */
    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name {
        font-family: 'Oswald', sans-serif;
        font-size: 15px; font-weight: 700; letter-spacing: .3px;
        color: var(--txt); margin: 0 0 3px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        display: flex; align-items: center; gap: 6px;
    }
    .cart-item-promo-badge {
        display: inline-flex; align-items: center; gap: 3px;
        font-family: 'Oswald', sans-serif;
        font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
        background: var(--gn); color: #fff;
        padding: 2px 7px; border-radius: 100px; flex-shrink: 0;
    }
    .cart-item-promo-includes {
        font-size: 11px; color: var(--gn2); margin: 2px 0 4px; font-weight: 500;
    }
    .cart-item-price {
        font-size: 12px; color: var(--txt-sub);
    }

    /* qty controls */
    .cart-qty {
        display: flex; align-items: center; gap: 6px; flex-shrink: 0;
    }
    .cart-qty-btn {
        width: 32px; height: 32px; border-radius: 7px; border: none;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 16px; font-weight: 700; line-height: 1;
        transition: background .15s, transform .1s;
    }
    .cart-qty-btn:hover { transform: scale(1.08); }
    .cart-qty-btn.minus {
        background: rgba(239,68,68,.1); color: #F87171;
        border: 1px solid rgba(239,68,68,.2);
    }
    .cart-qty-btn.minus:hover { background: rgba(239,68,68,.2); }
    .cart-qty-btn.plus {
        background: var(--gn); color: #fff;
        box-shadow: 0 2px 8px rgba(22,163,74,.35);
    }
    .cart-qty-btn.plus:hover { background: var(--gn2); }
    .cart-qty-num {
        width: 36px; text-align: center;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 20px; letter-spacing: 1px;
        color: var(--txt);
    }

    /* subtotal + remove */
    .cart-item-right {
        display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    }
    .cart-subtotal {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 22px; letter-spacing: 1px;
        color: var(--gn2); min-width: 70px; text-align: right;
    }
    .cart-remove-btn {
        width: 30px; height: 30px; border-radius: 7px;
        background: var(--bg-card2); border: 1px solid var(--bdr);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: var(--txt-sub); transition: background .15s, color .15s, border-color .15s;
    }
    .cart-remove-btn:hover {
        background: rgba(239,68,68,.1); color: #F87171;
        border-color: rgba(239,68,68,.25);
    }
    .cart-remove-btn svg { width: 13px; height: 13px; }

    /* ── FOOTER ── */
    .cart-footer {
        background: var(--bg-card2);
        border-top: 1px solid var(--bdr);
        padding: 20px 24px;
    }
    .cart-total-row {
        display: flex; align-items: baseline;
        justify-content: space-between; margin-bottom: 16px;
    }
    .cart-total-label {
        font-family: 'Oswald', sans-serif;
        font-size: 12px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        color: var(--txt-sub);
    }
    .cart-total-amount {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 42px; letter-spacing: 2px;
        color: var(--txt); line-height: 1;
    }
    .cart-total-amount span { color: var(--or); }

    /* benefit banner */
    .cart-benefit {
        border-radius: 9px; padding: 10px 14px;
        font-size: 12px; font-weight: 600;
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 16px;
    }
    .cart-benefit.logged {
        background: rgba(37,99,235,.08); border: 1px solid rgba(37,99,235,.2);
        color: #60A5FA;
    }
    .cart-benefit.guest {
        background: rgba(234,179,8,.07); border: 1px solid rgba(234,179,8,.2);
        color: #FCD34D;
    }
    .cart-benefit a { font-weight: 700; text-decoration: underline; color: inherit; }
    .cart-benefit svg { width: 14px; height: 14px; flex-shrink: 0; opacity: .7; }

    /* action buttons */
    .cart-actions { display: flex; gap: 10px; flex-wrap: wrap; }

    .cart-btn-clear {
        padding: 12px 20px;
        border-radius: 9px;
        background: transparent;
        border: 1px solid var(--bdr);
        font-family: 'Oswald', sans-serif;
        font-size: 13px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;
        color: var(--txt-sub); cursor: pointer;
        transition: border-color .15s, color .15s;
    }
    .cart-btn-clear:hover { border-color: #F87171; color: #F87171; }

    .cart-btn-pay {
        flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 16px 28px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--gn) 0%, var(--gn2) 100%);
        font-family: 'Oswald', sans-serif;
        font-size: 16px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;
        color: #fff; text-decoration: none;
        box-shadow: 0 4px 18px rgba(22,163,74,.35);
        transition: background .2s, transform .15s, box-shadow .2s;
        position: relative;
        overflow: hidden;
        animation: cart-pulse-glow 2s infinite;
    }
    .cart-btn-pay:hover {
        background: linear-gradient(135deg, var(--gn2) 0%, #4ade80 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(34,197,94,.5);
    }
    .cart-btn-pay:active {
        transform: scale(0.98) translateY(0);
    }
    .cart-btn-pay svg { width: 16px; height: 16px; }

    @keyframes cart-pulse-glow {
        0% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.6);
        }
        70% {
            box-shadow: 0 0 0 14px rgba(34, 197, 94, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
        }
    }

    /* ── EMPTY ── */
    .cart-empty {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 14px; overflow: hidden;
        text-align: center;
    }
    .cart-empty::before {
        content: '';
        display: block; height: 3px;
        background: linear-gradient(to right, var(--or), var(--gn));
    }
    .cart-empty-inner { padding: 64px 32px; }
    .cart-empty-icon {
        width: 72px; height: 72px; border-radius: 16px;
        background: var(--bg-card2); border: 1px solid var(--bdr);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px; font-size: 36px;
    }
    .cart-empty h2 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 42px; letter-spacing: 3px;
        color: var(--txt); margin: 0 0 8px;
    }
    .cart-empty p { font-size: 14px; color: var(--txt-sub); margin: 0 0 28px; }
    .cart-empty-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 13px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
        padding: 12px 28px; border-radius: 9px; text-decoration: none;
        box-shadow: 0 3px 16px rgba(249,115,22,.4);
        clip-path: polygon(10px 0%,100% 0%,calc(100% - 10px) 100%,0% 100%);
        transition: background .2s;
    }
    .cart-empty-btn:hover { background: var(--or2); }

    /* ── RESPONSIVE ── */
    @media (max-width: 600px) {
        .cart-topbar { padding: 24px 20px 20px; }
        .cart-count-badge { display: none; }
        .cart-main { padding: 24px 16px 60px; }
        .cart-items { padding: 0 14px; }
        .cart-item { gap: 10px; }
        .cart-subtotal { display: none; }
        .cart-footer { padding: 16px; }
    }
</style>

<div class="cart-page" style="background-color: var(--bg);">

    {{-- TOPBAR --}}
    <div class="cart-topbar">
        <div class="cart-topbar-inner">
            <div class="cart-topbar-text">
                <div class="cart-topbar-badge">La 501 Sports · Pedido</div>
                <h1>Tu <span>Orden</span></h1>
                <p>Revisa tu pedido antes de proceder al pago</p>
            </div>
            @php $cartCount = count((array) session('cart')); @endphp
            @if($cartCount > 0)
            <div class="cart-count-badge">
                {{ $cartCount }}
                <span>{{ $cartCount === 1 ? 'Platillo' : 'Platillos' }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="cart-main">

        {{-- Alerts --}}
        @if(session('success'))
        <div class="cart-alert success">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="cart-alert error">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        @if(count((array) session('cart')) > 0)

        <div class="cart-card">

            {{-- Items --}}
            <div class="cart-items">
                @foreach(session('cart') as $id => $details)
                <div class="cart-item {{ !empty($details['is_promotion']) ? 'promo' : '' }}">

                    {{-- Imagen --}}
                    {{-- Si la imagen no carga, se quita y queda el ícono de respaldo --}}
                    <div class="cart-item-img">
                        <span aria-hidden="true">{{ !empty($details['is_promotion']) ? '🎉' : '🍔' }}</span>
                        @if(!empty($details['image']))
                            <img src="{{ (str_starts_with($details['image'], 'http://') || str_starts_with($details['image'], 'https://')) ? $details['image'] : asset('storage/' . $details['image']) }}" alt="{{ $details['name'] }}" onerror="this.remove()">
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="cart-item-info">
                        <p class="cart-item-name">
                            {{ $details['name'] }}
                            @if(!empty($details['is_promotion']))
                                <span class="cart-item-promo-badge">🎉 Promo</span>
                            @endif
                        </p>

                        @if(!empty($details['options_summary']))
                            <p class="cart-item-options" style="font-size: 11px; color: var(--txt-sub); margin: 2px 0 4px; font-weight: 600;">
                                {{ $details['options_summary'] }}
                            </p>
                        @endif

                        @if(!empty($details['is_promotion']))
                            <p class="cart-item-promo-includes">
                                <strong>Incluye:</strong> {{ $details['promotion_includes'] ?: 'Paquete de promoción' }}
                            </p>
                        @elseif(!empty($details['excluded_ingredients']))
                            <p class="cart-item-exclusions" style="font-size: 11px; color: var(--or); margin: 2px 0 4px; font-weight: 500;">
                                <strong>Sin:</strong> {{ implode(', ', $details['excluded_ingredients']) }}
                            </p>
                        @endif

                        @if(!empty($details['notes']))
                            <p class="cart-item-notes" style="font-size: 11px; color: #3B82F6; margin: 2px 0 4px; font-weight: 500;">
                                📝 {{ $details['notes'] }}
                            </p>
                        @endif

                        <p class="cart-item-price">${{ number_format($details['price'], 2) }} c/u</p>
                    </div>

                    {{-- Qty --}}
                    <div class="cart-qty">
                        <form action="{{ route('cart.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $id }}">
                            <input type="hidden" name="quantity" value="{{ $details['quantity'] - 1 }}">
                            <button type="submit" class="cart-qty-btn minus">−</button>
                        </form>

                        <span class="cart-qty-num">{{ $details['quantity'] }}</span>

                        <form action="{{ route('cart.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $id }}">
                            <input type="hidden" name="quantity" value="{{ $details['quantity'] + 1 }}">
                            <button type="submit" class="cart-qty-btn plus">+</button>
                        </form>
                    </div>

                    {{-- Subtotal + Remove --}}
                    <div class="cart-item-right">
                        <span class="cart-subtotal">${{ number_format($details['price'] * $details['quantity'], 2) }}</span>
                        <form action="{{ route('cart.remove') }}" method="POST">
                            @csrf @method('DELETE')
                            <input type="hidden" name="id" value="{{ $id }}">
                            <button type="submit" class="cart-remove-btn" title="Quitar">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="cart-footer">
                <div class="cart-total-row">
                    <span class="cart-total-label">Total a Pagar</span>
                    <span class="cart-total-amount"><span>$</span>{{ number_format($total, 2) }}</span>
                </div>

                @auth
                <div class="cart-benefit logged">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    Acumularás <strong>{{ floor($total / 10) }} Puntos La 501</strong> con esta compra.
                </div>
                @else
                <div class="cart-benefit guest">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <a href="{{ route('login') }}">Inicia sesión</a> para acumular puntos con esta compra.
                </div>
                @endauth

                <div class="cart-actions">
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="cart-btn-clear">Vaciar Carrito</button>
                    </form>
                    <a href="{{ route('checkout.index') }}" class="cart-btn-pay">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Proceder al Pago
                    </a>
                </div>
            </div>
        </div>

        {{-- SECCIÓN DE RECOMENDACIONES ADAPTATIVAS (DESLOGUEADOS VS LOGUEADOS) --}}
        @if(!empty($recommendations['products']) && count($recommendations['products']) > 0)
        <div style="margin-top: 24px; background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 14px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div>
                    @if(!empty($recommendations['badge']))
                    <span style="display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #F97316; background: rgba(249,115,22,0.12); padding: 3px 10px; border-radius: 6px; border: 1px solid rgba(249,115,22,0.2);">
                        {{ $recommendations['badge'] }}
                    </span>
                    @endif
                    <h3 style="font-family: 'Oswald', sans-serif; font-size: 16px; font-weight: 700; color: var(--txt); margin: 6px 0 0; letter-spacing: 0.5px;">
                        {{ $recommendations['title'] ?? 'Sugerencias recomendadas' }}
                    </h3>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">
                @foreach($recommendations['products'] as $recProduct)
                    <div style="background: var(--bg-card2); border: 1px solid var(--bdr); border-radius: 10px; padding: 10px; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.15s, border-color 0.15s;">
                        <div>
                            <div style="width: 100%; height: 85px; border-radius: 7px; overflow: hidden; margin-bottom: 8px; background: var(--bg-card); display: flex; align-items: center; justify-content: center;">
                                @if($recProduct->image)
                                    <img src="{{ str_starts_with($recProduct->image, 'http') ? $recProduct->image : asset('storage/' . $recProduct->image) }}" alt="{{ $recProduct->name }}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.remove()">
                                @else
                                    <span style="font-size: 28px;">🍔</span>
                                @endif
                            </div>
                            <h4 style="font-family: 'Oswald', sans-serif; font-size: 13px; font-weight: 700; color: var(--txt); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $recProduct->name }}
                            </h4>
                            <p style="font-size: 12px; font-weight: 700; color: #22C55E; margin: 0 0 8px;">
                                ${{ number_format($recProduct->price, 2) }}
                            </p>
                        </div>
                        <button type="button" onclick="recAbrirPersonalizar({{ $recProduct->id }})" style="width: 100%; padding: 6px; background: #F97316; border: none; border-radius: 6px; color: #ffffff; font-family: 'Oswald', sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; transition: background 0.15s;">
                            + Agregar
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Modal de personalización para las recomendaciones --}}
        <style>
            .rec-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; display: none; align-items: center; justify-content: center; padding: 16px; }
            .rec-overlay.open { display: flex; }
            .rec-modal { background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 16px; width: 100%; max-width: 440px; max-height: 88vh; overflow-y: auto; }
            .rec-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid var(--bdr); }
            .rec-modal-header h3 { font-family: 'Oswald', sans-serif; font-size: 17px; font-weight: 700; color: var(--txt); margin: 0; }
            .rec-modal-close { background: none; border: none; color: var(--txt-sub); font-size: 22px; cursor: pointer; line-height: 1; }
            .rec-modal-body { padding: 18px 20px; }
            .rec-ing-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--txt-sub); margin: 0 0 10px; }
            .rec-ing-list { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 18px; }
            .rec-ing-item { display: flex; align-items: center; gap: 8px; background: var(--bg-card2); border: 1px solid var(--bdr); border-radius: 8px; padding: 9px 10px; font-size: 12px; color: var(--txt); }
            .rec-textarea { width: 100%; box-sizing: border-box; background: var(--bg-card2); border: 1px solid var(--bdr); border-radius: 8px; padding: 10px 12px; color: var(--txt); font-size: 13px; resize: none; font-family: inherit; }
            .rec-note-help { font-size: 11px; color: var(--txt-sub); margin: 6px 0 0; }
            .rec-modal-footer { display: flex; gap: 10px; padding: 16px 20px; border-top: 1px solid var(--bdr); }
            .rec-btn-cancel, .rec-btn-confirm { flex: 1; padding: 12px; border-radius: 8px; border: none; font-family: 'Oswald', sans-serif; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; }
            .rec-btn-cancel { background: var(--bg-card2); color: var(--txt); border: 1px solid var(--bdr); }
            .rec-btn-confirm { background: #16A34A; color: #fff; }
        </style>

        <div class="rec-overlay" id="rec-overlay">
            <div class="rec-modal">
                <div class="rec-modal-header">
                    <h3 id="rec-modal-title">Personalizar</h3>
                    <button type="button" class="rec-modal-close" onclick="recCerrarPersonalizar()">&times;</button>
                </div>
                <div class="rec-modal-body">
                    <div id="rec-ing-wrapper">
                        <p class="rec-ing-title">Ingredientes a incluir:</p>
                        <div class="rec-ing-list" id="rec-ing-list"></div>
                    </div>
                    <p class="rec-ing-title">Comentarios / Extra:</p>
                    <textarea id="rec-comment" class="rec-textarea" rows="3" maxlength="100" placeholder="Ej: papas extra, salsa extra, más picante, sin sal…"></textarea>
                    <p class="rec-note-help">Cualquier instrucción especial o algo extra que quieras pedir.</p>
                </div>
                <div class="rec-modal-footer">
                    <button type="button" class="rec-btn-cancel" onclick="recCerrarPersonalizar()">Cancelar</button>
                    <button type="button" class="rec-btn-confirm" onclick="recConfirmarPersonalizar()">Agregar al Pedido</button>
                </div>
            </div>
        </div>

        <form id="rec-add-form" action="{{ route('cart.add') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="id" id="rec-input-id">
            <input type="hidden" name="name" id="rec-input-name">
            <input type="hidden" name="price" id="rec-input-price">
            <input type="hidden" name="image" id="rec-input-image">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="notes" id="rec-input-notes">
            <div id="rec-excluded-container"></div>
        </form>

        @php
            $recProductosParaJs = $recommendations['products']->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'image' => $p->image,
                    'ingredientes' => $p->ingredientes->pluck('nombre'),
                    // Lleva presentación o salsas/papas por elegir: se elige en el menú
                    'elegir' => $p->variants()->exists() || $p->optionGroups()->exists(),
                ];
            });
        @endphp

        <script>
            const recProductos = @json($recProductosParaJs);

            let recProductoActual = null;

            function recAbrirPersonalizar(id) {
                const producto = recProductos.find(p => p.id === id);
                if (!producto) return;
                if (producto.elegir) {
                    window.location.href = '{{ route('pedido') }}?producto=' + producto.id;
                    return;
                }
                recProductoActual = producto;

                document.getElementById('rec-modal-title').textContent = 'Personalizar ' + producto.name;
                document.getElementById('rec-comment').value = '';

                const wrapper = document.getElementById('rec-ing-wrapper');
                const lista = document.getElementById('rec-ing-list');
                lista.innerHTML = '';

                if (producto.ingredientes && producto.ingredientes.length > 0) {
                    wrapper.style.display = '';
                    producto.ingredientes.forEach(nombre => {
                        const label = document.createElement('label');
                        label.className = 'rec-ing-item';
                        label.innerHTML = '<input type="checkbox" checked data-ing="' + nombre.replace(/"/g, '&quot;') + '"> <span>' + nombre + '</span>';
                        lista.appendChild(label);
                    });
                    document.getElementById('rec-overlay').classList.add('open');
                } else {
                    // Sin ingredientes que personalizar: se agrega directo, sin abrir el modal.
                    recSubmit(producto, [], '');
                }
            }

            function recCerrarPersonalizar() {
                document.getElementById('rec-overlay').classList.remove('open');
                recProductoActual = null;
            }

            function recConfirmarPersonalizar() {
                if (!recProductoActual) return;

                const excluidos = [];
                document.querySelectorAll('#rec-ing-list input[type=checkbox]').forEach(cb => {
                    if (!cb.checked) excluidos.push(cb.dataset.ing);
                });
                const comentario = document.getElementById('rec-comment').value.trim();

                recSubmit(recProductoActual, excluidos, comentario);
            }

            function recSubmit(producto, excluidos, comentario) {
                document.getElementById('rec-input-id').value = producto.id;
                document.getElementById('rec-input-name').value = producto.name;
                document.getElementById('rec-input-price').value = producto.price;
                document.getElementById('rec-input-image').value = producto.image || '';
                document.getElementById('rec-input-notes').value = comentario;

                const container = document.getElementById('rec-excluded-container');
                container.innerHTML = '';
                excluidos.forEach(nombre => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'excluded_ingredients[]';
                    input.value = nombre;
                    container.appendChild(input);
                });

                document.getElementById('rec-add-form').submit();
            }
        </script>
        @endif

        @else

        <div class="cart-empty">
            <div class="cart-empty-inner">
                <div class="cart-empty-icon">🛒</div>
                <h2>Carrito Vacío</h2>
                <p>Aún no has agregado nada delicioso a tu pedido.</p>
                <a href="/a-domicilio" class="cart-empty-btn">Ver Menú</a>
            </div>
        </div>

        @endif
    </div>
</div>

@endsection