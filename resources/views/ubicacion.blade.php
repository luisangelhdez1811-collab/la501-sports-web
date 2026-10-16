@extends('layouts.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Barlow:wght@400;500&display=swap');

    .ubi-page {
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
    :root:not(.dark) .ubi-page {
        --bg:       #F5F3EF;
        --bg-card:  #FFFFFF;
        --bg-card2: #EBEBEB;
        --txt:      #1A1208;
        --txt-sub:  #6B6560;
        --bdr:      rgba(0,0,0,0.09);
        --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-light.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-light.svg')) }}');
    }

    /* ── HERO ── */
    .ubi-hero {
        position: relative;
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border-bottom: 3px solid var(--or);
        padding: 80px 40px 70px;
        text-align: center;
        overflow: hidden;
    }
    .ubi-hero::before {
        content: '';
        position: absolute; top: -100px; left: 50%; transform: translateX(-50%);
        width: 700px; height: 350px;
        background: radial-gradient(ellipse, rgba(249,115,22,.18) 0%, transparent 70%);
        pointer-events: none;
    }
    .ubi-badge {
        display: inline-block;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 11px; font-weight: 700;
        letter-spacing: 3px; text-transform: uppercase;
        padding: 6px 22px; margin-bottom: 24px;
        clip-path: polygon(10px 0%,100% 0%,calc(100% - 10px) 100%,0% 100%);
    }
    .ubi-hero h1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(58px, 11vw, 108px);
        line-height: .93; letter-spacing: 5px;
        color: var(--txt); position: relative; z-index: 1; margin: 0 0 18px;
    }
    .ubi-hero h1 span { color: var(--or); }
    .ubi-hero p {
        font-size: 17px; color: var(--txt-sub);
        max-width: 500px; margin: 0 auto;
        line-height: 1.75; position: relative; z-index: 1;
    }

    /* ── MAIN ── */
    .ubi-main {
        max-width: 1200px;
        margin: 0 auto;
        padding: 80px 32px;
        background-color: var(--bg);
    }

    /* ── GRID ── */
    .ubi-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        align-items: start;
    }

    /* ── MAP ── */
    .ubi-map-wrap {
        position: relative;
        border-radius: 20px;
    }
    .ubi-map-glow {
        position: absolute; inset: -3px;
        background: linear-gradient(135deg, var(--or), var(--or2) 45%, var(--gn));
        border-radius: 22px; z-index: 0; opacity: .65;
        transition: opacity .4s;
    }
    .ubi-map-wrap:hover .ubi-map-glow { opacity: 1; }
    .ubi-map-inner {
        position: relative; z-index: 1;
        border-radius: 18px; overflow: hidden;
        height: 520px; background: var(--bg-card2);
    }
    .ubi-map-inner iframe {
        position: absolute; inset: 0;
        width: 100%; height: 100%; border: 0;
    }

    /* ── INFO COLUMN ── */
    .ubi-info { display: flex; flex-direction: column; gap: 20px; }

    /* shared card */
    .ubi-card {
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border: 1px solid var(--bdr);
        border-radius: 18px;
        padding: 28px 28px 24px;
        position: relative;
        overflow: hidden;
        transition: border-color .3s, box-shadow .3s;
    }
    .ubi-card:hover {
        border-color: var(--or);
        box-shadow: 0 8px 36px rgba(249,115,22,.12);
    }
    .ubi-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(to right, var(--or), var(--gn));
        opacity: 0; transition: opacity .3s;
    }
    .ubi-card:hover::before { opacity: 1; }

    /* card title row */
    .ubi-card-title {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 20px;
    }
    .ubi-card-title-icon {
        width: 42px; height: 42px; border-radius: 10px;
        background: linear-gradient(135deg, var(--or), var(--or2));
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        box-shadow: 0 3px 14px rgba(249,115,22,.35);
        flex-shrink: 0;
    }
    .ubi-card-title h2 {
        font-family: 'Oswald', sans-serif;
        font-size: 18px; font-weight: 700;
        letter-spacing: 1.5px; text-transform: uppercase;
        color: var(--txt); margin: 0;
    }

    /* ── DIRECCIÓN ── */
    .ubi-addr-line1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 28px; letter-spacing: 2px;
        color: var(--or); line-height: 1; margin-bottom: 6px;
    }
    .ubi-addr-sub {
        font-size: 14px; color: var(--txt-sub); line-height: 1.7;
    }

    /* ── HORARIOS ── */
    .ubi-schedule { display: flex; flex-direction: column; gap: 0; }
    .ubi-schedule-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid var(--bdr);
        transition: background .2s;
    }
    .ubi-schedule-row:last-child { border-bottom: none; }
    .ubi-schedule-row:hover {
        padding-left: 8px; padding-right: 8px;
        margin-left: -8px; margin-right: -8px;
        border-radius: 8px;
        background: rgba(249,115,22,.06);
        border-bottom-color: transparent;
    }
    .ubi-day {
        font-family: 'Oswald', sans-serif;
        font-size: 13px; font-weight: 500;
        letter-spacing: 1px; text-transform: uppercase;
        color: var(--txt-sub);
    }
    .ubi-time {
        font-family: 'Oswald', sans-serif;
        font-size: 14px; font-weight: 600;
        letter-spacing: .5px;
        color: var(--txt);
    }
    .ubi-time.cerrado { color: #F87171; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
        .ubi-grid { grid-template-columns: 1fr; }
        .ubi-map-inner { height: 360px; }
    }
    @media (max-width: 540px) {
        .ubi-hero { padding: 60px 24px; }
        .ubi-main { padding: 50px 20px; }
    }
</style>

<div class="ubi-page" style="background-color: var(--bg);">

    {{-- HERO --}}
    <div class="ubi-hero">
        <div class="ubi-badge">La 501 Sports Restaurant</div>
        <h1>Encuéntranos<br><span>Aquí</span></h1>
        <p>Ven a visitarnos y disfruta de la mejor experiencia deportiva y gastronómica de la ciudad.</p>
    </div>

    <div class="ubi-main">
        <div class="ubi-grid">

            {{-- MAPA --}}
            <div class="ubi-map-wrap">
                <div class="ubi-map-glow"></div>
                <div class="ubi-map-inner">
                    @php
                        $mapUrlSetting = \App\Models\Setting::where('key', 'map_url')->value('value');
                        $finalMapUrl = $mapUrlSetting ?: 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d10000!2d-98.42!3d21.14!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1ses!2smx';
                    @endphp
                    <iframe title="Mapa de ubicación de La 501 Sports"
                        src="{{ $finalMapUrl }}"
                        class="grayscale-[0.15] dark:invert-[0.9] dark:hue-rotate-[180deg]"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            {{-- INFO --}}
            <div class="ubi-info">

                {{-- Dirección --}}
                <div class="ubi-card">
                    <div class="ubi-card-title">
                        <div class="ubi-card-title-icon">📍</div>
                        <h2>Dirección</h2>
                    </div>
                    @php
                        $addr1 = \App\Models\Setting::where('key', 'address_line1')->value('value') ?: 'Av. Deportiva #501';
                        $addr2 = \App\Models\Setting::where('key', 'address_line2')->value('value') ?: 'Col. Centro';
                        $addr3 = \App\Models\Setting::where('key', 'address_line3')->value('value') ?: 'CP 43000, Tulancingo, Hgo.';
                    @endphp
                    <p class="ubi-addr-line1">{{ $addr1 }}</p>
                    @if($addr2)
                        <p class="ubi-addr-sub">{{ $addr2 }}</p>
                    @endif
                    @if($addr3)
                        <p class="ubi-addr-sub">{{ $addr3 }}</p>
                    @endif
                </div>

                {{-- Horarios --}}
                <div class="ubi-card">
                    <div class="ubi-card-title">
                        <div class="ubi-card-title-icon">🕒</div>
                        <h2>Horario</h2>
                    </div>

                    @php
                        $dias = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
                    @endphp

                    <div class="ubi-schedule">
                        @foreach($dias as $dia)
                            @php
                                $key = 'schedule_' . strtolower($dia);
                                $horario = \App\Models\Setting::where('key', $key)->value('value') ?: '12:30 PM – 10:30 PM';
                                $cerrado = strtolower(trim($horario)) === 'cerrado';
                            @endphp
                            <div class="ubi-schedule-row">
                                <span class="ubi-day">{{ $dia }}</span>
                                <span class="ubi-time {{ $cerrado ? 'cerrado' : '' }}">
                                    {{ $horario }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection