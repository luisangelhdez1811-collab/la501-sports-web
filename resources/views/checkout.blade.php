@extends('layouts.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Barlow:wght@400;500&display=swap');

    .co-page {
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
    :root:not(.dark) .co-page {
        --bg:       #F5F3EF;
        --bg-card:  #FFFFFF;
        --bg-card2: #EBEBEB;
        --txt:      #1A1208;
        --txt-sub:  #6B6560;
        --bdr:      rgba(0,0,0,0.09);
    }

    /* ── TOPBAR ── */
    .co-topbar {
        position: relative;
        background: var(--bg-card);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='98'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23F97316' fill-opacity='0.04'%3E%3Cpath d='M28 66L0 49V16L28 0l28 16v33L28 66zm0-2l26-15V18L28 2 2 18v31l26 15zM56 98L28 81 0 98V81L0 66l28-16 28 16v15L28 98l28-17V98z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        border-bottom: 3px solid var(--or);
        padding: 32px 32px 28px;
        overflow: hidden;
    }
    .co-topbar::before {
        content: '';
        position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
        width: 500px; height: 180px;
        background: radial-gradient(ellipse, rgba(249,115,22,.14) 0%, transparent 70%);
        pointer-events: none;
    }
    .co-topbar-inner {
        max-width: 720px; margin: 0 auto;
        position: relative; z-index: 1;
    }
    .co-topbar-badge {
        display: inline-block;
        background: var(--or); color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 10px; font-weight: 700;
        letter-spacing: 3px; text-transform: uppercase;
        padding: 4px 16px; margin-bottom: 10px;
        clip-path: polygon(8px 0%,100% 0%,calc(100% - 8px) 100%,0% 100%);
    }
    .co-topbar-inner h1 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(40px, 6vw, 62px);
        line-height: .95; letter-spacing: 4px;
        color: var(--txt); margin: 0;
    }
    .co-topbar-inner h1 span { color: var(--or); }
    .co-topbar-inner p {
        font-size: 14px; color: var(--txt-sub); margin: 8px 0 0;
    }

    /* steps indicator */
    .co-steps {
        display: flex; align-items: center; gap: 0;
        margin-top: 20px;
    }
    .co-step {
        display: flex; align-items: center; gap: 6px;
        font-family: 'Oswald', sans-serif;
        font-size: 11px; font-weight: 600;
        letter-spacing: 1px; text-transform: uppercase;
    }
    .co-step-num {
        width: 22px; height: 22px; border-radius: 5px;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700;
    }
    .co-step.done   .co-step-num { background: var(--gn2); color: #fff; }
    .co-step.active .co-step-num { background: var(--or);  color: #fff; }
    .co-step.next   .co-step-num { background: var(--bdr); color: var(--txt-sub); }
    .co-step.done   .co-step-lbl { color: var(--gn2); }
    .co-step.active .co-step-lbl { color: var(--txt); }
    .co-step.next   .co-step-lbl { color: var(--txt-sub); }
    .co-step-sep {
        flex: 1; height: 1px; background: var(--bdr);
        margin: 0 10px; min-width: 20px; max-width: 40px;
    }

    /* ── MAIN ── */
    .co-main {
        max-width: 720px; margin: 0 auto;
        padding: 36px 32px 80px;
        background-color: var(--bg);
    }

    /* ── FORM CARD ── */
    .co-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 14px;
        overflow: hidden;
    }
    .co-card::before {
        content: '';
        display: block; height: 3px;
        background: linear-gradient(to right, var(--or), var(--gn));
    }

    /* section */
    .co-section { padding: 22px 24px; border-bottom: 1px solid var(--bdr); }
    .co-section:last-of-type { border-bottom: none; }
    .co-section-head {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 16px;
    }
    .co-section-icon {
        width: 30px; height: 30px; border-radius: 7px;
        background: rgba(249,115,22,.1);
        border: 1px solid rgba(249,115,22,.2);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .co-section-icon svg { width: 14px; height: 14px; color: var(--or); }
    .co-section-head h3 {
        font-family: 'Oswald', sans-serif;
        font-size: 14px; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase;
        color: var(--txt); margin: 0;
    }

    /* inputs */
    .co-label {
        display: block; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--txt-sub); margin-bottom: 5px;
    }
    .co-input {
        width: 100%; padding: 10px 12px;
        background: var(--bg-card2); border: 1px solid var(--bdr);
        border-radius: 8px; font-size: 13px; color: var(--txt);
        outline: none; transition: border-color .15s, box-shadow .15s;
        font-family: 'Barlow', sans-serif;
    }
    .co-input:focus {
        border-color: var(--or);
        box-shadow: 0 0 0 3px rgba(249,115,22,.12);
    }
    .co-input::placeholder { color: var(--txt-sub); opacity: .6; }
    .co-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    /* payment options */
    .co-pay-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .co-pay-option {
        position: relative; cursor: pointer;
    }
    .co-pay-option input[type="radio"] {
        position: absolute; opacity: 0; pointer-events: none;
    }
    .co-pay-label {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 16px;
        background: var(--bg-card2);
        border: 1px solid var(--bdr);
        border-radius: 9px;
        transition: border-color .2s, background .2s, box-shadow .2s;
        cursor: pointer;
    }
    .co-pay-option input:checked + .co-pay-label {
        border-color: var(--gn);
        background: rgba(22,163,74,.07);
        box-shadow: 0 0 0 3px rgba(22,163,74,.12);
    }
    .co-pay-icon {
        width: 36px; height: 36px; border-radius: 8px;
        background: var(--bg-card); border: 1px solid var(--bdr);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
        transition: border-color .2s;
    }
    .co-pay-option input:checked + .co-pay-label .co-pay-icon {
        border-color: var(--gn);
    }
    .co-pay-txt { flex: 1; }
    .co-pay-txt strong {
        display: block;
        font-family: 'Oswald', sans-serif;
        font-size: 13px; font-weight: 700; letter-spacing: .3px;
        color: var(--txt);
    }
    .co-pay-txt small {
        font-size: 11px; color: var(--txt-sub);
    }
    .co-pay-check {
        width: 18px; height: 18px; border-radius: 50%;
        border: 2px solid var(--bdr); flex-shrink: 0;
        transition: border-color .2s, background .2s;
        display: flex; align-items: center; justify-content: center;
    }
    .co-pay-option input:checked + .co-pay-label .co-pay-check {
        background: var(--gn); border-color: var(--gn);
    }
    .co-pay-check::after {
        content: '';
        width: 6px; height: 6px; border-radius: 50%;
        background: #fff; opacity: 0; transition: opacity .15s;
    }
    .co-pay-option input:checked + .co-pay-label .co-pay-check::after { opacity: 1; }

    /* ── FOOTER ── */
    .co-footer {
        background: var(--bg-card2);
        border-top: 1px solid var(--bdr);
        padding: 20px 24px;
        display: flex; align-items: center;
        justify-content: space-between; gap: 16px;
        flex-wrap: wrap;
    }
    .co-total-label {
        font-family: 'Oswald', sans-serif;
        font-size: 11px; font-weight: 700;
        letter-spacing: 2px; text-transform: uppercase;
        color: var(--txt-sub); display: block; margin-bottom: 2px;
    }
    .co-total-amount {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 40px; letter-spacing: 2px;
        color: var(--txt); line-height: 1;
    }
    .co-total-amount span { color: var(--or); }

    .co-submit-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 16px 32px;
        background: linear-gradient(135deg, var(--gn) 0%, var(--gn2) 100%);
        color: #fff;
        font-family: 'Oswald', sans-serif;
        font-size: 16px; font-weight: 800;
        letter-spacing: 2px; text-transform: uppercase;
        border: none; border-radius: 12px; cursor: pointer;
        box-shadow: 0 4px 18px rgba(22,163,74,.35);
        transition: background .2s, transform .15s, box-shadow .2s;
        animation: co-pulse-glow 2s infinite;
    }
    .co-submit-btn:hover {
        background: linear-gradient(135deg, var(--gn2) 0%, #4ade80 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(34,197,94,.5);
    }
    .co-submit-btn:active { transform: scale(.98) translateY(0); }
    .co-submit-btn svg { width: 16px; height: 16px; }

    @keyframes co-pulse-glow {
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

    /* back link */
    .co-back {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 13px; color: var(--txt-sub);
        text-decoration: none; margin-bottom: 20px;
        transition: color .15s;
    }
    .co-back:hover { color: var(--or); }
    .co-back svg { width: 14px; height: 14px; }

    @media (max-width: 560px) {
        .co-topbar { padding: 24px 20px 20px; }
        .co-main { padding: 24px 16px 60px; }
        .co-section { padding: 18px 16px; }
        .co-row-2 { grid-template-columns: 1fr; }
        .co-pay-grid { grid-template-columns: 1fr; }
        .co-footer { flex-direction: column; align-items: stretch; }
        .co-submit-btn { justify-content: center; }
        .co-steps { display: none; }
    }
</style>

<div class="co-page" style="background-color: var(--bg);">

    {{-- TOPBAR --}}
    <div class="co-topbar">
        <div class="co-topbar-inner">
            <div class="co-topbar-badge">La 501 Sports · Checkout</div>
            <h1>Finalizar <span>Pedido</span></h1>
            <p>Ingresa tus datos para la entrega a domicilio</p>

            {{-- Steps --}}
            {{-- FIX Web:S6819: div[role=list] → <ol>, div[role=listitem] → <li> --}}
            <ol class="co-steps" aria-label="Pasos del proceso" style="list-style:none;padding:0;margin:0;">
                <li class="co-step done">
                    <div class="co-step-num" aria-label="Paso completado">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="co-step-lbl">Carrito</span>
                </li>
                <li class="co-step-sep" aria-hidden="true"></li>
                <li class="co-step active" aria-current="step">
                    <div class="co-step-num">2</div>
                    <span class="co-step-lbl">Datos</span>
                </li>
                <li class="co-step-sep" aria-hidden="true"></li>
                <li class="co-step next">
                    <div class="co-step-num">3</div>
                    <span class="co-step-lbl">Confirmación</span>
                </li>
            </ol>
        </div>
    </div>

    <div class="co-main">

        {{-- Back --}}
        <a href="{{ route('cart.index') }}" class="co-back">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver al carrito
        </a>

        <form action="{{ route('checkout.process') }}" method="POST" class="co-card">
            @csrf

            {{-- Datos de Contacto --}}
            <div class="co-section">
                <div class="co-section-head">
                    <div class="co-section-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3>Datos de Contacto</h3>
                </div>
                <div class="co-row-2">
                    <div>
                        {{-- FIX L337: label for="customer-name" + id en el input --}}
                        <label class="co-label" for="customer-name">Nombre Completo *</label>
                        <input type="text" id="customer-name" name="customer_name"
                               value="{{ Auth::check() ? Auth::user()->name : '' }}"
                               required placeholder="Juan Pérez"
                               class="co-input">
                    </div>
                    <div>
                        {{-- FIX L344: label for="customer-phone" + id en el input --}}
                        <label class="co-label" for="customer-phone">Teléfono *</label>
                        <input type="tel" id="customer-phone" name="customer_phone"
                               required placeholder="10 dígitos"
                               class="co-input">
                    </div>
                </div>
                <div style="margin-top: 12px;">
                    <label class="co-label" for="customer-email">Correo (opcional)</label>
                    <input type="email" id="customer-email" name="customer_email"
                           value="{{ Auth::check() ? Auth::user()->email : '' }}"
                           placeholder="tucorreo@ejemplo.com"
                           class="co-input">
                    <p style="font-size: 11px; color: #A8A29E; margin: 4px 0 0;">Te avisamos por aquí cuando tu pedido esté listo y vaya en camino.</p>
                </div>
            </div>

            {{-- Dirección --}}
            <div class="co-section">
                <div class="co-section-head">
                    <div class="co-section-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3>Dirección de Entrega</h3>
                </div>
                <div>
                    {{-- FIX L363: label for="customer-address" + id en el textarea --}}
                    <label class="co-label" for="customer-address">Calle, Número, Colonia y Referencia *</label>
                    <textarea id="customer-address" name="customer_address" required rows="3"
                              placeholder="Ej: Av. Principal 123, Col. Centro. Casa blanca con portón negro."
                              class="co-input" style="resize: none;"></textarea>

                    {{-- Ubicación GPS opcional: la dirección escrita sigue siendo obligatoria --}}
                    <input type="hidden" name="delivery_lat" id="delivery-lat">
                    <input type="hidden" name="delivery_lng" id="delivery-lng">
                    <div id="geo-box" style="margin-top:10px; display:none;">
                        <button type="button" id="geo-btn" class="co-input" style="cursor:pointer; text-align:center; font-weight:600;">
                            📍 Usar mi ubicación
                        </button>
                        <p id="geo-msg" style="margin:6px 0 0; font-size:12px; opacity:.75;">
                            Opcional. Ayuda al repartidor a encontrar tu domicilio.
                        </p>
                    </div>
                    <script>
                        (function () {
                            if (!('geolocation' in navigator)) return;
                            var box = document.getElementById('geo-box');
                            var btn = document.getElementById('geo-btn');
                            var msg = document.getElementById('geo-msg');
                            var lat = document.getElementById('delivery-lat');
                            var lng = document.getElementById('delivery-lng');
                            box.style.display = 'block';

                            btn.addEventListener('click', function () {
                                btn.disabled = true;
                                msg.textContent = 'Obteniendo ubicación…';
                                navigator.geolocation.getCurrentPosition(function (pos) {
                                    lat.value = pos.coords.latitude.toFixed(7);
                                    lng.value = pos.coords.longitude.toFixed(7);
                                    btn.disabled = false;
                                    btn.textContent = '✅ Ubicación guardada (volver a obtener)';
                                    msg.textContent = 'Precisión aproximada: ±' + Math.round(pos.coords.accuracy) + ' m. Tu dirección escrita sigue siendo la principal.';
                                }, function () {
                                    lat.value = '';
                                    lng.value = '';
                                    btn.disabled = false;
                                    msg.textContent = 'No se pudo obtener tu ubicación. Escribe tu dirección y continúa con tu pedido.';
                                }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
                            });
                        })();
                    </script>
                </div>
            </div>

            {{-- Método de Pago --}}
            <div class="co-section">
                <div class="co-section-head">
                    <div class="co-section-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3>Método de Pago</h3>
                </div>
                <div class="co-pay-grid" role="radiogroup" aria-label="Selecciona método de pago">
                    <label class="co-pay-option">
                        <input type="radio" name="payment_method" value="efectivo" required>
                        <div class="co-pay-label">
                            <div class="co-pay-icon" aria-hidden="true">💵</div>
                            <div class="co-pay-txt">
                                <strong>Efectivo</strong>
                                <small>Al recibir</small>
                            </div>
                            <div class="co-pay-check" aria-hidden="true"></div>
                        </div>
                    </label>

                    <label class="co-pay-option">
                        <input type="radio" name="payment_method" value="tarjeta" required>
                        <div class="co-pay-label">
                            <div class="co-pay-icon" aria-hidden="true">💳</div>
                            <div class="co-pay-txt">
                                <strong>Tarjeta</strong>
                                <small>Débito o Crédito</small>
                            </div>
                            <div class="co-pay-check" aria-hidden="true"></div>
                        </div>
                    </label>
                </div>
            </div>

            <p style="font-size: 12px; color: #A8A29E; text-align: center; margin: 4px 0 12px; line-height: 1.5;">
                Este total es solo del platillo. El costo del envío lo cobra el repartidor por separado al entregar tu pedido.
            </p>

            {{-- Footer con total y submit --}}
            <div class="co-footer">
                <div>
                    <span class="co-total-label">Total a Pagar</span>
                    <span class="co-total-amount" aria-live="polite"><span aria-hidden="true">$</span>{{ number_format($total, 2) }}</span>
                </div>
                <button type="submit" class="co-submit-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Confirmar Pedido
                </button>
            </div>

        </form>
    </div>
</div>

@endsection
