@extends('layouts.app')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Inter:wght@400;500&display=swap');

/* ══════════════════════════════════════════
   VARIABLES
══════════════════════════════════════════ */
.rv-page {
    --or:  #F97316; --or2: #EA580C;
    --gn:  #16A34A; --gn2: #15803D;
    --bg:      #0A0A0A; --card:  #141414;
    --inp:     #1C1C1C; --txt:   #FFFFFF;
    --sub:     #A1A1AA; --bdr:   rgba(255,255,255,.07);
    --glow:    rgba(249,115,22,.15);
    --glow-g:  rgba(22,163,74,.14);
    --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-dark.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-dark.svg')) }}');
}
html:not(.dark) .rv-page {
    --bg:   #F5F3EF; --card:  #FFFFFF;
    --inp:  #F4F4F5; --txt:   #18181B;
    --sub:  #71717A; --bdr:   rgba(0,0,0,.09);
    --glow: rgba(249,115,22,.09);
    --glow-g: rgba(22,163,74,.09);
    --brand-pattern: url('{{ asset('images/patterns/logo-501-pattern-light.svg') }}?v={{ @filemtime(public_path('images/patterns/logo-501-pattern-light.svg')) }}');
}

.rv-page { background:var(--bg); color:var(--txt); font-family:'Inter',sans-serif; min-height:100vh; transition:background .3s,color .3s; }

/* ── HERO ── */
.rv-hero {
    position:relative; background:var(--card); background-image:var(--brand-pattern);
    border-bottom:3px solid var(--or); padding:64px 32px 52px;
    text-align:center; overflow:hidden; transition:background .3s;
}
.rv-hero::before {
    content:''; position:absolute; top:-80px; left:50%; transform:translateX(-50%);
    width:600px; height:280px;
    background:radial-gradient(ellipse, var(--glow) 0%, transparent 70%);
    pointer-events:none;
}
.rv-hero-badge {
    display:inline-block; background:var(--or); color:#fff;
    font-family:'Oswald',sans-serif; font-size:10px; font-weight:700;
    letter-spacing:3px; text-transform:uppercase; padding:5px 18px;
    clip-path:polygon(10px 0%,100% 0%,calc(100% - 10px) 100%,0% 100%);
    margin-bottom:18px;
}
.rv-hero h1 {
    font-family:'Bebas Neue',sans-serif;
    font-size:clamp(52px,10vw,88px);
    letter-spacing:5px; line-height:.95;
    color:var(--txt); margin:0 0 14px;
    position:relative; z-index:1; transition:color .3s;
}
.rv-hero h1 span { color:var(--gn); }
.rv-hero p {
    font-size:15px; color:var(--sub); max-width:440px;
    margin:0 auto; line-height:1.75;
    position:relative; z-index:1; transition:color .3s;
}
.rv-hero p strong { color:var(--gn); font-weight:600; }

/* ── WRAP ── */
.rv-wrap { max-width:900px; margin:0 auto; padding:60px 24px 80px; }
@media(max-width:560px){ .rv-wrap { padding:40px 16px 60px; } }

/* ── ALERTS ── */
.rv-alert { display:flex; align-items:flex-start; gap:8px; padding:10px 14px; border-radius:8px; margin-bottom:20px; }
.rv-alert svg { width:14px; height:14px; flex-shrink:0; margin-top:1px; }
.rv-alert p { font-size:12px; font-weight:500; margin:0; line-height:1.5; }
.rv-alert.ok  { background:rgba(22,163,74,.07);  border:1px solid rgba(22,163,74,.2);  }
.rv-alert.ok  svg, .rv-alert.ok  p { color:var(--gn); }
.rv-alert.err { background:rgba(220,38,38,.07);  border:1px solid rgba(220,38,38,.2);  }
.rv-alert.err svg, .rv-alert.err p { color:#DC2626; }
.rv-alert.err ul { margin:4px 0 0 16px; font-size:12px; color:#DC2626; }

/* ── LAYOUT 2 COL ── */
.rv-layout { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; }
@media(max-width:740px){ .rv-layout { grid-template-columns:1fr; } .rv-side { order:-1; } }

/* ── FORM CARD ── */
.rv-card {
    background:var(--card); background-image:var(--brand-pattern);
    border:1px solid var(--bdr); border-radius:12px;
    overflow:hidden; transition:background .3s,border-color .3s;
}
.rv-card-top { height:3px; background:linear-gradient(90deg,var(--or),var(--gn)); }
.rv-card-body { padding:24px 28px 28px; }
@media(max-width:560px){ .rv-card-body { padding:18px 18px 22px; } }

.rv-card-ttl { display:flex; align-items:center; gap:8px; margin-bottom:4px; }
.rv-card-ttl-ico { width:28px; height:28px; border-radius:7px; background:rgba(22,163,74,.1); border:1px solid rgba(22,163,74,.18); display:flex; align-items:center; justify-content:center; }
.rv-card-ttl-ico svg { width:13px; height:13px; color:var(--gn); }
.rv-card-ttl h2 { font-family:'Oswald',sans-serif; font-size:16px; font-weight:700; letter-spacing:.5px; color:var(--txt); margin:0; transition:color .3s; }
.rv-card-desc { font-size:12px; color:var(--sub); margin:0 0 20px; transition:color .3s; }

/* form fields */
.rv-form { display:flex; flex-direction:column; gap:14px; }
.rv-g2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:500px){ .rv-g2 { grid-template-columns:1fr; } }
.rv-lbl { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); margin-bottom:5px; }
.rv-lbl .req { color:var(--or); }
.rv-inp, .rv-sel {
    width:100%; padding:10px 12px;
    background:var(--inp); border:1.5px solid var(--bdr);
    border-radius:8px; font-size:13px; color:var(--txt);
    outline:none; font-family:inherit;
    transition:border-color .15s,box-shadow .15s,background .3s;
}
.rv-inp:focus, .rv-sel:focus {
    border-color:var(--gn); box-shadow:0 0 0 3px rgba(22,163,74,.12);
}
.rv-sel { cursor:pointer; }

/* zona pills */
.rv-zona-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.rv-zona-opt {
    padding:10px 12px; border-radius:8px; cursor:pointer;
    border:1.5px solid var(--bdr); background:var(--inp);
    display:flex; flex-direction:column; align-items:center; gap:3px;
    font-size:12px; font-weight:600; color:var(--sub);
    transition:border-color .15s,background .15s,color .15s; text-align:center;
}
.rv-zona-opt span.ico { font-size:20px; }
.rv-zona-opt:hover { border-color:var(--gn); color:var(--txt); }
.rv-zona-opt.on { border-color:var(--gn); background:rgba(22,163,74,.07); color:var(--gn); }

/* personas select row */
.rv-personas-row { display:flex; align-items:center; gap:10px; }
.rv-personas-row .rv-sel { flex:1; }
.rv-personas-note { font-size:11px; color:var(--sub); margin:4px 0 0; line-height:1.5; }

/* submit */
.rv-submit {
    width:100%; padding:12px;
    background:var(--gn); color:#fff;
    border:none; border-radius:8px;
    font-size:14px; font-weight:700; letter-spacing:.2px;
    cursor:pointer; font-family:inherit;
    display:flex; align-items:center; justify-content:center; gap:6px;
    box-shadow:0 2px 10px rgba(22,163,74,.3);
    transition:background .15s,transform .1s; margin-top:4px;
}
.rv-submit:hover { background:var(--gn2); }
.rv-submit:active { transform:scale(.98); }
.rv-submit svg { width:15px; height:15px; }
.rv-submit:disabled { background:var(--bdr); color:var(--sub); box-shadow:none; cursor:not-allowed; }

/* whatsapp btn */
.rv-wa {
    display:none; width:100%; padding:12px;
    background:#16A34A; color:#fff;
    border:none; border-radius:8px;
    font-size:14px; font-weight:700;
    cursor:pointer; font-family:inherit;
    align-items:center; justify-content:center; gap:6px;
    box-shadow:0 2px 10px rgba(22,163,74,.3);
    transition:background .15s; margin-top:4px;
    text-decoration:none;
}
.rv-wa.visible { display:flex; }
.rv-wa:hover { background:#15803D; }
.rv-wa svg { width:16px; height:16px; }

/* ── SIDEBAR ── */
.rv-side { display:flex; flex-direction:column; gap:12px; }
.rv-info-card {
    background:var(--card); background-image:var(--brand-pattern);
    border:1px solid var(--bdr); border-radius:10px;
    padding:16px; transition:background .3s,border-color .3s;
}
.rv-info-card:hover { border-color:var(--or); }
.rv-info-sec { font-family:'Oswald',sans-serif; font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--or); margin:0 0 10px; display:flex; align-items:center; gap:7px; }
.rv-info-sec::after { content:''; flex:1; height:1px; background:var(--bdr); }
.rv-info-row { display:flex; align-items:flex-start; gap:8px; padding:8px 0; border-bottom:1px solid var(--bdr); }
.rv-info-row:last-child { border-bottom:none; padding-bottom:0; }
.rv-info-row:first-of-type { padding-top:0; }
.rv-info-ico { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rv-info-ico svg { width:13px; height:13px; }
.rv-info-ico.or { background:rgba(249,115,22,.1); color:var(--or); }
.rv-info-ico.gn { background:rgba(22,163,74,.1);  color:var(--gn); }
.rv-info-ico.bl { background:rgba(59,130,246,.1);  color:#3B82F6; }
.rv-info-l { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); margin:0 0 2px; }
.rv-info-v { font-size:12px; font-weight:600; color:var(--txt); margin:0; line-height:1.4; transition:color .3s; }

/* horarios */
.rv-hor { width:100%; display:flex; flex-direction:column; gap:0; }
.rv-hor-row { display:grid; grid-template-columns:1fr 1fr; padding:5px 0; font-size:11px; border-bottom:1px solid var(--bdr); align-items:center; }
.rv-hor-row:last-child { border-bottom:none; }
.rv-hor .day { color:var(--sub); font-weight:500; white-space:nowrap; }
.rv-hor .hrs { color:var(--txt); font-weight:700; text-align:right; transition:color .3s; white-space:nowrap; font-variant-numeric:tabular-nums; }
.rv-hor .hrs.gn { color:var(--gn); }

/* nota card */
.rv-note {
    background:rgba(249,115,22,.06); border:1px solid rgba(249,115,22,.18);
    border-left:3px solid var(--or); border-radius:8px; padding:12px 14px;
}
.rv-note p { font-size:11px; color:var(--sub); margin:0; line-height:1.7; }
.rv-note p strong { color:var(--or); }
</style>

<div class="rv-page" x-data="reservaPage()">

    {{-- HERO --}}
    <div class="rv-hero">
        <div class="rv-hero-badge">La 501 Sports Restaurant</div>
        <h1>Reser<span>vaciones</span></h1>
        <p>Aparta tu mesa y disfruta de la mejor experiencia. <strong>Sujeto a confirmación del personal.</strong></p>
    </div>

    <div class="rv-wrap">

        {{-- ALERTAS --}}
        @if($errors->any())
        <div class="rv-alert err">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <p>Por favor corrige los siguientes errores:</p>
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
        @endif

        @if(session('success'))
        <div class="rv-alert ok">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>{{ session('success') }}</p>
        </div>
        @endif

        <div class="rv-layout">

            {{-- ── FORMULARIO ── --}}
            <div>
                <div class="rv-card">
                    <div class="rv-card-top"></div>
                    <div class="rv-card-body">

                        <div class="rv-card-ttl">
                            <div class="rv-card-ttl-ico">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h2>Solicitar Mesa</h2>
                        </div>
                        <p class="rv-card-desc">Completa el formulario y te confirmaremos la disponibilidad.</p>

                        <form action="{{ route('reservations.store') }}" method="POST" class="rv-form" @submit="sending=true">
                            @csrf

                            {{-- Nombre + Teléfono --}}
                            <div class="rv-g2">
                                <div>
                                    <label class="rv-lbl" for="nombre_completo">Nombre completo <span class="req">*</span></label>
                                    <input type="text" id="nombre_completo" name="nombre_completo" class="rv-inp"
                                           placeholder="Juan Pérez"
                                           value="{{ old('nombre_completo') }}" required>
                                </div>
                                <div>
                                    <label class="rv-lbl" for="telefono">Teléfono <span class="req">*</span></label>
                                    <input type="tel" id="telefono" name="telefono" class="rv-inp"
                                           placeholder="771-XXX-XXXX"
                                           value="{{ old('telefono') }}" required>
                                </div>
                            </div>

                            {{-- Correo --}}
                            <div>
                                <label class="rv-lbl" for="correo_electronico">Correo electrónico <span class="req">*</span></label>
                                <input type="email" id="correo_electronico" name="correo_electronico" class="rv-inp"
                                       placeholder="ejemplo@correo.com"
                                       value="{{ old('correo_electronico') }}" required>
                            </div>

                            {{-- Fecha + Hora --}}
                            <div class="rv-g2">
                                <div>
                                    <label class="rv-lbl" for="fecha_reservacion">Fecha <span class="req">*</span></label>
                                    <input type="date" id="fecha_reservacion" name="fecha_reservacion" class="rv-inp"
                                           min="{{ date('Y-m-d') }}"
                                           value="{{ old('fecha_reservacion') }}" required>
                                </div>
                                <div>
                                    <label class="rv-lbl" for="hora_reservacion">Hora <span class="req">*</span></label>
                                    <input type="time" id="hora_reservacion" name="hora_reservacion" class="rv-inp"
                                           value="{{ old('hora_reservacion') }}" required>
                                </div>
                            </div>

                            {{-- Zona --}}
                            <div>
                                <label class="rv-lbl" for="zona">Zona <span class="req">*</span></label>
                                <div class="rv-zona-grid" id="zona">
                                    <div class="rv-zona-opt" :class="zona==='General'?'on':''" @click="zona='General'">
                                        <span class="ico">🍽️</span>
                                        <span>Área General</span>
                                    </div>
                                    <div class="rv-zona-opt" :class="zona==='Terraza'?'on':''" @click="zona='Terraza'">
                                        <span class="ico">🌿</span>
                                        <span>Terraza</span>
                                    </div>
                                </div>
                                <input type="hidden" name="zona" :value="zona">
                            </div>

                            {{-- Personas --}}
                            <div>
                                <label class="rv-lbl" for="cantidad_personas">Cantidad de personas <span class="req">*</span></label>
                                <select id="cantidad_personas" name="cantidad_personas" class="rv-sel" x-model="personas" @change="checkGrupo()">
                                    @for($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}">{{ $i }} {{ $i == 1 ? 'Persona' : 'Personas' }}</option>
                                    @endfor
                                    <option value="11">Más de 10 personas...</option>
                                </select>
                                <p class="rv-personas-note" x-show="personas=='11'" style="color:var(--or);">
                                    Para grupos grandes te redirigiremos a WhatsApp para coordinar mejor.
                                </p>
                            </div>

                            {{-- Botón enviar --}}
                            <button type="submit" class="rv-submit" :disabled="sending" x-show="personas!='11'">
                                <template x-if="!sending">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </template>
                                <template x-if="sending">
                                    <svg class="animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </template>
                                <span x-text="sending ? 'Enviando...' : 'Solicitar Reservación'"></span>
                            </button>

                            {{-- WhatsApp para grupos --}}
                            <a :href="waLink()" target="_blank"
                               class="rv-wa" :class="personas=='11'?'visible':''"
                               x-show="personas=='11'">
                                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Contactar por WhatsApp para Grupos
                            </a>

                        </form>
                    </div>
                </div>
            </div>

            {{-- ── SIDEBAR ── --}}
            <aside class="rv-side">

                {{-- Horarios Dinámicos Uniformes --}}
                <div class="rv-info-card">
                    <p class="rv-info-sec">Horarios</p>
                    <div class="rv-hor">
                        @php
                            $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
                        @endphp

                        @foreach($dias as $dia)
                            @php
                                $key = 'schedule_' . strtolower($dia);
                                $horario = \App\Models\Setting::where('key', $key)->value('value') ?: '12:00 – 22:00';
                                $cerrado = strtolower(trim($horario)) === 'cerrado';
                            @endphp
                            <div class="rv-hor-row">
                                <div class="day">
                                    {{ $dia }}
                                </div>
                                <div class="hrs {{ $cerrado ? 'cerrado' : '' }}">
                                    {{ $horario }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Nota --}}
                <div class="rv-note">
                    <p>
                        <strong>⚠️ Importante:</strong> Las reservaciones están sujetas a disponibilidad y confirmación por parte de nuestro personal. Te contactaremos para confirmar tu mesa.
                    </p>
                </div>

            </aside>

        </div>
    </div>
</div>

<script>
function reservaPage() {
    return {
        zona:    '{{ old("zona", "General") }}',
        personas:'{{ old("cantidad_personas", "1") }}',
        sending: false,

        checkGrupo() {
            // solo para el efecto visual, el x-show en el template ya lo maneja
        },

        waLink() {
            const tel = '527711097827';
            const msg = encodeURIComponent('Hola La 501, me gustaría reservar para un grupo de más de 10 personas.');
            return `https://wa.me/${tel}?text=${msg}`;
        }
    }
}
</script>

@endsection