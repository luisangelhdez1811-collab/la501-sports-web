@extends('layouts.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Barlow:wght@400;500&display=swap');

    .nov-page {
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
    :root:not(.dark) .nov-page {
        --bg:       #F5F3EF;
        --bg-card:  #FFFFFF;
        --bg-card2: #EBEBEB;
        --txt:      #1A1208;
        --txt-sub:  #6B6560;
        --bdr:      rgba(0,0,0,0.09);
        --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-light.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-light.svg')) }}');
    }

    /* ── HERO ── */
    .nov-hero {
        position: relative;
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border-bottom: 3px solid var(--or);
        padding: 80px 40px 70px;
        text-align: center;
        overflow: hidden;
    }
    .nov-hero::before {
        content: '';
        position: absolute; top: -100px; left: 50%; transform: translateX(-50%);
        width: 700px; height: 350px;
        background: radial-gradient(ellipse, rgba(249,115,22,.18) 0%, transparent 70%);
        pointer-events: none;
    }
    .nov-badge {
        display: inline-block;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 11px; font-weight: 700;
        letter-spacing: 3px; text-transform: uppercase;
        padding: 6px 22px; margin-bottom: 24px;
        clip-path: polygon(10px 0%,100% 0%,calc(100% - 10px) 100%,0% 100%);
    }
    .nov-hero h1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(58px, 11vw, 108px);
        line-height: .93; letter-spacing: 5px;
        color: var(--txt); position: relative; z-index: 1; margin: 0 0 18px;
    }
    .nov-hero h1 span { color: var(--or); }
    .nov-hero p {
        font-size: 17px; color: var(--txt-sub);
        max-width: 560px; margin: 0 auto;
        line-height: 1.75; position: relative; z-index: 1;
    }

    /* ── MAIN ── */
    .nov-main {
        max-width: 1200px;
        margin: 0 auto;
        padding: 80px 32px;
        background-color: var(--bg);
        display: flex;
        flex-direction: column;
        gap: 80px;
    }

    /* ── SECTION HEADER ── */
    .nov-sec-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 40px;
    }
    .nov-sec-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-shrink: 0;
    }
    .nov-sec-bar {
        width: 5px; height: 44px;
        border-radius: 3px;
        flex-shrink: 0;
    }
    .bar-avisos   { background: linear-gradient(to bottom, #FBBF24, #F59E0B); }
    .bar-deportes { background: linear-gradient(to bottom, var(--or), var(--or2)); }
    .bar-eventos  { background: linear-gradient(to bottom, var(--gn), var(--gn2)); }

    .nov-sec-header h2 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 38px; letter-spacing: 3px;
        color: var(--txt); margin: 0; line-height: 1;
    }
    .nov-sec-divider {
        flex: 1; height: 1px;
        background: linear-gradient(to right, var(--bdr), transparent);
    }

    /* ── GRID ── */
    .nov-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    /* ── CARD ── */
    .nov-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 18px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        transition: transform .3s, border-color .3s, box-shadow .3s;
    }
    .nov-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0;
        height: 3px; opacity: 0;
        transition: opacity .3s;
    }
    .nov-card.avisos::before   { background: linear-gradient(to right, #FBBF24, #F59E0B); }
    .nov-card.deportes::before { background: linear-gradient(to right, var(--or), var(--or2)); }
    .nov-card.eventos::before  { background: linear-gradient(to right, var(--gn), var(--gn2)); }

    .nov-card:hover { transform: translateY(-6px); }
    .nov-card:hover::before { opacity: 1; }

    .nov-card.avisos:hover   { border-color: #FBBF24; box-shadow: 0 12px 48px rgba(251,191,36,.15); }
    .nov-card.deportes:hover { border-color: var(--or); box-shadow: 0 12px 48px rgba(249,115,22,.15); }
    .nov-card.eventos:hover  { border-color: var(--gn2); box-shadow: 0 12px 48px rgba(34,197,94,.15); }

    /* image */
    .nov-card-img {
        height: 200px;
        position: relative;
        overflow: hidden;
        background: var(--bg-card2);
    }
    .nov-card-img img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        transition: transform .5s ease;
    }
    .nov-card:hover .nov-card-img img { transform: scale(1.08); }
    .nov-card-img-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(10,9,6,.65) 0%, transparent 55%);
        z-index: 1;
    }

    /* chip */
    .nov-chip {
        position: absolute; top: 14px; left: 14px; z-index: 3;
        font-family: 'Oswald', sans-serif;
        font-size: 10px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        padding: 5px 14px; color: #fff;
        clip-path: polygon(6px 0%,100% 0%,calc(100% - 6px) 100%,0% 100%);
    }
    .chip-avisos   { background: #D97706; box-shadow: 0 2px 10px rgba(217,119,6,.4); }
    .chip-deportes { background: var(--or); box-shadow: 0 2px 10px rgba(249,115,22,.4); }
    .chip-eventos  { background: var(--gn); box-shadow: 0 2px 10px rgba(22,163,74,.4); }

    /* body */
    .nov-card-body {
        padding: 24px 24px 20px;
        display: flex; flex-direction: column; flex-grow: 1;
    }
    .nov-card-date {
        display: flex; align-items: center; gap: 6px;
        font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
        color: var(--txt-sub); margin-bottom: 10px;
    }
    .nov-card-date svg { flex-shrink: 0; opacity: .55; }

    .nov-card-body h3 {
        font-family: 'Oswald', sans-serif;
        font-size: 20px; font-weight: 700; letter-spacing: .5px;
        color: var(--txt); margin: 0 0 10px; line-height: 1.25;
        display: -webkit-box; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden;
        transition: color .25s;
    }
    .nov-card.avisos:hover   h3 { color: #FBBF24; }
    .nov-card.deportes:hover h3 { color: var(--or); }
    .nov-card.eventos:hover  h3 { color: var(--gn2); }

    .nov-card-body p {
        font-size: 14px; color: var(--txt-sub);
        line-height: 1.65; flex-grow: 1;
        margin: 0;
    }
    .nov-clamp {
        display: -webkit-box; -webkit-line-clamp: 3;
        -webkit-box-orient: vertical; overflow: hidden;
    }
    .nov-readmore {
        align-self: flex-start;
        background: none; border: none; cursor: pointer;
        font-family: 'Oswald', sans-serif;
        font-size: 11px; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase;
        padding: 8px 0 0; margin: 0;
    }
    .nov-card.avisos   .nov-readmore { color: #FBBF24; }
    .nov-card.deportes .nov-readmore { color: var(--or); }
    .nov-card.eventos  .nov-readmore { color: var(--gn2); }
    .nov-readmore:hover { text-decoration: underline; }

    /* fallback img */
    .nov-card-img-fallback {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        background: var(--bg-card2);
        color: var(--txt-sub); font-size: 13px;
    }

    /* ── EMPTY STATE ── */
    .nov-empty {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-left: 5px solid var(--or);
        border-radius: 18px;
        padding: 80px 40px;
        text-align: center;
        background-image: var(--brand-pattern);
    }
    .nov-empty-icon { font-size: 54px; display: block; margin-bottom: 16px; }
    .nov-empty h3 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 38px; letter-spacing: 3px;
        color: var(--txt); margin: 0 0 10px;
    }
    .nov-empty p { color: var(--txt-sub); font-size: 15px; }

    @media (max-width: 640px) {
        .nov-grid  { grid-template-columns: 1fr; }
        .nov-hero  { padding: 60px 24px; }
        .nov-main  { padding: 50px 20px; gap: 56px; }
    }
</style>

<div class="nov-page" style="background-color: var(--bg);">

    {{-- HERO --}}
    <div class="nov-hero">
        <div class="nov-badge">La 501 Sports Restaurant</div>
        <h1>Novedades<br><span>La 501</span></h1>
        <p>Entérate de lo último en deportes, avisos importantes y nuestros próximos eventos.</p>
    </div>

    <div class="nov-main">

        {{-- ══ AVISOS ══ --}}
        @if($avisos->count() > 0)
        <section>
            <div class="nov-sec-header">
                <div class="nov-sec-header-left">
                    <div class="nov-sec-bar bar-avisos" aria-hidden="true"></div>
                    <h2>⚠️ Avisos Importantes</h2>
                </div>
                <div class="nov-sec-divider" aria-hidden="true"></div>
            </div>
            <div class="nov-grid">
                @foreach($avisos as $item)
                <article class="nov-card avisos">
                    <div class="nov-card-img">
                        <div x-data="{ imgError: false }">
                            <img x-show="!imgError"
                                 src="{{ (str_starts_with($item->image, 'http://') || str_starts_with($item->image, 'https://')) ? $item->image : asset('storage/' . $item->image) }}"
                                 alt="{{ $item->title }}"
                                 x-on:error="imgError = true">
                            <div x-show="imgError" class="nov-card-img-fallback" aria-hidden="true">
                                Sin imagen
                            </div>
                        </div>
                        <div class="nov-card-img-overlay" aria-hidden="true"></div>
                        <div class="nov-chip chip-avisos">{{ $item->category }}</div>
                    </div>
                    <div class="nov-card-body" x-data="{ expanded: false }">
                        <div class="nov-card-date">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <time datetime="{{ $item->created_at->toDateString() }}">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('d M, Y') }}
                            </time>
                        </div>
                        <h3>{{ $item->title }}</h3>
                        <p :class="expanded ? '' : 'nov-clamp'">{{ $item->content }}</p>
                        @if(mb_strlen($item->content) > 180)
                        <button type="button" class="nov-readmore" @click="expanded = !expanded" x-text="expanded ? 'Leer menos' : 'Leer más'"></button>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ══ DEPORTES ══ --}}
        @if($deportes->count() > 0)
        <section>
            <div class="nov-sec-header">
                <div class="nov-sec-header-left">
                    <div class="nov-sec-bar bar-deportes" aria-hidden="true"></div>
                    <h2>⚽ Deportes</h2>
                </div>
                <div class="nov-sec-divider" aria-hidden="true"></div>
            </div>
            <div class="nov-grid">
                @foreach($deportes as $item)
                <article class="nov-card deportes">
                    <div class="nov-card-img">
                        {{-- FIX L295: mismo patrón Alpine sin onerror --}}
                        <div x-data="{ imgError: false }">
                            <img x-show="!imgError"
                                 src="{{ (str_starts_with($item->image, 'http://') || str_starts_with($item->image, 'https://')) ? $item->image : asset('storage/' . $item->image) }}"
                                 alt="{{ $item->title }}"
                                 x-on:error="imgError = true">
                            <div x-show="imgError" class="nov-card-img-fallback" aria-hidden="true">
                                Sin imagen
                            </div>
                        </div>
                        <div class="nov-card-img-overlay" aria-hidden="true"></div>
                        <div class="nov-chip chip-deportes">{{ $item->category }}</div>
                    </div>
                    <div class="nov-card-body" x-data="{ expanded: false }">
                        <div class="nov-card-date">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <time datetime="{{ $item->created_at->toDateString() }}">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('d M, Y') }}
                            </time>
                        </div>
                        <h3>{{ $item->title }}</h3>
                        <p :class="expanded ? '' : 'nov-clamp'">{{ $item->content }}</p>
                        @if(mb_strlen($item->content) > 180)
                        <button type="button" class="nov-readmore" @click="expanded = !expanded" x-text="expanded ? 'Leer menos' : 'Leer más'"></button>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ══ EVENTOS ══ --}}
        @if($eventos->count() > 0)
        <section>
            <div class="nov-sec-header">
                <div class="nov-sec-header-left">
                    <div class="nov-sec-bar bar-eventos" aria-hidden="true"></div>
                    <h2>🎉 Próximos Eventos</h2>
                </div>
                <div class="nov-sec-divider" aria-hidden="true"></div>
            </div>
            <div class="nov-grid">
                @foreach($eventos as $item)
                <article class="nov-card eventos">
                    <div class="nov-card-img">
                        {{-- FIX L331: mismo patrón Alpine sin onerror --}}
                        <div x-data="{ imgError: false }">
                            <img x-show="!imgError"
                                 src="{{ (str_starts_with($item->image, 'http://') || str_starts_with($item->image, 'https://')) ? $item->image : asset('storage/' . $item->image) }}"
                                 alt="{{ $item->title }}"
                                 x-on:error="imgError = true">
                            <div x-show="imgError" class="nov-card-img-fallback" aria-hidden="true">
                                Sin imagen
                            </div>
                        </div>
                        <div class="nov-card-img-overlay" aria-hidden="true"></div>
                        <div class="nov-chip chip-eventos">{{ $item->category }}</div>
                    </div>
                    <div class="nov-card-body" x-data="{ expanded: false }">
                        <div class="nov-card-date">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <time datetime="{{ $item->created_at->toDateString() }}">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('d M, Y') }}
                            </time>
                        </div>
                        <h3>{{ $item->title }}</h3>
                        <p :class="expanded ? '' : 'nov-clamp'">{{ $item->content }}</p>
                        @if(mb_strlen($item->content) > 180)
                        <button type="button" class="nov-readmore" @click="expanded = !expanded" x-text="expanded ? 'Leer menos' : 'Leer más'"></button>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ══ EMPTY STATE ══ --}}
        @if($deportes->isEmpty() && $avisos->isEmpty() && $eventos->isEmpty())
        <div class="nov-empty">
            <span class="nov-empty-icon" aria-hidden="true">📭</span>
            <h3>Sin Novedades Por Ahora</h3>
            <p>Estamos preparando contenido nuevo para ti. ¡Regresa pronto!</p>
        </div>
        @endif

    </div>
</div>

@endsection