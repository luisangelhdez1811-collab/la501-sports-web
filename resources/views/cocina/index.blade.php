<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantalla de cocina · La 501 Sports</title>
    <script>
        // Claro u oscuro: lo que se eligió en esta pantalla o, si no, el del sistema
        (function () {
            let tema = null;
            try { tema = localStorage.getItem('la501_tema'); } catch (e) {}
            if (tema !== 'light' && tema !== 'dark') tema = matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            document.documentElement.dataset.theme = tema;
        })();
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
         * Pantalla de cocina (KDS) pensada para un monitor fijo: todo cabe sin hacer scroll.
         * Las 3 órdenes más antiguas van en grande; el resto espera en la fila de abajo.
         *
         * Color con intención: lo único llamativo es el nombre del platillo (--food). Lo demás
         * es neutro, salvo los avisos que sí piden atención: "SIN" (alergias) y la espera
         * larga (ámbar a los 10 min, rojo a los 20).
         */
        :root, [data-theme="dark"] {
            --bg: #0b0b0c;
            --panel: #141416;
            --panel-2: #1b1b1e;
            --line: #2a2a2e;
            --check: #3f3f46;
            --txt: #f4f4f5;
            --muted: #a1a1aa;
            --faint: #71717a;
            --food: #fcd34d;
            --sin: #fca5a5;
            --sin-bg: rgba(239,68,68,.16);
            --warn: #f59e0b;
            --late: #ef4444;
            --hover: rgba(255,255,255,.03);
        }
        [data-theme="dark"] [data-station="barra"] { --food: #7dd3fc; }
        [data-theme="light"] {
            --bg: #e9e9ec;
            --panel: #ffffff;
            --panel-2: #f6f6f7;
            --line: #dcdce0;
            --check: #a1a1aa;
            --txt: #18181b;
            --muted: #52525b;
            --faint: #8a8a93;
            --food: #b4400b;
            --sin: #b91c1c;
            --sin-bg: rgba(220,38,38,.1);
            --warn: #b45309;
            --late: #dc2626;
            --hover: rgba(0,0,0,.03);
        }
        [data-theme="light"] [data-station="barra"] { --food: #0369a1; }
        [x-cloak] { display: none !important; }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            background: var(--bg); color: var(--txt);
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-tap-highlight-color: transparent;
            display: flex; flex-direction: column; overflow: hidden;
        }
        .cond { font-family: 'Barlow Condensed', 'Inter', sans-serif; letter-spacing: .02em; }
        button { font: inherit; color: inherit; cursor: pointer; }
        svg { flex-shrink: 0; }

        /* ── Barra superior ── */
        .kds-top {
            height: 60px; flex-shrink: 0; display: flex; align-items: center; gap: 24px;
            padding: 0 20px; background: var(--panel); border-bottom: 1px solid var(--line);
        }
        .kds-brand { display: flex; align-items: baseline; gap: 10px; min-width: 0; }
        .kds-brand b { font-size: 26px; font-weight: 700; text-transform: uppercase; }
        .kds-brand span { font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: .12em; }
        .kds-seg { display: flex; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; padding: 3px; }
        .kds-seg button {
            border: 0; background: transparent; padding: 7px 22px; border-radius: 7px;
            font-size: 15px; font-weight: 700; text-transform: uppercase; color: var(--muted);
        }
        .kds-seg button[aria-selected="true"] { background: var(--txt); color: var(--bg); }
        .kds-stats { display: flex; gap: 22px; margin-left: auto; align-items: center; }
        .kds-stat { text-align: right; line-height: 1.05; }
        .kds-stat b { display: block; font-size: 24px; font-weight: 700; }
        .kds-stat small { font-size: 11px; color: var(--faint); text-transform: uppercase; letter-spacing: .1em; }
        .kds-clock { font-size: 30px; font-weight: 700; min-width: 74px; text-align: right; }
        .kds-icon {
            width: 38px; height: 38px; display: grid; place-items: center;
            background: var(--panel-2); border: 1px solid var(--line); border-radius: 9px; color: var(--muted);
        }
        .kds-icon:hover { color: var(--txt); }
        .kds-icon.on { color: var(--txt); border-color: var(--muted); }
        .kds-live { display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--muted); }
        .kds-live i { width: 8px; height: 8px; border-radius: 50%; background: var(--faint); }
        .kds-live.off i { background: var(--late); }

        /* ── Órdenes en prioridad ── */
        .kds-main { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; padding: 14px; }
        .ticket {
            min-height: 0; display: flex; flex-direction: column;
            background: var(--panel); border: 1px solid var(--line); border-radius: 12px; overflow: hidden;
        }
        .ticket.closing { opacity: .7; }
        .ticket-head { display: flex; gap: 12px; align-items: stretch; padding: 12px 14px; border-top: 4px solid var(--line); background: var(--panel-2); }
        .ticket[data-age="warn"] .ticket-head { border-top-color: var(--warn); }
        .ticket[data-age="late"] .ticket-head { border-top-color: var(--late); }
        .ticket-key {
            width: 42px; flex-shrink: 0; display: grid; place-items: center; border-radius: 8px;
            background: var(--bg); border: 1px solid var(--line); font-size: 26px; font-weight: 700; color: var(--muted);
        }
        .ticket-title { min-width: 0; flex: 1; }
        .ticket-title h2 { margin: 0; font-size: 30px; line-height: 1; font-weight: 700; text-transform: uppercase; }
        .ticket-title p { margin: 4px 0 0; font-size: 13px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ticket-time { text-align: right; flex-shrink: 0; }
        .ticket-time b { display: block; font-size: 30px; line-height: 1; font-weight: 700; font-variant-numeric: tabular-nums; }
        .ticket[data-age="warn"] .ticket-time b { color: var(--warn); }
        .ticket[data-age="late"] .ticket-time b { color: var(--late); }
        .ticket-time small { font-size: 11px; color: var(--faint); text-transform: uppercase; letter-spacing: .08em; }
        .ticket-other { padding: 6px 14px; font-size: 12px; color: var(--muted); border-bottom: 1px solid var(--line); }
        .ticket-other b { color: var(--txt); }

        /* Lista de platillos: se compacta y pasa a 2 columnas si no cabe (data-density) */
        .ticket-body { flex: 1; min-height: 0; overflow: hidden; padding: 6px 8px; }
        .ticket-body[data-density="3"], .ticket-body[data-density="4"] { column-count: 2; column-gap: 8px; column-rule: 1px solid var(--line); }
        .item {
            width: 100%; display: flex; gap: 10px; align-items: flex-start; text-align: left;
            background: transparent; border: 0; border-radius: 8px; padding: 9px 8px;
            break-inside: avoid; border-bottom: 1px solid var(--line);
        }
        .item:last-child { border-bottom: 0; }
        .item:hover { background: var(--hover); }
        .item-check {
            width: 26px; height: 26px; border-radius: 6px; border: 2px solid var(--check);
            display: grid; place-items: center; color: transparent; margin-top: 1px;
        }
        .item.done .item-check { background: var(--check); border-color: var(--check); color: var(--panel); }
        .item-qty { min-width: 34px; font-size: 22px; font-weight: 700; line-height: 1.1; color: var(--food); }
        .item-info { min-width: 0; flex: 1; }
        .item-name { display: block; font-size: 19px; font-weight: 700; line-height: 1.2; color: var(--food); }
        .item.done .item-name, .item.done .item-qty { color: var(--faint); }
        .item.done .item-name { text-decoration: line-through; }
        .item.done .item-mods { opacity: .45; }
        .item-mods { display: block; margin-top: 3px; font-size: 14px; line-height: 1.3; }
        .item-mods .opt { color: var(--txt); }
        .item-mods .sin { color: var(--sin); font-weight: 600; }
        .item-mods .nota { color: var(--txt); font-weight: 600; font-style: italic; }
        .tag { font-size: 10px; font-weight: 700; font-style: normal; letter-spacing: .08em; padding: 1px 5px; border-radius: 4px; margin-right: 4px; vertical-align: 1px; }
        .tag.sin { background: var(--sin-bg); color: var(--sin); }
        .tag.nota { border: 1px solid var(--line); color: var(--muted); }
        .ticket-body[data-density="2"] .item, .ticket-body[data-density="4"] .item { padding: 5px 6px; gap: 8px; }
        .ticket-body[data-density="2"] .item-name, .ticket-body[data-density="4"] .item-name { font-size: 15px; }
        .ticket-body[data-density="2"] .item-qty, .ticket-body[data-density="4"] .item-qty { font-size: 17px; min-width: 28px; }
        .ticket-body[data-density="2"] .item-mods, .ticket-body[data-density="4"] .item-mods { font-size: 12px; margin-top: 1px; }
        .ticket-body[data-density="2"] .item-check, .ticket-body[data-density="4"] .item-check { width: 20px; height: 20px; }
        .ticket-body[data-density="3"] .item-name { font-size: 16px; }
        /*
         * Último recurso (comanda enorme en pantalla chica): 2 columnas con desplazamiento y un
         * aviso fijo. Nunca se ocultan platillos sin avisar.
         */
        .ticket-body[data-density="5"] {
            column-count: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 0 8px; align-content: start;
            overflow-y: auto; position: relative;
        }
        .ticket-body[data-density="5"] .item { padding: 5px 6px; gap: 8px; }
        .ticket-body[data-density="5"] .item-name { font-size: 15px; }
        .ticket-body[data-density="5"] .item-qty { font-size: 17px; min-width: 28px; }
        .ticket-body[data-density="5"] .item-mods { font-size: 12px; margin-top: 1px; }
        .ticket-body[data-density="5"] .item-check { width: 20px; height: 20px; }
        .ticket-body[data-density="5"]::after {
            content: 'Hay más platillos · desliza'; grid-column: 1 / -1; position: sticky; bottom: 0;
            padding: 6px; text-align: center; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
            background: var(--panel-2); color: var(--txt); border: 1px solid var(--warn); border-radius: 6px;
        }

        .ticket-foot { padding: 10px 12px; border-top: 1px solid var(--line); background: var(--panel-2); }
        .btn-done {
            width: 100%; height: 52px; border: 1px solid var(--line); border-radius: 9px; background: var(--panel); color: var(--txt);
            font-size: 20px; font-weight: 700; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-done:hover { border-color: var(--muted); }
        .btn-done kbd, .closing-bar kbd {
            font-family: inherit; font-size: 13px; padding: 1px 7px; border-radius: 5px; background: var(--panel-2); border: 1px solid var(--line); color: var(--muted);
        }
        .closing-bar { height: 52px; display: flex; align-items: center; gap: 12px; }
        .closing-bar p { flex: 1; margin: 0; font-size: 18px; font-weight: 700; color: var(--txt); text-transform: uppercase; }
        .closing-bar button {
            height: 100%; padding: 0 18px; border-radius: 9px; border: 1px solid var(--line); background: var(--bg);
            font-size: 16px; font-weight: 700; text-transform: uppercase; display: flex; align-items: center; gap: 8px;
        }
                .ticket-empty {
            display: grid; place-items: center; border: 1px dashed var(--line); border-radius: 12px; color: var(--faint);
            font-size: 18px; text-transform: uppercase; letter-spacing: .1em;
        }

        /* ── Fila de espera ── */
        .kds-queue { height: 112px; flex-shrink: 0; display: flex; gap: 10px; align-items: stretch; padding: 0 14px 14px; }
        .queue-label {
            width: 110px; flex-shrink: 0; display: flex; flex-direction: column; justify-content: center;
            border-radius: 10px; background: var(--panel); border: 1px solid var(--line); padding: 0 14px;
        }
        .queue-label b { font-size: 34px; line-height: 1; font-weight: 700; }
        .queue-label small { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; }
        .queue-list { flex: 1; min-width: 0; display: flex; gap: 10px; overflow: hidden; }
        .queue-card {
            width: 230px; flex-shrink: 0; background: var(--panel); border: 1px solid var(--line); border-left: 4px solid var(--line);
            border-radius: 10px; padding: 9px 12px; display: flex; flex-direction: column; justify-content: space-between; min-width: 0;
        }
        .queue-card[data-age="warn"] { border-left-color: var(--warn); }
        .queue-card[data-age="late"] { border-left-color: var(--late); }
        .queue-card header { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
        .queue-card h3 { margin: 0; font-size: 21px; font-weight: 700; text-transform: uppercase; }
        .queue-card time { font-size: 18px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .queue-card[data-age="warn"] time { color: var(--warn); }
        .queue-card[data-age="late"] time { color: var(--late); }
        .queue-card p { margin: 0; font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .queue-more { flex-shrink: 0; display: grid; place-items: center; padding: 0 18px; color: var(--muted); font-size: 16px; font-weight: 700; }
        .queue-empty { display: flex; align-items: center; color: var(--faint); font-size: 14px; padding-left: 6px; }

        .kds-idle { grid-column: 1 / -1; display: grid; place-items: center; text-align: center; color: var(--muted); }
        .kds-idle h2 { margin: 0 0 6px; font-size: 40px; font-weight: 700; color: var(--txt); text-transform: uppercase; }

        /* Pantallas chicas (tableta): se permite scroll y menos columnas */
        @media (max-width: 1100px) {
            body { overflow: auto; }
            .kds-main { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ticket { min-height: 420px; }
            .kds-stats .kds-stat { display: none; }
        }
        @media (max-width: 720px) {
            .kds-top { flex-wrap: wrap; height: auto; padding: 10px 14px; gap: 12px; }
            .kds-main { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body x-data="pantallaCocina()" :data-station="estacion">

    {{-- BARRA SUPERIOR --}}
    <header class="kds-top">
        <div class="kds-brand cond">
            <b x-text="estacion === 'barra' ? 'Barra' : 'Cocina'">Cocina</b>
            <span>La 501 Sports</span>
        </div>

        <div class="kds-seg cond" role="tablist" aria-label="Estación">
            <button type="button" role="tab" :aria-selected="estacion === 'cocina'" @click="cambiarEstacion('cocina')">Cocina</button>
            <button type="button" role="tab" :aria-selected="estacion === 'barra'" @click="cambiarEstacion('barra')">Barra</button>
        </div>

        <div class="kds-stats cond">
            <div class="kds-stat"><b x-text="ordenes.length">0</b><small>Órdenes</small></div>
            <div class="kds-stat"><b x-text="platillosPendientes()">0</b><small>Platillos</small></div>
            <div class="kds-stat"><b x-text="esperaMaxima()">—</b><small>Mayor espera</small></div>
            <div class="kds-live" :class="enLinea ? '' : 'off'"><i></i><span x-text="enLinea ? 'En vivo' : 'Sin conexión'"></span></div>
            <div class="kds-clock" x-text="reloj()"></div>
            <button type="button" class="kds-icon" :class="sonido ? 'on' : ''" @click="alternarSonido()" :title="sonido ? 'Sonido de nuevas órdenes: activado' : 'Activar sonido de nuevas órdenes'" :aria-label="sonido ? 'Desactivar sonido' : 'Activar sonido'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path x-show="sonido" d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/><path x-show="!sonido" d="m22 9-6 6m0-6 6 6"/></svg>
            </button>
            <button type="button" class="kds-icon" @click="alternarTema()" :title="tema === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'" :aria-label="tema === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'">
                <svg x-show="tema === 'dark'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                <svg x-show="tema === 'light'" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </button>
            <button type="button" class="kds-icon" @click="cargarOrdenes()" title="Actualizar ahora" aria-label="Actualizar ahora">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36L21 8"/><path d="M21 3v5h-5"/></svg>
            </button>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="kds-icon" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                </button>
            </form>
        </div>
    </header>

    {{-- ÓRDENES EN PRIORIDAD (las 3 más antiguas) --}}
    <main class="kds-main">
        <template x-if="ordenes.length === 0 && cargado">
            <div class="kds-idle cond">
                <div>
                    <h2>Sin órdenes pendientes</h2>
                    <p x-text="'La pantalla se actualiza sola. Última revisión: ' + ultimaRevision"></p>
                </div>
            </div>
        </template>

        <template x-for="(orden, idx) in prioridad()" :key="orden.id">
            <section class="ticket" :class="orden.cierre ? 'closing' : ''" :data-age="nivelEspera(orden)" :aria-label="titulo(orden)">
                <div class="ticket-head cond">
                    <div class="ticket-key" x-text="idx + 1" title="Tecla para despachar"></div>
                    <div class="ticket-title">
                        <h2 x-text="titulo(orden)"></h2>
                        <p x-text="'#' + orden.folio + (orden.detail ? ' · ' + orden.detail : '')"></p>
                    </div>
                    <div class="ticket-time">
                        <b x-text="cronometro(orden.created_at)"></b>
                        <small>En espera</small>
                    </div>
                </div>
                <div class="ticket-other" x-show="orden.other_station_pending > 0">
                    <span x-text="estacion === 'barra' ? 'Cocina' : 'Barra'"></span> tiene <b x-text="orden.other_station_pending"></b> pendiente(s) de esta orden
                </div>

                <div class="ticket-body" data-density="1">
                    <template x-for="item in orden.items" :key="item.id">
                        <button type="button" class="item" :class="item.prepared ? 'done' : ''" @click="alternar(orden, item)" :disabled="!!orden.cierre"
                                :aria-pressed="item.prepared" :aria-label="(item.prepared ? 'Desmarcar ' : 'Marcar listo ') + item.product_name">
                            <span class="item-check"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 10 17 19 7"/></svg></span>
                            <span class="item-qty cond" x-text="item.quantity + '×'"></span>
                            <span class="item-info">
                                <span class="item-name" x-text="item.product_name"></span>
                                <span class="item-mods" x-show="item.options || (item.excluded_ingredients || []).length || item.notes">
                                    <span class="opt" x-show="item.options" x-text="item.options"></span>
                                    <template x-if="(item.excluded_ingredients || []).length">
                                        <span class="sin" style="display:block"><span class="tag sin">SIN</span><span x-text="item.excluded_ingredients.join(', ')"></span></span>
                                    </template>
                                    <template x-if="item.notes">
                                        <span class="nota" style="display:block"><span class="tag nota">NOTA</span><span x-text="item.notes"></span></span>
                                    </template>
                                </span>
                            </span>
                        </button>
                    </template>
                </div>

                <div class="ticket-foot cond">
                    <template x-if="!orden.cierre">
                        <button type="button" class="btn-done" @click="todoListo(orden)">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 10 17 19 7"/></svg>
                            Orden lista <kbd x-text="idx + 1"></kbd>
                        </button>
                    </template>
                    <template x-if="orden.cierre">
                        <div class="closing-bar">
                            <p x-text="'Despachando en ' + orden.cierre.segundos + ' s'"></p>
                            <button type="button" @click="deshacerCierre(orden)">Deshacer <kbd>U</kbd></button>
                        </div>
                    </template>
                </div>
            </section>
        </template>

        <template x-for="n in espaciosVacios()" :key="'vacio-' + n">
            <div class="ticket-empty cond">Sin orden</div>
        </template>
    </main>

    {{-- FILA DE ESPERA: lo que sigue, para no olvidarlo --}}
    <footer class="kds-queue cond" aria-label="Órdenes en espera">
        <div class="queue-label"><b x-text="enCola().length">0</b><small>En cola</small></div>
        <div class="queue-list" x-ref="cola">
            <template x-for="orden in colaVisible()" :key="orden.id">
                <article class="queue-card" :data-age="nivelEspera(orden)">
                    <header>
                        <h3 x-text="titulo(orden)"></h3>
                        <time x-text="cronometro(orden.created_at)"></time>
                    </header>
                    <p x-text="'#' + orden.folio + ' · ' + totalPlatillos(orden) + ' platillo(s)'"></p>
                    <p x-text="orden.items.map(i => i.quantity + '× ' + i.product_name).join(', ')"></p>
                </article>
            </template>
            <div class="queue-more" x-show="enCola().length > colaVisible().length" x-text="'+' + (enCola().length - colaVisible().length) + ' más'"></div>
            <div class="queue-empty" x-show="enCola().length === 0">No hay más órdenes en espera.</div>
        </div>
    </footer>

    <script>
        const ORDENES_EN_PRIORIDAD = 3;
        const SEGUNDOS_PARA_QUITAR = 4;
        // Minutos de espera para pasar a amarillo y a rojo
        const MINUTOS_AVISO = 10;
        const MINUTOS_TARDE = 20;
        const ANCHO_TARJETA_COLA = 240;

        function pantallaCocina() {
            return {
                ordenes: [],
                estacion: 'cocina',
                enLinea: true,
                cargado: false,
                ultimaRevision: '',
                ahora: Date.now(),
                sonido: false,
                audio: null,
                tema: document.documentElement.dataset.theme || 'dark',
                vistas: new Set(),

                init() {
                    try {
                        const guardada = localStorage.getItem('la501_estacion');
                        if (guardada === 'barra' || guardada === 'cocina') this.estacion = guardada;
                        this.sonido = localStorage.getItem('la501_sonido') === '1';
                    } catch (e) {}

                    this.cargarOrdenes();
                    setInterval(() => this.cargarOrdenes(), 10000);
                    setInterval(() => { this.ahora = Date.now(); }, 1000);
                    window.addEventListener('resize', () => this.ajustar());
                    window.addEventListener('keydown', (e) => this.teclado(e));
                    this.$watch('ordenes', () => this.$nextTick(() => this.ajustar()));
                },

                // ── Datos ──
                prioridad() { return this.ordenes.slice(0, ORDENES_EN_PRIORIDAD); },
                enCola() { return this.ordenes.slice(ORDENES_EN_PRIORIDAD); },
                colaVisible() {
                    const ancho = this.$refs.cola ? this.$refs.cola.clientWidth : 1200;
                    const caben = Math.max(1, Math.floor((ancho - 90) / ANCHO_TARJETA_COLA));
                    return this.enCola().slice(0, caben);
                },
                espaciosVacios() {
                    const faltan = this.ordenes.length === 0 ? 0 : ORDENES_EN_PRIORIDAD - this.prioridad().length;
                    return Array.from({ length: Math.max(0, faltan) }, (_, i) => i + 1);
                },
                totalPlatillos(orden) { return orden.items.reduce((s, i) => s + i.quantity, 0); },
                platillosPendientes() {
                    return this.ordenes.reduce((s, o) => s + o.items.filter(i => !i.prepared).reduce((t, i) => t + i.quantity, 0), 0);
                },
                titulo(orden) { return orden.table_number ? 'Mesa ' + orden.table_number : 'Domicilio'; },

                // ── Tiempo ──
                minutos(fecha) { return Math.max(0, Math.floor((this.ahora - new Date(fecha)) / 60000)); },
                cronometro(fecha) {
                    const seg = Math.max(0, Math.floor((this.ahora - new Date(fecha)) / 1000));
                    const m = Math.floor(seg / 60), s = seg % 60;
                    return (m >= 60 ? Math.floor(m / 60) + ':' + String(m % 60).padStart(2, '0') : m) + ':' + String(s).padStart(2, '0');
                },
                nivelEspera(orden) {
                    const m = this.minutos(orden.created_at);
                    return m >= MINUTOS_TARDE ? 'late' : (m >= MINUTOS_AVISO ? 'warn' : 'ok');
                },
                esperaMaxima() {
                    if (!this.ordenes.length) return '—';
                    return this.minutos(this.ordenes[0].created_at) + ' min';
                },
                reloj() {
                    return new Date(this.ahora).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });
                },

                /**
                 * Si una comanda no cabe, se compacta y luego pasa a dos columnas (sin scroll). Si
                 * aun así no cabe, se permite desplazar con un aviso visible: nunca se oculta un platillo.
                 */
                ajustar() {
                    const noCabe = el => el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1;
                    document.querySelectorAll('.ticket-body').forEach(el => {
                        let densidad = 1;
                        el.dataset.density = densidad;
                        while (noCabe(el) && densidad < 4) {
                            el.dataset.density = ++densidad;
                        }
                        if (noCabe(el)) el.dataset.density = 5;
                    });
                },

                // ── Conexión con el servidor ──
                cambiarEstacion(estacion) {
                    if (this.estacion === estacion) return;
                    this.estacion = estacion;
                    try { localStorage.setItem('la501_estacion', estacion); } catch (e) {}
                    this.ordenes = [];
                    this.vistas = new Set();
                    this.cargado = false;
                    this.cargarOrdenes();
                },

                async cargarOrdenes() {
                    try {
                        const res = await fetch('/api/cocina/ordenes?estacion=' + this.estacion, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error();
                        const data = await res.json();

                        // Las comandas en cuenta regresiva se quedan como están en pantalla
                        const enCierre = this.ordenes.filter(o => o.cierre);
                        const nuevas = data.filter(o => !this.vistas.has(o.id));
                        if (this.cargado && nuevas.length) this.avisar();
                        data.forEach(o => this.vistas.add(o.id));

                        this.ordenes = data
                            .filter(o => !enCierre.some(c => c.id === o.id))
                            .concat(enCierre)
                            .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                        this.enLinea = true;
                        this.cargado = true;
                        this.ultimaRevision = this.reloj();
                    } catch (e) {
                        this.enLinea = false;
                    }
                },

                async enviarItem(item, listo) {
                    const res = await fetch(`/cocina/item/${item.id}/listo`, {
                        method: 'POST', headers: this.headers(), body: JSON.stringify({ listo })
                    });
                    if (!res.ok) throw new Error();
                },

                async enviarOrden(orden) {
                    const res = await fetch(`/cocina/orden/${orden.id}/ready`, {
                        method: 'POST', headers: this.headers(), body: JSON.stringify({ estacion: this.estacion })
                    });
                    if (!res.ok) throw new Error();
                },

                headers() {
                    return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };
                },

                // ── Marcar platillos ──
                async alternar(orden, item) {
                    const listo = !item.prepared;
                    item.prepared = listo;

                    // El último platillo no se manda de inmediato: hay unos segundos para deshacer
                    if (listo && orden.items.every(i => i.prepared)) {
                        this.iniciarCierre(orden, () => this.enviarItem(item, true), [item]);
                        return;
                    }

                    try {
                        await this.enviarItem(item, listo);
                    } catch (e) {
                        item.prepared = !listo;
                        this.enLinea = false;
                    }
                },

                todoListo(orden) {
                    if (orden.cierre) return;
                    const pendientes = orden.items.filter(i => !i.prepared);
                    pendientes.forEach(i => i.prepared = true);
                    this.iniciarCierre(orden, () => this.enviarOrden(orden), pendientes);
                },

                // marcados = lo que se marcó localmente y todavía no se envía (lo que deshace "Deshacer")
                iniciarCierre(orden, enviar, marcados) {
                    orden.cierre = { segundos: SEGUNDOS_PARA_QUITAR, marcados, timer: null, inicio: Date.now() };
                    orden.cierre.timer = setInterval(async () => {
                        orden.cierre.segundos--;
                        if (orden.cierre.segundos > 0) return;

                        clearInterval(orden.cierre.timer);
                        try {
                            await enviar();
                            this.ordenes = this.ordenes.filter(o => o.id !== orden.id);
                        } catch (e) {
                            orden.cierre.marcados.forEach(i => i.prepared = false);
                            orden.cierre = null;
                            this.enLinea = false;
                        }
                    }, 1000);
                },

                deshacerCierre(orden) {
                    clearInterval(orden.cierre.timer);
                    orden.cierre.marcados.forEach(i => i.prepared = false);
                    orden.cierre = null;
                },

                /** Sin tocar la pantalla: 1, 2 y 3 despachan esa orden; U deshace la última. */
                teclado(e) {
                    if (e.target instanceof Element && e.target.closest('input, textarea')) return;
                    const n = parseInt(e.key, 10);
                    if (n >= 1 && n <= ORDENES_EN_PRIORIDAD) {
                        const orden = this.prioridad()[n - 1];
                        if (orden) this.todoListo(orden);
                    } else if (e.key === 'u' || e.key === 'U') {
                        const ultima = this.ordenes.filter(o => o.cierre).sort((a, b) => b.cierre.inicio - a.cierre.inicio)[0];
                        if (ultima) this.deshacerCierre(ultima);
                    } else if (e.key === 'r' || e.key === 'R') {
                        this.cargarOrdenes();
                    }
                },

                alternarTema() {
                    this.tema = this.tema === 'dark' ? 'light' : 'dark';
                    document.documentElement.dataset.theme = this.tema;
                    try { localStorage.setItem('la501_tema', this.tema); } catch (e) {}
                },

                // ── Aviso sonoro de orden nueva: para notarla sin estar viendo la pantalla ──
                alternarSonido() {
                    this.sonido = !this.sonido;
                    try { localStorage.setItem('la501_sonido', this.sonido ? '1' : '0'); } catch (e) {}
                    if (this.sonido) this.avisar();
                },

                avisar() {
                    if (!this.sonido) return;
                    try {
                        this.audio = this.audio || new (window.AudioContext || window.webkitAudioContext)();
                        [0, 0.18].forEach((t, i) => {
                            const osc = this.audio.createOscillator();
                            const gain = this.audio.createGain();
                            osc.frequency.value = i ? 1175 : 880;
                            gain.gain.setValueAtTime(0.0001, this.audio.currentTime + t);
                            gain.gain.exponentialRampToValueAtTime(0.25, this.audio.currentTime + t + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.0001, this.audio.currentTime + t + 0.16);
                            osc.connect(gain).connect(this.audio.destination);
                            osc.start(this.audio.currentTime + t);
                            osc.stop(this.audio.currentTime + t + 0.18);
                        });
                    } catch (e) {}
                },
            }
        }
    </script>
</body>
</html>
