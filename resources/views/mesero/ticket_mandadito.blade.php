<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket mandadito #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; margin: 0; padding: 12px; }
        .ticket { max-width: 300px; margin: 0 auto; font-size: 13px; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 2px; }
        .sub { text-align: center; font-size: 11px; margin: 0 0 10px; }
        hr { border: 0; border-top: 1px dashed #000; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
        .lbl { font-weight: bold; }
        .addr { font-size: 15px; font-weight: bold; margin: 4px 0; }
        .qr { text-align: center; margin-top: 8px; line-height: 0; }
        .qr small { display: block; font-size: 11px; line-height: 1.3; margin-top: 4px; }
        .total { font-size: 16px; font-weight: bold; }
        .actions { text-align: center; margin-top: 16px; }
        .actions button { padding: 10px 18px; font-size: 14px; cursor: pointer; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="ticket">
    <h1>LA 501 SPORTS</h1>
    <p class="sub">Entrega a domicilio · Mandadito</p>

    <div class="row"><span class="lbl">Folio:</span><span>#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span></div>
    <div class="row"><span class="lbl">Hora:</span><span>{{ optional($order->delivery_started_at ?? now())->timezone('America/Mexico_City')->format('d/m/Y h:i A') }}</span></div>
    <hr>
    <div class="row"><span class="lbl">Cliente:</span><span>{{ $order->customer_name }}</span></div>
    <div class="row"><span class="lbl">Teléfono:</span><span>{{ $order->customer_phone }}</span></div>
    <div class="lbl" style="margin-top:8px;">Dirección:</div>
    <div class="addr">{{ $order->customer_address }}</div>


    <hr>
    @foreach($order->items as $item)
        <div class="row">
            <span>{{ $item->quantity }}x {{ $item->product_name ?? ($item->product->name ?? 'Platillo') }}</span>
        </div>
        @if($item->optionsSummary() !== '')
            <div class="row" style="font-size:11px;"><span>&nbsp;&nbsp;{{ $item->optionsSummary() }}</span></div>
        @endif
    @endforeach
    <hr>
    <div class="row total"><span>Total pedido</span><span>${{ number_format($order->total, 2) }}</span></div>
    <div class="row"><span class="lbl">Pago:</span><span>{{ ucfirst(strtolower($order->payment_method)) }}</span></div>
    <p style="font-size:11px; margin:8px 0 0;">El envío lo cobra el mandadito por separado.</p>

    {{-- QR al final del ticket --}}
    @if($order->hasLocation() && ($qrSvg = $order->mapsQrSvg(240)))
        <div class="qr">
            {!! $qrSvg !!}
            <small>Escanea para abrir la ubicación en Maps</small>
        </div>
    @endif

    <div class="actions">
        <button onclick="window.print()">Imprimir</button>
    </div>
</div>
@unless(request()->boolean('embed'))
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
@endunless
</body>
</html>
