@extends('layouts.app')
@section('content')

<style>
.auth-page{--or:#F97316;--or2:#EA580C;--gn:#16A34A;--gn2:#15803D;--rd:#DC2626;--bg:#F5F3EF;--card:#FFFFFF;--inp:#F4F4F5;--txt:#18181B;--sub:#71717A;--bdr:#E4E4E7;}
.dark .auth-page{--bg:#0f0d0a;--card:#1a1612;--inp:#111111;--txt:#FAFAFA;--bdr:rgba(255,255,255,.07);}
.auth-page{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:40px 16px;position:relative;overflow:hidden;}
.auth-page::before{content:'';position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52' viewBox='0 0 60 52'%3E%3Cpolygon points='30,2 58,17 58,47 30,62 2,47 2,17' fill='none' stroke='%23F97316' stroke-width='.35' stroke-opacity='.13'/%3E%3C/svg%3E");background-size:60px 52px;pointer-events:none;z-index:0;}
.auth-page::after{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.07) 0%,transparent 70%);top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;z-index:0;}
.auth-card{position:relative;z-index:1;width:100%;max-width:420px;background:var(--card);border:1px solid var(--bdr);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.10);overflow:hidden;}
.dark .auth-card{box-shadow:0 20px 60px rgba(0,0,0,.45);}
.auth-top{height:4px;background:linear-gradient(90deg,var(--or),var(--gn));}
.auth-body{padding:32px 32px 28px;}
.auth-logo{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:24px;}
.auth-logo-ico{width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,var(--or),var(--or2));display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(249,115,22,.35);}
.auth-logo-ico svg{width:26px;height:26px;color:#fff;}
.auth-logo h1{font-family:'Bebas Neue','Oswald',sans-serif;font-size:28px;letter-spacing:1px;color:var(--txt);margin:0;line-height:1;}
.auth-logo p{font-size:12px;color:var(--sub);margin:0;font-weight:500;}
.auth-alert.err{display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border-radius:8px;margin-bottom:18px;background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.2);}
.auth-alert.err svg{width:14px;height:14px;flex-shrink:0;margin-top:1px;color:#DC2626;}
.auth-alert.err p{font-size:12px;font-weight:500;color:#DC2626;margin:0;}
.auth-lbl{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:5px;}
.auth-inp-wrap{position:relative;}
.auth-inp{width:100%;padding:10px 12px;background:var(--inp);border:1.5px solid var(--bdr);border-radius:8px;font-size:13px;color:var(--txt);outline:none;transition:border-color .15s,box-shadow .15s;font-family:inherit;}
.auth-inp:focus{border-color:var(--or);box-shadow:0 0 0 3px rgba(249,115,22,.12);}
.auth-inp.pr{padding-right:40px;}
.auth-inp.ro{color:var(--sub);cursor:not-allowed;opacity:.7;}
.auth-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:28px;height:28px;border-radius:6px;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sub);transition:color .15s,background .15s;padding:0;}
.auth-eye:hover{color:var(--txt);background:var(--bdr);}
.auth-eye svg{width:15px;height:15px;pointer-events:none;}
.auth-btn{width:100%;padding:11px;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s,transform .1s;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;}
.auth-btn:active{transform:scale(.98);}
.auth-btn:disabled{background:var(--bdr) !important;color:var(--sub) !important;box-shadow:none !important;cursor:not-allowed;transform:none;}
.auth-btn svg{width:15px;height:15px;}
.auth-foot{padding:12px 32px;border-top:1px solid var(--bdr);background:var(--inp);text-align:center;}
.auth-foot p{font-size:11px;color:var(--sub);margin:0;}

/* Barra de fortaleza */
.pwd-bar-wrap{height:5px;background:var(--bdr);border-radius:3px;margin-top:10px;overflow:hidden;}
.pwd-bar-fill{height:100%;border-radius:3px;transition:width .3s,background .3s;}

/* Requisitos */
.pwd-req{display:flex;flex-direction:column;gap:5px;margin-top:10px;}
.pwd-req-item{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:500;color:var(--sub);transition:color .2s;}
.pwd-req-item.ok{color:var(--gn);}
.pwd-req-ico{width:14px;height:14px;border-radius:50%;border:1.5px solid var(--bdr);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:border-color .2s,background .2s;}
.pwd-req-item.ok .pwd-req-ico{background:var(--gn);border-color:var(--gn);}
.pwd-req-ico svg{width:8px;height:8px;color:#fff;display:none;}
.pwd-req-item.ok .pwd-req-ico svg{display:block;}

/* Match confirm */
.pwd-match{font-size:11px;font-weight:600;margin-top:5px;display:flex;align-items:center;gap:5px;}
.pwd-match.ok{color:var(--gn);}
.pwd-match.no{color:var(--rd);}
.pwd-match svg{width:12px;height:12px;}

@media(max-width:480px){.auth-body{padding:24px 20px 20px;}}
</style>

<div class="auth-page" x-data="resetPwd()">
    <div class="auth-card">
        <div class="auth-top"></div>
        <div class="auth-body">

            <div class="auth-logo">
                <div class="auth-logo-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1>La 501 centro</h1>
                <p>Crear nueva contraseña</p>
            </div>

            <p style="font-size:17px;font-weight:700;color:var(--txt);margin:0 0 4px;">Nueva contraseña</p>
            <p style="font-size:13px;color:var(--sub);margin:0 0 22px;line-height:1.5;">
                Crea una contraseña segura para tu cuenta.
            </p>

            @if($errors->any())
            <div class="auth-alert err">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p>{{ $errors->first() }}</p>
            </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}"
                  style="display:flex;flex-direction:column;gap:16px;">
                @csrf

                {{-- Email readonly --}}
                <div>
                    <label for="email_input" class="auth-lbl">Correo electrónico</label>
                    <input id="email_input" type="email" name="email"
                           value="{{ $email ?? old('email') }}"
                           class="auth-inp ro" readonly autocomplete="username">
                </div>

                {{-- Nueva contraseña --}}
                <div>
                    <label for="password_input" class="auth-lbl">Nueva contraseña</label>
                    <div class="auth-inp-wrap">
                        <input id="password_input" type="password" :type="showPwd ? 'text' : 'password'" name="password"
                            class="auth-inp pr" placeholder="Mínimo 8 caracteres"
                            x-model="pwd" required autocomplete="new-password">
                        <button type="button" class="auth-eye" @click="showPwd=!showPwd">
                            <svg x-show="!showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>

                    {{-- Barra de fortaleza --}}
                    <div class="pwd-bar-wrap" x-show="pwd.length > 0">
                        <div class="pwd-bar-fill"
                             :style="'width:' + (score/4*100) + '%;background:' + barColor()"></div>
                    </div>

                    {{-- Requisitos --}}
                    <div class="pwd-req">
                        <div class="pwd-req-item" :class="r.length ? 'ok' : ''">
                            <span class="pwd-req-ico"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                            Mínimo 8 caracteres
                        </div>
                        <div class="pwd-req-item" :class="r.upper ? 'ok' : ''">
                            <span class="pwd-req-ico"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                            Al menos una mayúscula
                        </div>
                        <div class="pwd-req-item" :class="r.number ? 'ok' : ''">
                            <span class="pwd-req-ico"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                            Al menos un número
                        </div>
                        <div class="pwd-req-item" :class="r.special ? 'ok' : ''">
                            <span class="pwd-req-ico"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                            Un carácter especial (!@#$)
                        </div>
                    </div>
                </div>

                {{-- Confirmar --}}
                <div>
                    <label for="password_confirmation_input" class="auth-lbl">Confirmar contraseña</label>
                    <div class="auth-inp-wrap">
                        <input id="password_confirmation_input" type="password" :type="showPwd ? 'text' : 'password'" name="password_confirmation"
                            class="auth-inp pr" placeholder="Repite tu contraseña"
                            x-model="conf" required autocomplete="new-password">
                        <button type="button" class="auth-eye" @click="showPwd=!showPwd">
                            <svg x-show="!showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    {{-- Match indicator --}}
                    <div x-show="conf.length > 0"
                         class="pwd-match" :class="conf === pwd ? 'ok' : 'no'">
                        <template x-if="conf === pwd">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="conf !== pwd">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </template>
                        <span x-text="conf === pwd ? 'Las contraseñas coinciden' : 'No coinciden'"></span>
                    </div>
                </div>

                <button type="submit" class="auth-btn"
                        :disabled="score < 4 || conf !== pwd"
                        :style="score < 4 || conf !== pwd ? '' : 'background:var(--gn);box-shadow:0 2px 8px rgba(22,163,74,.3);'">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Guardar nueva contraseña
                </button>
            </form>

        </div>
        <div class="auth-foot"><p>La 501 centro Sports Restaurant &copy; {{ date('Y') }} — Acceso seguro</p></div>
    </div>
</div>

<script>
function resetPwd() {
    return {
        pwd: '', conf: '', showPwd: false,
        get r() {
            return {
                length:  this.pwd.length >= 8,
                upper:   /[A-Z]/.test(this.pwd),
                number:  /[0-9]/.test(this.pwd),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(this.pwd),
            };
        },
        get score() { return Object.values(this.r).filter(Boolean).length; },
        barColor() {
            if(this.score <= 1) return '#DC2626';
            if(this.score <= 2) return '#F97316';
            if(this.score <= 3) return '#D97706';
            return '#16A34A';
        },
    }
}
</script>

@endsection
