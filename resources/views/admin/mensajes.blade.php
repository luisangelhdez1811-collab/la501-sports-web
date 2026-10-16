@extends('layouts.admin')
@section('content')

<style>
/* ══════════════════════════════════════════════
   VARIABLES — sistema admin La 501
══════════════════════════════════════════════ */
.msg {
    --ac:  #2563EB; --ac2: #1D4ED8;
    --gn:  #16A34A; --gn2: #22C55E;
    --rd:  #DC2626; --rd2: #EF4444;
    --am:  #D97706; --am2: #F59E0B;
    --bl:  #3B82F6;
    --bg:  #F8F8F8; --card: #FFFFFF;
    --inp: #F4F4F5; --txt: #18181B;
    --sub: #71717A; --bdr: #E4E4E7;
}
.dark .msg {
    --bg:  #0A0A0A; --card: #111111;
    --inp: #1C1C1C; --txt: #FAFAFA;
    --sub: #71717A; --bdr: rgba(255,255,255,.08);
}
.msg { background:var(--bg); min-height:100%; padding:28px 32px 60px; color:var(--txt); }

/* ── HEADER ──────────────────────────────── */
.msg-hd { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.msg-hd h1 { font-size:20px; font-weight:700; color:var(--txt); margin:0 0 2px; }
.msg-hd p  { font-size:13px; color:var(--sub); margin:0; }

/* ── STATS ───────────────────────────────── */
.msg-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.msg-stat {
    flex:1; min-width:140px;
    background:var(--card); border:1px solid var(--bdr);
    border-radius:8px; padding:12px 16px;
    display:flex; align-items:center; gap:10px;
}
.msg-stat-ico { width:30px; height:30px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.msg-stat-ico svg { width:14px; height:14px; }
.msg-stat-ico.bl { background:rgba(37,99,235,.1);  color:var(--ac); }
.msg-stat-ico.rd { background:rgba(220,38,38,.1);  color:var(--rd); }
.msg-stat-ico.am { background:rgba(217,119,6,.1);  color:var(--am); }
.msg-stat-ico.gn { background:rgba(22,163,74,.1);  color:var(--gn); }
.dark .msg-stat-ico.bl { color:#60A5FA; }
.dark .msg-stat-ico.rd { color:#F87171; }
.dark .msg-stat-ico.am { color:#FCD34D; }
.dark .msg-stat-ico.gn { color:#4ADE80; }
.msg-stat-v { font-size:20px; font-weight:800; color:var(--txt); margin:0; line-height:1; letter-spacing:-.5px; }
.msg-stat-l { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); }

/* ── FILTROS + SEARCH ────────────────────── */
.msg-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
.msg-search { position:relative; flex:1; min-width:180px; }
.msg-search svg { position:absolute; left:9px; top:50%; transform:translateY(-50%); width:14px; height:14px; color:var(--sub); pointer-events:none; }
.msg-search input {
    width:100%; padding:7px 10px 7px 30px;
    background:var(--card); border:1px solid var(--bdr);
    border-radius:7px; font-size:13px; color:var(--txt);
    outline:none; transition:border-color .15s,box-shadow .15s; font-family:inherit;
}
.msg-search input:focus { border-color:var(--ac); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.msg-filter-btn {
    padding:6px 13px; border-radius:6px; font-size:12px; font-weight:600;
    border:1px solid var(--bdr); background:var(--card); color:var(--sub);
    cursor:pointer; white-space:nowrap; flex-shrink:0;
    transition:background .15s,color .15s,border-color .15s;
}
.msg-filter-btn:hover { color:var(--txt); }
.msg-filter-btn.pend-txt  { color:var(--ac); }
.msg-filter-btn.resp-txt  { color:var(--gn); }
.msg-filter-btn.queja-txt { color:var(--rd); }
.msg-filter-btn.sug-txt   { color:var(--am); }
.msg-filter-btn.on { background:var(--ac); color:#fff; border-color:var(--ac); }
.msg-filter-btn.rd-on { background:var(--rd); color:#fff; border-color:var(--rd); }
.msg-filter-btn.am-on { background:var(--am); color:#fff; border-color:var(--am); }
.msg-filter-btn.bl-on { background:var(--bl); color:#fff; border-color:var(--bl); }
.msg-filter-btn.gn-on { background:var(--gn); color:#fff; border-color:var(--gn); }

/* ── CARD CONTENEDOR ─────────────────────── */
.msg-card {
    background:var(--card); border:1px solid var(--bdr);
    border-radius:10px; overflow:hidden;
}
.msg-card-hd {
    display:flex; align-items:center; justify-content:space-between;
    padding:12px 18px; border-bottom:1px solid var(--bdr);
}
.msg-card-ttl { display:flex; align-items:center; gap:8px; }
.msg-card-ttl-ico { width:26px; height:26px; border-radius:6px; background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.14); display:flex; align-items:center; justify-content:center; }
.msg-card-ttl-ico svg { width:13px; height:13px; color:var(--ac); }
.msg-card-ttl span { font-size:13px; font-weight:700; color:var(--txt); }
.msg-badge-count { font-size:11px; font-weight:600; color:var(--sub); background:var(--inp); border:1px solid var(--bdr); border-radius:20px; padding:2px 9px; }

/* ── TABLA ───────────────────────────────── */
.msg-table { width:100%; border-collapse:collapse; min-width:700px; }
.msg-table-wrap { overflow-x:auto; scrollbar-width:thin; scrollbar-color:var(--bdr) transparent; }
.msg-table-wrap::-webkit-scrollbar { height:4px; }
.msg-table-wrap::-webkit-scrollbar-thumb { background:var(--bdr); border-radius:4px; }
.msg-table th {
    padding:9px 16px; font-size:10px; font-weight:700;
    text-transform:uppercase; letter-spacing:.6px; color:var(--sub);
    background:var(--inp); border-bottom:1px solid var(--bdr); white-space:nowrap;
}
.msg-table td { padding:0; border-bottom:1px solid var(--bdr); vertical-align:top; }
.msg-table tbody tr:last-child td { border-bottom:none; }
.msg-table tbody tr { transition:background .12s; }
.msg-table tbody tr:hover { background:var(--bg); }
/* row pendiente tiene borde izquierdo */
.msg-table tbody tr.pending { border-left:3px solid var(--ac); }
.msg-table tbody tr.pending td:first-child { padding-left:13px; }

/* celda interna */
.msg-cell { padding:12px 16px; }

/* remitente */
.msg-sender-name { font-size:13px; font-weight:600; color:var(--txt); margin:0 0 2px; }
.msg-sender-email { font-size:11px; color:var(--sub); }
.msg-sender-email a { color:var(--ac); text-decoration:none; }
.msg-sender-email a:hover { text-decoration:underline; }

/* asunto */
.msg-subject { font-size:13px; font-weight:600; color:var(--txt); margin:0 0 4px; display:flex; align-items:center; gap:6px; }
.msg-ping { display:inline-flex; position:relative; width:8px; height:8px; flex-shrink:0; }
.msg-ping-ring { position:absolute; inset:0; border-radius:50%; background:var(--ac); opacity:.6; animation:ping .9s cubic-bezier(0,0,.2,1) infinite; }
.msg-ping-dot  { position:relative; width:8px; height:8px; border-radius:50%; background:var(--ac); }
@keyframes ping { 75%,100% { transform:scale(1.8); opacity:0; } }

/* preview del mensaje */
.msg-preview {
    font-size:12px; color:var(--sub); margin:0;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
    overflow:hidden; line-height:1.5; max-width:360px;
}

/* tipo chip */
.msg-type-chip {
    display:inline-flex; align-items:center; gap:4px;
    font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
    padding:3px 8px; border-radius:5px; white-space:nowrap;
}
.msg-type-chip.queja     { background:rgba(220,38,38,.08);  color:var(--rd);  border:1px solid rgba(220,38,38,.2); }
.msg-type-chip.sugerencia{ background:rgba(217,119,6,.08);  color:var(--am);  border:1px solid rgba(217,119,6,.2); }
.msg-type-chip.pregunta  { background:rgba(59,130,246,.08); color:var(--bl);  border:1px solid rgba(59,130,246,.2); }
.msg-status-chip {
    display:inline-flex; align-items:center; gap:4px;
    font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
    padding:3px 8px; border-radius:5px; white-space:nowrap;
}
.msg-status-chip.pen { background:rgba(37,99,235,.08);  color:var(--ac); border:1px solid rgba(37,99,235,.2); }
.msg-status-chip.res { background:rgba(22,163,74,.08);  color:var(--gn); border:1px solid rgba(22,163,74,.2); }

/* fecha */
.msg-date { font-size:11px; color:var(--sub); white-space:nowrap; }

/* acciones */
.msg-actions { display:flex; gap:6px; align-items:center; justify-content:flex-end; }
.msg-act {
    padding:5px 10px; border-radius:6px; font-size:11px; font-weight:600;
    border:1px solid var(--bdr); background:var(--inp); cursor:pointer;
    color:var(--sub); transition:background .15s,border-color .15s,color .15s;
    display:inline-flex; align-items:center; gap:4px; white-space:nowrap;
    text-decoration:none; font-family:inherit;
}
.msg-act svg { width:11px; height:11px; }
.msg-act.reply:hover { border-color:var(--ac); color:var(--ac); background:rgba(37,99,235,.06); }
.msg-act.done:hover  { border-color:var(--gn); color:var(--gn); background:rgba(22,163,74,.06); }
.msg-act.del:hover   { border-color:var(--rd); color:var(--rd); background:rgba(220,38,38,.06); }

/* expandir mensaje */
.msg-expand-btn {
    background:none; border:none; cursor:pointer; color:var(--ac);
    font-size:11px; font-weight:600; padding:0; font-family:inherit;
    display:inline-flex; align-items:center; gap:3px; margin-top:3px;
}
.msg-expand-btn svg { width:10px; height:10px; transition:transform .2s; }
.msg-expand-btn.open svg { transform:rotate(180deg); }
.msg-full-text {
    font-size:12px; color:var(--sub); line-height:1.6;
    padding:10px 12px; border-radius:6px; background:var(--inp);
    border:1px solid var(--bdr); margin-top:8px;
}

/* empty state */
.msg-empty { padding:60px 20px; text-align:center; }
.msg-empty svg { width:36px; height:36px; color:var(--bdr); margin:0 auto 10px; display:block; }
.msg-empty p { font-size:13px; color:var(--sub); margin:0; }

/* alerta éxito */
.msg-success {
    display:flex; align-items:center; gap:8px;
    padding:10px 14px; border-radius:8px; margin-bottom:16px;
    background:rgba(22,163,74,.07); border:1px solid rgba(22,163,74,.2);
}
.msg-success svg { width:14px; height:14px; color:var(--gn); flex-shrink:0; }
.msg-success p { font-size:12px; font-weight:600; color:var(--gn); margin:0; }

@media(max-width:640px){ .msg { padding:20px 16px 40px; } }
</style>

<div class="msg" x-data="mensajesAdmin()">

    {{-- HEADER --}}
    <div class="msg-hd">
        <div>
            <h1>Bandeja de Mensajes</h1>
            <p>Gestiona las quejas, sugerencias y preguntas de tus clientes</p>
        </div>
    </div>

    {{-- STATS --}}
    <div class="msg-stats">
        <div class="msg-stat">
            <div class="msg-stat-ico bl">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <div>
                <p class="msg-stat-v">{{ $mensajes->count() }}</p>
                <p class="msg-stat-l">Total mensajes</p>
            </div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-ico rd">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="msg-stat-v" style="color:var(--rd)">{{ $mensajes->where('status','pendiente')->count() }}</p>
                <p class="msg-stat-l">Pendientes</p>
            </div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-ico am">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="msg-stat-v" style="color:var(--am)">{{ $mensajes->where('type','queja')->count() }}</p>
                <p class="msg-stat-l">Quejas</p>
            </div>
        </div>
        <div class="msg-stat">
            <div class="msg-stat-ico gn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="msg-stat-v" style="color:var(--gn)">{{ $mensajes->where('status','respondido')->count() }}</p>
                <p class="msg-stat-l">Atendidos</p>
            </div>
        </div>
    </div>

    {{-- ALERTA ÉXITO --}}
    @if(session('success'))
    <div class="msg-success">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p>{{ session('success') }}</p>
    </div>
    @endif

    {{-- BARRA FILTROS --}}
    <div class="msg-bar">
        <div class="msg-search">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" x-model="search" placeholder="Buscar por nombre, asunto o correo...">
        </div>
        <button @click="activeFilter='todos'"       :class="activeFilter==='todos'?'on':''"        class="msg-filter-btn">Todos</button>
        <button @click="activeFilter='pendiente'"   :class="activeFilter==='pendiente'?'bl-on':''"  class="msg-filter-btn pend-txt">Pendientes</button>
        <button @click="activeFilter='respondido'"  :class="activeFilter==='respondido'?'gn-on':''" class="msg-filter-btn resp-txt">Atendidos</button>
        <button @click="activeFilter='queja'"       :class="activeFilter==='queja'?'rd-on':''"      class="msg-filter-btn queja-txt">⚠ Quejas</button>
        <button @click="activeFilter='sugerencia'"  :class="activeFilter==='sugerencia'?'am-on':''" class="msg-filter-btn sug-txt">💡 Sugerencias</button>
    </div>

    {{-- TABLA --}}
    <div class="msg-card">
        <div class="msg-card-hd">
            <div class="msg-card-ttl">
                <div class="msg-card-ttl-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <span>Mensajes</span>
            </div>
            <span class="msg-badge-count" x-text="visibleCount() + ' registros'"></span>
        </div>

        <div class="msg-table-wrap">
            <table class="msg-table">
                <thead>
                    <tr>
                        <th>Remitente</th>
                        <th>Asunto / Mensaje</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mensajes as $msg)
                    <tr class="{{ $msg->status === 'pendiente' ? 'pending' : '' }}"
                        x-show="rowVisible('{{ addslashes($msg->name) }}','{{ addslashes($msg->email) }}','{{ addslashes($msg->subject) }}','{{ $msg->status }}','{{ $msg->type }}')"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                        {{-- Remitente --}}
                        <td>
                            <div class="msg-cell">
                                <p class="msg-sender-name">{{ $msg->name }}</p>
                                <p class="msg-sender-email">
                                    <a href="mailto:{{ $msg->email }}">{{ $msg->email }}</a>
                                </p>
                            </div>
                        </td>

                        {{-- Asunto + preview --}}
                        <td style="max-width:320px">
                            <div class="msg-cell" x-data="{open:false}">
                                <p class="msg-subject">
                                    @if($msg->status === 'pendiente')
                                        <span class="msg-ping">
                                            <span class="msg-ping-ring"></span>
                                            <span class="msg-ping-dot"></span>
                                        </span>
                                    @endif
                                    {{ $msg->subject }}
                                </p>
                                <p class="msg-preview" x-show="!open">{{ $msg->message }}</p>
                                <div class="msg-full-text" x-show="open" style="display:none">{{ $msg->message }}</div>
                                <button class="msg-expand-btn" :class="open?'open':''" @click="open=!open">
                                    <span x-text="open ? 'Ver menos' : 'Ver completo'"></span>
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                        </td>

                        {{-- Tipo --}}
                        <td>
                            <div class="msg-cell">
                                @if($msg->type === 'queja')
                                    <span class="msg-type-chip queja">⚠ Queja</span>
                                @elseif($msg->type === 'sugerencia')
                                    <span class="msg-type-chip sugerencia">💡 Sugerencia</span>
                                @else
                                    <span class="msg-type-chip pregunta">🤔 Pregunta</span>
                                @endif
                            </div>
                        </td>

                        {{-- Estado --}}
                        <td>
                            <div class="msg-cell">
                                @if($msg->status === 'pendiente')
                                    <span class="msg-status-chip pen">● Pendiente</span>
                                @else
                                    <span class="msg-status-chip res">✓ Atendido</span>
                                @endif
                            </div>
                        </td>

                        {{-- Fecha --}}
                        <td>
                            <div class="msg-cell">
                                <p class="msg-date">
                                    {{ $msg->created_at ? $msg->created_at->format('d/m/Y') : '—' }}
                                </p>
                                <p class="msg-date" style="color:var(--sub);font-size:10px;margin-top:2px;">
                                    {{ $msg->created_at ? $msg->created_at->diffForHumans() : '' }}
                                </p>
                            </div>
                        </td>

                        {{-- Acciones --}}
                        <td>
                            <div class="msg-cell">
                                <div class="msg-actions">
                                    {{-- Responder por correo --}}
                                    <a href="https://mail.google.com/mail/?view=cm&fs=1&to={{ urlencode($msg->email) }}&su={{ urlencode('Re: '.$msg->subject) }}&body={{ urlencode('Hola '.$msg->name.','."\n\n") }}"
                                       target="_blank" class="msg-act reply" title="Responder por correo">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        Responder
                                    </a>

                                    @if($msg->status === 'pendiente')
                                    {{-- Marcar atendido --}}
                                    <form action="{{ route('admin.mensajes.leido', $msg->id) }}" method="POST" style="display:inline">
                                        @csrf @method('PUT')
                                        <button type="submit" class="msg-act done" title="Marcar como atendido">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Atendido
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Eliminar
                                    <form action="{{ route('admin.mensajes.destroy', $msg->id) }}" method="POST"
                                          style="display:inline"
                                          @submit.prevent="if(confirm('¿Eliminar este mensaje?')) $el.submit()">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="msg-act del" title="Eliminar">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form> --}}
                                </div>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="msg-empty">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                <p>Bandeja vacía — no hay mensajes aún.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function mensajesAdmin() {
    return {
        search:  '',
        activeFilter: 'todos', // 'todos' | 'pendiente' | 'respondido' | 'queja' | 'sugerencia'

        rowVisible(name, email, subject, status, type) {
            const q = this.search.toLowerCase();
            const matchSearch = !q ||
                name.toLowerCase().includes(q)    ||
                email.toLowerCase().includes(q)   ||
                subject.toLowerCase().includes(q);

            let matchFilter = true;
            if (this.activeFilter === 'pendiente' || this.activeFilter === 'respondido') {
                matchFilter = status === this.activeFilter;
            } else if (this.activeFilter === 'queja' || this.activeFilter === 'sugerencia') {
                matchFilter = type === this.activeFilter;
            }

            return matchSearch && matchFilter;
        },

        visibleCount() {
            return document.querySelectorAll('.msg-table tbody tr[style=""],.msg-table tbody tr:not([style])').length;
        },
    }
}
</script>

@endsection