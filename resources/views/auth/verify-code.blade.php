@extends('layouts.app')
@section('content')

<style>
.auth-page{--or:#F97316;--or2:#EA580C;--gn:#16A34A;--rd:#DC2626;--bg:#F5F3EF;--card:#FFFFFF;--inp:#F4F4F5;--txt:#18181B;--sub:#71717A;--bdr:#E4E4E7;}
.dark .auth-page{--bg:#0f0d0a;--card:#1a1612;--inp:#111111;--txt:#FAFAFA;--bdr:rgba(255,255,255,.07);}
.auth-page{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:40px 16px;position:relative;overflow:hidden;}
.auth-page::before{content:'';position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52' viewBox='0 0 60 52'%3E%3Cpolygon points='30,2 58,17 58,47 30,62 2,47 2,17' fill='none' stroke='%23F97316' stroke-width='.35' stroke-opacity='.13'/%3E%3C/svg%3E");background-size:60px 52px;pointer-events:none;z-index:0;}
.auth-page::after{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.07) 0%,transparent 70%);top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;z-index:0;}
.auth-card{position:relative;z-index:1;width:100%;max-width:420px;background:var(--card);border:1px solid var(--bdr);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.10);overflow:hidden;}
.dark .auth-card{box-shadow:0 20px 60px rgba(0,0,0,.45);}
.auth-top{height:4px;background:linear-gradient(90deg,var(--or),var(--gn));}
.auth-body{padding:32px 32px 28px;}
.auth-logo{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:20px;}
.auth-logo-ico{width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,var(--or),var(--or2));display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(249,115,22,.35);}
.auth-logo-ico svg{width:26px;height:26px;color:#fff;}
.auth-logo h1{font-family:'Bebas Neue','Oswald',sans-serif;font-size:28px;letter-spacing:1px;color:var(--txt);margin:0;line-height:1;}
.auth-logo p{font-size:12px;color:var(--sub);margin:0;font-weight:500;}
.auth-alert{display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border-radius:8px;margin-bottom:18px;}
.auth-alert.err{background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.2);}
.auth-alert svg{width:14px;height:14px;flex-shrink:0;margin-top:1px;color:#DC2626;}
.auth-alert p{font-size:12px;font-weight:500;color:#DC2626;margin:0;}
.auth-btn{width:100%;padding:11px;background:var(--or);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s,transform .1s;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(249,115,22,.3);font-family:inherit;}
.auth-btn:hover{background:var(--or2);}
.auth-btn:active{transform:scale(.98);}
.auth-btn:disabled{background:var(--bdr);color:var(--sub);box-shadow:none;cursor:not-allowed;transform:none;}
.auth-btn svg{width:15px;height:15px;}
.auth-link{font-size:12px;color:var(--sub);text-decoration:none;transition:color .15s;font-weight:500;text-align:center;display:block;}
.auth-link:hover{color:var(--or);}
.auth-link .ac{color:var(--or);font-weight:700;}
.auth-foot{padding:12px 32px;border-top:1px solid var(--bdr);background:var(--inp);text-align:center;}
.auth-foot p{font-size:11px;color:var(--sub);margin:0;}

/* ── Input OTP ── */
.code-wrap{position:relative;margin-bottom:6px;}
.code-real{
    position:absolute;left:0;top:0;width:100%;height:100%;
    opacity:0;font-size:16px;cursor:text;z-index:2;
    border:none;outline:none;background:transparent;
    caret-color:transparent;letter-spacing:0;
}
.code-grid{
    display:grid;grid-template-columns:repeat(8,1fr);
    gap:7px;pointer-events:none;position:relative;
}
.code-cell{
    aspect-ratio:1;background:var(--inp);border:1.5px solid var(--bdr);
    border-radius:8px;font-size:22px;font-weight:800;color:var(--txt);
    display:flex;align-items:center;justify-content:center;
    transition:border-color .15s,box-shadow .15s,background .15s,color .15s;
    font-family:'Bebas Neue',monospace;user-select:none;position:relative;
    line-height:1;
}
.code-cell.active{border-color:var(--or);box-shadow:0 0 0 3px rgba(249,115,22,.12);background:rgba(249,115,22,.03);}
.code-cell.active.empty::after{
    content:'';position:absolute;width:2px;height:50%;
    background:var(--or);border-radius:1px;
    animation:blink .85s step-end infinite;
}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0}}
.code-cell.filled{border-color:var(--gn);background:rgba(22,163,74,.05);color:var(--gn);}
.code-grid.shaking .code-cell{border-color:var(--rd);background:rgba(220,38,38,.04);animation:shake .35s ease;}
@keyframes shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-5px)}60%{transform:translateX(5px)}}

.email-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;background:var(--inp);border:1px solid var(--bdr);font-size:12px;font-weight:600;color:var(--sub);margin-bottom:18px;}
.email-badge svg{width:12px;height:12px;color:var(--or);flex-shrink:0;}
.email-badge span{color:var(--txt);}

.code-timer{font-size:11px;color:var(--sub);text-align:center;margin-top:8px;font-weight:500;}
.code-timer .t{font-weight:800;color:var(--or);}
.code-timer .expired{color:var(--rd);font-weight:700;}

@media(max-width:480px){.auth-body{padding:24px 16px 20px;}.code-cell{font-size:16px;border-radius:6px;}.code-grid{gap:4px;}}
</style>

<div class="auth-page" x-data="verifyCode()" x-init="startTimer()">
    <div class="auth-card">
        <div class="auth-top"></div>
        <div class="auth-body">

            <div class="auth-logo">
                <div class="auth-logo-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1>La 501 centro</h1>
                <p>Verificación de seguridad</p>
            </div>

            <div style="text-align:center;">
                <div class="email-badge" style="display:inline-flex;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>{{ $email }}</span>
                </div>
            </div>

            <p style="font-size:13px;color:var(--sub);text-align:center;margin:0 0 20px;line-height:1.5;">
                Ingresa el código de <strong style="color:var(--txt);">8 dígitos</strong> que enviamos a tu correo.
            </p>

            @if($errors->any())
            <div class="auth-alert err" role="alert">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p>{{ $errors->first() }}</p>
            </div>
            @endif

            <form method="POST" action="{{ route('password.verify.code.post') }}"
                  x-ref="form" @submit.prevent="submit()">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="code" :value="raw">

                {{-- FIX L125: label for="otp-input" + id en el input real --}}
                <label for="otp-input"
                       style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:10px;text-align:center;">
                    Código de verificación
                </label>

                <div class="code-wrap" @click="focusInput()">
                    <input
                        type="text"
                        id="otp-input"
                        inputmode="numeric"
                        maxlength="8"
                        class="code-real"
                        x-ref="realInput"
                        x-model="raw"
                        @input="onRawInput()"
                        @keydown="onRawKey($event)"
                        @paste.prevent="onRawPaste($event)"
                        @focus="focused=true"
                        @blur="focused=false"
                        autocomplete="one-time-code"
                        autocorrect="off"
                        spellcheck="false"
                        aria-label="Código de verificación de 8 dígitos">

                    <div class="code-grid" :class="{ shaking: shaking }" aria-hidden="true">
                        <template x-for="i in 8" :key="i">
                            <div class="code-cell"
                                 :class="{
                                     filled:  raw.length >= i,
                                     active:  focused && raw.length === i-1,
                                     empty:   focused && raw.length === i-1
                                 }"
                                 x-text="raw[i-1] || ''">
                            </div>
                        </template>
                    </div>
                </div>

                <output class="code-timer" aria-live="polite">
                    <template x-if="secs > 0">
                        <span>Expira en <span class="t" x-text="fmt()"></span></span>
                    </template>
                    <template x-if="secs <= 0">
                        <span class="expired">⏱ Código expirado</span>
                    </template>
                </output>

                <div style="display:flex;flex-direction:column;gap:10px;margin-top:20px;">
                    <button type="submit" class="auth-btn"
                            :disabled="codeStr.length < 8 || secs <= 0">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Verificar código
                    </button>
                    <a href="{{ route('password.request') }}" class="auth-link">
                        ¿No llegó el código? <span class="ac">Reenviar</span>
                    </a>
                </div>
            </form>

        </div>
        <div class="auth-foot"><p>La 501 centro Sports Restaurant &copy; {{ date('Y') }} — Acceso seguro</p></div>
    </div>
</div>

<script>
function verifyCode() {
    return {
        raw:     '',
        focused: false,
        secs:    300,
        shaking: false,
        _t:      null,

        get codeStr() { return this.raw; },

        startTimer() {
            this._t = setInterval(() => {
                if (this.secs > 0) this.secs--;
                else clearInterval(this._t);
            }, 1000);
        },

        fmt() {
            return Math.floor(this.secs / 60) + ':' + String(this.secs % 60).padStart(2, '0');
        },

        focusInput() {
            this.$refs.realInput.focus();
        },

        onRawInput() {
            this.raw = this.raw.replace(/\D/g, '').slice(0, 8);
        },

        onRawKey(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.submit();
            }
        },

        onRawPaste(e) {
            const text = (e.clipboardData || window.clipboardData)
                .getData('text').replace(/\D/g, '').slice(0, 8);
            this.raw = text;
        },

        submit() {
            if (this.raw.length < 8) {
                this.shaking = true;
                this.$refs.realInput.focus();
                setTimeout(() => { this.shaking = false; }, 400);
                return;
            }
            this.$refs.form.submit();
        },
    }
}
</script>

@endsection
