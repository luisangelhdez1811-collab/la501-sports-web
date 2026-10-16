@extends('layouts.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Barlow:wght@400;500&display=swap');

    .promo-page {
        --or:   #F97316;
        --or2:  #EA580C;
        --gn:   #16A34A;
        --gn2:  #22C55E;

        /* dark */
        --bg:       #0f0d0a;
        --bg-card:  #161310;
        --bg-card2: #1e1a14;
        --txt:      #FFFFFF;
        --txt-sub:  #A8A29E;
        --bdr:      rgba(255,255,255,0.07);
        --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-dark.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-dark.svg')) }}');
    }

    :root:not(.dark) .promo-page {
        --bg:       #F5F3EF;
        --bg-card:  #FFFFFF;
        --bg-card2: #EBEBEB;
        --txt:      #1A1208;
        --txt-sub:  #6B6560;
        --bdr:      rgba(0,0,0,0.09);
        --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-light.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-light.svg')) }}');
    }

    /* ── HERO ── */
    .promo-hero {
        position: relative;
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border-bottom: 3px solid var(--or);
        padding: 80px 40px 70px;
        text-align: center;
        overflow: hidden;
    }
    .promo-hero::before {
        content: '';
        position: absolute; top: -100px; left: 50%; transform: translateX(-50%);
        width: 700px; height: 350px;
        background: radial-gradient(ellipse, rgba(249,115,22,.18) 0%, transparent 70%);
        pointer-events: none;
    }
    .promo-badge {
        display: inline-block;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 11px; font-weight: 700;
        letter-spacing: 3px; text-transform: uppercase;
        padding: 6px 22px; margin-bottom: 24px;
        clip-path: polygon(10px 0%,100% 0%,calc(100% - 10px) 100%,0% 100%);
    }
    .promo-hero h1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(58px, 11vw, 108px);
        line-height: .93; letter-spacing: 5px;
        color: var(--txt); position: relative; z-index: 1; margin: 0 0 18px;
    }
    .promo-hero h1 span { color: var(--or); }
    .promo-hero p {
        font-size: 17px; color: var(--txt-sub);
        max-width: 540px; margin: 0 auto;
        line-height: 1.75; position: relative; z-index: 1;
    }

    /* ── SECTION ── */
    .promo-section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 80px 32px;
        background-color: var(--bg);
    }

    /* ── DIVIDER ── */
    .promo-divider {
        display: flex; align-items: center; gap: 14px; margin-bottom: 48px;
    }
    .promo-divider span {
        font-family: 'Oswald', sans-serif; font-size: 12px;
        letter-spacing: 4px; text-transform: uppercase;
        color: var(--or); white-space: nowrap;
    }
    .promo-divider::before, .promo-divider::after {
        content: ''; flex: 1; height: 1px;
        background: linear-gradient(to right, transparent, var(--or), transparent);
        opacity: .3;
    }

    /* ── GRID ── */
    .promo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        margin-bottom: 80px;
    }

    /* ── CARD ── */
    .promo-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        transition: transform .3s, border-color .3s, box-shadow .3s;
    }
    .promo-card:hover {
        transform: translateY(-6px);
        border-color: var(--or);
        box-shadow: 0 12px 48px rgba(249,115,22,.15);
    }

    /* top accent line */
    .promo-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(to right, var(--or), var(--gn));
        opacity: 0;
        transition: opacity .3s;
    }
    .promo-card:hover::before { opacity: 1; }

    /* image area */
    .promo-card-img {
        height: 200px;
        position: relative;
        overflow: hidden;
        background: var(--bg-card2);
        display: flex; align-items: center; justify-content: center;
    }
    .promo-card-img img {
        position: absolute; inset: 0;
        width: 100%; height: 100%; object-fit: cover;
        transition: transform .5s ease;
    }
    .promo-card:hover .promo-card-img img { transform: scale(1.08); }
    .promo-card-emoji {
        font-size: 64px;
        transition: transform .4s;
        position: relative; z-index: 1;
        filter: drop-shadow(0 4px 12px rgba(0,0,0,.3));
    }
    .promo-card:hover .promo-card-emoji { transform: scale(1.15) rotate(-4deg); }

    /* overlay on image */
    .promo-card-img-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to top,
            rgba(10,9,6,.7) 0%,
            transparent 55%);
        z-index: 1;
    }

    /* tag chip */
    .promo-tag {
        position: absolute; top: 14px; right: 14px; z-index: 3;
        background: var(--or);
        font-family: 'Oswald', sans-serif;
        font-size: 10px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        color: #fff; padding: 5px 14px;
        clip-path: polygon(6px 0%,100% 0%,calc(100% - 6px) 100%,0% 100%);
        box-shadow: 0 2px 12px rgba(249,115,22,.45);
    }

    /* body */
    .promo-card-body {
        padding: 28px 28px 24px;
        display: flex; flex-direction: column; flex-grow: 1;
    }
    .promo-card-body h3 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 28px; letter-spacing: 2px;
        color: var(--txt); margin: 0 0 8px;
        transition: color .3s;
    }
    .promo-card:hover .promo-card-body h3 { color: var(--or); }
    .promo-card-body p {
        font-size: 14px; color: var(--txt-sub);
        line-height: 1.65; flex-grow: 1; margin: 0 0 20px;
    }

    /* footer row */
    .promo-card-footer {
        display: flex; align-items: center;
        justify-content: space-between;
        gap: 12px; margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid var(--bdr);
    }
    .promo-price {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 36px; letter-spacing: 2px;
        color: var(--gn2); line-height: 1;
    }
    .promo-btn {
        font-family: 'Oswald', sans-serif;
        font-size: 12px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        color: #fff; background: var(--or);
        border: none; cursor: pointer;
        padding: 11px 24px;
        clip-path: polygon(8px 0%,100% 0%,calc(100% - 8px) 100%,0% 100%);
        box-shadow: 0 3px 16px rgba(249,115,22,.4);
        transition: background .2s, transform .2s, box-shadow .2s;
        text-decoration: none;
    }
    .promo-btn:hover {
        background: var(--or2);
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(249,115,22,.5);
    }
    .promo-btn-disabled {
        font-family: 'Oswald', sans-serif;
        font-size: 10px; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase;
        color: var(--txt-sub); background: var(--bg-card2);
        border: 1px dashed var(--bdr);
        padding: 10px 16px; border-radius: 8px;
        text-align: right; line-height: 1.4;
    }
    .promo-btn-disabled strong { display: block; color: var(--or); font-size: 11px; }

    /* ── EMPTY STATE ── */
    .promo-empty {
        grid-column: 1 / -1;
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border: 1px solid var(--bdr);
        border-left: 5px solid var(--or);
        border-radius: 20px;
        padding: 80px 40px;
        text-align: center;
    }
    .promo-empty-icon { font-size: 56px; display: block; margin-bottom: 16px; }
    .promo-empty h3 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 40px; letter-spacing: 3px;
        color: var(--txt); margin: 0 0 10px;
    }
    .promo-empty p { color: var(--txt-sub); font-size: 15px; }

    /* ── CTA GUEST BANNER ── */
    .promo-cta-banner {
        position: relative;
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border: 1px solid var(--bdr);
        border-top: 4px solid var(--gn);
        border-radius: 20px;
        padding: 56px 52px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 32px;
        overflow: hidden;
    }
    .promo-cta-banner::before {
        content: '';
        position: absolute; right: -60px; top: 50%; transform: translateY(-50%);
        width: 320px; height: 320px; border-radius: 50%;
        background: radial-gradient(ellipse, rgba(22,163,74,.12) 0%, transparent 70%);
        pointer-events: none;
    }
    .promo-cta-deco {
        position: absolute; right: 32px; top: 50%; transform: translateY(-50%);
        font-family: 'Bebas Neue', sans-serif;
        font-size: 200px; color: var(--gn);
        opacity: .04; line-height: 1;
        user-select: none; pointer-events: none;
    }
    .promo-cta-text { position: relative; z-index: 1; }
    .promo-cta-text h2 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(28px, 4vw, 44px);
        letter-spacing: 2px; color: var(--txt);
        margin: 0 0 8px; line-height: 1.1;
    }
    .promo-cta-text h2 span { color: var(--or); }
    .promo-cta-text p { color: var(--txt-sub); font-size: 15px; line-height: 1.6; }
    .promo-cta-form {
        display: flex; gap: 10px; flex-wrap: wrap;
        position: relative; z-index: 1;
    }
    .promo-cta-input {
        font-family: 'Barlow', sans-serif;
        font-size: 14px;
        padding: 13px 20px;
        border-radius: 10px;
        background: var(--bg-card2);
        border: 1px solid var(--bdr);
        color: var(--txt);
        outline: none;
        width: 240px;
        transition: border-color .2s, box-shadow .2s;
    }
    .promo-cta-input::placeholder { color: var(--txt-sub); }
    .promo-cta-input:focus {
        border-color: var(--or);
        box-shadow: 0 0 0 3px rgba(249,115,22,.15);
    }
    .promo-cta-submit {
        font-family: 'Oswald', sans-serif;
        font-size: 13px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        color: #fff; background: var(--gn);
        border: none; cursor: pointer;
        padding: 13px 28px;
        clip-path: polygon(8px 0%,100% 0%,calc(100% - 8px) 100%,0% 100%);
        box-shadow: 0 3px 16px rgba(22,163,74,.4);
        transition: background .2s, transform .2s;
    }
    .promo-cta-submit:hover {
        background: var(--gn2); transform: translateY(-2px);
    }

    @media (max-width: 640px) {
        .promo-grid { grid-template-columns: 1fr; }
        .promo-cta-banner { padding: 36px 28px; }
        .promo-cta-deco { display: none; }
        .promo-cta-input { width: 100%; }
    }
</style>

<div class="promo-page" style="background-color: var(--bg);">

    {{-- HERO --}}
    <div class="promo-hero">
        <div class="promo-badge">La 501 Sports Restaurant</div>
        <h1>Promociones<br><span>Especiales</span></h1>
        <p>Aprovecha nuestras ofertas exclusivas para ti y tu familia. ¡No dejes pasar estas oportunidades!</p>
    </div>

    <div class="promo-section">

        <div class="promo-divider"><span>Ofertas Activas</span></div>

        {{-- GRID --}}
        <div class="promo-grid">

            @forelse($promotions as $promo)
            <div class="promo-card">

                {{-- Imagen / Emoji --}}
                <div class="promo-card-img {{ !$promo->image ? 'bg-gradient-to-br ' . $promo->color_gradient : '' }}">
                    @if($promo->image)
                        <img src="{{ (str_starts_with($promo->image, 'http://') || str_starts_with($promo->image, 'https://')) ? $promo->image : asset('storage/' . $promo->image) }}" alt="{{ $promo->title }}">
                        <div class="promo-card-img-overlay"></div>
                    @else
                        <span class="promo-card-emoji">{{ $promo->icon }}</span>
                    @endif

                    @if($promo->tag)
                        <div class="promo-tag">{{ $promo->tag }}</div>
                    @endif
                </div>

                {{-- Cuerpo --}}
                <div class="promo-card-body">
                    <h3>{{ $promo->title }}</h3>
                    <p>{{ $promo->description }}</p>

                    <div class="promo-card-footer">
                        <span class="promo-price">{{ $promo->price_text }}</span>
                        @if(!$promo->sellable_online)
                        <span class="promo-btn-disabled">
                            Solo en el local<strong>Pide con tu mesero</strong>
                        </span>
                        @elseif($promo->isActiveToday())
                        <form action="{{ route('cart.addPromotion') }}" method="POST">
                            @csrf
                            <input type="hidden" name="promotion_id" value="{{ $promo->id }}">
                            <button type="submit" class="promo-btn">Aprovechar</button>
                        </form>
                        @else
                        <span class="promo-btn-disabled">
                            Disponible<strong>{{ $promo->activeDaysLabel() }}</strong>
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            @empty
            <div class="promo-empty">
                <span class="promo-empty-icon">😔</span>
                <h3>Sin Promociones Por Ahora</h3>
                <p>Estamos cocinando nuevas ofertas para ti. ¡Regresa pronto!</p>
            </div>
            @endforelse

        </div>

        {{-- CTA SUSCRIPCIÓN (solo guests) --}}
        @guest
        <div class="promo-cta-banner">
            <div class="promo-cta-deco">501</div>
            <div class="promo-cta-text">
                <h2>¿No quieres perderte<br><span>ninguna oferta?</span></h2>
                @if(session('success'))
                    <p style="color:#4ADE80; font-weight:700;">{{ session('success') }}</p>
                @else
                    <p>Suscríbete para recibir nuestras ofertas flash<br>directamente en tu correo.</p>
                @endif
            </div>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="promo-cta-form">
                @csrf
                <input type="email" name="email" required placeholder="tu@correo.com" class="promo-cta-input">
                <button type="submit" class="promo-cta-submit">Unirme →</button>
            </form>
        </div>
        @endguest

    </div>
</div>

@endsection