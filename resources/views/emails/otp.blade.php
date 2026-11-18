@extends('emails.layout')

@section('title', 'Código de seguridad')
@section('preheader', "Tu código es {$codigo}. Vence en 10 minutos.")
@section('eyebrow', 'Recuperación de contraseña')

@section('content')
    <h1 class="h1" style="margin:0 0 10px;font-size:24px;line-height:1.25;font-weight:800;color:#1C1917;text-align:center;">
        Tu código de seguridad
    </h1>
    <p style="margin:0 0 26px;color:#57534E;text-align:center;">
        Recibimos una solicitud para restablecer la contraseña de tu cuenta. Escribe este código para continuar.
    </p>

    {{-- El código va en un solo bloque de texto (sin espacios) para que se copie completo al mantenerlo presionado --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:22px 12px 18px;background-color:#FAF9F7;border:1px solid #E7E5E4;border-radius:12px;">
                <div class="code" style="font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:36px;line-height:1.1;font-weight:700;letter-spacing:7px;color:#1C1917;">{{ $codigo }}</div>
                <div style="margin-top:10px;font-size:12px;line-height:1.5;color:#78716C;">
                    Mantén presionado el código para copiarlo
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:20px 0 0;text-align:center;font-size:14px;color:#57534E;">
        Vence en <strong style="color:#B91C1C;">10 minutos</strong> y solo funciona una vez.
    </p>
@endsection

@section('note')
    ¿No pediste este código? Ignora este correo: tu contraseña no cambia. Nunca te pediremos este código por teléfono ni por mensaje.
@endsection
