@extends('layouts.app')
@section('content')
<style>
    .cp { --or:#F97316; --bg:#0f0d0a; --card:#161310; --txt:#fff; --sub:#A8A29E; --bdr:rgba(255,255,255,.1); }
    :root:not(.dark) .cp { --bg:#F5F3EF; --card:#fff; --txt:#1A1208; --sub:#6B6560; --bdr:rgba(0,0,0,.12); }
    .cp { background: var(--bg); min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px; }
    .cp-card { width: 100%; max-width: 420px; background: var(--card); border: 1px solid var(--bdr); border-radius: 16px; padding: 28px 24px; }
    .cp h1 { font-family: 'Oswald', sans-serif; font-size: 24px; font-weight: 700; color: var(--txt); margin: 0 0 6px; }
    .cp p { color: var(--sub); font-size: 13px; line-height: 1.55; margin: 0 0 18px; }
    .cp label { display: block; font-size: 12px; font-weight: 600; color: var(--txt); margin-bottom: 6px; }
    .cp input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1.5px solid var(--bdr); background: transparent; color: var(--txt); font-size: 15px; }
    .cp input:focus { outline: none; border-color: var(--or); }
    .cp .err { color: #F87171; font-size: 12px; margin-top: 6px; }
    .cp button { width: 100%; min-height: 46px; margin-top: 16px; border: 0; border-radius: 10px; background: var(--or); color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; }
</style>
<div class="cp">
    <form class="cp-card" method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <h1>Confirma tu contraseña</h1>
        <p>Estás por entrar a una sección con datos delicados (respaldos de la base de datos o el personal). Por seguridad, escribe de nuevo tu contraseña.</p>
        <label for="cp-pass">Contraseña</label>
        <input id="cp-pass" type="password" name="password" required autofocus autocomplete="current-password">
        @error('password')<p class="err" role="alert">{{ $message }}</p>@enderror
        <button type="submit">Continuar</button>
    </form>
</div>
@endsection
