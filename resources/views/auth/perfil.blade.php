@extends('layouts.app')
@section('content')

<style>
/* Se mantienen todos tus estilos originales intactos */
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Inter:wght@400;500&display=swap');

.pf {
    --or:#F97316; --or2:#EA580C;
    --gn:#16A34A; --gn2:#22C55E;
    --bg:#0A0A0A; --card:#141414;
    --inp:#1C1C1C; --txt:#FFFFFF;
    --sub:#A1A1AA; --bdr:rgba(255,255,255,.07);
    --hex:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49'%3E%3Cg fill='%23ffffff' fill-opacity='0.025'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15L13.99 9.25z'/%3E%3C/g%3E%3C/svg%3E");
}
html:not(.dark) .pf {
    --bg:#F5F3EF; --card:#FFFFFF;
    --inp:#F4F4F5; --txt:#18181B;
    --sub:#71717A; --bdr:rgba(0,0,0,.09);
    --hex:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49'%3E%3Cg fill='%23000000' fill-opacity='0.025'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15L13.99 9.25z'/%3E%3C/g%3E%3C/svg%3E");
}

.pf { background:var(--bg); color:var(--txt); font-family:'Inter',sans-serif; min-height:100vh; padding:40px 24px 80px; transition:background .3s,color .3s; }
.pf-wrap { max-width:1000px; margin:0 auto; display:flex; flex-direction:column; gap:20px; }

/* TOP GRID */
.pf-top { display:grid; grid-template-columns:260px 1fr; gap:20px; align-items:start; }
@media(max-width:720px){ .pf-top { grid-template-columns:1fr; } }

/* CARD */
.pf-card { background:var(--card); background-image:var(--hex); border:1px solid var(--bdr); border-radius:12px; overflow:hidden; transition:background .3s,border-color .3s; }
.pf-card-bar { height:3px; }
.pf-card-bar.or { background:linear-gradient(90deg,var(--or),var(--gn)); }
.pf-card-bar.gn { background:linear-gradient(90deg,var(--gn),var(--gn2)); }
.pf-card-bar.am { background:linear-gradient(90deg,#D97706,#F59E0B); }
.pf-card-body { padding:20px; }

/* AVATAR - Ajuste para botón interactivo */
.pf-avatar-card { text-align:center; }
.pf-av-wrap {
    position:relative; width:96px; height:96px; margin:0 auto 14px; cursor:pointer;
    padding:0; border:none; background:none; display:block; /* Reset de estilos de botón */
}
.pf-av-wrap img, .pf-av-ph {
    width:100%; height:100%; border-radius:50%; object-fit:cover;
    border:3px solid var(--or); box-shadow:0 0 0 4px rgba(249,115,22,.15); transition:opacity .2s;
}
.pf-av-ph { background:linear-gradient(135deg,var(--or),var(--or2)); display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700; color:#fff; font-family:'Oswald',sans-serif; }
.pf-av-ovl { position:absolute; inset:0; border-radius:50%; background:rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .2s; }
.pf-av-wrap:hover .pf-av-ovl, .pf-av-wrap:focus .pf-av-ovl { opacity:1; }
.pf-av-wrap:hover img, .pf-av-wrap:hover .pf-av-ph { opacity:.7; }
.pf-av-ovl span { font-size:10px; font-weight:700; color:#fff; letter-spacing:.5px; text-transform:uppercase; }

.pf-name { font-family:'Oswald',sans-serif; font-size:20px; font-weight:700; color:var(--txt); margin:0 0 10px; letter-spacing:.5px; transition:color .3s; }
.pf-since { font-size:11px; color:var(--sub); }
.pf-sep { height:1px; background:var(--bdr); margin:14px 0; }

.pf-mini-stats { display:flex; }
.pf-mini-stat { flex:1; padding:8px 4px; text-align:center; }
.pf-mini-stat:not(:last-child) { border-right:1px solid var(--bdr); }
.pf-mini-stat-v { font-size:16px; font-weight:800; margin:0; line-height:1; }
.pf-mini-stat-l { font-size:9px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); margin-top:2px; }

/* RIGHT */
.pf-right { display:flex; flex-direction:column; gap:16px; }
.pf-stats { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.pf-stat { background:var(--card); background-image:var(--hex); border:1px solid var(--bdr); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:10px; transition:border-color .15s; }
.pf-stat:hover { border-color:var(--or); }
.pf-stat-ico { font-size:24px; flex-shrink:0; }
.pf-stat-v { font-size:22px; font-weight:800; margin:0; line-height:1; letter-spacing:-.5px; transition:color .3s; }
.pf-stat-l { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); }

/* FORM */
.pf-lbl { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); margin-bottom:5px; }
.pf-inp, .pf-ro { width:100%; padding:9px 12px; background:var(--inp); border:1.5px solid var(--bdr); border-radius:8px; font-size:13px; color:var(--txt); outline:none; font-family:inherit; transition:border-color .15s,box-shadow .15s; }
.pf-inp:focus { border-color:var(--or); box-shadow:0 0 0 3px rgba(249,115,22,.1); }
.pf-ro { cursor:not-allowed; opacity:.6; }
.pf-ro-note { font-size:10px; color:var(--sub); margin:4px 0 0; }
.pf-g2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:500px){ .pf-g2 { grid-template-columns:1fr; } }
.pf-submit { padding:10px 24px; background:var(--or); color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 10px rgba(249,115,22,.3); transition:background .15s,transform .1s; margin-top:14px; }
.pf-submit:hover { background:var(--or2); }
.pf-submit:active { transform:scale(.98); }
.pf-submit svg { width:14px; height:14px; }
.pf-alert-ok { display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:7px; margin-bottom:14px; background:rgba(22,163,74,.07); border:1px solid rgba(22,163,74,.2); font-size:12px; font-weight:600; color:var(--gn); }
.pf-alert-ok svg { width:13px; height:13px; flex-shrink:0; }

/* LOGROS */
.pf-logros-hd { display:flex; align-items:baseline; justify-content:space-between; margin-bottom:16px; }
.pf-logros-ttl { font-family:'Oswald',sans-serif; font-size:18px; font-weight:700; letter-spacing:.5px; color:var(--txt); margin:0; transition:color .3s; }
.pf-logros-count { font-size:11px; color:var(--sub); }
.pf-logros-count strong { color:var(--or); }
.pf-cat-lbl { display:flex; align-items:center; gap:8px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--sub); margin:18px 0 10px; }
.pf-cat-lbl::after { content:''; flex:1; height:1px; background:var(--bdr); }

.pf-logros-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:10px; }

/* BADGE */
.pf-badge {
    position:relative; display:flex; flex-direction:column; align-items:center;
    text-align:center; padding:16px 12px 14px;
    background:var(--inp); border:1.5px solid var(--bdr);
    border-radius:10px; cursor:default;
    transition:border-color .2s,transform .2s,box-shadow .2s;
    overflow:hidden;
}
.pf-badge::before { content:''; position:absolute; inset:0; border-radius:10px; background:radial-gradient(ellipse at top,rgba(249,115,22,.06) 0%,transparent 70%); opacity:0; transition:opacity .2s; }
.pf-badge:hover { border-color:var(--or); transform:translateY(-3px); box-shadow:0 8px 24px rgba(249,115,22,.12); }
.pf-badge:hover::before { opacity:1; }
.pf-badge-shine { position:absolute; top:0; left:-100%; width:60%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.06),transparent); transition:left .4s; pointer-events:none; }
.pf-badge:hover .pf-badge-shine { left:150%; }
.pf-badge-ico  { font-size:30px; margin-bottom:8px; position:relative; z-index:1; }
.pf-badge-name { font-size:11px; font-weight:700; color:var(--txt); margin:0 0 4px; line-height:1.3; position:relative; z-index:1; transition:color .3s; }
.pf-badge-date { font-size:9px; color:var(--or); font-weight:600; position:relative; z-index:1; }

/* TOOLTIP GLOBAL */
#pf-tooltip {
    position:fixed; z-index:99999;
    background:#1a1a1a; border:1px solid rgba(255,255,255,.12);
    border-radius:9px; padding:10px 14px;
    font-family:'Inter',sans-serif; font-size:12px; color:#e4e4e7;
    line-height:1.6; width:190px; text-align:center;
    pointer-events:none;
    opacity:0; transition:opacity .15s;
    box-shadow:0 10px 30px rgba(0,0,0,.6);
}
#pf-tooltip.show { opacity:1; }
</style>

<div id="pf-tooltip"></div>

<div class="pf">
<div class="pf-wrap">

    <div class="pf-top">
        <div class="pf-card pf-avatar-card">
            <div class="pf-card-bar or"></div>
            <div class="pf-card-body">
                {{-- CAMBIO: div por button para accesibilidad --}}
                <button type="button" class="pf-av-wrap"
                        onclick="document.getElementById('avatar-input').click()"
                        aria-label="Cambiar foto de perfil">
                    @if($user->avatar)
                        <img src="{{ asset($user->avatar) }}" id="avatar-preview" alt="Foto de {{ $user->name }}">
                    @else
                        <div class="pf-av-ph" id="avatar-placeholder">{{ substr($user->name,0,1) }}</div>
                    @endif
                    <div class="pf-av-ovl"><span>Cambiar</span></div>
                </button>

                <p class="pf-name">{{ $user->name }}</p>
                <p class="pf-since">Miembro desde {{ $user->created_at->format('M Y') }}</p>

                <div class="pf-sep"></div>

                <div class="pf-mini-stats">
                    <div class="pf-mini-stat">
                        <p class="pf-mini-stat-v" style="color:var(--or)">{{ $user->points ?? 0 }}</p>
                        <p class="pf-mini-stat-l">Puntos</p>
                    </div>
                    <div class="pf-mini-stat">
                        <p class="pf-mini-stat-v" style="color:var(--gn)">{{ count($unlockedIds) }}</p>
                        <p class="pf-mini-stat-l">Logros</p>
                    </div>
                    <div class="pf-mini-stat">
                        <p class="pf-mini-stat-v" style="color:var(--txt)">{{ $reservacionesTotales }}</p>
                        <p class="pf-mini-stat-l">Reservas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="pf-right">
            <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" id="avatar-input" name="avatar" class="hidden" accept="image/*" onchange="previewAvatar(this)">
                <div class="pf-card">
                    <div class="pf-card-bar gn"></div>
                    <div class="pf-card-body">
                        @if(session('status'))
                        <div class="pf-alert-ok">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ session('status') }}
                        </div>
                        @endif
                        <div style="font-family:'Oswald',sans-serif;font-size:14px;font-weight:700;letter-spacing:.5px;color:var(--txt);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                            <svg style="width:14px;height:14px;color:var(--gn);flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Información de Contacto
                        </div>
                        <div class="pf-g2">
                            <div>
                                {{-- CAMBIO: id y for añadidos --}}
                                <label for="email-readonly" class="pf-lbl">Correo electrónico</label>
                                <input type="text" id="email-readonly" class="pf-ro" value="{{ $user->email }}" readonly>
                                <p class="pf-ro-note">Dato de seguridad (no editable)</p>
                            </div>
                            <div>
                                {{-- CAMBIO: id y for añadidos --}}
                                <label for="telefono-input" class="pf-lbl">Teléfono</label>
                                <input type="text" id="telefono-input" name="telefono" class="pf-inp" value="{{ $user->telefono }}" placeholder="614 000 0000">
                            </div>
                        </div>
                        <button type="submit" class="pf-submit">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>

            <div class="pf-stats">
                <div class="pf-stat">
                    <span class="pf-stat-ico" aria-hidden="true">🏆</span>
                    <div>
                        <p class="pf-stat-v" style="color:var(--or)">{{ $user->points ?? 0 }}</p>
                        <p class="pf-stat-l">Puntos La 501</p>
                    </div>
                </div>
                <div class="pf-stat">
                    <span class="pf-stat-ico" aria-hidden="true">📅</span>
                    <div>
                        <p class="pf-stat-v" style="color:var(--gn)">{{ $reservacionesTotales }}</p>
                        <p class="pf-stat-l">Reservaciones</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN DE MIS PEDIDOS --}}
    <div class="pf-card" style="margin-top: 4px; margin-bottom: 20px;">
        <div class="pf-card-bar gn" style="background: linear-gradient(90deg, var(--gn), var(--or));"></div>
        <div class="pf-card-body">
            <div class="pf-logros-hd" style="margin-bottom: 20px;">
                <h2 class="pf-logros-ttl">Mis Pedidos Recientes</h2>
                <span class="pf-logros-count">Total: <strong>{{ $orders->count() }}</strong> pedidos</span>
            </div>

            @if($orders->isEmpty())
                <div style="text-align: center; padding: 32px 0;">
                    <p style="font-size: 32px; margin-bottom: 8px;">🍽️</p>
                    <p style="font-weight: 600; color: var(--txt);">Aún no tienes pedidos registrados</p>
                    <p style="font-size: 12px; color: var(--sub); margin-top: 6px;">¡Haz tu primer pedido a domicilio para acumular puntos!</p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 500px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--bdr); font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--sub); letter-spacing: 0.5px;">
                                <th style="padding: 12px 10px;">Folio</th>
                                <th style="padding: 12px 10px;">Fecha</th>
                                <th style="padding: 12px 10px;">Total</th>
                                <th style="padding: 12px 10px;">Estado</th>
                                <th style="padding: 12px 10px; text-align: right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            @foreach($orders as $order)
                                <tr style="border-bottom: 1px solid var(--bdr); transition: background 0.15s; background: rgba(255,255,255,0.01);">
                                    <td style="padding: 14px 10px; font-weight: 700; color: var(--txt);">
                                        #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td style="padding: 14px 10px; color: var(--sub);">
                                        {{ $order->created_at->format('d/m/Y h:i A') }}
                                    </td>
                                    <td style="padding: 14px 10px; font-weight: 700; color: var(--txt);">
                                        ${{ number_format($order->total, 2) }}
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        @if($order->status === 'paid')
                                            <span style="display: inline-block; background: rgba(234,88,12,0.15); color: var(--or); border: 1px solid rgba(234,88,12,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px;">
                                                En Cocina
                                            </span>
                                        @elseif($order->status === 'ready')
                                            <span style="display: inline-block; background: rgba(34,197,94,0.15); color: var(--gn2); border: 1px solid rgba(34,197,94,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px;">
                                                ¡Listo!
                                            </span>
                                        @elseif($order->status === 'delivering')
                                            <span style="display: inline-block; background: rgba(59,130,246,0.15); color: #60A5FA; border: 1px solid rgba(59,130,246,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px;">
                                                En Camino
                                            </span>
                                        @elseif($order->status === 'delivered')
                                            <span style="display: inline-block; background: rgba(34,197,94,0.15); color: var(--gn2); border: 1px solid rgba(34,197,94,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px;">
                                                Entregado
                                            </span>
                                        @elseif($order->status === 'pending')
                                            <span style="display: inline-block; background: rgba(220,38,38,0.15); color: #EF4444; border: 1px solid rgba(220,38,38,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px;">
                                                Pago Pendiente
                                            </span>
                                        @else
                                            <span style="display: inline-block; background: rgba(255,255,255,0.05); color: var(--txt-sub); border: 1px solid var(--bdr); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px;">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <a href="{{ route('order.track', $order->trackingToken()) }}"
                                           style="display: inline-inline-flex; align-items: center; justify-content: center; background: rgba(249,115,22,0.1); border: 1px solid rgba(249,115,22,0.25); color: var(--or); padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; text-decoration: none; transition: all 0.15s;"
                                           onmouseover="this.style.background='var(--or)'; this.style.color='white';"
                                           onmouseout="this.style.background='rgba(249,115,22,0.1)'; this.style.color='var(--or)';">
                                            Ver Ticket / Seguir
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- LOGROS --}}
    <div class="pf-card">
        <div class="pf-card-bar am"></div>
        <div class="pf-card-body">
            <div class="pf-logros-hd">
                <h2 class="pf-logros-ttl">🏅 Mis Logros</h2>
                <span class="pf-logros-count"><strong>{{ count($unlockedIds) }}</strong> logros desbloqueados</span>
            </div>

            @php
                // ... (mismo bloque PHP de categorías y howTo)
                $categorias = [
                    'compras'       => ['label'=>'Compras',       'ico'=>'🛒'],
                    'reservaciones' => ['label'=>'Reservaciones', 'ico'=>'📅'],
                    'fidelidad'     => ['label'=>'Fidelidad',     'ico'=>'❤️'],
                    'variedad'      => ['label'=>'Variedad',      'ico'=>'🍽️'],
                    'especial'      => ['label'=>'Especial',      'ico'=>'⭐'],
                ];
                $howTo = [
                    'primera_orden'   => 'Completa tu primer pedido a domicilio.',
                    '5_ordenes'       => 'Realiza 5 pedidos en total.',
                    '10_ordenes'      => 'Realiza 10 pedidos en total.',
                    '25_ordenes'      => 'Realiza 25 pedidos en total.',
                    '50_ordenes'      => 'Realiza 50 pedidos en total.',
                    'primera_reserva' => 'Haz tu primera reservación confirmada.',
                    '5_reservas'      => 'Acumula 5 reservaciones confirmadas.',
                    '10_reservas'     => 'Acumula 10 reservaciones confirmadas.',
                    '1_mes'           => 'Lleva 1 mes registrado en La 501.',
                    '3_meses'         => 'Lleva 3 meses registrado en La 501.',
                    '6_meses'         => 'Lleva 6 meses registrado en La 501.',
                    '1_anio'          => 'Lleva 1 año registrado en La 501.',
                    '2_anios'         => 'Lleva 2 años registrado en La 501.',
                    'fan_hamburguesas'=> 'Ordena hamburguesas 5 veces.',
                    'fan_bar'         => 'Ordena del bar 5 veces.',
                    'fan_alitas'      => 'Ordena alitas o costillas 5 veces.',
                    'explorador'      => 'Ordena de al menos 4 categorías distintas.',
                    'noche_viernes'   => 'Haz un pedido un viernes después de las 8pm.',
                    'grupo_grande'    => 'Reserva para 8 o más personas.',
                    'puntos_100'      => 'Acumula 100 puntos de fidelidad.',
                ];
                $hayLogros = false;
                foreach($categorias as $cat=>$meta)
                    foreach($allAchievements->where('categoria',$cat) as $l)
                        if(in_array($l->id,$unlockedIds)){ $hayLogros=true; break 2; }
            @endphp

            @if(!$hayLogros)
            <div class="pf-empty">
                <p style="font-size:36px;margin-bottom:8px" aria-hidden="true">🔒</p>
                <p style="font-weight:600;color:var(--txt)">Aún no tienes logros desbloqueados</p>
                <p style="font-size:12px;margin-top:6px;line-height:1.6">Realiza pedidos, haz reservaciones y sigue visitándonos para ganarlos.</p>
            </div>
            @else
                @foreach($categorias as $cat => $meta)
                @php
                    $desbloqueados = $allAchievements->where('categoria',$cat)->filter(fn($l)=>in_array($l->id,$unlockedIds));
                @endphp
                @if($desbloqueados->count() > 0)
                <p class="pf-cat-lbl"><span>{{ $meta['ico'] }}</span> {{ $meta['label'] }}</p>
                <div class="pf-logros-grid">
                    @foreach($desbloqueados as $logro)
                    @php
                        $pivotRow = $user->achievements()->where('achievement_id',$logro->id)->first();
                        $desc  = addslashes($logro->descripcion);
                        $como  = addslashes($howTo[$logro->slug] ?? $logro->descripcion);
                    @endphp
                    
                    {{-- CAMBIO: Usamos <button> en lugar de <div> y añadimos type="button" --}}
                    <button type="button"
                        class="pf-badge"
                        data-desc="{{ $desc }}"
                        data-como="{{ $como }}"
                        onmouseenter="showTip(event,this)"
                        onmouseleave="hideTip()"
                        onfocus="showTip(event,this)"
                        onblur="hideTip()"
                        aria-label="Logro: {{ $logro->nombre }}">
                        
                        <div class="pf-badge-shine"></div>
                        <span class="pf-badge-ico" aria-hidden="true">{{ $logro->icono }}</span>
                        <p class="pf-badge-name">{{ $logro->nombre }}</p>
                        <p class="pf-badge-date">
                            ✓ {{ $pivotRow?->pivot->unlocked_at
                                ? \Carbon\Carbon::parse($pivotRow->pivot->unlocked_at)->format('d M Y')
                                : '' }}
                        </p>
                    </button>
                    @endforeach
                </div>
                @endif
                @endforeach
            @endif
        </div>
    </div>

</div>
</div>

<script>
// El script se mantiene igual pero ahora es activado también por foco de teclado
const tip = document.getElementById('pf-tooltip');
let tipTimer;

function showTip(e, el) {
    clearTimeout(tipTimer);
    const desc = el.dataset.desc || '';
    const como = el.dataset.como || '';
    tip.innerHTML = desc + (como ? '<div class="tt-how">🎯 ' + como + '</div>' : '');
    tip.classList.add('show');
    positionTip(e, el);
}

function positionTip(e, el) {
    const rect = el.getBoundingClientRect();
    const tw = 190;
    let left = rect.left + rect.width / 2 - tw / 2 + window.scrollX;
    let top  = rect.top - tip.offsetHeight - 14 + window.scrollY;

    if (left < 8) left = 8;
    if (left + tw > window.innerWidth - 8) left = window.innerWidth - tw - 8;
    
    if (rect.top < tip.offsetHeight + 20) {
        top = rect.bottom + 10 + window.scrollY;
    }

    tip.style.left = left + 'px';
    tip.style.top  = top  + 'px';
    tip.style.width = tw  + 'px';
}

function hideTip() {
    tipTimer = setTimeout(() => tip.classList.remove('show'), 80);
}

function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        let img = document.getElementById('avatar-preview');
        if (img) {
            img.src = e.target.result;
        } else {
            const ph = document.getElementById('avatar-placeholder');
            if (ph) ph.style.display = 'none';
            const newImg = document.createElement('img');
            newImg.id = 'avatar-preview';
            newImg.src = e.target.result;
            newImg.alt = "Vista previa";
            newImg.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;border:3px solid #F97316;box-shadow:0 0 0 4px rgba(249,115,22,.15);';
            document.querySelector('.pf-av-wrap').prepend(newImg);
        }
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

@endsection
