@extends('layouts.app')
@section('content')

<style>
    .ph-page {
        --or:#F97316; --or2:#EA580C;
        --gn:#16A34A; --gn2:#22C55E;
        --bg:#0f0d0a; --card:#161310;
        --txt:#FFFFFF; --sub:#A8A29E; --bdr:rgba(255,255,255,.07);
    }
    :root:not(.dark) .ph-page {
        --bg:#F5F3EF; --card:#FFFFFF;
        --txt:#1A1208; --sub:#6B6560; --bdr:rgba(0,0,0,.09);
    }
    .ph-page { background: var(--bg); min-height: 100vh; padding: 40px 20px 80px; }
    .ph-wrap { max-width: 640px; margin: 0 auto; }
    .ph-title { font-family:'Oswald',sans-serif; font-size: 26px; font-weight: 700; color: var(--txt); margin: 0 0 4px; }
    .ph-sub { color: var(--sub); font-size: 13px; margin: 0 0 24px; }

    .ph-search { display: flex; gap: 10px; margin-bottom: 12px; }
    .ph-inp { flex-grow: 1; padding: 12px 14px; background: var(--card); border: 1.5px solid var(--bdr); border-radius: 10px; color: var(--txt); font-size: 14px; outline: none; }
    .ph-inp:focus { border-color: var(--or); }
    .ph-btn { padding: 12px 20px; border-radius: 10px; border: none; background: var(--or); color: #fff; font-weight: 700; font-size: 13px; text-transform: uppercase; cursor: pointer; }
    .ph-btn:hover { background: var(--or2); }

    .ph-alert { border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
    .ph-alert.success { background: rgba(22,163,74,.1); color: #22C55E; border: 1px solid rgba(34,197,94,.25); }
    .ph-alert.error { background: rgba(239,68,68,.1); color: #F87171; border: 1px solid rgba(239,68,68,.25); }

    .ph-card { background: var(--card); border: 1px solid var(--bdr); border-radius: 14px; padding: 16px 18px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; text-decoration: none; transition: border-color .15s; }
    .ph-card:hover { border-color: var(--or); }
    .ph-folio { font-weight: 800; color: var(--txt); font-size: 14px; }
    .ph-meta { color: var(--sub); font-size: 12px; margin-top: 2px; }
    .ph-badge { font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px; white-space: nowrap; }
    .ph-empty { text-align: center; padding: 40px 0; color: var(--sub); }
</style>

<div class="ph-page">
    <div class="ph-wrap">
        <h1 class="ph-title">Mis Pedidos</h1>

        @if(session('success'))
            <div class="ph-alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="ph-alert error">⚠️ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="ph-alert error">⚠️ {{ $errors->first() }}</div>
        @endif

        @if($step === 'phone')
            <p class="ph-sub">Escribe el teléfono que usaste al pedir. Te mandamos un código por correo para confirmar que eres tú.</p>
            <form method="POST" action="{{ route('order.lookup.request-otp') }}" class="ph-search">
                @csrf
                <input type="tel" name="telefono" value="{{ $telefono }}" placeholder="Tu teléfono a 10 dígitos" required class="ph-inp">
                <button type="submit" class="ph-btn">Enviar Código</button>
            </form>

        @elseif($step === 'code')
            <p class="ph-sub">Te mandamos un código de 6 dígitos por correo. Escríbelo aquí (vence en 10 minutos).</p>
            <form method="POST" action="{{ route('order.lookup.verify.post') }}" class="ph-search">
                @csrf
                <input type="text" name="codigo" inputmode="numeric" maxlength="6" placeholder="123456" required class="ph-inp">
                <button type="submit" class="ph-btn">Verificar</button>
            </form>
            <form method="POST" action="{{ route('order.lookup.request-otp') }}">
                @csrf
                <input type="hidden" name="telefono" value="{{ $telefono }}">
                <button type="submit" style="background:none; border:none; color: var(--sub); font-size:12px; text-decoration:underline; cursor:pointer; padding:0;">¿No te llegó? Enviar otro código</button>
            </form>

        @elseif($step === 'results')
            <p class="ph-sub">Pedidos hechos con el teléfono <strong>{{ $telefono }}</strong>.</p>

            @if($orders->isEmpty())
                <div class="ph-empty">
                    <p style="font-size: 32px; margin-bottom: 8px;">🔍</p>
                    <p>No encontramos pedidos con ese teléfono.</p>
                </div>
            @else
                @foreach($orders as $order)
                    @php
                        $badgeStyles = [
                            'pending' => 'background:rgba(220,38,38,.15);color:#EF4444;border:1px solid rgba(220,38,38,.3);',
                            'paid' => 'background:rgba(234,88,12,.15);color:var(--or);border:1px solid rgba(234,88,12,.3);',
                            'preparing' => 'background:rgba(234,88,12,.15);color:var(--or);border:1px solid rgba(234,88,12,.3);',
                            'ready' => 'background:rgba(34,197,94,.15);color:var(--gn2);border:1px solid rgba(34,197,94,.3);',
                            'delivering' => 'background:rgba(59,130,246,.15);color:#60A5FA;border:1px solid rgba(59,130,246,.3);',
                            'delivered' => 'background:rgba(34,197,94,.15);color:var(--gn2);border:1px solid rgba(34,197,94,.3);',
                        ];
                        $badgeLabels = [
                            'pending' => 'Pago Pendiente',
                            'paid' => 'En Cocina',
                            'preparing' => 'En Cocina',
                            'ready' => $order->esADomicilio() ? 'Listo' : '¡Listo!',
                            'delivering' => 'En Camino',
                            'delivered' => 'Entregado',
                        ];
                    @endphp
                    <a href="{{ route('order.track', $order->trackingToken()) }}" class="ph-card">
                        <div>
                            <div class="ph-folio">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} — ${{ number_format($order->total, 2) }}</div>
                            <div class="ph-meta">{{ $order->created_at->format('d/m/Y h:i A') }}</div>
                        </div>
                        <span class="ph-badge" style="{{ $badgeStyles[$order->status] ?? 'background:rgba(255,255,255,.05);color:var(--sub);border:1px solid var(--bdr);' }}">
                            {{ $badgeLabels[$order->status] ?? ucfirst($order->status) }}
                        </span>
                    </a>
                @endforeach
            @endif
        @endif
    </div>
</div>

@endsection
