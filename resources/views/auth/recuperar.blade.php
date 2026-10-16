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
.auth-logo{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:24px;}
.auth-logo-ico{width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,var(--or),var(--or2));display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(249,115,22,.35);}
.auth-logo-ico svg{width:26px;height:26px;color:#fff;}
.auth-logo h1{font-family:'Bebas Neue','Oswald',sans-serif;font-size:28px;letter-spacing:1px;color:var(--txt);margin:0;line-height:1;}
.auth-logo p{font-size:12px;color:var(--sub);margin:0;font-weight:500;}
.auth-alert{display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border-radius:8px;margin-bottom:18px;}
.auth-alert.err{background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.2);}
.auth-alert.ok{background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.2);}
.auth-alert svg{width:14px;height:14px;flex-shrink:0;margin-top:1px;}
.auth-alert.err svg,.auth-alert.err p{color:#DC2626;}
.auth-alert.ok svg,.auth-alert.ok p{color:#16A34A;}
.auth-alert p{font-size:12px;font-weight:500;margin:0;}
.auth-lbl{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:5px;}
.auth-inp{width:100%;padding:10px 12px;background:var(--inp);border:1.5px solid var(--bdr);border-radius:8px;font-size:13px;color:var(--txt);outline:none;transition:border-color .15s,box-shadow .15s;font-family:inherit;}
.auth-inp:focus{border-color:var(--or);box-shadow:0 0 0 3px rgba(249,115,22,.12);}
.auth-btn{width:100%;padding:11px;background:var(--or);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s,transform .1s;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(249,115,22,.3);font-family:inherit;}
.auth-btn:hover{background:var(--or2);}
.auth-btn:active{transform:scale(.98);}
.auth-btn:disabled{background:var(--bdr);color:var(--sub);box-shadow:none;cursor:not-allowed;transform:none;}
.auth-btn svg{width:15px;height:15px;}
.auth-link{font-size:12px;color:var(--sub);text-decoration:none;transition:color .15s;font-weight:500;text-align:center;display:block;}
.auth-link:hover{color:var(--or);}
.auth-foot{padding:12px 32px;border-top:1px solid var(--bdr);background:var(--inp);text-align:center;}
.auth-foot p{font-size:11px;color:var(--sub);margin:0;}
@media(max-width:480px){.auth-body{padding:24px 20px 20px;}}
</style>

<div class="auth-page" x-data="{sending:false}">
    <div class="auth-card">
        <div class="auth-top"></div>
        <div class="auth-body">

            <div class="auth-logo">
                <div class="auth-logo-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <h1>La 501 centro</h1>
                <p>Recuperación de contraseña</p>
            </div>

            <p style="font-size:17px;font-weight:700;color:var(--txt);margin:0 0 4px;">Recuperar acceso</p>
            <p style="font-size:13px;color:var(--sub);margin:0 0 22px;line-height:1.5;">
                Ingresa tu correo y te enviaremos un código de seguridad para restablecer tu contraseña.
            </p>

            @if(session('status'))
            <div class="auth-alert ok" role="alert">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p>{{ session('status') }}</p>
            </div>
            @endif

            @if($errors->any())
            <div class="auth-alert err" role="alert">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p>{{ $errors->first() }}</p>
            </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}"
                  style="display:flex;flex-direction:column;gap:14px;"
                  @submit="sending=true">
                @csrf
                <div>
                    {{-- FIX L81: label for="recover-email" + id en el input --}}
                    <label class="auth-lbl" for="recover-email">Correo electrónico</label>
                    <input type="email" id="recover-email" name="email" class="auth-inp"
                           placeholder="tu@correo.com"
                           value="{{ old('email') }}"
                           required autocomplete="email" autofocus>
                </div>

                <button type="submit" class="auth-btn" :disabled="sending">
                    <template x-if="!sending">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </template>
                    <template x-if="sending">
                        <svg class="animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </template>
                    <span x-text="sending ? 'Enviando...' : 'Enviar código'"></span>
                </button>

                <a href="{{ route('login') }}" class="auth-link">← Volver al inicio de sesión</a>
            </form>

        </div>
        <div class="auth-foot"><p>La 501 Sports Restaurant &copy; {{ date('Y') }} — Acceso seguro</p></div>
    </div>
</div>

@endsection
