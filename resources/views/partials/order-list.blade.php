{{--
    Lista de pedidos del cliente (perfil y «Mis pedidos»). Toma los colores de la página que la incluye:
    --txt, --sub, --bdr, --card y --or. En el celular cada pedido se vuelve una tarjeta.
--}}
@once
<style>
    .ol { list-style: none; margin: 0; padding: 0; }
    .ol-row {
        display: grid; grid-template-columns: 92px 1fr 110px 140px auto; gap: 14px; align-items: center;
        padding: 14px 10px; border-bottom: 1px solid var(--bdr);
    }
    .ol-row:last-child { border-bottom: 0; }
    .ol-folio { font-weight: 700; color: var(--txt); font-variant-numeric: tabular-nums; }
    .ol-main { min-width: 0; }
    .ol-date { font-size: 13px; color: var(--txt); font-variant-numeric: tabular-nums; }
    .ol-items { font-size: 12px; color: var(--sub); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ol-total { font-weight: 700; color: var(--txt); text-align: right; font-variant-numeric: tabular-nums; }
    .ol-chip {
        justify-self: start; display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .3px;
        padding: 4px 9px; border-radius: 999px; border: 1px solid currentColor; white-space: nowrap;
    }
    .ol-chip.st-ok { color: #16A34A; }
    .ol-chip.st-progress { color: #EA580C; }
    .ol-chip.st-road { color: #2563EB; }
    .ol-chip.st-bad { color: #DC2626; }
    .ol-chip.st-off { color: var(--sub); }
    .dark .ol-chip.st-ok { color: #4ADE80; }
    .dark .ol-chip.st-progress { color: #FB923C; }
    .dark .ol-chip.st-road { color: #60A5FA; }
    .dark .ol-chip.st-bad { color: #F87171; }
    .ol-link {
        justify-self: end; display: inline-flex; align-items: center; gap: 6px; min-height: 36px;
        padding: 0 12px; border-radius: 8px; border: 1px solid var(--bdr); color: var(--txt);
        font-size: 12px; font-weight: 600; text-decoration: none; white-space: nowrap;
        transition: border-color .15s, color .15s;
    }
    .ol-link:hover, .ol-link:focus-visible { border-color: var(--or); color: var(--or); }
    @media (max-width: 640px) {
        .ol-row {
            grid-template-columns: 1fr auto; grid-template-areas: "folio total" "main main" "chip link";
            gap: 8px 12px; padding: 14px 4px;
        }
        .ol-folio { grid-area: folio; }
        .ol-total { grid-area: total; }
        .ol-main { grid-area: main; }
        .ol-chip { grid-area: chip; align-self: center; }
        .ol-link { grid-area: link; }
        .ol-items { white-space: normal; }
    }
</style>
@endonce

@php
    $tonos = [
        'delivered' => 'st-ok', 'ready' => 'st-ok',
        'paid' => 'st-progress', 'preparing' => 'st-progress', 'pending' => 'st-progress',
        'delivering' => 'st-road',
        'failed' => 'st-bad',
        'cancelled' => 'st-off',
    ];
@endphp

<ul class="ol">
    @foreach ($orders as $order)
        @php
            $resumen = $order->items->map(fn ($i) => $i->quantity . '× ' . $i->product_name);
        @endphp
        <li class="ol-row">
            <span class="ol-folio">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
            <div class="ol-main">
                <div class="ol-date">{{ $order->created_at->translatedFormat('d M Y · h:i A') }}</div>
                @if ($resumen->isNotEmpty())
                    <div class="ol-items" title="{{ $resumen->implode(', ') }}">{{ $resumen->take(3)->implode(', ') }}{{ $resumen->count() > 3 ? ' y ' . ($resumen->count() - 3) . ' más' : '' }}</div>
                @endif
            </div>
            <span class="ol-total">${{ number_format($order->total, 2) }}</span>
            <span class="ol-chip {{ $tonos[$order->status] ?? 'st-off' }}">{{ $order->statusLabel() }}</span>
            <a class="ol-link" href="{{ route('order.track', $order->trackingToken()) }}">
                Ver ticket <x-icon name="chevron-right" size="14" />
            </a>
        </li>
    @endforeach
</ul>
