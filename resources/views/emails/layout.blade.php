{{--
    Base de todos los correos. Tablas y estilos en línea porque Gmail, Outlook y Apple Mail ignoran casi
    todo lo demás; el ancho es fluido (máximo 560px) para que se lea igual en un teléfono de 320px que en
    escritorio. El pie lleva siempre los datos del proveedor (LFPC art. 76 Bis III).

    Secciones: title, preheader (texto que se ve en la bandeja junto al asunto), eyebrow (etiqueta bajo la
    marca), content y, opcionales, note (nota gris al final) y unsubscribe.
--}}
@php($proveedor = \App\Services\ProviderInfo::get())
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title>@yield('title') · La 501 Sports</title>
<style>
    body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table { border-collapse: collapse; }
    a { color: #C2410C; }
    @media only screen and (max-width: 480px) {
        .px { padding-left: 22px !important; padding-right: 22px !important; }
        .h1 { font-size: 22px !important; }
        .code { font-size: 30px !important; letter-spacing: 5px !important; }
        .btn a { display: block !important; }
    }
</style>
</head>
<body style="margin:0;padding:0;background-color:#EFEDE8;">

{{-- Vista previa en la bandeja de entrada (oculta en el cuerpo) --}}
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#EFEDE8;opacity:0;">
    @yield('preheader')&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#EFEDE8;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #E2DED6;">

                {{-- Marca --}}
                <tr>
                    <td style="background-color:#141210;padding:0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="height:4px;line-height:4px;font-size:0;background-color:#C2410C;">&nbsp;</td></tr>
                            <tr>
                                <td class="px" align="center" style="padding:26px 36px 24px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                    <div style="font-size:22px;line-height:1;font-weight:800;letter-spacing:3px;color:#FFFFFF;">
                                        LA 501 <span style="color:#F97316;">SPORTS</span>
                                    </div>
                                    <div style="margin-top:8px;font-size:11px;line-height:1.4;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:#A8A29E;">
                                        @yield('eyebrow')
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Contenido --}}
                <tr>
                    <td class="px" style="padding:34px 40px 30px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#292524;font-size:15px;line-height:1.6;">
                        @yield('content')
                    </td>
                </tr>

                @hasSection('note')
                    <tr>
                        <td class="px" style="padding:18px 40px;background-color:#FAF9F7;border-top:1px solid #EFEDE8;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#78716C;">
                            @yield('note')
                        </td>
                    </tr>
                @endif

                {{-- Proveedor --}}
                <tr>
                    <td class="px" align="center" style="padding:22px 40px 26px;background-color:#141210;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;color:#A8A29E;">
                        <strong style="color:#E7E5E4;">{{ $proveedor['name'] }}</strong><br>
                        {{ $proveedor['address'] }}<br>
                        Tel. <a href="tel:{{ preg_replace('/\D/', '', $proveedor['phone']) }}" style="color:#E7E5E4;text-decoration:none;">{{ $proveedor['phone'] }}</a>
                        &nbsp;·&nbsp;
                        <a href="mailto:{{ $proveedor['email'] }}" style="color:#E7E5E4;text-decoration:none;">{{ $proveedor['email'] }}</a>
                        @if($proveedor['rfc'])<br>RFC {{ $proveedor['rfc'] }}@endif
                        <br>
                        <a href="{{ route('aviso-privacidad') }}" style="color:#A8A29E;">Aviso de Privacidad</a>
                        &nbsp;·&nbsp;
                        <a href="{{ route('terminos-condiciones') }}" style="color:#A8A29E;">Términos y Condiciones</a>
                        @hasSection('unsubscribe')
                            <br>@yield('unsubscribe')
                        @endif
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
