@extends('layouts.app')

@section('title', $documento['title'] . ' | La 501 Sports')

@section('content')
@include('legal.styles')

@php($secciones = collect($documento['blocks'])->where('type', 'h2'))

<div class="lg-page">
    <header class="lg-hero">
        <p class="lg-eyebrow">La 501 Sports Restaurant</p>
        <h1>{{ $documento['title'] }}</h1>
        <p class="lg-meta">Última actualización: {{ $documento['updated'] }} · Versión {{ $documento['version'] }}</p>
    </header>

    <div class="lg-wrap">
        <nav class="lg-toc" aria-label="Contenido">
            <details open>
                <summary style="list-style:none; cursor:pointer;"><p style="display:inline">Contenido</p></summary>
                <ol>
                    @foreach($secciones as $seccion)
                        <li><a href="#{{ $seccion['id'] ?? \Illuminate\Support\Str::slug($seccion['text']) }}">{{ $seccion['text'] }}</a></li>
                    @endforeach
                </ol>
            </details>
        </nav>

        <article class="lg-card">
            @include('legal.blocks', ['blocks' => $documento['blocks']])
        </article>
    </div>
</div>
@endsection
