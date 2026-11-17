@extends('layouts.app')

@section('content')
@include('legal.styles')

@php
    $rutas = [
        \App\Services\LegalDocuments::PRIVACY => route('aviso-privacidad'),
        \App\Services\LegalDocuments::TERMS => route('terminos-condiciones'),
        \App\Services\LegalDocuments::STAFF => route('personal.aviso-privacidad'),
    ];
@endphp

<div class="lg-page">
    <header class="lg-hero">
        <p class="lg-eyebrow">La 501 Sports Restaurant</p>
        <h1>{{ $nuevo ? 'Antes de continuar' : 'Actualizamos nuestros documentos' }}</h1>
        <p class="lg-meta">Lee y acepta {{ $documentos->count() > 1 ? 'estos documentos' : 'este documento' }} para seguir usando tu cuenta.</p>
    </header>

    <div style="max-width: 720px; margin: 0 auto; padding: 32px 20px 80px;">
        <article class="lg-card">
            @foreach($documentos as $doc => $documento)
                <h2 class="lg-h2">{{ $documento['title'] }}</h2>
                <p class="lg-p" style="margin-bottom: 8px;">Versión del {{ $documento['updated'] }}. Lo principal:</p>
                <ul class="lg-list">
                    @foreach($documento['changes'] ?? [] as $cambio)
                        <li>{{ $cambio }}</li>
                    @endforeach
                </ul>
                <p class="lg-p"><a href="{{ $rutas[$doc] }}" target="_blank" rel="noopener" style="color: var(--lg-accent); font-weight: 600;">Leer el documento completo</a></p>
            @endforeach

            <form method="POST" action="{{ route('legal.accept.store') }}" style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--lg-bdr);">
                @csrf
                <label style="display: flex; gap: 12px; align-items: flex-start; font-size: 15px; line-height: 1.55; cursor: pointer;">
                    <input type="checkbox" name="acepto" value="1" required style="margin-top: 4px; width: 18px; height: 18px; flex-shrink: 0;">
                    <span>Leí y acepto {{ $documentos->pluck('title')->join(', ', ' y ') }}.</span>
                </label>
                @error('acepto')
                    <p role="alert" style="color: #DC2626; font-size: 13px; margin: 8px 0 0;">{{ $message }}</p>
                @enderror

                <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; align-items: center;">
                    <button type="submit" style="padding: 12px 22px; border-radius: 10px; border: 0; background: #C2410C; color: #fff; font-weight: 700; font-size: 15px; cursor: pointer;">
                        Aceptar y continuar
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: 14px;">
                @csrf
                <p class="lg-p" style="font-size: 13px; margin: 0;">
                    ¿No estás de acuerdo? Puedes
                    <button type="submit" style="background: none; border: 0; padding: 0; color: var(--lg-accent); text-decoration: underline; cursor: pointer; font: inherit;">cerrar sesión</button>
                    o <a href="{{ route('eliminar-cuenta') }}" style="color: var(--lg-accent);">eliminar tu cuenta</a>.
                </p>
            </form>
        </article>
    </div>
</div>
@endsection
