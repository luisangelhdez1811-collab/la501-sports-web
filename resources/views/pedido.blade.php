@extends('layouts.app')

@section('content')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="{{ asset('js/menu-opciones.js') }}?v={{ @filemtime(public_path('js/menu-opciones.js')) }}"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Barlow:wght@400;500&display=swap');

    .ped-page {
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
    :root:not(.dark) .ped-page {
        --bg:       #F5F3EF;
        --bg-card:  #FFFFFF;
        --bg-card2: #EBEBEB;
        --txt:      #1A1208;
        --txt-sub:  #6B6560;
        --bdr:      rgba(0,0,0,0.09);
        --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-light.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-light.svg')) }}');
    }

    /* ── TOPBAR ── */
    .ped-topbar {
        position: relative;
        background: var(--bg-card);
        background-image: var(--brand-pattern);
        border-bottom: 3px solid var(--or);
        padding: 32px 32px 0;
        overflow: hidden;
    }
    .ped-topbar::before {
        content: '';
        position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
        width: 600px; height: 200px;
        background: radial-gradient(ellipse, rgba(249,115,22,.14) 0%, transparent 70%);
        pointer-events: none;
    }
    .ped-topbar-inner {
        max-width: 1200px; margin: 0 auto;
        display: flex; align-items: flex-end;
        justify-content: space-between;
        gap: 24px; flex-wrap: wrap;
        position: relative; z-index: 1;
    }
    .ped-topbar-badge {
        display: inline-block;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 10px; font-weight: 700;
        letter-spacing: 3px; text-transform: uppercase;
        padding: 4px 16px; margin-bottom: 10px;
        clip-path: polygon(8px 0%,100% 0%,calc(100% - 8px) 100%,0% 100%);
    }
    .ped-topbar-text h1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(42px, 7vw, 72px);
        line-height: .95; letter-spacing: 4px; color: var(--txt); margin: 0;
    }
    .ped-topbar-text h1 span { color: var(--or); }
    .ped-topbar-text p { font-size: 14px; color: var(--txt-sub); margin: 8px 0 0; line-height: 1.5; }

    /* ── FILTROS STICKY ── */
    .ped-filters-sticky {
        position: sticky; top: 88px; z-index: 40;
        background: var(--bg-card);
        border-bottom: 1px solid var(--bdr);
        box-shadow: 0 4px 24px rgba(0,0,0,.14);
        padding: 0 32px;
    }
    .ped-mobile-price { display: none; }
    .ped-card-body-footer { display: block; }
    .ped-filters-inner { max-width: 1200px; margin: 0 auto; }

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

    /* ── SCROLL NAV (flechas laterales del filtro) ── */
    .mf-scroll-wrap { position: relative; padding: 0 32px; }
    .mf-scroll-wrap .mf-row { padding: 10px 0; }
    .mf-nav-btn {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 30px; height: 30px; border-radius: 50%;
        border: 1.5px solid rgba(249,115,22,.4);
        background: rgba(249,115,22,.1); color: var(--or);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Oswald', sans-serif; font-size: 16px; font-weight: 700; line-height: 1;
        cursor: pointer; z-index: 5; padding: 0;
        box-shadow: 0 2px 10px rgba(0,0,0,.25);
        transition: background .2s, color .2s, border-color .2s, transform .15s;
    }
    .mf-nav-btn:hover  { background: var(--or); color: #fff; border-color: var(--or); transform: translateY(-50%) scale(1.08); }
    .mf-nav-btn:active { transform: translateY(-50%) scale(.94); }
    .mf-nav-btn.left  { left: 0; }
    .mf-nav-btn.right { right: 0; }
    .mf-fade {
        position: absolute; top: 0; bottom: 0; width: 34px;
        pointer-events: none; z-index: 4;
    }
    .mf-fade.left  { left: 0;  background: linear-gradient(to right, var(--bg-card), transparent); }
    .mf-fade.right { right: 0; background: linear-gradient(to left,  var(--bg-card), transparent); }

    /* ── SUCCESS ── */
    .ped-success {
        background: rgba(22,163,74,.1);
        border: 1px solid rgba(34,197,94,.25);
        border-left: 4px solid var(--gn2);
        border-radius: 12px; padding: 14px 20px;
        margin-bottom: 28px;
        display: flex; align-items: center; gap: 10px;
        font-family: 'Oswald', sans-serif;
        font-size: 14px; font-weight: 600; letter-spacing: 1px;
        color: var(--gn2);
    }

    /* ── MAIN ── */
    .ped-main { max-width: 1200px; margin: 0 auto; padding: 40px 32px 80px; background-color: var(--bg); }
    .ped-cat-label { display: flex; align-items: center; gap: 14px; margin-bottom: 28px; }
    .ped-cat-label::before {
        content: ''; display: block; width: 4px; height: 36px;
        background: linear-gradient(to bottom, var(--or), var(--gn2));
        border-radius: 3px; flex-shrink: 0;
    }
    .ped-cat-label h2 { font-family: 'Bebas Neue', sans-serif; font-size: 34px; letter-spacing: 3px; color: var(--txt); margin: 0; line-height: 1; }
    .ped-cat-label .cat-icon { font-size: 24px; }

    /* ── GRID ── */
    .ped-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }

    /* ── CARD ── */
    .ped-card {
        background: var(--bg-card); border: 1px solid var(--bdr);
        border-radius: 16px; overflow: hidden;
        display: flex; flex-direction: column; position: relative;
        transition: transform .3s, border-color .3s, box-shadow .3s;
    }
    .ped-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(to right, var(--or), var(--gn)); opacity: 0; transition: opacity .3s;
    }
    .ped-card:hover { transform: translateY(-5px); border-color: var(--or); box-shadow: 0 10px 40px rgba(249,115,22,.16); }
    .ped-card:hover::before { opacity: 1; }

    .ped-card-img { height: 190px; position: relative; overflow: hidden; background: var(--bg-card2); flex-shrink: 0; }
    .ped-card-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s ease; display: block; }
    .ped-card:hover .ped-card-img img { transform: scale(1.1); }
    .ped-card-img-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,9,6,.72) 0%, transparent 55%); z-index: 1; }
    .ped-card-no-img {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 58px;
        background-image: var(--brand-pattern);
    }
    .ped-price-badge {
        position: absolute; top: 12px; right: 12px; z-index: 3;
        background: var(--or); font-family: 'Bebas Neue', sans-serif;
        font-size: 20px; letter-spacing: 1px; color: #fff;
        padding: 4px 14px; border-radius: 100px;
        box-shadow: 0 3px 14px rgba(249,115,22,.45); line-height: 1.2;
    }
    .ped-card-body { padding: 16px 18px 18px; display: flex; flex-direction: column; flex-grow: 1; }
    .ped-card-body h3 {
        font-family: 'Oswald', sans-serif; font-size: 17px; font-weight: 700; letter-spacing: .5px;
        color: var(--txt); margin: 0 0 6px; line-height: 1.2; transition: color .25s;
    }
    .ped-card:hover .ped-card-body h3 { color: var(--or); }
    .ped-card-body p {
        font-size: 13px; color: var(--txt-sub); line-height: 1.55; flex-grow: 1; margin-bottom: 16px;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    /* ── BOTÓN AGREGAR ── */
    .ped-add-btn {
        width: 100%; font-family: 'Oswald', sans-serif;
        font-size: 13px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
        color: #fff; background: var(--gn); border: none; cursor: pointer;
        padding: 13px 0; border-radius: 10px;
        box-shadow: 0 3px 16px rgba(22,163,74,.35);
        transition: background .2s, transform .15s, box-shadow .2s;
    }
    .ped-add-btn:hover { background: var(--gn2); transform: translateY(-1px); box-shadow: 0 5px 22px rgba(34,197,94,.45); }

    /* ── CONTROLES CANTIDAD ── */
    .ped-in-cart-label {
        font-family: 'Oswald', sans-serif; font-size: 11px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        color: var(--gn2); text-align: center; margin-bottom: 10px;
    }
    .ped-qty-row { display: flex; align-items: center; gap: 8px; }
    .ped-qty-btn {
        width: 44px; height: 44px; border-radius: 10px;
        border: none; cursor: pointer;
        font-family: 'Bebas Neue', sans-serif; font-size: 22px; line-height: 1;
        display: flex; align-items: center; justify-content: center;
        transition: background .2s, transform .15s; flex-shrink: 0;
    }
    .ped-qty-btn.minus { background: rgba(239,68,68,.12); color: #F87171; border: 1px solid rgba(239,68,68,.2); }
    .ped-qty-btn.minus:hover { background: rgba(239,68,68,.22); transform: scale(1.05); }
    .ped-qty-btn.plus { background: var(--gn); color: #fff; box-shadow: 0 2px 10px rgba(22,163,74,.35); }
    .ped-qty-btn.plus:hover { background: var(--gn2); transform: scale(1.05); }
    .ped-qty-display {
        flex: 1; height: 44px; border-radius: 10px;
        background: var(--bg-card2); border: 1px solid var(--bdr);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 1px; color: var(--txt);
    }

    /* ── EMPTY ── */
    .ped-empty {
        grid-column: 1 / -1; background: var(--bg-card);
        border: 1px solid var(--bdr); border-left: 5px solid var(--or);
        border-radius: 16px; padding: 60px 32px; text-align: center;
    }
    .ped-empty-icon { font-size: 48px; display: block; margin-bottom: 12px; }
    .ped-empty h3 { font-family: 'Bebas Neue', sans-serif; font-size: 34px; letter-spacing: 3px; color: var(--txt); margin: 0 0 8px; }
    .ped-empty p { color: var(--txt-sub); font-size: 14px; }

    @media (max-width: 768px) {
        .ped-topbar, .ped-filters-sticky { padding-left: 20px; padding-right: 20px; }
        .ped-main { padding: 28px 20px 60px; }
        .ped-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
    }
    @media (max-width: 540px) {
        .ped-grid {
            grid-template-columns: 1fr !important;
            gap: 14px !important;
        }
        .ped-card {
            flex-direction: row !important;
            align-items: stretch !important;
            padding: 12px !important;
            gap: 14px !important;
            border-radius: 16px !important;
            min-height: 120px !important;
        }
        .ped-card-img {
            order: 2 !important;
            width: 90px !important;
            height: 90px !important;
            border-radius: 10px !important;
            flex-shrink: 0 !important;
            align-self: center !important;
        }
        .ped-price-badge {
            display: none !important;
        }
        .ped-card-body {
            order: 1 !important;
            padding: 0 !important;
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }
        .ped-card-body h3 {
            font-size: 16px !important;
            margin-bottom: 4px !important;
        }
        .ped-card-body p {
            font-size: 12px !important;
            line-height: 1.4 !important;
            margin-bottom: 8px !important;
            -webkit-line-clamp: 2 !important;
        }
        .ped-mobile-price {
            display: inline-block !important;
            font-family: 'Bebas Neue', sans-serif !important;
            font-size: 19px !important;
            color: var(--or) !important;
            letter-spacing: 0.5px !important;
        }
        .ped-card-body-footer {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 10px !important;
            margin-top: auto !important;
        }
        .ped-add-btn {
            padding: 8px 16px !important;
            font-size: 11px !important;
            letter-spacing: 1px !important;
            width: auto !important;
            border-radius: 8px !important;
        }
        .ped-qty-row {
            gap: 8px !important;
            margin-top: 0 !important;
        }
        .ped-qty-btn {
            width: 28px !important;
            height: 28px !important;
            font-size: 14px !important;
            border-radius: 6px !important;
        }
        .ped-qty-display {
            font-size: 15px !important;
            min-width: 20px !important;
        }
        .ped-in-cart-label {
            display: none !important;
        }
    }

    /* ── CUSTOMIZATION MODAL ── */
    .cust-overlay {
        position: fixed; inset: 0; z-index: 100;
        background: rgba(0,0,0,0.75);
        backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
    }
    .cust-modal {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 18px;
        width: 100%; max-width: 460px;
        max-height: 90vh;
        display: flex; flex-direction: column;
        overflow: hidden;
        position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    .cust-modal::before {
        content: ''; display: block; height: 4px;
        background: linear-gradient(to right, var(--or), var(--gn));
    }
    .dark .cust-modal::before {
        background: linear-gradient(to right, var(--or), var(--gn2));
    }
    .cust-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--bdr);
        display: flex; align-items: center; justify-content: space-between;
    }
    .cust-header h3 {
        font-family: 'Oswald', sans-serif;
        font-size: 20px; font-weight: 700;
        letter-spacing: .5px; color: var(--txt);
        margin: 0;
    }
    .cust-close {
        background: transparent; border: none;
        color: var(--txt-sub); font-size: 24px;
        cursor: pointer; line-height: 1;
        transition: color .2s;
    }
    .cust-close:hover { color: #F87171; }
    
    .cust-body {
        padding: 24px;
        overflow-y: auto;
        display: flex; flex-direction: column; gap: 18px;
    }
    .cust-product-info {
        display: flex; gap: 16px; align-items: center;
    }
    .cust-product-img {
        width: 70px; height: 70px; border-radius: 10px;
        overflow: hidden; background: var(--bg-card2);
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; flex-shrink: 0;
    }
    .cust-product-img img { width: 100%; height: 100%; object-fit: cover; }
    .cust-product-desc {
        font-size: 13px; color: var(--txt-sub); line-height: 1.5;
    }
    .cust-title-ingredients {
        font-family: 'Oswald', sans-serif;
        font-size: 12px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        color: var(--txt);
        margin: 0 0 10px 0;
    }
    .cust-ingredients-list {
        display: grid; grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .cust-ingredient-item {
        display: flex; align-items: center; gap: 10px;
        background: var(--bg-card2);
        border: 1px solid var(--bdr);
        border-radius: 10px;
        padding: 10px 14px;
        cursor: pointer;
        user-select: none;
        transition: border-color .2s, background .2s;
    }
    .cust-ingredient-item:hover {
        border-color: var(--or);
    }
    .cust-ingredient-item input[type="checkbox"] {
        accent-color: var(--or);
        width: 16px; height: 16px;
        cursor: pointer;
    }
    .cust-ingredient-label {
        font-size: 13px; font-weight: 500;
        color: var(--txt);
        cursor: pointer;
    }
    
    .cust-footer {
        padding: 16px 24px;
        background: var(--bg-card2);
        border-top: 1px solid var(--bdr);
        display: flex; gap: 12px;
    }
    .cust-btn-cancel {
        flex: 1; padding: 12px; border-radius: 10px;
        background: transparent; border: 1px solid var(--bdr);
        font-family: 'Oswald', sans-serif; font-size: 13px; font-weight: 600;
        letter-spacing: 1px; text-transform: uppercase; color: var(--txt-sub);
        cursor: pointer; transition: border-color .2s, color .2s;
    }
    .cust-btn-cancel:hover { border-color: #F87171; color: #F87171; }
    
    .cust-btn-confirm {
        flex: 2; padding: 12px; border-radius: 10px;
        background: var(--gn); border: none;
        font-family: 'Oswald', sans-serif; font-size: 13px; font-weight: 700;
        letter-spacing: 1.5px; text-transform: uppercase; color: #fff;
        cursor: pointer; box-shadow: 0 3px 16px rgba(22,163,74,.35);
        transition: background .2s, transform .15s;
    }
    .cust-btn-confirm:hover { background: var(--gn2); transform: translateY(-1px); }

    .cust-textarea {
        width: 100%; padding: 12px 14px; border-radius: 10px;
        background: var(--bg-card2); border: 1px solid var(--bdr);
        color: var(--txt); font-size: 13px; font-family: inherit;
        outline: none; resize: none; transition: border-color .2s;
    }
    .cust-textarea:focus { border-color: var(--or); }
    .cust-note-help { font-size: 11px; color: var(--txt-sub); margin-top: 6px; }

    /* ── PRESENTACIONES Y OPCIONES (salsas, papas, Arma tus Papas) ── */
    .cust-group-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
    .cust-group-head .cust-title-ingredients { margin: 0; }
    .cust-group-rule { font-size: 11px; font-weight: 600; color: var(--txt-sub); white-space: nowrap; }
    .cust-group-rule.missing { color: #F87171; }
    .cust-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .cust-option {
        display: flex; align-items: center; gap: 6px;
        background: var(--bg-card2); border: 1.5px solid var(--bdr); border-radius: 10px;
        transition: border-color .2s, background .2s;
    }
    .cust-option.on { border-color: var(--or); background: rgba(249,115,22,.1); }
    .cust-option-main {
        flex: 1; min-width: 0; text-align: left; padding: 10px 12px;
        background: transparent; border: none; cursor: pointer; color: var(--txt);
        font-family: inherit;
    }
    .cust-option-name { display: block; font-size: 13px; font-weight: 600; }
    .cust-option-detail { display: block; font-size: 11px; color: var(--txt-sub); margin-top: 1px; }
    .cust-option-price { display: block; font-size: 13px; font-weight: 700; color: var(--gn2); margin-top: 2px; }
    .cust-stepper { display: flex; align-items: center; gap: 4px; padding-right: 8px; }
    .cust-stepper button {
        width: 24px; height: 24px; border-radius: 6px; border: 1px solid var(--bdr);
        background: var(--bg-card); color: var(--txt); font-weight: 700; cursor: pointer;
    }
    .cust-stepper button:disabled { opacity: .3; cursor: not-allowed; }
    .cust-stepper span { min-width: 12px; text-align: center; font-size: 13px; font-weight: 700; color: var(--txt); }
    .cust-missing { font-size: 12px; font-weight: 600; color: #F87171; margin: 0 0 10px; }
    .cust-reminder { font-size: 12px; font-weight: 600; color: #D97706; margin: 0 0 10px; }
    .cust-btn-confirm:disabled { opacity: .45; cursor: not-allowed; transform: none; }
    .cust-footer-wrap { padding: 16px 24px; background: var(--bg-card2); border-top: 1px solid var(--bdr); }
    .cust-footer-wrap .cust-footer { padding: 0; border: none; background: transparent; }

    /* ── COMIDAS / BEBIDAS ── */
    .ped-sections { display: flex; gap: 8px; padding-top: 12px; }
    .ped-section-btn {
        flex: 1; padding: 10px 14px; border-radius: 12px;
        border: 1px solid var(--bdr); background: transparent; color: var(--txt-sub);
        font-family: 'Oswald', sans-serif; font-size: 13px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase; cursor: pointer;
        transition: background .2s, color .2s, border-color .2s;
    }
    .ped-section-btn.active { background: var(--txt); color: var(--bg-card); border-color: var(--txt); }
    .ped-card-presentation { font-size: 12px; font-weight: 600; color: var(--or); margin: -2px 0 6px; }
    .ped-card-variants { font-size: 12px; font-weight: 700; color: var(--gn2); margin: 0 0 8px; }
</style>

<div class="ped-page" style="background-color: var(--bg);"
     x-data="pedidoManager()" x-init="initMenu()">

    {{-- ── TOPBAR ── --}}
    <div class="ped-topbar">
        <div class="ped-topbar-inner">
            <div class="ped-topbar-text">
                <div class="ped-topbar-badge">La 501 Sports · A Domicilio</div>
                <h1>Arma Tu <span>Pedido</span></h1>
                <p>Agrega tus platillos favoritos y nosotros te los llevamos</p>
            </div>
        </div>
    </div>

    {{-- ── FILTROS STICKY ── --}}
    <div class="ped-filters-sticky">
        <div class="ped-filters-inner">

            {{-- Comidas / Bebidas --}}
            <div class="ped-sections" role="tablist" aria-label="Sección del menú">
                <template x-for="sec in secciones" :key="sec.id">
                    <button type="button" role="tab" class="ped-section-btn"
                            :class="seccion === sec.id ? 'active' : ''" :aria-selected="seccion === sec.id"
                            @click="cambiarSeccion(sec.id)" x-text="sec.icono + ' ' + sec.nombre"></button>
                </template>
            </div>

            {{-- Categorías principales --}}
            <div class="mf-scroll-wrap">
                <button type="button" class="mf-nav-btn left" @click="scrollMF($event, -1)" aria-label="Ver categorías anteriores">‹</button>
                <div class="mf-fade left" aria-hidden="true"></div>
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
                <div class="mf-fade right" aria-hidden="true"></div>
                <button type="button" class="mf-nav-btn right" @click="scrollMF($event, 1)" aria-label="Ver más categorías">›</button>
            </div>

            {{-- Subfiltros --}}
            <template x-if="subcategoriasMap[filtro]">
                <div>
                    <hr class="mf-divider">
                    <div class="mf-scroll-wrap">
                        <button type="button" class="mf-nav-btn left" @click="scrollMF($event, -1)" aria-label="Ver subcategorías anteriores">‹</button>
                        <div class="mf-fade left" aria-hidden="true"></div>
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
                        <div class="mf-fade right" aria-hidden="true"></div>
                        <button type="button" class="mf-nav-btn right" @click="scrollMF($event, 1)" aria-label="Ver más subcategorías">›</button>
                    </div>
                </div>
            </template>

        </div>
    </div>

    {{-- ── CONTENT ── --}}
    <div class="ped-main">

        @if(session('success'))
        <div class="ped-success">{{ session('success') }}</div>
        @endif

        {{-- Cat label --}}
        <div class="ped-cat-label">
            <template x-for="cat in listaCategorias" :key="cat.nombre">
                <template x-if="filtro === cat.nombre">
                    <div style="display:contents;">
                        <span class="cat-icon" x-text="cat.icono" aria-hidden="true"></span>
                        {{-- FIX L312 + L334: x-text en headings necesita aria-label como fallback para analizadores estáticos --}}
                        <h2 x-text="filtro" x-bind:aria-label="filtro"><span class="sr-only">Categoría seleccionada</span></h2>
                    </div>
                </template>
            </template>
        </div>

        {{-- Grid --}}
        <div class="ped-grid">
            <template x-for="item in productosFiltrados()" :key="item.id">
                <div class="ped-card">
                    <div class="ped-card-img">
                        <template x-if="item.image">
                            <img :src="item.image.startsWith('http') ? item.image : '/storage/' + item.image" :alt="item.name" x-on:error="item.image = null">
                        </template>
                        <template x-if="!item.image">
                            <div class="ped-card-no-img" aria-hidden="true">🍔</div>
                        </template>
                        <div class="ped-card-img-overlay" aria-hidden="true"></div>
                        <div class="ped-price-badge" x-text="precioBadge(item)" aria-hidden="true"></div>
                    </div>

                    <div class="ped-card-body">
                        {{-- FIX L334: aria-label como fallback para el analizador estático --}}
                        <h3 x-text="item.name" x-bind:aria-label="item.name"><span class="sr-only">Platillo</span></h3>
                        <p class="ped-card-presentation" x-show="item.presentation" x-text="item.presentation"></p>
                        <p class="ped-card-variants" x-show="item.variants.length > 0" x-text="MenuOpciones.precioCarta(item)"></p>
                        <p x-text="item.description || 'Delicioso platillo preparado al momento con los mejores ingredientes.'"></p>

                        <div class="ped-card-body-footer">
                            <span class="ped-mobile-price" x-text="precioBadge(item)"></span>
                            
                            {{-- Estado 1: No está en carrito --}}
                            <div x-show="!isInCart(item.id)">
                                <button type="button" @click="addOrCustomize(item)" class="ped-add-btn">+ Agregar al Pedido</button>
                            </div>

                            {{-- Estado 2: Ya está en carrito --}}
                            <div x-show="isInCart(item.id)" x-cloak>
                                <p class="ped-in-cart-label">✓ En tu pedido</p>
                                <div class="ped-qty-row">
                                    <button type="button" @click.prevent="restarUno(item.id)" class="ped-qty-btn minus" :aria-label="'Quitar una unidad de ' + item.name">−</button>
                                    <output class="ped-qty-display" :aria-label="'Cantidad: ' + getCartQty(item.id)">
                                        <span x-text="getCartQty(item.id)"></span>
                                    </output>
                                    <button type="button" @click="addOrCustomize(item)" class="ped-qty-btn plus" :aria-label="'Agregar una unidad de ' + item.name">+</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </template>

            <div x-show="productosFiltrados().length === 0" class="ped-empty" style="display:none;">
                <span class="ped-empty-icon" aria-hidden="true">🍽️</span>
                <h3>Aún No Hay Platillos Aquí</h3>
                <p>Estamos cocinando nuevas opciones para esta categoría.</p>
            </div>
        </div>
    </div>

    {{-- Formulario oculto para agregar al carrito --}}
    <form id="hidden-add-to-cart-form" action="{{ route('cart.add') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="id" id="hidden-product-id">
        <input type="hidden" name="name" id="hidden-product-name">
        <input type="hidden" name="price" id="hidden-product-price">
        <input type="hidden" name="image" id="hidden-product-image">
        <input type="hidden" name="quantity" value="1">
        <div id="hidden-excluded-ingredients-container"></div>
    </form>

    {{-- Modal de Personalización (Exclusión de Ingredientes) --}}
    <div x-show="showCustomizeModal" class="cust-overlay" x-cloak style="display: none;">
        <div class="cust-modal" @click.away="showCustomizeModal = false">
            <div class="cust-header">
                <h3 x-text="'Personalizar ' + customizingProduct.name"></h3>
                <button type="button" class="cust-close" @click="showCustomizeModal = false">&times;</button>
            </div>
            <div class="cust-body">
                <div class="cust-product-info">
                    <div class="cust-product-img">
                        <template x-if="customizingProduct.image">
                            <img :src="customizingProduct.image.startsWith('http') ? customizingProduct.image : '/storage/' + customizingProduct.image" :alt="customizingProduct.name" x-on:error="customizingProduct.image = null">
                        </template>
                        <template x-if="!customizingProduct.image">
                            <span>🍔</span>
                        </template>
                    </div>
                    <div class="cust-product-desc" x-text="customizingProduct.description || 'Personaliza los ingredientes a excluir de tu platillo.'"></div>
                </div>
                
                {{-- Presentación (vaso / litro, copeo / botella) --}}
                <template x-if="(customizingProduct.variants || []).length > 0">
                    <div>
                        <h4 class="cust-title-ingredients">Presentación</h4>
                        <div class="cust-options">
                            <template x-for="v in customizingProduct.variants" :key="v.id">
                                <div class="cust-option" :class="seleccion.variantId === v.id ? 'on' : ''">
                                    <button type="button" class="cust-option-main" @click="seleccion.variantId = v.id" :aria-pressed="seleccion.variantId === v.id">
                                        <span class="cust-option-name" x-text="v.name"></span>
                                        <span class="cust-option-price" x-text="MenuOpciones.formatoPrecio(v.price)"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Salsas, papas, sabores, pasos de Arma tus Papas --}}
                <template x-for="grupo in (customizingProduct.option_groups || [])" :key="grupo.id">
                    <div>
                        <div class="cust-group-head">
                            <h4 class="cust-title-ingredients" x-text="grupo.label"></h4>
                            <span class="cust-group-rule" :class="MenuOpciones.totalGrupo(grupo, seleccion) < grupo.min ? 'missing' : ''" x-text="reglaGrupo(grupo)"></span>
                        </div>
                        <div class="cust-options">
                            <template x-for="op in grupo.options" :key="op.id">
                                <div class="cust-option" :class="MenuOpciones.cantidad(seleccion, op) ? 'on' : ''">
                                    <button type="button" class="cust-option-main" @click="MenuOpciones.alternar(customizingProduct, grupo, op, seleccion)" :aria-pressed="MenuOpciones.cantidad(seleccion, op) > 0">
                                        <span class="cust-option-name" x-text="op.name"></span>
                                        <span class="cust-option-detail" x-show="op.detail || op.price > 0"
                                              x-text="[op.detail, op.price > 0 ? '+' + MenuOpciones.formatoPrecio(op.price) : ''].filter(Boolean).join(' · ')"></span>
                                    </button>
                                    <template x-if="grupo.max_per_option > 1 && MenuOpciones.cantidad(seleccion, op) > 0">
                                        <div class="cust-stepper">
                                            <button type="button" @click="MenuOpciones.quitar(op, seleccion)" aria-label="Quitar una">−</button>
                                            <span x-text="MenuOpciones.cantidad(seleccion, op)"></span>
                                            <button type="button" @click="MenuOpciones.sumar(grupo, op, seleccion)" :disabled="!MenuOpciones.puedeSumar(grupo, op, seleccion)" aria-label="Agregar otra">+</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="customizingIngredients.length > 0">
                    <h4 class="cust-title-ingredients">Ingredientes a incluir:</h4>
                    <div class="cust-ingredients-list">
                        <template x-for="(ing, idx) in customizingIngredients" :key="idx">
                            <label class="cust-ingredient-item">
                                <input type="checkbox" x-model="ing.included">
                                <span class="cust-ingredient-label" x-text="ing.nombre"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div>
                    <h4 class="cust-title-ingredients">Comentarios / Extra:</h4>
                    <textarea x-model="customizingComment" rows="3" maxlength="100"
                              placeholder="Ej: bien dorado, más picante, sin sal…"
                              class="cust-textarea"></textarea>
                    <p class="cust-note-help">Cualquier instrucción especial para la cocina.</p>
                </div>
            </div>
            <div class="cust-footer-wrap">
                <p class="cust-missing" x-show="showCustomizeModal && pendientes().length" x-text="pendientes()[0]"></p>
                <p class="cust-reminder" x-show="showCustomizeModal && !pendientes().length && recordatorio()" x-text="recordatorio()"></p>
                <div class="cust-footer">
                    <button type="button" class="cust-btn-cancel" @click="showCustomizeModal = false">Cancelar</button>
                    <button type="button" class="cust-btn-confirm" @click="confirmCustomization()" :disabled="pendientes().length > 0"
                            x-text="'Agregar · ' + MenuOpciones.formatoPrecio(showCustomizeModal ? MenuOpciones.precio(customizingProduct, seleccion) : 0)">Agregar al Pedido</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function pedidoManager() {
        return {
            filtro:    'Hamburguesas',
            subfiltro: 'Todos',
            seccion:   'comidas',
            secciones: [
                { id: 'comidas', nombre: 'Comidas', icono: '🍔' },
                { id: 'bebidas', nombre: 'Bebidas', icono: '🍹' },
            ],

            showCustomizeModal: false,
            customizingProduct: {},
            customizingIngredients: [],
            customizingComment: '',
            seleccion: { variantId: null, cantidades: {} },

            listaCategorias: [],
            // Subcategorías por categoría, sacadas del menú (solo donde hay más de una)
            subcategoriasMap: {},

            allProductos: @json($products ?? []),
            cartData:     @json(session('cart', [])),
            lastDataHash: '',

            initMenu() {
                this.lastDataHash = JSON.stringify(this.allProductos);

                const cats = [...new Set(this.allProductos.map(p => p.category))];
                cats.forEach(c => {
                    const subs = [...new Set(this.allProductos.filter(p => p.category === c && p.subcategory).map(p => p.subcategory))];
                    if (subs.length > 1) this.subcategoriasMap[c] = subs;
                });

                this.cambiarSeccion('comidas');

                // Desde las recomendaciones del carrito: abre el platillo para elegir sus opciones
                const pedido = Number(new URLSearchParams(window.location.search).get('producto'));
                const producto = this.allProductos.find(p => p.id === pedido);
                if (producto) {
                    this.seccion = producto.section;
                    this.cambiarSeccion(producto.section, producto.category);
                    this.abrirPersonalizar(producto);
                }

                const pusher  = new Pusher('491d18da8b8b427e4969', { cluster: 'us2' });
                const channel = pusher.subscribe('menu-channel');
                channel.bind('menu.updated', () => { this.fetchLiveProducts(); });
            },

            cambiarSeccion(id, categoria = null) {
                const iconMap = {
                    'Hamburguesas': '🍔',
                    'Jochos': '🌭',
                    'Burritos': '🌯',
                    'Tacos': '🌮',
                    'Arma tus Papas': '🍟',
                    'Para ir Picando': '🧀',
                    'Alitas, Costillas y Especialidades': '🍗',
                    'Por si Andas Fit': '🥗',
                    'Algo Dulce': '🍰',
                    'Cervecería': '🍺',
                    'Destilados': '🥃',
                    '6 Shots': '🥂',
                    'Coctelería': '🍹',
                    'Digestivos': '☕',
                    'Bebidas sin Alcohol': '🥤',
                };

                this.seccion = id;
                const cats = [...new Set(this.allProductos.filter(p => (p.section || 'comidas') === id).map(p => p.category))];
                this.listaCategorias = cats.map(c => ({ nombre: c, icono: iconMap[c] || '🍽️' }));
                this.filtro = categoria && cats.includes(categoria) ? categoria : (cats[0] || '');
                this.subfiltro = 'Todos';
            },

            productosFiltrados() {
                let lista = this.allProductos.filter(p => p.category === this.filtro);
                if (this.subcategoriasMap[this.filtro] && this.subfiltro !== 'Todos') {
                    lista = lista.filter(p => p.subcategory === this.subfiltro);
                }
                return lista;
            },

            precioBadge(item) {
                return (item.variants || []).length ? 'Desde ' + MenuOpciones.formatoPrecio(item.price) : MenuOpciones.formatoPrecio(item.price);
            },

            reglaGrupo(grupo) {
                return MenuOpciones.regla(grupo);
            },

            pendientes() {
                return MenuOpciones.pendientes(this.customizingProduct, this.seleccion);
            },

            recordatorio() {
                const sinElegir = MenuOpciones.recordatorios(this.customizingProduct, this.seleccion);
                return sinElegir.length ? 'Sin elegir: ' + sinElegir.join(', ') + '. Puedes agregarlo así.' : '';
            },

            scrollMF(event, dir) {
                const wrap = event.currentTarget.closest('.mf-scroll-wrap');
                const row  = wrap ? wrap.querySelector('.mf-row') : null;
                if (row) row.scrollBy({ left: dir * 240, behavior: 'smooth' });
            },

            isInCart(id)   { 
                return Object.keys(this.cartData).some(key => key == id || key.startsWith(id + '_')); 
            },
            getCartQty(id) {
                let qty = 0;
                Object.keys(this.cartData).forEach(key => {
                    if (key == id || key.startsWith(id + '_')) {
                        qty += Number(this.cartData[key].quantity) || 0;
                    }
                });
                return qty;
            },
            restarUno(id) {
                const keys = Object.keys(this.cartData).filter(key => key == id || key.startsWith(id + '_'));
                if (keys.length === 0) return;
                const key = keys[keys.length - 1];
                const actual = Number(this.cartData[key].quantity) || 0;
                this.updateCartQty(key, actual - 1);
            },
            addOrCustomize(item) {
                if (MenuOpciones.necesitaElegir(item)) {
                    this.abrirPersonalizar(item);
                } else {
                    this.submitAddToCart(item.id, MenuOpciones.crear(item), [], '');
                }
            },
            abrirPersonalizar(item) {
                this.customizingProduct = item;
                this.seleccion = MenuOpciones.crear(item);
                this.customizingIngredients = (item.ingredientes || []).map(ing => ({
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
                const comment = this.customizingComment.trim();

                this.showCustomizeModal = false;
                this.submitAddToCart(this.customizingProduct.id, this.seleccion, excluded, comment);
            },
            // El nombre y el precio los pone el servidor con la presentación y opciones elegidas
            submitAddToCart(id, seleccion, excludedIngredients, comment) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('id', id);
                formData.append('quantity', 1);
                const elegido = MenuOpciones.payload(seleccion);
                if (elegido.variant_id) {
                    formData.append('variant_id', elegido.variant_id);
                }
                elegido.options.forEach((op, i) => {
                    formData.append(`options[${i}][id]`, op.id);
                    formData.append(`options[${i}][quantity]`, op.quantity);
                });
                excludedIngredients.forEach(ing => {
                    formData.append('excluded_ingredients[]', ing);
                });
                if (comment) {
                    formData.append('notes', comment);
                }

                fetch('{{ route('cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.cartData = data.cart;
                        this.updateGlobalCartBadge(data.total_items);
                        this.showNotification('¡Platillo agregado al pedido!');
                    } else {
                        this.showNotification(data.message || 'Error al agregar');
                    }
                })
                .catch(err => {
                    console.error(err);
                    this.showNotification('Error de conexión');
                });
            },
            updateCartQty(key, newQty) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('id', key);
                formData.append('quantity', newQty);

                fetch('{{ route('cart.update') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.cartData = data.cart;
                        this.updateGlobalCartBadge(data.total_items);
                        this.showNotification('Pedido actualizado');
                    } else {
                        this.showNotification(data.message || 'Error al actualizar');
                    }
                })
                .catch(err => {
                    console.error(err);
                    this.showNotification('Error de conexión');
                });
            },
            updateGlobalCartBadge(totalItems) {
                // Actualizar badge del menú superior
                const badge = document.getElementById('global-cart-badge');
                if (badge) {
                    if (totalItems > 0) {
                        badge.innerText = totalItems;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }

                // Actualizar botón flotante global
                const floatingBtn = document.getElementById('floating-cart-btn');
                const floatingBadge = document.getElementById('floating-cart-badge');
                if (floatingBtn && floatingBadge) {
                    if (totalItems > 0) {
                        floatingBadge.innerText = totalItems;
                        floatingBtn.style.display = 'flex';
                    } else {
                        floatingBtn.style.display = 'none';
                    }
                }
            },
            showNotification(message) {
                let container = document.getElementById('floating-notification-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'floating-notification-container';
                    container.style.position = 'fixed';
                    container.style.bottom = '30px';
                    container.style.right = '30px';
                    container.style.zIndex = '9999';
                    container.style.display = 'flex';
                    container.style.flexDirection = 'column';
                    container.style.gap = '10px';
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                toast.style.background = '#F97316';
                toast.style.color = '#FFFFFF';
                toast.style.padding = '12px 24px';
                toast.style.borderRadius = '30px';
                toast.style.fontWeight = 'bold';
                toast.style.fontSize = '14px';
                toast.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                toast.style.transition = 'all 0.3s ease-out';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                toast.innerText = message;

                container.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                }, 10);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                }, 2500);
            }
        }
    }
</script>

@endsection