@extends('layouts.admin')

@section('content')
<style>
.db {
    --ac:  #2563EB; --ac2: #1D4ED8;
    --gn:  #16A34A; --gn2: #15803D;
    --rd:  #DC2626; --or:  #EA580C;
    --bg:  #F8F8F8; --card: #FFFFFF;
    --inp: #F4F4F5; --txt:  #18181B;
    --sub: #71717A; --bdr:  #E4E4E7;
}
.dark .db {
    --bg:  #0A0A0A; --card: #111111;
    --inp: #1C1C1C; --txt:  #FAFAFA;
    --sub: #71717A; --bdr:  rgba(255,255,255,.08);
}
.db { background:var(--bg); min-height:100%; padding:28px 32px 60px; color:var(--txt); }

/* HEADER */
.db-hd { margin-bottom:24px; }
.db-hd-row { display:flex; align-items:center; gap:8px; margin-bottom:4px; }
.db-hd-ico { width:28px; height:28px; border-radius:7px; background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.15); display:flex; align-items:center; justify-content:center; }
.db-hd-ico svg { width:14px; height:14px; color:var(--ac); }
.db-hd h1 { font-size:20px; font-weight:700; color:var(--txt); margin:0; }
.db-hd p  { font-size:13px; color:var(--sub); margin:0; }

/* ALERT */
.db-ok { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; margin-bottom:20px; background:rgba(22,163,74,.07); border:1px solid rgba(22,163,74,.2); font-size:12px; font-weight:600; color:var(--gn); }
.db-ok svg { width:13px; height:13px; flex-shrink:0; }

/* GRID */
.db-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:14px; }

/* CARD */
.db-card { background:var(--card); border:1px solid var(--bdr); border-radius:10px; overflow:hidden; transition:background .2s,border-color .2s; }
.db-card-bar { height:3px; }
.db-card-bar.bl { background:linear-gradient(90deg,var(--ac),#60A5FA); }
.db-card-bar.gn { background:linear-gradient(90deg,var(--gn),#4ADE80); }
.db-card-bar.or { background:linear-gradient(90deg,var(--or),#FB923C); }
.db-card-body { padding:20px; }

/* CARD HEAD */
.db-ch { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:12px; gap:10px; }
.db-ch-l { display:flex; align-items:center; gap:10px; }
.db-ico { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.db-ico svg { width:15px; height:15px; }
.db-ico.bl { background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.15); color:var(--ac); }
.db-ico.gn { background:rgba(22,163,74,.08);  border:1px solid rgba(22,163,74,.15);  color:var(--gn); }
.db-ico.or { background:rgba(234,88,12,.08);  border:1px solid rgba(234,88,12,.15);  color:var(--or); }
.db-ttl { font-size:14px; font-weight:700; color:var(--txt); margin:0 0 2px; }
.db-sub { font-size:12px; color:var(--sub); margin:0; line-height:1.5; }

/* DIVIDER */
.db-div { height:1px; background:var(--bdr); margin:14px 0; }

/* LABELS + INPUTS */
.db-lbl { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--sub); margin-bottom:5px; }
.db-inp, .db-sel {
    width:100%; padding:8px 10px; background:var(--inp); border:1px solid var(--bdr);
    border-radius:7px; font-size:13px; color:var(--txt); outline:none; font-family:inherit;
    transition:border-color .15s,box-shadow .15s;
}
.db-inp:focus, .db-sel:focus { border-color:var(--ac); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.db-sel { cursor:pointer; }
.db-hint { font-size:11px; color:var(--sub); margin:5px 0 0; line-height:1.5; }

/* INFO BOX */
.db-box { display:flex; align-items:flex-start; gap:8px; padding:10px 12px; border-radius:7px; font-size:11px; line-height:1.6; }
.db-box svg { width:13px; height:13px; flex-shrink:0; margin-top:1px; }
.db-box.bl { background:rgba(37,99,235,.06); border:1px solid rgba(37,99,235,.14); color:#1d4ed8; }
.db-box.rd { background:rgba(220,38,38,.06);  border:1px solid rgba(220,38,38,.14); color:var(--rd); }
.dark .db-box.bl { color:#93C5FD; }
.dark .db-box.rd { color:#FCA5A5; }

/* TOGGLE */
.db-tog { position:relative; width:38px; height:22px; flex-shrink:0; cursor:pointer; }
.db-tog input { opacity:0; width:0; height:0; position:absolute; }
.db-tog-sl { position:absolute; inset:0; border-radius:20px; background:var(--bdr); transition:background .2s; }
.db-tog-sl::before { content:''; position:absolute; left:3px; top:3px; width:16px; height:16px; border-radius:50%; background:#fff; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.db-tog input:checked + .db-tog-sl { background:var(--gn); }
.db-tog input:checked + .db-tog-sl::before { transform:translateX(16px); }

/* TABS */
.db-tabs { display:flex; gap:2px; border-bottom:1px solid var(--bdr); margin-bottom:14px; }
.db-tab { padding:7px 14px; font-size:12px; font-weight:600; border:none; background:none; cursor:pointer; color:var(--sub); border-bottom:2px solid transparent; margin-bottom:-1px; transition:color .15s,border-color .15s; font-family:inherit; }
.db-tab:hover { color:var(--txt); }
.db-tab.on { color:var(--or); border-bottom-color:var(--or); }

/* BACKUP LIST */
.db-bk { display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-radius:8px; background:var(--inp); border:1px solid var(--bdr); margin-bottom:6px; transition:border-color .15s; }
.db-bk:hover { border-color:var(--or); }
.db-bk:last-child { margin-bottom:0; }
.db-bk-name { font-size:12px; font-weight:600; color:var(--txt); margin:0 0 2px; }
.db-bk-meta { font-size:10px; color:var(--sub); font-family:monospace; }
.db-bk-acts { display:flex; gap:5px; }

/* EMPTY */
.db-empty { padding:32px 20px; text-align:center; border:2px dashed var(--bdr); border-radius:8px; }
.db-empty p { font-size:12px; color:var(--sub); margin:0; }

/* FILE DROP */
.db-file-lbl { display:flex; flex-direction:column; align-items:center; gap:8px; padding:24px 16px; background:var(--inp); border:2px dashed var(--bdr); border-radius:8px; cursor:pointer; transition:border-color .2s; text-align:center; }
.db-file-lbl:hover { border-color:var(--or); }
.db-file-lbl svg { width:24px; height:24px; color:var(--sub); }
.db-file-lbl span { font-size:12px; font-weight:600; color:var(--sub); }
.db-file-lbl small { font-size:11px; color:var(--sub); opacity:.7; }

/* BUTTONS */
.db-btn { width:100%; padding:10px; border-radius:7px; border:none; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .15s,transform .1s; }
.db-btn:active { transform:scale(.98); }
.db-btn svg { width:14px; height:14px; }
.db-btn.bl  { background:var(--ac);  color:#fff; box-shadow:0 2px 8px rgba(37,99,235,.25); }
.db-btn.bl:hover  { background:var(--ac2); }
.db-btn.gn  { background:var(--gn);  color:#fff; box-shadow:0 2px 8px rgba(22,163,74,.25); }
.db-btn.gn:hover  { background:var(--gn2); }
.db-btn.dk  { background:#18181B; color:#fff; }
.db-btn.dk:hover  { background:#27272A; }
.dark .db-btn.dk  { background:#FAFAFA; color:#18181B; }
.dark .db-btn.dk:hover { background:#E4E4E7; }

.db-btn-sm { padding:5px 10px; border-radius:6px; border:none; font-size:11px; font-weight:700; cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:4px; transition:background .15s; }
.db-btn-sm svg { width:11px; height:11px; }
.db-btn-sm.or { background:var(--or); color:#fff; }
.db-btn-sm.or:hover { background:#C2410C; }
.db-btn-sm.bl { background:var(--ac); color:#fff; }
.db-btn-sm.bl:hover { background:var(--ac2); }

@media(max-width:640px){ .db { padding:20px 16px 40px; } .db-grid { grid-template-columns:1fr; } }
</style>

<div class="db">

    {{-- HEADER --}}
    <div class="db-hd">
        <div class="db-hd-row">
            <div class="db-hd-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
            </div>
            <h1>Gestión del Sistema</h1>
        </div>
        <p>Administración avanzada de datos, copias de seguridad y tareas programadas.</p>
    </div>

    @if(session('success'))
    <div class="db-ok">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    <div class="db-grid">

        {{-- ── TARJETA 1: RESPALDO MANUAL ── --}}
        <div class="db-card">
            <div class="db-card-bar bl"></div>
            <div class="db-card-body">
                <div class="db-ch">
                    <div class="db-ch-l">
                        <div class="db-ico bl">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </div>
                        <div>
                            <p class="db-ttl">Crear Punto de Restauración</p>
                            <p class="db-sub">Respaldo manual inmediato</p>
                        </div>
                    </div>
                </div>

                <div class="db-div"></div>

                <p style="font-size:13px; color:var(--sub); margin:0 0 14px; line-height:1.6;">
                    Genera un respaldo completo de toda la información actual del sistema. El archivo se guardará en el historial del servidor.
                </p>

                <div class="db-box bl" style="margin-bottom:14px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Se recomienda generar un respaldo antes de cualquier cambio importante en el sistema.</span>
                </div>

                <form action="{{ route('admin.database.backup') }}" method="POST">
                    @csrf
                    <button type="submit" class="db-btn bl">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Generar Respaldo Ahora
                    </button>
                </form>
            </div>
        </div>

        {{-- ── TARJETA 2: RESPALDO AUTOMÁTICO ── --}}
        <div class="db-card" x-data="{
            autoEnabled: {{ \App\Models\Setting::where('key', 'backup_enabled')->value('value') == '1' ? 'true' : 'false' }},
            frecuencia: '{{ \App\Models\Setting::where('key', 'backup_frecuencia')->value('value') ?? 'intervalo' }}',
            intervalo: '{{ \App\Models\Setting::where('key', 'backup_intervalo')->value('value') ?? '60' }}'
        }">
            <div class="db-card-bar gn"></div>
            <div class="db-card-body">
                <div class="db-ch">
                    <div class="db-ch-l">
                        <div class="db-ico gn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="db-ttl">Respaldo Automático</p>
                            <p class="db-sub">Programa copias periódicas</p>
                        </div>
                    </div>
                    {{-- Toggle --}}
                    <label class="db-tog" for="backup_toggle">
                        <span style="display:none;">Activar respaldo automático</span>
                        <input id="backup_toggle" type="checkbox" x-model="autoEnabled">
                        <span class="db-tog-sl"></span>
                    </label>
                </div>

                <div class="db-div"></div>

                {{-- Apagado --}}
                <div x-show="!autoEnabled">
                    <div class="db-box rd">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        <span>El motor de respaldo automático está <strong>apagado</strong>. Actívalo con el interruptor.</span>
                    </div>
                </div>

                {{-- Encendido --}}
                <form action="{{ route('admin.database.saveAuto') }}" method="POST" x-show="autoEnabled" x-transition.opacity style="display:none;">
                    @csrf
                    <input type="hidden" name="backup_enabled" value="1">

                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <label class="db-lbl" for="frecuencia_sel">Frecuencia</label>
                            <select id="frecuencia_sel" name="frecuencia" class="db-sel" x-model="frecuencia">
                                <option value="intervalo">Por intervalos cortos</option>
                                <option value="diario">Diariamente</option>
                                <option value="semanal">Semanalmente</option>
                                <option value="mensual">Mensualmente</option>
                            </select>
                        </div>

                        <div x-show="frecuencia !== 'intervalo'">
                            <label class="db-lbl" for="hora_inp">Hora de ejecución</label>
                            <input id="hora_inp" type="time" name="hora" class="db-inp"
                                   value="{{ \App\Models\Setting::where('key', 'backup_hora')->value('value') ?? '03:00' }}">
                            <p class="db-hint">El respaldo se creará en el primer chequeo del servidor después de esta hora.</p>
                        </div>

                        <div x-show="frecuencia === 'intervalo'">
                            <label class="db-lbl" for="intervalo_sel">Intervalo</label>
                            <select id="intervalo_sel" name="intervalo" class="db-sel" x-model="intervalo">
                                <option value="1">Cada 1 minuto (prueba)</option>
                                <option value="15">Cada 15 minutos</option>
                                <option value="30">Cada 30 minutos</option>
                                <option value="60">Cada hora (recomendado)</option>
                            </select>
                            <div class="db-box bl" style="margin-top:8px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>
                                    <span x-show="intervalo=='1'">Respaldo cada 1 minuto desde la última ejecución exitosa.</span>
                                    <span x-show="intervalo=='15'">Respaldo cada 15 minutos desde la última ejecución exitosa.</span>
                                    <span x-show="intervalo=='30'">Respaldo cada 30 minutos desde la última ejecución exitosa.</span>
                                    <span x-show="intervalo=='60'">Respaldo cada hora desde la última ejecución exitosa.</span>
                                </span>
                            </div>
                        </div>

                        <p style="font-size:11px; color:var(--sub); margin:0; line-height:1.6;">
                            Los respaldos con más de 72 horas se eliminarán automáticamente del servidor.
                        </p>

                        <button type="submit" class="db-btn gn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── TARJETA 3: RESTAURACIÓN ── --}}
        <div class="db-card" x-data="{ tab: 'historial' }">
            <div class="db-card-bar or"></div>
            <div class="db-card-body">
                <div class="db-ch">
                    <div class="db-ch-l">
                        <div class="db-ico or">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <div>
                            <p class="db-ttl">Restauración de Datos</p>
                            <p class="db-sub">Reemplaza la base de datos actual</p>
                        </div>
                    </div>
                </div>

                <div class="db-box rd" style="margin-bottom:14px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Esta acción es <strong>irreversible</strong>. Todos los datos actuales serán reemplazados.</span>
                </div>

                {{-- Tabs --}}
                <div class="db-tabs">
                    <button class="db-tab" :class="tab==='historial'?'on':''" @click="tab='historial'">Historial del Servidor</button>
                    <button class="db-tab" :class="tab==='subir'?'on':''"    @click="tab='subir'">Subir Archivo</button>
                </div>

                {{-- Historial --}}
                <div x-show="tab==='historial'" x-transition.opacity>
                    @if(count($backups) > 0)
                        @foreach($backups as $backup)
                        <div class="db-bk">
                            <div>
                                <p class="db-bk-name">{{ \Carbon\Carbon::parse($backup['date'])->diffForHumans() }}</p>
                                <p class="db-bk-meta">{{ $backup['date'] }} · {{ $backup['size'] }}</p>
                            </div>
                            <div class="db-bk-acts">
                                <form action="{{ route('admin.database.download') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="file_path" value="{{ $backup['path'] }}">
                                    <button type="submit" class="db-btn-sm bl" title="Descargar">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Descargar
                                    </button>
                                </form>
                                <form action="{{ route('admin.database.restore') }}" method="POST"
                                      onsubmit="return confirm('¿Restaurar esta copia? Los datos actuales se perderán.')">
                                    @csrf
                                    <input type="hidden" name="file_path" value="{{ $backup['path'] }}">
                                    <button type="submit" class="db-btn-sm or" title="Restaurar">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Restaurar
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                        <div style="text-align:center; padding-top:12px; border-top:1px solid var(--bdr); margin-top:8px;">
                            <a href="{{ route('admin.database.history') }}" style="font-size:11px; font-weight:700; color:var(--or); text-decoration:none; text-transform:uppercase; letter-spacing:.5px;">
                                Ver historial completo →
                            </a>
                        </div>
                    @else
                        <div class="db-empty">
                            <p>No hay copias automáticas guardadas en el servidor aún.</p>
                        </div>
                    @endif
                </div>

                {{-- Subir manual --}}
                <div x-show="tab==='subir'" x-transition.opacity style="display:none;">
                    <form action="{{ route('admin.database.restore.upload') }}" method="POST"
                          enctype="multipart/form-data"
                          onsubmit="return confirm('¿Restaurar este archivo? Los datos actuales se reemplazarán.')">
                        @csrf
                        <label class="db-file-lbl" style="margin-bottom:14px;" id="sql-drop-lbl">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span>Haz clic para seleccionar</span>
                            <small id="sql-filename">Solo archivos .sql</small>
                            <input type="file" name="sql_file" accept=".sql" required style="display:none;"
                                   onchange="document.getElementById('sql-filename').textContent = this.files[0] ? this.files[0].name : 'Solo archivos .sql'">
                        </label>
                        <button type="submit" class="db-btn dk">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Subir y Restaurar
                        </button>
                    </form>
                </div>

            </div>
        </div>

        {{-- ── TARJETA 4: MANTENIMIENTO Y OPTIMIZACIÓN (NUEVA) ── --}}
        <div class="db-card">
            <div class="db-card-bar gn"></div>
            <div class="db-card-body">
                <div class="db-ch">
                    <div class="db-ch-l">
                        <div class="db-ico gn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="db-ttl">Mantenimiento y Optimización</p>
                            <p class="db-sub">Mejora el rendimiento estructural</p>
                        </div>
                    </div>
                </div>

                <div class="db-div"></div>
                
                <p style="font-size:13px; color:var(--sub); margin:0 0 14px; line-height:1.6;">
                    Ejecuta rutinas para reorganizar datos físicos y actualizar estadísticas del motor de base de datos.
                </p>

                <form action="{{ route('admin.database.optimize') ?? '#' }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="indices">
                    <button type="submit" class="db-btn gn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Mantenimiento de índices
                    </button>
                </form>

                <form action="{{ route('admin.database.optimize') ?? '#' }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="tablas">
                    <button type="submit" class="db-btn dk">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Actualización de tablas e índices
                    </button>
                </form>

                <form action="{{ route('admin.database.reindex') ?? '#' }}" method="POST">
                    @csrf
                    <button type="submit" class="db-btn dk">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Reorganización y reindexado
                    </button>
                </form>
            </div>
        </div>

        {{-- ── TARJETA 5: LIMPIEZA DE DATOS (NUEVA) ── --}}
        <div class="db-card">
            <div class="db-card-bar rd"></div>
            <div class="db-card-body">
                <div class="db-ch">
                    <div class="db-ch-l">
                        <div class="db-ico rd">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <p class="db-ttl">Limpieza de Datos</p>
                            <p class="db-sub">Depuración de registros</p>
                        </div>
                    </div>
                </div>

                <div class="db-div"></div>
                
                <p style="font-size:13px; color:var(--sub); margin:0 0 14px; line-height:1.6;">
                    Elimina registros huérfanos, caché expirada, sesiones de usuarios antiguos y optimiza el espacio de almacenamiento.
                </p>

                <div class="db-box rd" style="margin-bottom:14px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Ejecuta estas tareas fuera de las horas pico de operación.</span>
                </div>

                <form action="{{ route('admin.database.cleanup') ?? '#' }}" method="POST" onsubmit="return confirm('¿Iniciar proceso de limpieza de datos?');">
                    @csrf
                    <button type="submit" class="db-btn rd">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Ejecutar Tareas de Limpieza
                    </button>
                </form>
            </div>
        </div>

        {{-- ── TARJETA 6: REPORTES DE INFORMACIÓN (NUEVA) ── --}}
        <div class="db-card">
            <div class="db-card-bar pu"></div>
            <div class="db-card-body">
                <div class="db-ch">
                    <div class="db-ch-l">
                        <div class="db-ico pu">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="db-ttl">Reportes de Información</p>
                            <p class="db-sub">Auditoría y estadísticas</p>
                        </div>
                    </div>
                </div>

                <div class="db-div"></div>
                
                <p style="font-size:13px; color:var(--sub); margin:0 0 14px; line-height:1.6;">
                    Genera y exporta informes detallados sobre el tamaño de la base de datos, crecimiento de tablas y estado general del sistema.
                </p>

                <form action="{{ route('admin.database.reports') ?? '#' }}" method="GET">
                    <button type="submit" class="db-btn pu">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Generar Reporte Completo
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
