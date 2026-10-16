@extends('layouts.app')
@section('content')

<style>
/* ══════════════════════════════════════════════
   VARIABLES — sistema público La 501
══════════════════════════════════════════════ */
.login-page {
    --or:     #F97316;
    --or2:    #EA580C;
    --gn:     #16A34A;
    --gn2:    #15803D;
    --bg:     #F5F3EF;
    --card:   #FFFFFF;
    --inp:    #F4F4F5;
    --txt:    #18181B;
    --sub:    #71717A;
    --bdr:    #E4E4E7;
    --shadow: 0 20px 60px rgba(0,0,0,.10);
}
.dark .login-page {
    --bg:     #0f0d0a;
    --card:   #1a1612;
    --inp:    #111111;
    --txt:    #FAFAFA;
    --sub:    #71717A;
    --bdr:    rgba(255,255,255,.07);
    --shadow: 0 20px 60px rgba(0,0,0,.45);
}

/* ── FONDO ────────────────────────────────── */
.login-page {
    min-height: 100vh;
    background: var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    position: relative;
    overflow: hidden;
}

/* Patrón hexagonal de fondo (igual que topbar del sitio) */
.login-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52' viewBox='0 0 60 52'%3E%3Cpolygon points='30,2 58,17 58,47 30,62 2,47 2,17' fill='none' stroke='%23F97316' stroke-width='.35' stroke-opacity='.13'/%3E%3C/svg%3E");
    background-size: 60px 52px;
    pointer-events: none;
    z-index: 0;
}

/* Glow radial naranja sutil */
.login-page::after {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(249,115,22,.07) 0%, transparent 70%);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 0;
}

/* ── CARD ─────────────────────────────────── */
.login-card {
    position: relative; z-index: 1;
    width: 100%; max-width: 420px;
    background: var(--card);
    border: 1px solid var(--bdr);
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

/* franja superior naranja */
.login-card-top {
    height: 4px;
    background: linear-gradient(90deg, var(--or), var(--gn));
}

.login-card-body { padding: 32px 32px 28px; }

/* ── LOGO / HEADER ────────────────────────── */
.login-logo {
    display: flex; flex-direction: column;
    align-items: center; gap: 10px;
    margin-bottom: 28px;
}
.login-logo-ico {
    width: 52px; height: 52px; border-radius: 12px;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 16px rgba(249,115,22,.35);
}
.login-logo-ico svg { width: 26px; height: 26px; color: #fff; }
.login-logo h1 {
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    font-size: 28px; letter-spacing: 1px;
    color: var(--txt); margin: 0;
    line-height: 1;
}
.login-logo p {
    font-size: 12px; color: var(--sub);
    margin: 0; font-weight: 500; letter-spacing: .3px;
}

/* ── ERRORES ──────────────────────────────── */
.login-error {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 10px 12px; border-radius: 8px;
    background: rgba(220,38,38,.07); border: 1px solid rgba(220,38,38,.2);
    margin-bottom: 18px;
}
.login-error svg { width: 14px; height: 14px; color: #DC2626; flex-shrink: 0; margin-top: 1px; }
.login-error p { font-size: 12px; color: #DC2626; margin: 0; font-weight: 500; }

/* ── FORM ─────────────────────────────────── */
.login-form { display: flex; flex-direction: column; gap: 14px; }

.login-field { display: flex; flex-direction: column; gap: 5px; }
.login-lbl {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    color: var(--sub);
}

/* input wrapper (para el ojo) */
.login-inp-wrap { position: relative; }

.login-inp {
    width: 100%; padding: 10px 12px;
    background: var(--inp); border: 1.5px solid var(--bdr);
    border-radius: 8px; font-size: 13px; color: var(--txt);
    outline: none; transition: border-color .15s, box-shadow .15s;
    font-family: inherit;
}
.login-inp:focus {
    border-color: var(--or);
    box-shadow: 0 0 0 3px rgba(249,115,22,.12);
}
.login-inp.has-icon { padding-right: 40px; }
.login-inp.is-error { border-color: #DC2626; }
.login-inp.is-error:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.1); }

/* botón ojo */
.login-eye {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    width: 28px; height: 28px; border-radius: 6px;
    background: none; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--sub); transition: color .15s, background .15s;
    padding: 0;
}
.login-eye:hover { color: var(--txt); background: var(--bdr); }
.login-eye svg { width: 15px; height: 15px; pointer-events: none; }

/* ── REMEMBER ─────────────────────────────── */
.login-remember {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; color: var(--sub); cursor: pointer;
}
.login-remember input[type="checkbox"] { display: none; }
.login-check-box {
    width: 16px; height: 16px; border-radius: 4px;
    border: 1.5px solid var(--bdr); background: var(--inp);
    display: flex; align-items: center; justify-content: center;
    transition: border-color .15s, background .15s; flex-shrink: 0;
}
.login-remember input:checked ~ .login-check-box {
    background: var(--or); border-color: var(--or);
}
.login-check-box svg { width: 10px; height: 10px; color: #fff; display: none; }
.login-remember input:checked ~ .login-check-box svg { display: block; }

/* ── BOTÓN SUBMIT ─────────────────────────── */
.login-submit {
    width: 100%; padding: 11px;
    background: var(--or); color: #fff;
    border: none; border-radius: 8px;
    font-size: 14px; font-weight: 700;
    letter-spacing: .3px; cursor: pointer;
    transition: background .15s, transform .1s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    box-shadow: 0 2px 8px rgba(249,115,22,.3);
    margin-top: 2px;
}
.login-submit:hover  { background: var(--or2); }
.login-submit:active { transform: scale(.98); }
.login-submit svg { width: 15px; height: 15px; }

/* ── DIVIDER ──────────────────────────────── */
.login-divider {
    display: flex; align-items: center; gap: 10px;
    margin: 4px 0;
}
.login-divider::before, .login-divider::after {
    content: ''; flex: 1; height: 1px; background: var(--bdr);
}
.login-divider span { font-size: 11px; color: var(--sub); font-weight: 500; }

/* ── LINKS ────────────────────────────────── */
.login-links {
    display: flex; flex-direction: column;
    align-items: center; gap: 8px;
    margin-top: 2px;
}
.login-link {
    font-size: 12px; color: var(--sub); text-decoration: none;
    transition: color .15s; font-weight: 500;
}
.login-link:hover { color: var(--or); }
.login-link .accent { color: var(--or); font-weight: 700; }

/* ── FOOTER CARD ──────────────────────────── */
.login-card-footer {
    padding: 12px 32px;
    border-top: 1px solid var(--bdr);
    background: var(--inp);
    text-align: center;
}
.login-card-footer p {
    font-size: 11px; color: var(--sub); margin: 0;
}

/* password strength indicator */
.pwd-strength { margin-top: 5px; display: flex; gap: 4px; }
.pwd-bar { flex: 1; height: 3px; border-radius: 2px; background: var(--bdr); transition: background .3s; }
.pwd-bar.weak   { background: #DC2626; }
.pwd-bar.medium { background: var(--am, #D97706); }
.pwd-bar.strong { background: var(--gn); }

@media(max-width: 480px) {
    .login-card-body { padding: 24px 20px 20px; }
}
</style>

<div class="login-page" x-data="loginPage()">

    <div class="login-card">
        <div class="login-card-top"></div>

        <div class="login-card-body">

            {{-- Logo / Encabezado --}}
            <div class="login-logo">
                <div class="login-logo-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <h1>La 501 Centro</h1>
                <p>Sports Restaurant</p>
            </div>

            {{-- Errores de validación --}}
            @if ($errors->any())
            <div class="login-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p>{{ $errors->first() }}</p>
            </div>
            @endif

            {{-- Session error --}}
            @if (session('error'))
            <div class="login-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p>{{ session('error') }}</p>
            </div>
            @endif

            {{-- FORM --}}
            <form method="POST" action="{{ route('login') }}" class="login-form" @submit="submitting=true">
                @csrf

                {{-- Email --}}
                <div class="login-field">
                    <label class="login-lbl" for="email">Correo electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="login-inp {{ $errors->has('email') ? 'is-error' : '' }}"
                        placeholder="tu@correo.com"
                        required
                        autocomplete="email"
                        autofocus>
                </div>

                {{-- Contraseña + ojo --}}
                <div class="login-field">
                    <label class="login-lbl" for="password">Contraseña</label>
                    <div class="login-inp-wrap">
                        <input
                        :type="showPwd ? 'text' : 'password'"
                        id="password"
                        name="password"
                        class="login-inp has-icon {{ $errors->has('password') ? 'is-error' : '' }}"
                        placeholder="••••••••"
                        required
                        :autocomplete="showPwd ? 'off' : 'current-password'"
                        x-model="pwd"
                        @input="typing=true">

                        {{-- Botón ojo --}}
                        <button type="button" class="login-eye"
                                @click="showPwd=!showPwd"
                                :title="showPwd ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                            {{-- Ojo abierto --}}
                            <svg x-show="!showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{-- Ojo tachado --}}
                            <svg x-show="showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Recuérdame --}}
                <label class="login-remember">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span class="login-check-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    Mantener sesión iniciada
                </label>

                {{-- Submit --}}
                <button type="submit" class="login-submit" :disabled="submitting">
                    <template x-if="!submitting">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </template>
                    <template x-if="submitting">
                        <svg class="animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </template>
                    <span x-text="submitting ? 'Entrando...' : 'Entrar'"></span>
                </button>

            </form>

            {{-- Links --}}
            <div style="margin-top:20px">
                <div class="login-divider"><span>o</span></div>
                <div class="login-links" style="margin-top:14px">
                    <a href="{{ route('password.request') }}" class="login-link">
                        ¿Olvidaste tu contraseña?
                    </a>
                    <a href="{{ route('register') }}" class="login-link">
                        ¿No tienes cuenta? <span class="accent">Regístrate</span>
                    </a>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="login-card-footer">
            <p>La 501 Sports Restaurant &copy; {{ date('Y') }} — Acceso seguro</p>
        </div>
    </div>

</div>

<script>
function loginPage() {
    return {
        showPwd:    false,
        pwd:        '',
        typing:     false,
        submitting: false,
    }
}
</script>

@endsection
