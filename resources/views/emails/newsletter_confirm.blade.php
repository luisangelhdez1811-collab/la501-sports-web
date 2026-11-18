@extends('emails.layout')

@section('title', 'Confirma tu suscripción')
@section('preheader', 'Confirma que quieres recibir las promociones de La 501 Sports.')
@section('eyebrow', 'Promociones por correo')

@section('content')
    <h1 class="h1" style="margin:0 0 10px;font-size:24px;line-height:1.25;font-weight:800;color:#1C1917;text-align:center;">
        Confirma tu suscripción
    </h1>
    <p style="margin:0 0 24px;color:#57534E;text-align:center;">
        Alguien (seguramente tú) pidió recibir en este correo las promociones de La 501 Sports. Para empezar a recibirlas, confírmalo con el botón.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <a href="{{ route('newsletter.confirm', $token) }}"
                   style="display:inline-block;background-color:#C2410C;color:#FFFFFF;text-decoration:none;font-weight:700;font-size:15px;padding:14px 28px;border-radius:10px;">
                    Sí, quiero recibir promociones
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('note')
    ¿No lo pediste? Ignora este correo: sin confirmar no te enviaremos nada.
@endsection
