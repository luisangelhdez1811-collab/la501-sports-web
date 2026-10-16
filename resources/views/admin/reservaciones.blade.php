@extends('layouts.admin')
@section('content')
<style>
.rv {
    --ac: #2563EB; --ac2: #1D4ED8;
    --gn: #16A34A; --gn2: #15803D;
    --rd: #DC2626; --am: #D97706;
    --or: #EA580C; --pu: #7C3AED;
    --bg: #F8F8F8; --card: #FFFFFF;
    --inp: #F4F4F5; --txt: #18181B;
    --sub: #71717A; --bdr: #E4E4E7;
}
.dark .rv {
    --bg: #0A0A0A; --card: #111111;
    --inp: #1C1C1C; --txt: #FAFAFA;
    --sub: #71717A; --bdr: rgba(255,255,255,.08);
}
.rv { background:var(--bg); min-height:100%; padding:28px 32px 60px; color:var(--txt); }

/* HEADER */
.rv-hd { margin-bottom:20px; }
.rv-hd h1 { font-size:20px; font-weight:700; color:var(--txt); margin:0 0 3px; }
.rv-hd p  { font-size:13px; color:var(--sub); margin:0; }

/* STATS */
.rv-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.rv-stat {
    flex:1; min-width:130px;
    background:var(--card); border:1px solid var(--bdr);
    border-radius:8px; padding:12px 16px;
    display:flex; align-items:center; gap:10px;
}
.rv-stat-ico { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rv-stat-ico svg { width:13px; height:13px; }
.rv-stat-ico.am { background:rgba(217,119,6,.1);  color:var(--am); }
.rv-stat-ico.gn { background:rgba(22,163,74,.1);  color:var(--gn); }
.rv-stat-ico.rd { background:rgba(220,38,38,.1);  color:var(--rd); }
.rv-stat-ico.pu { background:rgba(124,58,237,.1); color:var(--pu); }
.rv-stat-v { font-size:18px; font-weight:800; color:var(--txt); margin:0; line-height:1; }
.rv-stat-l { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); }

/* TABS */
.rv-tabs { display:flex; gap:2px; border-bottom:1px solid var(--bdr); margin-bottom:16px; }
.rv-tab {
    padding:8px 16px; font-size:12px; font-weight:600;
    text-decoration:none; border-bottom:2px solid transparent;
    margin-bottom:-1px; color:var(--sub);
    transition:color .15s, border-color .15s;
    display:flex; align-items:center; gap:6px;
}
.rv-tab:hover { color:var(--txt); }
.rv-tab.am.on { color:var(--am); border-bottom-color:var(--am); }
.rv-tab.gn.on { color:var(--gn); border-bottom-color:var(--gn); }
.rv-tab.rd.on { color:var(--rd); border-bottom-color:var(--rd); }
.rv-tab.pu.on { color:var(--pu); border-bottom-color:var(--pu); }
.rv-tab-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

/* TABLE CARD */
.rv-card { background:var(--card); border:1px solid var(--bdr); border-radius:10px; overflow:hidden; }
.rv-card-hd { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-bottom:1px solid var(--bdr); }
.rv-card-ttl { display:flex; align-items:center; gap:8px; }
.rv-card-ttl-ico { width:26px; height:26px; border-radius:6px; background:rgba(22,163,74,.08); border:1px solid rgba(22,163,74,.15); display:flex; align-items:center; justify-content:center; }
.rv-card-ttl-ico svg { width:13px; height:13px; color:var(--gn); }
.rv-card-ttl span { font-size:13px; font-weight:700; color:var(--txt); }
.rv-badge { font-size:11px; font-weight:600; color:var(--sub); background:var(--inp); border:1px solid var(--bdr); border-radius:20px; padding:2px 9px; }

/* TABLE */
.rv-table-wrap { overflow-x:auto; scrollbar-width:thin; scrollbar-color:var(--bdr) transparent; }
.rv-table-wrap::-webkit-scrollbar { height:4px; }
.rv-table-wrap::-webkit-scrollbar-thumb { background:var(--bdr); border-radius:4px; }
.rv-table { width:100%; border-collapse:collapse; min-width:700px; }
.rv-table th { padding:9px 16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--sub); background:var(--inp); border-bottom:1px solid var(--bdr); white-space:nowrap; }
.rv-table td { padding:0; border-bottom:1px solid var(--bdr); vertical-align:top; }
.rv-table tbody tr:last-child td { border-bottom:none; }
.rv-table tbody tr { transition:background .12s; }
.rv-table tbody tr:hover { background:var(--bg); }
.rv-cell { padding:11px 16px; }

/* cliente */
.rv-name { font-size:13px; font-weight:600; color:var(--txt); margin:0 0 2px; }
.rv-email { font-size:11px; color:var(--sub); }

/* fecha */
.rv-fecha { font-size:13px; font-weight:600; color:var(--txt); margin:0 0 2px; }
.rv-hora  { font-size:11px; font-weight:700; color:var(--sub); }

/* chips */
.rv-chip {
    display:inline-flex; align-items:center; gap:3px;
    font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px;
    padding:3px 8px; border-radius:5px; white-space:nowrap;
}
.rv-chip.am  { background:rgba(217,119,6,.08);  color:var(--am);  border:1px solid rgba(217,119,6,.2); }
.rv-chip.gn  { background:rgba(22,163,74,.08);  color:var(--gn);  border:1px solid rgba(22,163,74,.2); }
.rv-chip.rd  { background:rgba(220,38,38,.08);  color:var(--rd);  border:1px solid rgba(220,38,38,.2); }
.rv-chip.pu  { background:rgba(124,58,237,.08); color:var(--pu);  border:1px solid rgba(124,58,237,.2); }
.rv-chip.bl  { background:rgba(37,99,235,.08);  color:var(--ac);  border:1px solid rgba(37,99,235,.2); }

/* zona pill */
.rv-zona { display:inline-block; font-size:11px; font-weight:600; color:var(--sub); background:var(--inp); border:1px solid var(--bdr); border-radius:5px; padding:2px 8px; }

/* acciones */
.rv-acts { display:flex; gap:5px; align-items:center; justify-content:flex-end; }
.rv-act {
    padding:5px 10px; border-radius:6px; border:1px solid var(--bdr);
    background:var(--inp); cursor:pointer; color:var(--sub);
    font-size:11px; font-weight:600; font-family:inherit;
    transition:background .15s,border-color .15s,color .15s;
    display:inline-flex; align-items:center; gap:4px; white-space:nowrap;
}
.rv-act svg { width:11px; height:11px; }
.rv-act.wa:hover  { border-color:var(--gn);  color:var(--gn);  background:rgba(22,163,74,.06); }
.rv-act.ok:hover  { border-color:var(--gn);  color:#fff;       background:var(--gn); }
.rv-act.del:hover { border-color:var(--rd);  color:#fff;       background:var(--rd); }
.rv-act.fin:hover { border-color:var(--pu);  color:var(--pu);  background:rgba(124,58,237,.06); }

/* btn exportar */
.rv-export {
    padding:6px 14px; border-radius:7px; background:var(--ac); color:#fff;
    border:none; font-size:12px; font-weight:700; cursor:pointer;
    font-family:inherit; display:flex; align-items:center; gap:5px;
    transition:background .15s;
}
.rv-export:hover { background:var(--ac2); }
.rv-export svg { width:13px; height:13px; }

/* empty */
.rv-empty { padding:60px 20px; text-align:center; }
.rv-empty svg { width:36px; height:36px; color:var(--bdr); margin:0 auto 10px; display:block; }
.rv-empty p { font-size:13px; color:var(--sub); margin:0; }

/* paginación override */
.rv-pagination { padding:12px 16px; border-top:1px solid var(--bdr); }

/* alert */
.rv-alert-ok { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; margin-bottom:16px; background:rgba(22,163,74,.07); border:1px solid rgba(22,163,74,.2); font-size:12px; font-weight:600; color:var(--gn); }
.rv-alert-ok svg { width:13px; height:13px; flex-shrink:0; }

@media(max-width:640px){ .rv { padding:20px 16px 40px; } }
</style>

<div class="rv">

    {{-- HEADER --}}
    <div class="rv-hd">
        <h1>Gestión de Reservaciones</h1>
        <p>Administra y confirma las solicitudes de mesa de tus clientes.</p>
    </div>

    {{-- ALERTA --}}
    @if(session('success'))
    <div class="rv-alert-ok">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- STATS --}}
    @php
        $counts = \App\Models\Reservation::selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total','status');
    @endphp
    <div class="rv-stats">
        <div class="rv-stat">
            <div class="rv-stat-ico am">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="rv-stat-v" style="color:var(--am)">{{ $counts['pendiente'] ?? 0 }}</p>
                <p class="rv-stat-l">Pendientes</p>
            </div>
        </div>
        <div class="rv-stat">
            <div class="rv-stat-ico gn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="rv-stat-v" style="color:var(--gn)">{{ $counts['confirmada'] ?? 0 }}</p>
                <p class="rv-stat-l">Confirmadas</p>
            </div>
        </div>
        <div class="rv-stat">
            <div class="rv-stat-ico rd">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="rv-stat-v" style="color:var(--rd)">{{ $counts['cancelada'] ?? 0 }}</p>
                <p class="rv-stat-l">Canceladas</p>
            </div>
        </div>
        <div class="rv-stat">
            <div class="rv-stat-ico pu">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <p class="rv-stat-v" style="color:var(--pu)">{{ $counts['finalizada'] ?? 0 }}</p>
                <p class="rv-stat-l">Finalizadas</p>
            </div>
        </div>
    </div>

    {{-- TABS --}}
    <div class="rv-tabs">
        <a href="{{ route('admin.reservations.index', ['status'=>'pendiente']) }}"
           class="rv-tab am {{ $status==='pendiente'?'on':'' }}">
            @if($status==='pendiente')<span class="rv-tab-dot"></span>@endif
            Pendientes
            @if($counts['pendiente'] ?? 0)<span style="font-size:10px;background:rgba(217,119,6,.12);color:var(--am);padding:1px 6px;border-radius:20px;">{{ $counts['pendiente'] }}</span>@endif
        </a>
        <a href="{{ route('admin.reservations.index', ['status'=>'confirmada']) }}"
           class="rv-tab gn {{ $status==='confirmada'?'on':'' }}">
            @if($status==='confirmada')<span class="rv-tab-dot"></span>@endif
            Confirmadas
        </a>
        <a href="{{ route('admin.reservations.index', ['status'=>'cancelada']) }}"
           class="rv-tab rd {{ $status==='cancelada'?'on':'' }}">
            @if($status==='cancelada')<span class="rv-tab-dot"></span>@endif
            Canceladas
        </a>
        <a href="{{ route('admin.reservations.index', ['status'=>'finalizada']) }}"
           class="rv-tab pu {{ $status==='finalizada'?'on':'' }}">
            @if($status==='finalizada')<span class="rv-tab-dot"></span>@endif
            Finalizadas
        </a>
    </div>

    {{-- TABLA --}}
    <div class="rv-card">
        <div class="rv-card-hd">
            <div class="rv-card-ttl">
                <div class="rv-card-ttl-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <span>Reservas
                    @if($status==='pendiente') Pendientes
                    @elseif($status==='confirmada') Confirmadas
                    @elseif($status==='cancelada') Canceladas
                    @else Finalizadas
                    @endif
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="rv-badge">{{ $reservations->total() }} registros</span>
                <button class="rv-export" onclick="window.print()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exportar
                </button>
            </div>
        </div>

        <div class="rv-table-wrap">
            <table class="rv-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Fecha y Hora</th>
                        <th style="text-align:center">Personas</th>
                        <th>Zona</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $res)
                    @php $st = strtolower($res->status); @endphp
                    <tr>
                        <td>
                            <div class="rv-cell">
                                <p class="rv-name">{{ $res->nombre_completo }}</p>
                                <p class="rv-email">{{ $res->correo_electronico }}</p>
                            </div>
                        </td>
                        <td>
                            <div class="rv-cell">
                                <p class="rv-fecha">{{ \Carbon\Carbon::parse($res->fecha_reservacion)->format('d M, Y') }}</p>
                                <p class="rv-hora">{{ \Carbon\Carbon::parse($res->hora_reservacion)->format('h:i A') }}</p>
                            </div>
                        </td>
                        <td style="text-align:center">
                            <div class="rv-cell">
                                <span class="rv-zona">{{ $res->cantidad_personas }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="rv-cell">
                                <span class="rv-zona">
                                    {{ $res->zona === 'Terraza' ? '🌿' : '🍽️' }} {{ $res->zona }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="rv-cell">
                                <p class="rv-email" style="font-size:12px;color:var(--txt);">{{ $res->telefono }}</p>
                            </div>
                        </td>
                        <td>
                            <div class="rv-cell">
                                @if($st==='pendiente')
                                    <span class="rv-chip am">⏳ Pendiente</span>
                                @elseif($st==='confirmada')
                                    <span class="rv-chip gn">✓ Confirmada</span>
                                @elseif($st==='cancelada')
                                    <span class="rv-chip rd">✕ Cancelada</span>
                                @else
                                    <span class="rv-chip pu">✔ Finalizada</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="rv-cell">
                                <div class="rv-acts">

                                    {{-- WhatsApp --}}
                                    <a href="https://wa.me/52{{ preg_replace('/[^0-9]/', '', $res->telefono) }}"
                                       target="_blank" class="rv-act wa">
                                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        WA
                                    </a>

                                    {{-- Confirmar (solo pendiente) --}}
                                    @if($st === 'pendiente')
                                    <form action="{{ route('admin.reservations.update-status', ['reservation'=>$res->id, 'status'=>'confirmada']) }}" method="POST" style="display:inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="rv-act ok">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Confirmar
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Cancelar (pendiente o confirmada) --}}
                                    @if(in_array($st, ['pendiente', 'confirmada']))
                                    <form action="{{ route('admin.reservations.update-status', ['reservation'=>$res->id, 'status'=>'cancelada']) }}" method="POST" style="display:inline"
                                          @submit.prevent="if(confirm('¿Cancelar esta reservación?')) $el.submit()">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="rv-act del">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Cancelar
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Finalizar manual (confirmada) --}}
                                    @if($st === 'confirmada')
                                    <form action="{{ route('admin.reservations.update-status', ['reservation'=>$res->id, 'status'=>'finalizada']) }}" method="POST" style="display:inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="rv-act fin">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Finalizar
                                        </button>
                                    </form>
                                    @endif

                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="rv-empty">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p>No hay reservaciones
                                    @if($status==='pendiente') pendientes
                                    @elseif($status==='confirmada') confirmadas
                                    @elseif($status==='cancelada') canceladas
                                    @else finalizadas
                                    @endif
                                    por el momento.
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reservations->hasPages())
        <div class="rv-pagination">
            {{ $reservations->appends(['status' => $status])->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
