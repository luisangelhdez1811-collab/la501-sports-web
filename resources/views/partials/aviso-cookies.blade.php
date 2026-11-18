{{--
    Aviso de cookies.

    - Sin Google Analytics configurado (GOOGLE_ANALYTICS_ID vacío): el sitio solo usa cookies técnicas, que no
      necesitan permiso; el aviso informa y basta con «Entendido».
    - Con Google Analytics: «Aceptar» y «Rechazar» con el mismo peso visual, nada premarcado, y gtag.js no se
      descarga hasta que la persona acepta. El enlace «Preferencias de cookies» del pie de página vuelve a abrir
      el aviso para cambiar la decisión (retirar el permiso debe ser tan fácil como darlo); al rechazar se
      borran las cookies _ga que hubiera.
    - La decisión se guarda en este navegador (localStorage la501_cookies) con su fecha.

    Lo de terceros que puede poner cookies sin Analytics es el mapa de Google, y ese se carga solo si la
    persona lo pide (página de Ubicación).
--}}
@php
    $gaId = \App\Support\Analytics::id();
    $gaAqui = \App\Support\Analytics::allowedOn(request());
@endphp
<div id="aviso-cookies" class="no-print" role="region" aria-label="Aviso de cookies" hidden
     style="position:fixed; left:16px; right:16px; bottom:16px; z-index:60; max-width:560px; margin:0 auto;
            background:#18181B; color:#F4F4F5; border:1px solid rgba(255,255,255,.12); border-radius:14px;
            padding:14px 16px; box-shadow:0 12px 32px rgba(0,0,0,.35); font-size:13px; line-height:1.55;">
    @if ($gaId)
        <p style="margin:0 0 10px;">
            Usamos <strong>cookies técnicas</strong>, necesarias para que el sitio funcione (tu sesión, tu carrito y la protección de los formularios).
            Con tu permiso usaríamos también <strong>cookies de analítica</strong> (Google Analytics) para saber cuántas personas nos visitan y mejorar el sitio.
            No usamos cookies de publicidad.
            <a href="{{ route('aviso-privacidad') }}#cookies" style="color:#FDBA74; text-decoration:underline;">Más información</a>.
        </p>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="button" data-cookies="aceptar"
                    style="flex:1 1 120px; min-height:40px; padding:0 18px; border:0; border-radius:10px; background:#C2410C; color:#fff; font-weight:700; font-size:13px; cursor:pointer;">
                Aceptar
            </button>
            <button type="button" data-cookies="rechazar"
                    style="flex:1 1 120px; min-height:40px; padding:0 18px; border:0; border-radius:10px; background:#C2410C; color:#fff; font-weight:700; font-size:13px; cursor:pointer;">
                Rechazar
            </button>
        </div>
    @else
        <p style="margin:0 0 10px;">
            Usamos solo <strong>cookies técnicas</strong>, necesarias para que el sitio funcione (tu sesión, tu carrito y la protección de los formularios).
            No usamos cookies de publicidad ni de rastreo.
            <a href="{{ route('aviso-privacidad') }}#cookies" style="color:#FDBA74; text-decoration:underline;">Más información</a>.
        </p>
        <button type="button" data-cookies="entendido"
                style="min-height:40px; padding:0 18px; border:0; border-radius:10px; background:#C2410C; color:#fff; font-weight:700; font-size:13px; cursor:pointer;">
            Entendido
        </button>
    @endif
</div>
<script>
    (function () {
        const CLAVE = 'la501_cookies';
        const GA_ID = @json($gaId);
        const MEDIR_AQUI = @json($gaAqui);
        const aviso = document.getElementById('aviso-cookies');

        function leer() {
            try { return JSON.parse(localStorage.getItem(CLAVE) || 'null'); } catch (e) { return null; }
        }
        function guardar(analitica) {
            try { localStorage.setItem(CLAVE, JSON.stringify({ v: 1, analitica: analitica, fecha: new Date().toISOString() })); } catch (e) {}
        }

        function cargarAnalytics() {
            if (!GA_ID || !MEDIR_AQUI || window.__la501GA) return;
            window.__la501GA = true;
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
            gtag('js', new Date());
            // Sin parámetros de la URL (pueden llevar códigos) y sin señales de publicidad
            gtag('config', GA_ID, {
                page_location: location.origin + location.pathname,
                allow_google_signals: false,
                allow_ad_personalization_signals: false,
            });
            const s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA_ID);
            document.head.appendChild(s);
        }

        function borrarCookiesDeAnalytics() {
            const dominio = location.hostname.replace(/^www\./, '');
            document.cookie.split(';').map(function (c) { return c.split('=')[0].trim(); })
                .filter(function (n) { return n === '_ga' || n.indexOf('_ga_') === 0 || n === '_gid' || n === '_gat'; })
                .forEach(function (n) {
                    [location.hostname, '.' + dominio, ''].forEach(function (d) {
                        document.cookie = n + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + (d ? '; domain=' + d : '');
                    });
                });
        }

        const decision = leer();
        if (GA_ID) {
            if (decision && decision.analitica === true) cargarAnalytics();
            if (!decision || typeof decision.analitica !== 'boolean') aviso.hidden = false;
        } else if (!decision) {
            // Quien ya cerró el aviso informativo antes de este cambio no lo vuelve a ver
            let visto = false;
            try { visto = localStorage.getItem('la501_aviso_cookies') === '1'; } catch (e) {}
            if (!visto) aviso.hidden = false;
        }

        aviso.addEventListener('click', function (e) {
            const boton = e.target.closest('[data-cookies]');
            if (!boton) return;
            const accion = boton.getAttribute('data-cookies');
            const antes = leer();
            if (accion === 'aceptar') {
                guardar(true);
                cargarAnalytics();
            } else if (accion === 'rechazar') {
                guardar(false);
                borrarCookiesDeAnalytics();
                // Si ya estaba cargado, se recarga para que deje de medir en esta visita
                if (antes && antes.analitica === true) { location.reload(); return; }
            } else {
                guardar(null);
            }
            aviso.hidden = true;
        });

        // Enlace «Preferencias de cookies» del pie de página
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-abrir-cookies]')) {
                e.preventDefault();
                aviso.hidden = false;
                const primero = aviso.querySelector('button');
                if (primero) primero.focus();
            }
        });
    })();
</script>
