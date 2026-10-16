@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Inter:wght@400;500&display=swap');

    .cf {
        --or:#F97316; --or2:#EA580C;
        --gn:#16A34A; --gn2:#22C55E;
        --bg:#0A0A0A; --card:#141414;
        --inp:#1C1C1C; --txt:#FFFFFF;
        --sub:#A1A1AA; --bdr:rgba(255,255,255,.08);
    }
    html:not(.dark) .cf {
        --bg:#F5F3EF; --card:#FFFFFF;
        --inp:#F4F4F5; --txt:#18181B;
        --sub:#71717A; --bdr:rgba(0,0,0,.09);
    }
    .cf { background:var(--bg); color:var(--txt); font-family:'Inter',sans-serif; min-height:100vh; padding:40px 20px 80px; transition:background .3s,color .3s; }
    .cf-wrap { max-width:640px; margin:0 auto; display:flex; flex-direction:column; gap:20px; }
    .cf-title { font-family:'Oswald',sans-serif; font-size:26px; font-weight:700; letter-spacing:.5px; margin:0 0 4px; }
    .cf-sub { color:var(--sub); font-size:13px; margin:0 0 8px; }

    .cf-card { background:var(--card); border:1px solid var(--bdr); border-radius:14px; overflow:hidden; }
    .cf-card-bar { height:3px; background:linear-gradient(90deg,var(--or),var(--gn)); }
    .cf-card-body { padding:22px 24px; }
    .cf-card-head { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
    .cf-card-head .ico { font-size:20px; }
    .cf-card-head h2 { font-family:'Oswald',sans-serif; font-size:15px; font-weight:700; margin:0; letter-spacing:.3px; }
    .cf-card-head p { font-size:12px; color:var(--sub); margin:2px 0 0; }

    .cf-lbl { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--sub); margin-bottom:5px; }
    .cf-inp { width:100%; padding:10px 12px; background:var(--inp); border:1.5px solid var(--bdr); border-radius:8px; font-size:13px; color:var(--txt); outline:none; font-family:inherit; transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
    .cf-inp:focus { border-color:var(--or); box-shadow:0 0 0 3px rgba(249,115,22,.12); }
    .cf-field { margin-bottom:14px; }

    .cf-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:8px; border:none; cursor:pointer; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; font-family:'Oswald',sans-serif; transition:background .15s,transform .1s; }
    .cf-btn.or { background:var(--or); color:#fff; }
    .cf-btn.or:hover { background:var(--or2); }
    .cf-btn.gn { background:var(--gn); color:#fff; }
    .cf-btn.gn:hover { background:var(--gn2); }
    .cf-btn.danger { background:rgba(239,68,68,.12); color:#EF4444; }
    .cf-btn.danger:hover { background:rgba(239,68,68,.2); }

    .cf-toggle-row { display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .cf-toggle-row p { font-size:13px; margin:0 0 4px; font-weight:600; }
    .cf-toggle-row span.desc { font-size:12px; color:var(--sub); font-weight:400; }

    .cf-alert { border-radius:10px; padding:12px 16px; font-size:13px; font-weight:600; margin-bottom:4px; }
    .cf-alert.success { background:rgba(22,163,74,.1); color:#22C55E; border:1px solid rgba(34,197,94,.25); }
    .cf-alert.error { background:rgba(239,68,68,.1); color:#F87171; border:1px solid rgba(239,68,68,.25); }
</style>

<div class="cf">
    <div class="cf-wrap">
        <div>
            <h1 class="cf-title">⚙️ Configuración</h1>
            <p class="cf-sub">Administra la seguridad y preferencias de tu cuenta.</p>
        </div>

        @if(session('success'))
            <div class="cf-alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="cf-alert error">⚠️ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="cf-alert error">⚠️ {{ $errors->first() }}</div>
        @endif

        {{-- MIS DATOS — Rectificación ARCO (todos los roles) --}}
        <div class="cf-card">
            <div class="cf-card-bar"></div>
            <div class="cf-card-body">
                <div class="cf-card-head">
                    <span class="ico">📝</span>
                    <div>
                        <h2>Mis Datos</h2>
                        <p>Corrige tu nombre, correo o teléfono cuando lo necesites.</p>
                    </div>
                </div>

                <form action="{{ route('configuracion.datos') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="cf-field">
                        <label class="cf-lbl" for="cf-name">Nombre completo</label>
                        <input type="text" id="cf-name" name="name" required minlength="3" value="{{ old('name', $user->name) }}" class="cf-inp">
                    </div>
                    <div class="cf-field">
                        <label class="cf-lbl" for="cf-email">Correo electrónico</label>
                        <input type="email" id="cf-email" name="email" required value="{{ old('email', $user->email) }}" class="cf-inp">
                    </div>
                    <button type="submit" class="cf-btn or">Guardar Nombre y Correo</button>
                </form>

                {{-- El teléfono va aparte porque cambiarlo pide un código por SMS --}}
                <div style="margin-top:20px;padding-top:18px;border-top:1px solid var(--bdr);"
                     x-data="{
                        step: 'idle',
                        nuevo: '{{ old('telefono', $user->telefono) }}',
                        loading: false,
                        info: '',
                        solicitar() {
                            this.loading = true; this.info = '';
                            fetch('{{ route('configuracion.telefono.solicitar') }}', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                body: JSON.stringify({ telefono: this.nuevo })
                            }).then(r => r.json()).then(d => {
                                this.loading = false; this.info = d.message;
                                if (d.success) this.step = 'code';
                            }).catch(() => { this.loading = false; this.info = 'No pudimos conectar. Intenta de nuevo.'; });
                        }
                     }">
                    <label class="cf-lbl">Teléfono</label>
                    <p style="font-size:13px;margin:0 0 10px;">{{ $user->telefono ?: 'No registrado' }}</p>

                    <template x-if="step === 'idle'">
                        <div class="cf-field">
                            <label class="cf-lbl" for="cf-telefono-nuevo">Nuevo teléfono (10 dígitos)</label>
                            <input type="tel" id="cf-telefono-nuevo" x-model="nuevo" pattern="\d{10}" maxlength="10" placeholder="7711234567" class="cf-inp">
                            <button type="button" class="cf-btn or" style="margin-top:8px" :disabled="loading || nuevo.length !== 10" @click="solicitar()">
                                <span x-text="loading ? 'Enviando…' : 'Enviar código SMS'"></span>
                            </button>
                            <p x-show="info" x-text="info" style="font-size:12px;color:var(--sub);margin-top:6px;"></p>
                        </div>
                    </template>

                    <template x-if="step === 'code'">
                        <div>
                            <p style="font-size:12px;color:var(--sub);margin:0 0 10px;" x-text="info"></p>
                            <form action="{{ route('configuracion.telefono.confirmar') }}" method="POST">
                                @csrf
                                <input type="hidden" name="telefono" :value="nuevo">
                                <div class="cf-field">
                                    <label class="cf-lbl" for="cf-codigo">Código recibido por SMS</label>
                                    <input type="text" id="cf-codigo" name="codigo" required maxlength="6" inputmode="numeric" placeholder="123456" class="cf-inp">
                                </div>
                                <button type="submit" class="cf-btn gn">Confirmar número</button>
                                <button type="button" class="cf-btn danger" style="margin-left:6px" @click="step='idle'">Cancelar</button>
                            </form>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- CAMBIAR CONTRASEÑA (todos los roles) --}}
        <div class="cf-card">
            <div class="cf-card-bar"></div>
            <div class="cf-card-body">
                <div class="cf-card-head">
                    <span class="ico">🔒</span>
                    <div>
                        <h2>Cambiar Contraseña</h2>
                        <p>Usa una contraseña que no compartas con otras cuentas.</p>
                    </div>
                </div>

                <form action="{{ route('configuracion.password') }}" method="POST">
                    @csrf
                    <div class="cf-field">
                        <label class="cf-lbl" for="current_password">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="cf-inp">
                    </div>
                    <div class="cf-field">
                        <label class="cf-lbl" for="password">Nueva contraseña</label>
                        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" class="cf-inp">
                    </div>
                    <div class="cf-field">
                        <label class="cf-lbl" for="password_confirmation">Confirmar nueva contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password" class="cf-inp">
                    </div>
                    <button type="submit" class="cf-btn or">Guardar Contraseña</button>
                </form>
            </div>
        </div>

        {{-- PREFERENCIAS DE CORREO (solo clientes) --}}
        @if($user->role === 'cliente')
        <div class="cf-card">
            <div class="cf-card-bar"></div>
            <div class="cf-card-body">
                <div class="cf-card-head">
                    <span class="ico">✉️</span>
                    <div>
                        <h2>Preferencias de Correo</h2>
                        <p>Elige si quieres enterarte de nuestras promociones.</p>
                    </div>
                </div>

                <div class="cf-toggle-row">
                    <div>
                        <p>Promociones y ofertas</p>
                        <span class="desc">Te avisamos por correo cuando haya una promoción nueva o cambie una existente.</span>
                    </div>
                    <form action="{{ route('configuracion.promo') }}" method="POST">
                        @csrf
                        @if($subscribedToPromos)
                            <button type="submit" class="cf-btn danger">Desactivar</button>
                        @else
                            <button type="submit" class="cf-btn gn">Activar</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- ELIMINAR CUENTA — Cancelación ARCO (solo clientes) --}}
        @if($user->role === 'cliente')
        <div class="cf-card" style="border-color:rgba(239,68,68,.3);">
            <div class="cf-card-bar" style="background:#EF4444;"></div>
            <div class="cf-card-body">
                <div class="cf-card-head">
                    <span class="ico">⚠️</span>
                    <div>
                        <h2>Eliminar mi Cuenta</h2>
                        <p>Borra tus datos personales de forma permanente. Esta acción no se puede deshacer.</p>
                    </div>
                </div>

                <p style="font-size:12px;color:var(--sub);margin:0 0 14px;line-height:1.5;">
                    Se eliminan tu nombre, correo, teléfono y pregunta secreta. Tus pedidos se conservan
                    sin datos que te identifiquen (obligación fiscal de 5 años). No podrás volver a
                    iniciar sesión con esta cuenta.
                </p>

                <form action="{{ route('configuracion.eliminar') }}" method="POST"
                      onsubmit="return confirm('¿Seguro que quieres eliminar tu cuenta? Esto no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <div class="cf-field">
                        <label class="cf-lbl" for="cf-del-password">Confirma tu contraseña</label>
                        <input type="password" id="cf-del-password" name="password" required autocomplete="current-password" class="cf-inp">
                    </div>
                    <div class="cf-field">
                        <label class="cf-lbl" for="cf-del-confirm">Escribe ELIMINAR para confirmar</label>
                        <input type="text" id="cf-del-confirm" name="confirmacion" required placeholder="ELIMINAR" class="cf-inp">
                    </div>
                    <button type="submit" class="cf-btn danger">Eliminar mi cuenta permanentemente</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
