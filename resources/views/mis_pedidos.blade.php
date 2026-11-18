@extends('layouts.app')
@section('content')

<style>
    .mo {
        --or:#F97316; --or2:#EA580C;
        --bg:#0f0d0a; --card:#161310;
        --txt:#FFFFFF; --sub:#A8A29E; --bdr:rgba(255,255,255,.08);
    }
    :root:not(.dark) .mo {
        --bg:#F5F3EF; --card:#FFFFFF;
        --txt:#1A1208; --sub:#6B6560; --bdr:rgba(0,0,0,.09);
    }
    .mo { background: var(--bg); min-height: 100vh; padding: 32px 16px 80px; }
    .mo-wrap { max-width: 860px; margin: 0 auto; }
    .mo-back {
        display: inline-flex; align-items: center; gap: 6px; min-height: 40px; margin-bottom: 12px;
        color: var(--sub); font-size: 13px; font-weight: 600; text-decoration: none;
    }
    .mo-back:hover, .mo-back:focus-visible { color: var(--or); }
    .mo-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .mo-title { font-family: 'Oswald', sans-serif; font-size: 28px; font-weight: 700; color: var(--txt); margin: 0; line-height: 1.1; }
    .mo-sub { color: var(--sub); font-size: 13px; margin: 4px 0 0; }
    .mo-count { color: var(--sub); font-size: 13px; font-variant-numeric: tabular-nums; }
    .mo-card { background: var(--card); border: 1px solid var(--bdr); border-radius: 14px; padding: 6px 14px; }
    .mo-empty { text-align: center; padding: 48px 16px; color: var(--sub); }
    .mo-empty strong { display: block; color: var(--txt); font-size: 15px; margin: 10px 0 4px; }
    .mo-cta {
        display: inline-flex; align-items: center; justify-content: center; min-height: 44px; margin-top: 16px; padding: 0 20px;
        border-radius: 10px; background: var(--or); color: #fff; font-weight: 700; font-size: 13px; text-decoration: none;
    }
    .mo-cta:hover { background: var(--or2); }
    .mo-pages { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; color: var(--sub); font-size: 13px; }
    .mo-pages a, .mo-pages span.off {
        display: inline-flex; align-items: center; min-height: 40px; padding: 0 14px; border-radius: 10px;
        border: 1px solid var(--bdr); color: var(--txt); text-decoration: none; font-weight: 600;
    }
    .mo-pages a:hover { border-color: var(--or); color: var(--or); }
    .mo-pages span.off { opacity: .4; }
    .mo-help { color: var(--sub); font-size: 12px; line-height: 1.6; margin-top: 18px; }
</style>

<div class="mo">
    <div class="mo-wrap">
        <a href="{{ $volverUrl }}" class="mo-back"><x-icon name="arrow-left" size="16" /> {{ $volverLabel }}</a>

        <div class="mo-head">
            <div>
                <h1 class="mo-title">Mis pedidos</h1>
                <p class="mo-sub">Todos los pedidos hechos con tu cuenta. Abre uno para ver su ticket o seguirlo.</p>
            </div>
            @if ($orders->total() > 0)
                <span class="mo-count">{{ $orders->total() }} {{ $orders->total() === 1 ? 'pedido' : 'pedidos' }}</span>
            @endif
        </div>

        <div class="mo-card">
            @if ($orders->isEmpty())
                <div class="mo-empty">
                    <x-icon name="receipt" size="36" />
                    <strong>Aún no tienes pedidos</strong>
                    Cuando hagas uno a domicilio aparecerá aquí.
                    <div><a href="{{ route('pedido') }}" class="mo-cta">Ver el menú</a></div>
                </div>
            @else
                @include('partials.order-list', ['orders' => $orders])
            @endif
        </div>

        @if ($orders->hasPages())
            <nav class="mo-pages" aria-label="Páginas">
                @if ($orders->onFirstPage())
                    <span class="off">Anteriores</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}" rel="prev">Anteriores</a>
                @endif
                <span>Página {{ $orders->currentPage() }} de {{ $orders->lastPage() }}</span>
                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" rel="next">Siguientes</a>
                @else
                    <span class="off">Siguientes</span>
                @endif
            </nav>
        @endif

        <p class="mo-help">
            ¿Pediste sin iniciar sesión? Esos pedidos no están ligados a tu cuenta; búscalos con el enlace de seguimiento que te dimos al pedir.
        </p>
    </div>
</div>
@endsection
