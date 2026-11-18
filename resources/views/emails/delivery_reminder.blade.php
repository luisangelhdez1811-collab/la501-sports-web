@extends('emails.layout')

@php($folio = str_pad($order->id, 5, '0', STR_PAD_LEFT))

@section('title', '¿Ya te llegó tu pedido?')
@section('preheader', "Confirma la entrega del pedido #{$folio}.")
@section('eyebrow', 'Pedido #' . $folio)

@section('content')
    <h1 class="h1" style="margin:0 0 10px;font-size:24px;line-height:1.25;font-weight:800;color:#1C1917;">
        ¿Ya te llegó tu pedido?
    </h1>
    <p style="margin:0 0 24px;color:#57534E;">
        Hola {{ $nombreDestinatario }}, tu pedido #{{ $folio }} salió a entrega hace un rato. Si ya lo recibiste, confírmalo y dejaremos de recordártelo.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" class="btn" style="margin:0 auto;">
        <tr>
            <td align="center" style="border-radius:10px;background-color:#C2410C;">
                <a href="{{ route('order.track', $order->trackingToken()) }}"
                   style="display:inline-block;padding:14px 30px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:10px;">
                    Confirmar que ya llegó
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('note')
    ¿Algo salió mal con tu entrega? Llámanos al {{ \App\Services\ProviderInfo::get()['phone'] }} o escríbenos y lo resolvemos.
@endsection
