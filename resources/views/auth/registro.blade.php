@extends('layouts.app')
@section('content')

<style>
.auth-page{--or:#F97316;--or2:#EA580C;--gn:#16A34A;--gn2:#15803D;--rd:#DC2626;--bg:#F5F3EF;--card:#FFFFFF;--inp:#F4F4F5;--txt:#18181B;--sub:#71717A;--bdr:#E4E4E7;}
.dark .auth-page{--bg:#0f0d0a;--card:#1a1612;--inp:#111111;--txt:#FAFAFA;--bdr:rgba(255,255,255,.07);}
.auth-page{min-height:100vh;background:var(--bg);display:flex;align-items:flex-start;justify-content:center;padding:40px 16px 60px;position:relative;overflow:hidden;}
.auth-page::before{content:'';position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52' viewBox='0 0 60 52'%3E%3Cpolygon points='30,2 58,17 58,47 30,62 2,47 2,17' fill='none' stroke='%23F97316' stroke-width='.35' stroke-opacity='.13'/%3E%3C/svg%3E");background-size:60px 52px;pointer-events:none;z-index:0;}
.auth-page::after{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.07) 0%,transparent 70%);top:30%;left:50%;transform:translate(-50%,-50%);pointer-events:none;z-index:0;}
.auth-card{position:relative;z-index:1;width:100%;max-width:460px;background:var(--card);border:1px solid var(--bdr);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.10);overflow:hidden;margin-top:10px;}
.dark .auth-card{box-shadow:0 20px 60px rgba(0,0,0,.45);}
.auth-top{height:4px;background:linear-gradient(90deg,var(--or),var(--gn));}
.auth-body{padding:32px 32px 28px;}
.auth-logo{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:24px;}
.auth-logo-ico{width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,var(--or),var(--or2));display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(249,115,22,.35);}
.auth-logo-ico svg{width:26px;height:26px;color:#fff;}
.auth-logo h1{font-family:'Bebas Neue','Oswald',sans-serif;font-size:28px;letter-spacing:1px;color:var(--txt);margin:0;line-height:1;}
.auth-logo p{font-size:12px;color:var(--sub);margin:0;font-weight:500;}
.auth-errors{padding:10px 12px;border-radius:8px;margin-bottom:18px;background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.2);}
.auth-errors li{font-size:12px;font-weight:500;color:#DC2626;list-style:none;padding:2px 0;}
.auth-errors li::before{content:'• ';font-weight:700;}
.auth-sec{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--sub);padding-bottom:8px;border-bottom:1px solid var(--bdr);margin-bottom:14px;}
.auth-g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.auth-lbl{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:5px;}
.auth-lbl .req{color:var(--or);}
.auth-inp-wrap{position:relative;}
.auth-inp,.auth-sel{width:100%;padding:10px 12px;background:var(--inp);border:1.5px solid var(--bdr);border-radius:8px;font-size:13px;color:var(--txt);outline:none;transition:border-color .15s,box-shadow .15s;font-family:inherit;}
.auth-inp:focus,.auth-sel:focus{border-color:var(--or);box-shadow:0 0 0 3px rgba(249,115,22,.12);}
.auth-inp.pr{padding-right:40px;}
.auth-sel{cursor:pointer;}
.auth-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:28px;height:28px;border-radius:6px;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sub);transition:color .15s,background .15s;padding:0;}
.auth-eye:hover{color:var(--txt);background:var(--bdr);}
.auth-eye svg{width:15px;height:15px;pointer-events:none;}

/* Fortaleza */
.pwd-bar-wrap{height:4px;background:var(--bdr);border-radius:2px;margin-top:8px;overflow:hidden;}
.pwd-bar-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;}
.pwd-req{display:grid;grid-template-columns:1fr 1fr;gap:4px 12px;margin-top:8px;}
.pwd-req-item{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:500;color:var(--sub);transition:color .2s;}
.pwd-req-item.ok{color:var(--gn);}
.pwd-req-ico{width:12px;height:12px;border-radius:50%;border:1.5px solid var(--bdr);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:border-color .2s,background .2s;}
.pwd-req-item.ok .pwd-req-ico{background:var(--gn);border-color:var(--gn);}
.pwd-req-ico svg{width:7px;height:7px;color:#fff;display:none;}
.pwd-req-item.ok .pwd-req-ico svg{display:block;}
.pwd-match{font-size:11px;font-weight:600;margin-top:5px;display:flex;align-items:center;gap:5px;}
.pwd-match.ok{color:var(--gn);}
.pwd-match.no{color:var(--rd);}
.pwd-match svg{width:11px;height:11px;}

/* Checkbox términos */
.terms-row{display:flex;align-items:flex-start;gap:10px;cursor:pointer;}
.terms-row input{display:none;}
.terms-chk{width:16px;height:16px;border-radius:4px;border:1.5px solid var(--bdr);background:var(--inp);flex-shrink:0;margin-top:2px;display:flex;align-items:center;justify-content:center;transition:border-color .15s,background .15s;}
.terms-row input:checked ~ .terms-chk{background:var(--or);border-color:var(--or);}
.terms-chk svg{width:9px;height:9px;color:#fff;display:none;}
.terms-row input:checked ~ .terms-chk svg{display:block;}
.terms-txt{font-size:12px;color:var(--sub);line-height:1.5;}
.terms-txt a{color:var(--or);text-decoration:none;font-weight:600;}
.terms-txt a:hover{text-decoration:underline;}

/* Submit */
.auth-btn{width:100%;padding:11px;background:var(--or);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s,transform .1s;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(249,115,22,.3);font-family:inherit;}
.auth-btn:hover{background:var(--or2);}
.auth-btn:active{transform:scale(.98);}
.auth-btn svg{width:15px;height:15px;}
.auth-divider{display:flex;align-items:center;gap:10px;margin:4px 0;}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:var(--bdr);}
.auth-divider span{font-size:11px;color:var(--sub);}
.auth-link{font-size:12px;color:var(--sub);text-decoration:none;transition:color .15s;font-weight:500;text-align:center;display:block;}
.auth-link:hover{color:var(--or);}
.auth-link .ac{color:var(--or);font-weight:700;}
.auth-foot{padding:12px 32px;border-top:1px solid var(--bdr);background:var(--inp);text-align:center;}
.auth-foot p{font-size:11px;color:var(--sub);margin:0;}
@media(max-width:480px){.auth-body{padding:24px 20px 20px;}.auth-g2{grid-template-columns:1fr;}.pwd-req{grid-template-columns:1fr;}}
</style>

<div class="auth-page" x-data="registerPage()">
    <div class="auth-card">
        <div class="auth-top"></div>
        <div class="auth-body">

            <div class="auth-logo">
                <div class="auth-logo-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h1>La 501 centro</h1>
                <p>Crea tu cuenta</p>
            </div>

            @if($errors->any())
            <ul class="auth-errors" role="alert">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            @endif

            <form method="POST" action="{{ route('register') }}"
                  style="display:flex;flex-direction:column;gap:16px;">
                @csrf

                {{-- Datos personales --}}
                <div>
                    <p class="auth-sec">Datos personales</p>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div>
                            {{-- FIX Web:S6853 L110: label for="reg-name" --}}
                            <label class="auth-lbl" for="reg-name">Nombre completo <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="reg-name" name="name" class="auth-inp"
                                   placeholder="Tu nombre y apellidos"
                                   value="{{ old('name') }}" required autocomplete="name">
                        </div>
                        <div class="auth-g2">
                            <div>
                                {{-- FIX Web:S6853 L117: label for="reg-email" --}}
                                <label class="auth-lbl" for="reg-email">Correo electrónico <span class="req" aria-hidden="true">*</span></label>
                                <input type="email" id="reg-email" name="email" class="auth-inp"
                                       placeholder="tu@correo.com"
                                       value="{{ old('email') }}" required autocomplete="email">
                            </div>
                            <div>
                                {{-- FIX Web:S6853 L123: label for="reg-telefono" --}}
                                <label class="auth-lbl" for="reg-telefono">Teléfono <span class="req" aria-hidden="true">*</span></label>
                                <input type="tel" id="reg-telefono" name="telefono" class="auth-inp"
                                       placeholder="614 000 0000"
                                       value="{{ old('telefono') }}" required autocomplete="tel">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pregunta de seguridad --}}
                <div>
                    <p class="auth-sec">Pregunta de seguridad</p>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <div>
                            {{-- FIX Web:S6853 L137: label for="reg-pregunta" --}}
                            <label class="auth-lbl" for="reg-pregunta">Pregunta <span class="req" aria-hidden="true">*</span></label>
                            <select id="reg-pregunta" name="pregunta_secreta" class="auth-sel" required autocomplete="off">
                                <option value="" disabled {{ old('pregunta_secreta') ? '' : 'selected' }}>Selecciona una pregunta</option>
                                @foreach([
                                    '¿Cuál es el nombre de tu primera mascota?',
                                    '¿Cuál es tu platillo favorito de La 501?',
                                    '¿En qué ciudad se conocieron tus padres?',
                                ] as $q)
                                    <option {{ old('pregunta_secreta') === $q ? 'selected' : '' }}>{{ $q }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            {{-- FIX Web:S6853 L150: label for="reg-respuesta" --}}
                            <label class="auth-lbl" for="reg-respuesta">Tu respuesta <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="reg-respuesta" name="respuesta_secreta" class="auth-inp"
                                   placeholder="Respuesta secreta"
                                   value="{{ old('respuesta_secreta') }}" required autocomplete="off">
                        </div>
                    </div>
                </div>

                {{-- Contraseña --}}
                <div>
                    <p class="auth-sec">Contraseña</p>
                    <div style="display:flex;flex-direction:column;gap:12px;">

                        <div>
                            {{-- FIX Web:S6853 L164: label for="reg-password" --}}
                            <label class="auth-lbl" for="reg-password">Nueva contraseña <span class="req" aria-hidden="true">*</span></label>
                            <div class="auth-inp-wrap">
                                {{-- FIX Web:S6840 L166: autocomplete="new-password" es correcto para campos de nueva contraseña --}}
                                <input :type="showPwd ? 'text' : 'password'" id="reg-password" name="password"
                                       class="auth-inp pr" placeholder="Mínimo 8 caracteres"
                                       x-model="pwd" required
                                       :autocomplete="showPwd ? 'off' : 'new-password'"
                                       aria-describedby="pwd-requirements">
                                <button type="button" class="auth-eye" @click="showPwd=!showPwd"
                                        :aria-label="showPwd ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                    <svg x-show="!showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>

                            <div class="pwd-bar-wrap" x-show="pwd.length > 0" aria-hidden="true">
                                <div class="pwd-bar-fill" :style="'width:'+(score/4*100)+'%;background:'+barColor()"></div>
                            </div>
                            <div class="pwd-req" id="pwd-requirements" aria-live="polite">
                                <div class="pwd-req-item" :class="r.length?'ok':''">
                                    <span class="pwd-req-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                                    8 caracteres
                                </div>
                                <div class="pwd-req-item" :class="r.upper?'ok':''">
                                    <span class="pwd-req-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                                    Mayúscula
                                </div>
                                <div class="pwd-req-item" :class="r.number?'ok':''">
                                    <span class="pwd-req-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                                    Número
                                </div>
                                <div class="pwd-req-item" :class="r.special?'ok':''">
                                    <span class="pwd-req-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                                    Especial (!@#$)
                                </div>
                            </div>
                        </div>

                        <div>
                            {{-- FIX Web:S6853 L199: label for="reg-password-confirm" --}}
                            <label class="auth-lbl" for="reg-password-confirm">Confirmar contraseña <span class="req" aria-hidden="true">*</span></label>
                            <div class="auth-inp-wrap">
                                {{-- FIX Web:S6840 L201: autocomplete="new-password" correcto para confirmación --}}
                                <input :type="showPwd ? 'text' : 'password'" id="reg-password-confirm" name="password_confirmation"
                                       class="auth-inp pr" placeholder="Repite tu contraseña"
                                       x-model="conf" required
                                       :autocomplete="showPwd ? 'off' : 'new-password'">
                                <button type="button" class="auth-eye" @click="showPwd=!showPwd"
                                        :aria-label="showPwd ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                    <svg x-show="!showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <div x-show="conf.length > 0" class="pwd-match" :class="conf===pwd?'ok':'no'" aria-live="polite">
                                <template x-if="conf===pwd"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></template>
                                <template x-if="conf!==pwd"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></template>
                                <span x-text="conf===pwd?'Las contraseñas coinciden':'No coinciden'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Términos --}}
                <label class="terms-row" for="reg-terms">
                    <input type="checkbox" id="reg-terms" name="terms" required>
                    <span class="terms-chk" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span class="terms-txt">
                        Acepto los <a href="#">términos y condiciones</a> y la <a href="#">política de privacidad</a> de La 501 centro Sports Restaurant.
                    </span>
                </label>

                <button type="submit" class="auth-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Crear mi cuenta
                </button>

                <div class="auth-divider"><span>o</span></div>
                <a href="{{ route('login') }}" class="auth-link">
                    ¿Ya tienes cuenta? <span class="ac">Inicia sesión</span>
                </a>
            </form>

        </div>
        <div class="auth-foot"><p>La 501 centro Sports Restaurant &copy; {{ date('Y') }} — Registro seguro</p></div>
    </div>
</div>

<script>
function registerPage() {
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
