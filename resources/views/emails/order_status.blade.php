@extends('emails.layout')

@php($enCamino = ($stage ?? 'ready') === 'delivering')
@php($folio = str_pad($order->id, 5, '0', STR_PAD_LEFT))

@section('title', $enCamino ? 'Tu pedido va en camino' : 'Tu pedido está listo')
@section('preheader', $enCamino ? "Pedido #{$folio}: ya salió a tu domicilio." : "Pedido #{$folio}: ya está listo.")
@section('eyebrow', 'Pedido #' . $folio)

@section('content')
    <h1 class="h1" style="margin:0 0 10px;font-size:24px;line-height:1.25;font-weight:800;color:#1C1917;">
        {{ $enCamino ? 'Tu pedido va en camino' : 'Tu pedido está listo' }}
    </h1>
    <p style="margin:0 0 24px;color:#57534E;">
        Hola {{ $nombreDestinatario }},
        @if($enCamino)
            tu pedido ya salió y va rumbo a tu domicilio.
        @else
            tu pedido ya está preparado{{ $order->esADomicilio() ? ' y está por salir a entrega' : '' }}.
        @endif
    </p>

    {{-- Resumen --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E7E5E4;border-radius:12px;">
        <tr>
            <td style="padding:16px 18px 6px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#78716C;">
                Resumen del pedido
            </td>
        </tr>
        @foreach($order->items as $item)
            <tr>
                <td style="padding:6px 18px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="font-size:14px;line-height:1.45;color:#292524;vertical-align:top;">
                                {{ $item->quantity }}× {{ $item->product_name }}
                                @if($item->optionsSummary() !== '')
                                    <br><span style="font-size:12px;color:#78716C;">{{ $item->optionsSummary() }}</span>
                                @endif
                            </td>
                            <td align="right" style="font-size:14px;color:#292524;white-space:nowrap;vertical-align:top;padding-left:12px;">
                                ${{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        @endforeach
        <tr>
            <td style="padding:10px 18px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #E7E5E4;">
                    <tr>
                        <td style="padding-top:12px;font-size:14px;font-weight:700;color:#1C1917;">Total</td>
                        <td align="right" style="padding-top:12px;font-size:16px;font-weight:800;color:#1C1917;white-space:nowrap;">${{ number_format($order->total, 2) }} MXN</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:18px;">
        <tr>
            <td style="font-size:13px;line-height:1.7;color:#57534E;">
                <strong style="color:#292524;">Fecha:</strong> {{ $order->created_at->timezone('America/Mexico_City')->format('d/m/Y h:i a') }}<br>
                @if($order->esADomicilio())
                    <strong style="color:#292524;">Entrega en:</strong> {{ $order->customer_address }}<br>
                @endif
                <strong style="color:#292524;">Pago:</strong> {{ $order->payment_method === 'tarjeta' ? 'Tarjeta' : 'Efectivo al recibir' }}
            </td>
        </tr>
    </table>

    {{-- Botón --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" class="btn" style="margin:26px auto 0;">
        <tr>
            <td align="center" style="border-radius:10px;background-color:#C2410C;">
                <a href="{{ route('order.track', $order->trackingToken()) }}"
                   style="display:inline-block;padding:14px 30px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:10px;">
                    Ver mi pedido
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('note')
    @if($order->payment_method !== 'tarjeta')
        Si pagas en efectivo, tener el monto exacto agiliza la entrega.
    @endif
    ¿Necesitas factura? Escríbenos a {{ \App\Services\ProviderInfo::get()['email'] }} con tu folio #{{ $folio }}, RFC, razón social, régimen fiscal, código postal y uso de CFDI, dentro del mismo mes.
@endsection
