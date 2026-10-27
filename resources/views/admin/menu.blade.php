@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.js"></script>
<style>
.am {
    --ac:  #2563EB; --ac2: #1D4ED8;
    --gn:  #16A34A; --rd:  #DC2626;
    --bg:  #F8F8F8; --card: #FFFFFF;
    --inp: #F4F4F5; --txt:  #18181B;
    --sub: #71717A; --bdr:  #E4E4E7;
}
[x-cloak] { display: none !important; }
.dark .am {
    --bg:  #0A0A0A; --card: #111111;
    --inp: #1C1C1C; --txt:  #FAFAFA;
    --sub: #71717A; --bdr:  rgba(255,255,255,.08);
}
.am { background:var(--bg); min-height:100%; padding:28px 32px 60px; color:var(--txt); }

/* ── HEADER ─────────────────────────────── */
.am-hd { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.am-hd h1 { font-size:20px; font-weight:700; margin:0 0 2px; color:var(--txt); }
.am-hd p  { font-size:13px; color:var(--sub); margin:0; }

.am-btn-add, .am-btn-export, .am-btn-import {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:7px;
    color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer;
    transition:background .15s; box-shadow:0 1px 4px rgba(0,0,0,.15); text-decoration: none;
}
.am-btn-add { background:var(--ac); }
.am-btn-add:hover { background:var(--ac2); }

.am-btn-export { background:#475569; }
.am-btn-export:hover { background:#334155; }

.am-btn-import { background:var(--gn); }
.am-btn-import:hover { background:#15803D; }
.am-btn-add svg, .am-btn-export svg, .am-btn-import svg { width:14px; height:14px; }

/* ── STATS ───────────────────────────────── */
.am-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.am-stat {
    flex:1; min-width:140px;
    background:var(--card); border:1px solid var(--bdr);
    border-radius:8px; padding:12px 18px;
    display:flex; align-items:center; gap:10px;
}
.am-stat-ico { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.am-stat-ico svg { width:14px; height:14px; }
.am-stat-ico.bl { background:rgba(37,99,235,.1);  color:#2563EB; }
.am-stat-ico.gn { background:rgba(22,163,74,.1);  color:#16A34A; }
.am-stat-ico.gy { background:rgba(113,113,122,.1);color:#71717A; }
.am-stat-ico.or { background:rgba(249,115,22,.1); color:#EA580C; }
.dark .am-stat-ico.bl { color:#60A5FA; }
.dark .am-stat-ico.gn { color:#4ADE80; }
.dark .am-stat-ico.or { color:#FB923C; }
.am-stat-v { font-size:20px; font-weight:800; letter-spacing:-.3px; color:var(--txt); margin:0 0 1px; line-height:1; }
.am-stat-l { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:var(--sub); }

/* ── FILTROS ─────────────────────────────── */
.am-filters {
    display:flex; gap:6px; overflow-x:auto; padding-bottom:4px;
    scrollbar-width:none; -ms-overflow-style:none;
}
.am-filters::-webkit-scrollbar { display:none; }
.am-fbtn {
    padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600;
    border:1px solid var(--bdr); background:var(--card); color:var(--sub);
    cursor:pointer; white-space:nowrap; flex-shrink:0;
    transition:background .15s, color .15s, border-color .15s;
}
.am-fbtn:hover { color:var(--txt); border-color:#C4C4C8; }
.am-fbtn.on { background:var(--ac); color:#fff; border-color:var(--ac); }

/* flechas de navegación del filtro */
.am-filters-wrap { position:relative; padding:0 30px; margin-bottom:14px; }
.am-filters-wrap .am-filters { margin-bottom:0; }
.am-filters-nav {
    position:absolute; top:50%; transform:translateY(-50%);
    width:26px; height:26px; border-radius:50%;
    border:1.5px solid rgba(37,99,235,.4);
    background:rgba(37,99,235,.1); color:var(--ac);
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:700; line-height:1;
    cursor:pointer; z-index:5; padding:0;
    box-shadow:0 2px 8px rgba(0,0,0,.15);
    transition:background .2s, color .2s, border-color .2s, transform .15s;
}
.am-filters-nav:hover  { background:var(--ac); color:#fff; border-color:var(--ac); transform:translateY(-50%) scale(1.08); }
.am-filters-nav:active { transform:translateY(-50%) scale(.94); }
.am-filters-nav.left  { left:0; }
.am-filters-nav.right { right:0; }
.am-filters-fade { position:absolute; top:0; bottom:4px; width:28px; pointer-events:none; z-index:4; }
.am-filters-fade.left  { left:0;  background:linear-gradient(to right, var(--bg), transparent); }
.am-filters-fade.right { right:0; background:linear-gradient(to left,  var(--bg), transparent); }

/* ── TABLE CARD ──────────────────────────── */
.am-card { background:var(--card); border:1px solid var(--bdr); border-radius:10px; overflow:hidden; }
.am-card-hd { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-bottom:1px solid var(--bdr); }
.am-card-ttl { display:flex; align-items:center; gap:8px; }
.am-card-ttl-ico { width:26px; height:26px; border-radius:6px; background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.14); display:flex; align-items:center; justify-content:center; }
.am-card-ttl-ico svg { width:13px; height:13px; color:var(--ac); }
.am-card-ttl span { font-size:13px; font-weight:700; color:var(--txt); }
.am-badge { font-size:11px; font-weight:600; color:var(--sub); background:var(--inp); border:1px solid var(--bdr); border-radius:20px; padding:2px 9px; }

/* ── ROWS ────────────────────────────────── */
.am-row { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; gap:14px; border-bottom:1px solid var(--bdr); transition:background .15s; }
.am-row:last-child { border-bottom:none; }
.am-row:hover { background:var(--bg); }
.am-row-l { display:flex; align-items:center; gap:12px; flex:1; min-width:0; }
.am-thumb { width:48px; height:48px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1px solid var(--bdr); background:var(--inp); }
.am-thumb-ph { width:48px; height:48px; border-radius:8px; background:var(--inp); border:1px solid var(--bdr); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
.am-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.am-dot.on  { background:#22C55E; box-shadow:0 0 0 2px rgba(34,197,94,.2); }
.am-dot.off { background:#71717A; }
.am-row-info { min-width:0; }
.am-row-name { font-size:13px; font-weight:600; color:var(--txt); margin:0 0 2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.am-row-name.off { color:var(--sub); text-decoration:line-through; }
.am-row-meta { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.am-chip-cat { font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; background:var(--inp); color:var(--sub); border:1px solid var(--bdr); border-radius:4px; padding:1px 7px; }
.am-chip-sub { font-size:10px; font-weight:600; background:rgba(37,99,235,.07); color:var(--ac); border:1px solid rgba(37,99,235,.15); border-radius:4px; padding:1px 7px; }
.am-price { font-size:12px; font-weight:700; color:var(--gn); }
.am-row-actions { display:flex; gap:6px; flex-shrink:0; }
.am-act { width:32px; height:32px; border-radius:6px; border:1px solid var(--bdr); background:var(--inp); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--sub); transition:background .15s, border-color .15s, color .15s; }
.am-act svg { width:13px; height:13px; }
.am-act.ed:hover { border-color:var(--ac); color:var(--ac); background:rgba(37,99,235,.06); }
.am-act.dl:hover { border-color:var(--rd); color:var(--rd); background:rgba(220,38,38,.06); }

/* ── EMPTY ───────────────────────────────── */
.am-empty { padding:60px 20px; text-align:center; }
.am-empty svg { width:36px; height:36px; color:var(--bdr); margin:0 auto 10px; display:block; }
.am-empty p { font-size:13px; color:var(--sub); margin:0; }

/* ══════════════════════════════════════════════
   MODAL ALPINE (EDICIÓN)
══════════════════════════════════════════════ */
.am-overlay {
    position:fixed; inset:0; z-index:50;
    background:rgba(0,0,0,.55); backdrop-filter:blur(4px);
    display:flex; align-items:flex-start; justify-content:center;
    padding:40px 16px 60px; overflow-y:auto;
}
.am-modal { background:var(--card); border:1px solid var(--bdr); border-radius:12px; width:100%; max-width:600px; box-shadow:0 24px 64px rgba(0,0,0,.24); flex-shrink:0; overflow:hidden; }
.am-mhd { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--bdr); background:var(--card); }
.am-mhd h3 { font-size:15px; font-weight:700; color:var(--txt); margin:0 0 2px; }
.am-mhd p  { font-size:12px; color:var(--sub); margin:0; }
.am-mclose { width:28px; height:28px; border-radius:7px; background:var(--inp); border:1px solid var(--bdr); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--sub); transition:background .15s; flex-shrink:0; }
.am-mclose:hover { background:var(--bdr); }
.am-mclose svg { width:13px; height:13px; }
.am-mbody { padding:20px; display:flex; flex-direction:column; gap:18px; }
.am-sec { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--sub); padding-bottom:8px; border-bottom:1px solid var(--bdr); }

/* imagen */
.am-imgbox { position:relative; border-radius:10px; overflow:hidden; border:2px dashed var(--bdr); background:var(--inp); width:180px; aspect-ratio:1/1; margin:0 auto; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:border-color .2s; }
.am-imgbox:hover { border-color:var(--ac); }
.am-imgph { text-align:center; pointer-events:none; }
.am-imgph svg { width:28px; height:28px; color:var(--sub); margin:0 auto 6px; display:block; }
.am-imgph p { font-size:12px; color:var(--sub); margin:0; }
.am-imgprev { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.am-imgovl { position:absolute; inset:0; background:rgba(0,0,0,.42); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .2s; pointer-events:none; font-size:12px; color:#fff; font-weight:600; gap:6px; text-align:center; padding:0 10px; }
.am-imgovl svg { width:15px; height:15px; }
.am-imgbox:hover .am-imgovl { opacity:1; }
.am-imghelp { font-size:11px; color:var(--sub); text-align:center; margin:8px auto 0; max-width:320px; line-height:1.6; }
.am-imghelp strong { color:var(--txt); }

/* ── RECORTE DE IMAGEN (Cropper.js) ── */
.am-crop-overlay {
    position:fixed; inset:0; z-index:70;
    background:rgba(0,0,0,.65); backdrop-filter:blur(4px);
    display:flex; align-items:center; justify-content:center; padding:20px;
}
.am-crop-modal {
    background:var(--card); border:1px solid var(--bdr); border-radius:12px;
    width:100%; max-width:460px; box-shadow:0 24px 64px rgba(0,0,0,.35);
    display:flex; flex-direction:column; overflow:hidden;
}
.am-crop-hd { padding:16px 20px; border-bottom:1px solid var(--bdr); }
.am-crop-hd h3 { font-size:15px; font-weight:700; color:var(--txt); margin:0 0 2px; }
.am-crop-hd p  { font-size:12px; color:var(--sub); margin:0; }
.am-crop-body { background:#000; height:360px; }
.am-crop-body img { display:block; max-width:100%; }
.am-crop-foot { padding:14px 20px; border-top:1px solid var(--bdr); display:flex; align-items:center; justify-content:space-between; gap:8px; }
.am-crop-hint { font-size:11px; color:var(--sub); }
.am-crop-actions { display:flex; gap:8px; }
@media(max-width:480px){ .am-crop-body { height:300px; } .am-crop-foot { flex-direction:column; align-items:stretch; } }

/* grid y campos */
.am-g2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.am-lbl { display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); margin-bottom:5px; }
.am-lbl .req { color:var(--ac); }
.am-inp, .am-sel, .am-ta {
    width:100%; padding:8px 10px; background:var(--inp); border:1px solid var(--bdr);
    border-radius:7px; font-size:13px; color:var(--txt); outline:none;
    transition:border-color .15s, box-shadow .15s; font-family:inherit;
}
.am-inp:focus, .am-sel:focus, .am-ta:focus { border-color:var(--ac); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.am-ta  { resize:vertical; min-height:72px; }
.am-sel { cursor:pointer; }
.am-subnotice { margin-top:6px; padding:6px 10px; border-radius:6px; font-size:11px; color:var(--ac); background:rgba(37,99,235,.06); border:1px solid rgba(37,99,235,.14); }

/* toggle */
.am-trow { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-radius:8px; background:var(--inp); border:1px solid var(--bdr); }
.am-tlbl { font-size:13px; font-weight:600; color:var(--txt); margin:0 0 2px; }
.am-tsub { font-size:11px; color:var(--sub); }
.am-tog  { position:relative; width:38px; height:22px; flex-shrink:0; }
.am-tog input { opacity:0; width:0; height:0; }
.am-tog-sl { position:absolute; inset:0; border-radius:20px; background:var(--bdr); cursor:pointer; transition:background .2s; }
.am-tog-sl::before { content:''; position:absolute; left:3px; top:3px; width:16px; height:16px; border-radius:50%; background:#fff; transition:transform .2s; }
.am-tog input:checked + .am-tog-sl { background:var(--gn); }
.am-tog input:checked + .am-tog-sl::before { transform:translateX(16px); }

/* ── INGREDIENTES ───────── */
.am-ing-wrap { position:relative; }
.am-ing-search { position:relative; }
.am-ing-search-ico { position:absolute; left:9px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--sub); pointer-events:none; }
.am-ing-search .am-inp { padding-left:30px; }
.am-ing-dropdown { position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--card); border:1px solid var(--bdr); border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:60; overflow:hidden; max-height:200px; display:flex; flex-direction:column; }
.am-ing-dd-search { padding:8px 10px; border-bottom:1px solid var(--bdr); display:flex; align-items:center; gap:8px; flex-shrink:0; }
.am-ing-dd-search svg { width:13px; height:13px; color:var(--sub); flex-shrink:0; }
.am-ing-dd-search input { flex:1; background:none; border:none; outline:none; font-size:12px; color:var(--txt); font-family:inherit; }
.am-ing-dd-list { overflow-y:auto; flex:1; scrollbar-width:thin; scrollbar-color:var(--bdr) transparent; }
.am-ing-dd-list::-webkit-scrollbar { width:4px; }
.am-ing-dd-list::-webkit-scrollbar-thumb { background:var(--bdr); border-radius:4px; }
.am-ing-dd-item { padding:8px 12px; font-size:12px; font-weight:500; color:var(--txt); cursor:pointer; display:flex; align-items:center; gap:8px; border-bottom:1px solid var(--bdr); transition:background .1s; }
.am-ing-dd-item:last-child { border-bottom:none; }
.am-ing-dd-item:hover { background:rgba(37,99,235,.06); color:var(--ac); }
.am-ing-dd-item svg { width:11px; height:11px; color:var(--sub); flex-shrink:0; }
.am-ing-dd-empty { padding:14px 12px; font-size:12px; color:var(--sub); text-align:center; font-style:italic; }
.am-ing-chips { display:flex; flex-wrap:wrap; gap:6px; }
.am-ing-chip { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.18); font-size:12px; font-weight:600; color:var(--ac); }
.am-ing-chip button { width:14px; height:14px; border-radius:50%; background:rgba(37,99,235,.15); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--ac); line-height:1; padding:0; transition:background .15s; }
.am-ing-chip button:hover { background:rgba(220,38,38,.2); color:var(--rd); }
.am-ing-chip button svg { width:8px; height:8px; }
.am-ing-add-btn { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--ac); background:rgba(37,99,235,.07); border:1px solid rgba(37,99,235,.14); border-radius:20px; padding:5px 12px; cursor:pointer; transition:background .15s; }
.am-ing-add-btn:hover { background:rgba(37,99,235,.13); }
.am-ing-add-btn svg { width:11px; height:11px; }
.am-mfoot { padding:14px 20px; border-top:1px solid var(--bdr); display:flex; align-items:center; justify-content:flex-end; gap:8px; }
.am-btn-cancel { padding:8px 16px; border-radius:7px; background:var(--inp); border:1px solid var(--bdr); font-size:13px; font-weight:600; color:var(--sub); cursor:pointer; transition:background .15s; }
.am-btn-cancel:hover { background:var(--bdr); }
.am-btn-save { padding:8px 20px; border-radius:7px; background:var(--ac); color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s; display:flex; align-items:center; gap:6px; }
.am-btn-save:hover { background:var(--ac2); }
.am-btn-save svg { width:14px; height:14px; }

/* ── MODAL SEGURO (Nativo para Importación) ── */
.mi-modal-seguro {
    display: none;
    position: fixed !important;
    inset: 0 !important;
    z-index: 9999999 !important;
    background: #18181B !important;
    backdrop-filter: blur(8px) !important;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

@media(max-width:640px){
    .am { padding:20px 16px 40px; }
    .am-stats { display:grid; grid-template-columns:1fr 1fr; }
    .am-g2 { grid-template-columns:1fr; }
    .am-overlay { padding:20px 12px 40px; }
    .am-hd { flex-direction: column; align-items: flex-start; }
}
</style>

{{-- MENSAJES DE ÉXITO --}}
@if(session('success'))
    <div style="background: var(--gn); color: white; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        {{ session('success') }}
    </div>
@endif
@if(session('error') || $errors->any())
    <div style="background: #DC2626; color: white; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center;">
        {{ session('error') ?: $errors->first() }}
    </div>
@endif

<div class="am" x-data="menuAdmin()" x-init="init()">

    {{-- HEADER --}}
    <div class="am-hd">
        <div>
            <h1>Gestionar Menú</h1>
            <p>{{ $products->count() }} platillos registrados</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            {{-- BOTÓN IMPORTAR (Abre Modal Seguro) --}}
            <button type="button" onclick="abrirModalImportar()" class="am-btn-import">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar Menú
            </button>
            <a href="{{ route('admin.menu.categories') }}" class="am-btn-import" style="text-decoration:none">
                Categorías
            </a>
            <a href="{{ route('admin.menu.options') }}" class="am-btn-import" style="text-decoration:none">
                Salsas y extras
            </a>

            {{-- BOTÓN EXPORTAR (Descarga Directa) --}}
            <a href="{{ route('admin.menu.export') }}" class="am-btn-export">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Descargar Excel
            </a>

            {{-- BOTÓN AGREGAR ORIGINAL (Alpine) --}}
            <button @click="openNew()" class="am-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Agregar Platillo
            </button>
        </div>
    </div>

    {{-- STATS --}}
    <div class="am-stats">
        <div class="am-stat">
            <div class="am-stat-ico bl">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div><p class="am-stat-v" id="am-stat-total">{{ $products->count() }}</p><p class="am-stat-l">Total</p></div>
        </div>
        <div class="am-stat">
            <div class="am-stat-ico gn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div><p class="am-stat-v" id="am-stat-disponibles">{{ $products->where('available', true)->count() }}</p><p class="am-stat-l">Disponibles</p></div>
        </div>
        <div class="am-stat">
            <div class="am-stat-ico gy">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <div><p class="am-stat-v" id="am-stat-no-disponibles">{{ $products->where('available', false)->count() }}</p><p class="am-stat-l">No disponibles</p></div>
        </div>
        <div class="am-stat">
            <div class="am-stat-ico or">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div><p class="am-stat-v">{{ $products->pluck('category')->unique()->count() }}</p><p class="am-stat-l">Categorías</p></div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="am-filters-wrap">
        <button type="button" class="am-filters-nav left" @click="scrollFilters($event, -1)" aria-label="Ver categorías anteriores">‹</button>
        <div class="am-filters-fade left" aria-hidden="true"></div>
        <div class="am-filters">
            <button @click="filter='todos'" :class="filter==='todos'?'on':''" class="am-fbtn">Todos</button>
            <template x-for="cat in categorias" :key="cat">
                <button @click="filter=cat" :class="filter===cat?'on':''" class="am-fbtn" x-text="cat"></button>
            </template>
        </div>
        <div class="am-filters-fade right" aria-hidden="true"></div>
        <button type="button" class="am-filters-nav right" @click="scrollFilters($event, 1)" aria-label="Ver más categorías">›</button>
    </div>

    {{-- TABLE --}}
    <div class="am-card">
        <div class="am-card-hd">
            <div class="am-card-ttl">
                <div class="am-card-ttl-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <span>Platillos</span>
            </div>
            <span class="am-badge" id="am-badge-count">{{ $products->count() }} registros</span>
        </div>

        @foreach($products as $product)
        <div class="am-row"
             x-show="filter==='todos'||filter==='{{ $product->category }}'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            <div class="am-row-l">
                @if($product->image)
                    <img src="{{ (str_starts_with($product->image, 'http://') || str_starts_with($product->image, 'https://')) ? $product->image : asset('storage/'.$product->image) }}" class="am-thumb" alt="{{ $product->name }}">
                @else
                    <div class="am-thumb-ph">🍔</div>
                @endif
                <div class="am-dot {{ $product->available ? 'on' : 'off' }}"></div>
                <div class="am-row-info">
                    <p class="am-row-name {{ $product->available ? '' : 'off' }}">{{ $product->name }}</p>
                    <div class="am-row-meta">
                        <span class="am-chip-cat">{{ $product->category }}</span>
                        @if($product->subcategory)
                            <span class="am-chip-sub">{{ $product->subcategory }}</span>
                        @endif
                        <span class="am-chip-sub" title="Quién lo prepara">
                            {{ ['cocina' => '🍳 Cocina', 'barra' => '🍹 Barra', 'ninguna' => '🧊 Directo'][$product->station] ?? '🍳 Cocina' }}
                        </span>
                        @if($product->variants->isNotEmpty())
                            <span class="am-price">{{ $product->variants->map(fn ($v) => $v->name . ' $' . number_format($v->price, 0))->implode(' · ') }}</span>
                        @else
                            <span class="am-price">${{ number_format($product->price, 2) }}</span>
                        @endif
                        @if($product->optionGroups->isNotEmpty())
                            <span class="am-chip-sub" title="Opciones">⚙️ {{ $product->optionGroups->pluck('name')->implode(', ') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="am-row-actions">
                <button class="am-act ed" title="Editar"
                        @click="openEdit({{ $product }}, {{ $product->ingredientes->pluck('nombre') }})">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <form action="{{ route('admin.menu.destroy', $product->id) }}" method="POST"
                      @submit.prevent="confirmDelete($el)">
                    @csrf @method('DELETE')
                    <button type="submit" class="am-act dl" title="Eliminar">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>
        @endforeach

        @if($products->isEmpty())
        <div class="am-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p>No hay platillos registrados aún.</p>
        </div>
        @endif
    </div>

    {{-- MODAL ORIGINAL DE EDICIÓN (ALPINE) --}}
    <div x-show="modalOpen" x-cloak class="am-overlay" @click.self="modalOpen=false">
        <div class="am-modal" @click.stop>

            <div class="am-mhd">
                <div>
                    <h3 x-text="isEdit ? 'Editar Platillo' : 'Nuevo Platillo'" :aria-label="isEdit ? 'Editar Platillo' : 'Nuevo Platillo'">Platillo</h3>
                    <p x-text="isEdit ? 'Modifica los datos del platillo seleccionado' : 'Completa la información del nuevo platillo'"></p>
                </div>
                <button @click="modalOpen=false" class="am-mclose">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="formAction" method="POST" enctype="multipart/form-data">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <template x-for="(ing, idx) in ingredients" :key="idx">
                    <input type="hidden" :name="'ingredients['+idx+']'" :value="ing">
                </template>

                <div class="am-mbody">

                    <div>
                        <p class="am-sec">Imagen</p>
                        <div class="am-imgbox" style="margin-top:10px" @click="$refs.imgFile.click()">
                            <input type="file" name="image" accept="image/*"
                                   x-ref="imgFile" style="display:none"
                                   @change="previewImage($event)">
                            <template x-if="!imagePreview">
                                <div class="am-imgph">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p>Haz clic para subir imagen</p>
                                </div>
                            </template>
                            <template x-if="imagePreview">
                                <img :src="imagePreview" class="am-imgprev" alt="Vista previa del platillo">
                            </template>
                            <div class="am-imgovl">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Cambiar imagen
                            </div>
                        </div>
                        <p class="am-imghelp">Recomendado: imagen <strong>cuadrada (1:1)</strong>, mínimo 800×800px.<br>Aceptamos JPG, PNG, WEBP y la mayoría de formatos.</p>
                    </div>

                    <div class="am-trow">
                        <div>
                            <p class="am-tlbl">Disponible en menú</p>
                            <p class="am-tsub">El platillo será visible para los clientes</p>
                        </div>
                        <label class="am-tog">
                            <input type="checkbox" name="available" value="1" x-model="formData.available">
                            <span class="am-tog-sl"></span>
                        </label>
                    </div>

                    <div>
                        <p class="am-sec">Información básica</p>
                        <div class="am-g2" style="margin-top:10px">
                            <div>
                                <label for="menu-name" class="am-lbl">Nombre <span class="req">*</span></label>
                                <input id="menu-name" type="text" name="name" class="am-inp" x-model="formData.name" placeholder="Ej: Hamburguesa La 501" required>
                            </div>
                            <div>
                                <label for="menu-price" class="am-lbl">Precio ($) <span class="req">*</span></label>
                                <input id="menu-price" type="number" name="price" class="am-inp" step="0.01" min="0" x-model="formData.price" placeholder="0.00" required>
                            </div>
                        </div>
                        <div style="margin-top:12px">
                            <label for="menu-description" class="am-lbl">Descripción</label>
                            <textarea id="menu-description" name="description" class="am-ta" x-model="formData.description" placeholder="Describe brevemente el platillo..."></textarea>
                        </div>
                    </div>

                    <div>
                        <p class="am-sec">Clasificación</p>
                        <div class="am-g2" style="margin-top:10px">
                            <div>
                                <label for="menu-category" class="am-lbl">Categoría <span class="req">*</span></label>
                                <select id="menu-category" name="category" class="am-sel" x-model="formData.category" required>
                                    @foreach(['comidas' => 'Comidas', 'bebidas' => 'Bebidas'] as $seccion => $titulo)
                                        <optgroup label="{{ $titulo }}">
                                            @foreach($categorias->where('section', $seccion) as $categoria)
                                                <option value="{{ $categoria->name }}">{{ $categoria->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="am-imghelp" style="text-align:left;margin:4px 0 0">¿Falta una? Agrégala en <a href="{{ route('admin.menu.categories') }}" style="color:var(--gn);font-weight:700">Categorías</a>.</p>
                            </div>

                            <div>
                                <label for="menu-subcategory" class="am-lbl">Subcategoría</label>
                                <input id="menu-subcategory" type="text" name="subcategory" class="am-inp" list="am-subcategorias" x-model="formData.subcategory" placeholder="Opcional (Ej: Tequila)" autocomplete="off">
                                <datalist id="am-subcategorias">
                                    <template x-for="sub in (subcatMap[formData.category] || [])" :key="sub">
                                        <option :value="sub"></option>
                                    </template>
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="am-sec">Carta y preparación</p>
                        <div class="am-g2" style="margin-top:10px">
                            <div style="grid-column:1 / -1">
                                <label for="menu-station" class="am-lbl">¿Quién lo prepara? <span class="req">*</span></label>
                                <select id="menu-station" name="station" class="am-sel" x-model="formData.station">
                                    <option value="cocina">Cocina</option>
                                    <option value="barra">Barra (bebida preparada)</option>
                                    <option value="ninguna">Nadie: se entrega directo (refresco, cerveza)</option>
                                </select>
                            </div>
                            <div>
                                <label for="menu-presentation" class="am-lbl">Presentación / porción</label>
                                <input id="menu-presentation" type="text" name="presentation" class="am-inp" x-model="formData.presentation" placeholder="Ej: 3 pzs, 350 g · 1 salsa">
                            </div>
                            <div>
                                <label for="menu-sort" class="am-lbl">Orden en la carta</label>
                                <input id="menu-sort" type="number" min="0" name="sort_order" class="am-inp" x-model="formData.sort_order" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="am-sec">Presentaciones con precio propio</p>
                        <p class="am-imghelp" style="text-align:left;margin:6px 0 10px">Ej: Vaso $80 / Litro $160, o Copeo $75 / Botella $770. Si hay presentaciones, el precio del platillo será el de la más barata.</p>
                        <template x-for="(v, idx) in variants" :key="idx">
                            <div style="display:flex; gap:8px; margin-bottom:8px; align-items:center">
                                <input type="text" class="am-inp" :name="'variants['+idx+'][name]'" x-model="v.name" placeholder="Nombre (Ej: Litro)" :aria-label="'Nombre de la presentación ' + (idx + 1)">
                                <input type="number" step="0.01" min="0" class="am-inp" style="max-width:120px" :name="'variants['+idx+'][price]'" x-model="v.price" placeholder="$" :aria-label="'Precio de la presentación ' + (idx + 1)">
                                <button type="button" class="am-act dl" @click="variants.splice(idx, 1)" title="Quitar presentación">&times;</button>
                            </div>
                        </template>
                        <button type="button" class="am-ing-add-btn" @click.prevent="variants.push({ name: '', price: '' })">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Agregar presentación
                        </button>
                    </div>

                    <div>
                        <p class="am-sec">Opciones del platillo</p>
                        <p class="am-imghelp" style="text-align:left;margin:6px 0 10px"><strong>Obligatorio:</strong> no se puede pedir sin elegir (ej. el tipo de papa). <strong>Opcional:</strong> solo se le recuerda a quien toma el pedido (ej. la salsa). Las listas se editan en <a href="{{ route('admin.menu.options') }}" style="color:var(--gn);font-weight:700">Salsas y extras</a>.</p>
                        @forelse($grupos as $grupo)
                            <div style="display:flex; gap:10px; align-items:center; padding:8px 10px; border:1px solid var(--bdr); border-radius:10px; margin-bottom:6px">
                                <label style="flex:1; display:flex; gap:8px; align-items:center; font-size:13px; cursor:pointer">
                                    <input type="checkbox" name="groups[{{ $grupo->id }}][on]" value="1" x-model="groups[{{ $grupo->id }}].on">
                                    <span><strong>{{ $grupo->name }}</strong> <span style="color:var(--sub)">· {{ $grupo->label }} ({{ $grupo->options_count }})</span></span>
                                </label>
                                <template x-if="groups[{{ $grupo->id }}].on">
                                    <div style="display:flex; gap:6px; align-items:center; font-size:12px">
                                        <select class="am-sel" style="width:auto; padding:6px 28px 6px 8px" aria-label="¿{{ $grupo->name }} es obligatorio?"
                                                :value="groups[{{ $grupo->id }}].min > 0 ? 'si' : 'no'"
                                                @change="groups[{{ $grupo->id }}].min = $event.target.value === 'si' ? 1 : 0">
                                            <option value="no">Opcional</option>
                                            <option value="si">Obligatorio</option>
                                        </select>
                                        <input type="hidden" name="groups[{{ $grupo->id }}][min]" :value="groups[{{ $grupo->id }}].min">
                                        <label for="grp-max-{{ $grupo->id }}">hasta</label>
                                        <input id="grp-max-{{ $grupo->id }}" type="number" min="1" max="20" class="am-inp" style="width:60px; padding:6px" name="groups[{{ $grupo->id }}][max]" x-model="groups[{{ $grupo->id }}].max">
                                    </div>
                                </template>
                            </div>
                        @empty
                            <p style="font-size:12px;color:var(--sub);font-style:italic">Aún no hay listas de opciones. Créalas en «Salsas y extras» o importa el Excel del menú.</p>
                        @endforelse
                    </div>

                    <div>
                        <p class="am-sec">Ingredientes</p>
                        <div class="am-ing-chips" style="margin-top:10px; margin-bottom:10px; min-height:28px;">
                            <template x-if="ingredients.length === 0">
                                <span style="font-size:12px; color:var(--sub); font-style:italic;">Ningún ingrediente añadido aún</span>
                            </template>
                            <template x-for="(ing, idx) in ingredients" :key="idx">
                                <span class="am-ing-chip">
                                    <span x-text="ing"></span>
                                    <button type="button" @click.prevent="removeIngredient(idx)" title="Quitar">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </span>
                            </template>
                        </div>

                        <div class="am-ing-wrap">
                            <div class="am-ing-search">
                                <svg class="am-ing-search-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                                <input type="text" class="am-inp" placeholder="Buscar ingrediente del inventario..." x-model="ingQuery" @focus="ingOpen=true" @input="ingOpen=true" @keydown.escape="ingOpen=false" @keydown.enter.prevent="ingPickFirst()" autocomplete="off" x-ref="ingInput">
                            </div>

                            <div class="am-ing-dropdown" x-show="ingOpen && ingFiltered().length > 0" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                <div class="am-ing-dd-list">
                                    <template x-for="item in ingFiltered()" :key="item.id">
                                        <div class="am-ing-dd-item" @mousedown.prevent="ingSelect(item)">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <span x-text="item.name"></span>
                                            <template x-if="ingredients.includes(item.name)">
                                                <svg style="margin-left:auto;color:var(--gn);width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div x-show="ingOpen && ingQuery.trim() && ingFiltered().length === 0" class="am-ing-dd-empty" style="border:1px solid var(--bdr); border-radius:8px; margin-top:4px;">
                                Sin coincidencias en inventario
                            </div>
                        </div>

                        <button type="button" class="am-ing-add-btn" style="margin-top:8px" @click.prevent="ingAddFree()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Escribir ingrediente libre
                        </button>
                    </div>

                </div>

                <div class="am-mfoot">
                    <button type="button" @click="modalOpen=false" class="am-btn-cancel">Cancelar</button>
                    <button type="submit" class="am-btn-save">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="isEdit ? 'Guardar Cambios' : 'Crear Platillo'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DE RECORTE DE IMAGEN (Cropper.js) --}}
    <div x-show="cropperOpen" x-cloak class="am-crop-overlay" @click.self="cancelCrop()">
        <div class="am-crop-modal" @click.stop>
            <div class="am-crop-hd">
                <h3>Ajustar imagen</h3>
                <p>Mueve y haz zoom para elegir qué parte se mostrará (siempre en formato cuadrado)</p>
            </div>
            <div class="am-crop-body">
                <template x-if="cropperSrc">
                    <img x-ref="cropperImg" :src="cropperSrc">
                </template>
            </div>
            <div class="am-crop-foot">
                <span class="am-crop-hint">Se guardará como imagen cuadrada de 800×800px</span>
                <div class="am-crop-actions">
                    <button type="button" @click="cancelCrop()" class="am-btn-cancel">Cancelar</button>
                    <button type="button" @click="confirmCrop()" class="am-btn-save">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Recortar y usar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL IMPORTAR MENÚ --}}
<div id="modal-import-menu" style="display:none; position:fixed; inset:0; z-index:9999999; background:rgba(0,0,0,.6); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:480px; box-shadow:0 32px 80px rgba(0,0,0,.18); overflow:hidden; font-family:inherit;">

        <div style="height:4px; background:linear-gradient(90deg,#16A34A,#2563EB);"></div>

        <div style="padding:24px 24px 0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:40px; height:40px; border-radius:10px; background:#f0fdf4; border:1px solid #bbf7d0; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg style="width:18px;height:18px;color:#16A34A;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <div>
                        <p style="margin:0 0 2px; font-size:15px; font-weight:700; color:#111827;">Importar Menú</p>
                        <p style="margin:0; font-size:12px; color:#6b7280;">Actualiza el catálogo desde un archivo CSV</p>
                    </div>
                </div>
                <button onclick="cerrarModalImportar()" style="width:30px; height:30px; border-radius:8px; background:#f9fafb; border:1px solid #e5e7eb; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#6b7280; flex-shrink:0;">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 14px; margin-bottom:12px;">
                <p style="margin:0 0 6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#1d4ed8;">📋 Instrucciones</p>
                <p style="margin:0; font-size:12px; color:#1e40af; line-height:1.7;">
                    1. Sube el <strong>Excel del menú (.xlsx)</strong> con las hojas Comidas, Bebidas y Extras y Salsas, o un CSV.<br>
                    2. Haz clic en <strong>"Subir y Actualizar"</strong>.<br>
                    3. Con el Excel se carga todo: precios, presentaciones, salsas y extras. Lo que ya no viene se archiva (no se borra).
                </p>
            </div>

            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:10px 14px; margin-bottom:20px; display:flex; gap:8px; align-items:center;">
                <svg style="width:15px;height:15px;color:#dc2626;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p style="margin:0; font-size:12px; color:#dc2626; line-height:1.5;">Archivos <strong>.xlsx</strong> (Excel del menú) o <strong>.csv</strong> — formato CSV: <code style="background:#fee2e2; padding:1px 5px; border-radius:4px; font-size:11px;">Nombre,Precio,Categoria,Subcategoria,Descripcion,Disponible</code></p>
            </div>
        </div>
        {{-- NUEVO BOTÓN DE PLANTILLA --}}
            <div style="text-align: right; margin-bottom: 20px;">
                <a href="{{ route('admin.menu.template') }}" style="font-size:12px; color:#2563EB; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition: color 0.2s;">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Descargar plantilla de ejemplo
                </a>
            </div>
        </div>

        <form action="{{ route('admin.menu.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="padding:0 24px 20px;">
                <label for="menu-csv-file" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; margin-bottom:8px;">
                    Seleccionar archivo <span style="color:#2563EB;">*</span>
                </label>
                <div id="menu-csv-dropzone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:28px 20px; background:#f9fafb; border:2px dashed #d1d5db; border-radius:10px; cursor:pointer; transition:border-color .2s;">
                    <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span style="font-size:13px; font-weight:600; color:#374151;">Haz clic para seleccionar</span>
                    <span style="font-size:11px; color:#9ca3af;" id="csv-filename">Ningún archivo seleccionado</span>
                    <input type="file" id="menu-csv-file" name="csv_file" accept=".csv,.xlsx" required style="display:none;"
                        onchange="document.getElementById('csv-filename').textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado'">
                </div>
            </div>

            <div style="padding:14px 24px 20px; border-top:1px solid #f3f4f6; display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="cerrarModalImportar()" style="padding:9px 18px; border-radius:8px; background:#f9fafb; border:1px solid #e5e7eb; font-size:13px; font-weight:600; color:#6b7280; cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" style="padding:9px 20px; border-radius:8px; background:#2563EB; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; box-shadow:0 2px 8px rgba(37,99,235,.3);">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Subir y Actualizar
                </button>
            </div>
        </form>

    </div>
</div>

<script>
// --- Funciones nativas para el modal de Importar ---
function abrirModalImportar() {
    document.getElementById('modal-import-menu').style.setProperty('display', 'flex', 'important');
}
function cerrarModalImportar() {
    document.getElementById('modal-import-menu').style.setProperty('display', 'none', 'important');
}

// --- Código Original de Alpine.js ---
const INVENTARIO = @json($inventario);
const CATEGORIAS_MENU = @json($products->pluck('category')->unique()->values());
// Categorías registradas: la primera es la que se propone en un platillo nuevo
const CATEGORIAS_REGISTRADAS = @json($categorias->pluck('name'));
// Subcategorías que ya existen en cada categoría (sugerencias del formulario)
const SUBCATEGORIAS_MENU = @json($products->whereNotNull('subcategory')->groupBy('category')->map(fn ($ps) => $ps->pluck('subcategory')->unique()->values()));
// Listas de opciones con los mínimos/máximos que se proponen al marcarlas
const GRUPOS_OPCIONES = @json($grupos->map(fn ($g) => ['id' => $g->id, 'min' => $g->min_select, 'max' => $g->max_select]));

function gruposVacios() {
    const grupos = {};
    GRUPOS_OPCIONES.forEach(g => { grupos[g.id] = { on: false, min: g.min, max: g.max }; });
    return grupos;
}

const menuDz = document.getElementById('menu-csv-dropzone');
    if (menuDz) {
        menuDz.addEventListener('mouseenter', () => menuDz.style.borderColor = '#2563EB');
        menuDz.addEventListener('mouseleave', () => menuDz.style.borderColor = '#d1d5db');
        menuDz.addEventListener('click', () => document.getElementById('menu-csv-file').click());
    }

function menuAdmin() {
    return {
        filter: 'todos',
        categorias: CATEGORIAS_MENU,
        modalOpen:    false,
        isEdit:       false,
        formAction:   '{{ route('admin.menu.store') }}',
        imagePreview: null,
        cropperOpen:  false,
        cropperSrc:   null,
        cropperInstance: null,
        formData: {
            name:'', description:'', price:'',
            category: CATEGORIAS_REGISTRADAS[0] || '', subcategory:'', available:true,
            section:'comidas', station:'cocina', presentation:'', sort_order:0,
        },
        variants: [],
        groups: gruposVacios(),
        ingredients: [],
        ingQuery: '',
        ingOpen:  false,
        subcatMap: SUBCATEGORIAS_MENU,

        init() {
            const saved = localStorage.getItem('am_menu_filter');
            if (saved && (saved === 'todos' || this.categorias.includes(saved))) {
                this.filter = saved;
            }
            this.$watch('filter', (value) => {
                localStorage.setItem('am_menu_filter', value);
            });
            this.$nextTick(() => {
                const activeBtn = this.$el.querySelector('.am-filters .am-fbtn.on');
                if (activeBtn) activeBtn.scrollIntoView({ inline: 'center', block: 'nearest' });
            });
        },

        scrollFilters(event, dir) {
            const wrap = event.currentTarget.closest('.am-filters-wrap');
            const row  = wrap ? wrap.querySelector('.am-filters') : null;
            if (row) row.scrollBy({ left: dir * 220, behavior: 'smooth' });
        },

        openNew() {
            this.isEdit       = false;
            this.formAction   = '{{ route('admin.menu.store') }}';
            this.imagePreview = null;
            this.ingredients  = [];
            this.ingQuery     = '';
            this.ingOpen      = false;
            this.formData     = {
                name:'', description:'', price:'',
                category: CATEGORIAS_REGISTRADAS.includes(this.filter) ? this.filter : (CATEGORIAS_REGISTRADAS[0] || ''), subcategory:'', available:true,
                section:'comidas', station:'cocina', presentation:'', sort_order:0,
            };
            this.variants  = [];
            this.groups    = gruposVacios();
            this.modalOpen = true;
        },

        openEdit(product, ingList) {
            this.isEdit       = true;
            this.formAction   = `/admin/menu/${product.id}`;
            this.formData     = {
                ...product,
                available: Boolean(product.available),
                subcategory: product.subcategory || '',
                presentation: product.presentation || '',
                section: product.section || 'comidas',
                station: product.station || 'cocina',
            };
            this.variants     = (product.variants || []).map(v => ({ name: v.name, price: v.price }));
            this.groups       = gruposVacios();
            (product.option_groups || []).forEach(g => {
                this.groups[g.id] = { on: true, min: g.pivot.min_select, max: g.pivot.max_select };
            });
            this.ingredients  = (ingList && ingList.length > 0) ? [...ingList] : [];
            this.ingQuery     = '';
            this.ingOpen      = false;
            this.imagePreview = product.image ? (product.image.startsWith('http') ? product.image : `/storage/${product.image}`) : null;
            this.modalOpen    = true;
        },

        previewImage(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                alert('Por favor selecciona un archivo de imagen válido (JPG, PNG, WEBP, etc.)');
                e.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = ev => {
                this.cropperSrc  = ev.target.result;
                this.cropperOpen = true;
                this.$nextTick(() => this.initCropper());
            };
            reader.readAsDataURL(file);
        },

        initCropper() {
            if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
            this.cropperInstance = new Cropper(this.$refs.cropperImg, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                background: false,
                movable: true,
                zoomable: true,
                scalable: false,
                responsive: true,
            });
        },

        confirmCrop() {
            if (!this.cropperInstance) return;
            const canvas = this.cropperInstance.getCroppedCanvas({
                width: 800, height: 800, imageSmoothingQuality: 'high',
            });
            canvas.toBlob((blob) => {
                const croppedFile = new File([blob], 'platillo-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(croppedFile);
                this.$refs.imgFile.files = dt.files;
                this.imagePreview = canvas.toDataURL('image/jpeg', 0.92);
                this.cropperOpen = false;
                this.cropperInstance.destroy();
                this.cropperInstance = null;
                this.cropperSrc = null;
            }, 'image/jpeg', 0.92);
        },

        cancelCrop() {
            this.cropperOpen = false;
            if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
            this.cropperSrc = null;
            this.$refs.imgFile.value = '';
        },

        removeIngredient(idx) { this.ingredients.splice(idx, 1); },

        ingFiltered() {
            const q = this.ingQuery.trim().toLowerCase();
            if (!q) return INVENTARIO;
            return INVENTARIO.filter(i => i.name.toLowerCase().includes(q));
        },

        ingSelect(item) {
            if (!this.ingredients.includes(item.name)) {
                this.ingredients.push(item.name);
            }
            this.ingQuery = '';
            this.ingOpen  = false;
        },

        ingPickFirst() {
            const results = this.ingFiltered();
            if (results.length > 0) {
                this.ingSelect(results[0]);
            } else if (this.ingQuery.trim()) {
                this.ingAddFree(this.ingQuery.trim());
            }
        },

        ingAddFree(val = null) {
            const name = val ?? prompt('Escribe el nombre del ingrediente:');
            if (name && name.trim() && !this.ingredients.includes(name.trim())) {
                this.ingredients.push(name.trim());
            }
            this.ingQuery = '';
            this.ingOpen  = false;
        },

        confirmDelete(form) {
            if (!confirm('¿Eliminar este platillo?\nEsta acción no se puede deshacer.')) return;

            const row = form.closest('.am-row');
            const wasAvailable = row ? !!row.querySelector('.am-dot.on') : false;

            fetch(form.action, {
                method: 'POST', // Laravel toma el _method=DELETE del FormData (method spoofing)
                headers: { 'Accept': 'application/json' },
                body: new FormData(form),
            })
            .then(res => {
                if (!res.ok) throw new Error('delete failed');
                if (row) row.remove();
                this.adjustStat('am-stat-total', -1);
                this.adjustStat(wasAvailable ? 'am-stat-disponibles' : 'am-stat-no-disponibles', -1);
                this.adjustStat('am-badge-count', -1, ' registros');
            })
            .catch(() => {
                alert('No se pudo eliminar el platillo. Intenta de nuevo.');
            });
        },

        adjustStat(id, delta, suffix = '') {
            const el = document.getElementById(id);
            if (!el) return;
            const current = parseInt(el.textContent, 10) || 0;
            el.textContent = (current + delta) + suffix;
        },
    }
}
</script>

@endsection