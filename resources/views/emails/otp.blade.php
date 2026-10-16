<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Código de Recuperación — La 501</title>
</head>
<body style="margin:0;padding:0;background-color:#F5F3EF;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">

<div style="width:100%;background-color:#F5F3EF;padding:40px 16px;text-align:center;box-sizing:border-box;">

    <div style="width:100%;max-width:520px;margin:0 auto;border-radius:12px;overflow:hidden;
                box-shadow:0 8px 30px rgba(0,0,0,.08);border:1px solid #E4E4E7;background-color:#FFFFFF;">

        {{-- HEADER con patrón --}}
        <div style="background-color:#0f0d0a;padding:28px 32px;text-align:center;position:relative;">
            {{-- Franja naranja→verde arriba --}}
            <div style="height:4px;background:linear-gradient(90deg,#F97316,#16A34A);margin:-28px -32px 24px;"></div>
            <span style="display:inline-block;background:linear-gradient(135deg,#F97316,#EA580C);width:48px;height:48px;border-radius:10px;line-height:48px;text-align:center;font-size:22px;box-shadow:0 4px 12px rgba(249,115,22,.4);margin-bottom:12px;">🔑</span>
            <h1 style="color:#FFFFFF;font-size:24px;font-weight:900;margin:0 0 4px;letter-spacing:1.5px;font-family:'Trebuchet MS',sans-serif;">
                LA 501 <span style="color:#F97316;">SPORTS</span>
            </h1>
            <p style="color:#71717A;font-size:12px;margin:0;font-weight:500;letter-spacing:.5px;text-transform:uppercase;">
                Recuperación de contraseña
            </p>
        </div>

        {{-- CUERPO --}}
        <div style="background-color:#FFFFFF;padding:36px 36px 28px;">

            <h2 style="color:#18181B;font-size:20px;font-weight:800;margin:0 0 12px;text-align:center;">
                Tu código de seguridad
            </h2>
            <p style="color:#71717A;font-size:14px;line-height:1.7;margin:0 0 28px;text-align:center;">
                Recibimos una solicitud para restablecer la contraseña de tu cuenta.<br>
                Usa el siguiente código para continuar:
            </p>

            {{-- Caja del código --}}
            <div style="margin-bottom:24px;text-align:center;">
                <div style="display:inline-block;background:#F5F3EF;border:2px dashed #E4E4E7;border-radius:12px;padding:24px 36px;text-align:center;">
                    {{-- Código con cada dígito separado visualmente --}}
                    <span style="font-family:'Courier New',monospace;font-size:44px;font-weight:900;letter-spacing:14px;color:#F97316;display:block;line-height:1;">
                        {{ $codigo }}
                    </span>
                    <span style="font-size:11px;color:#71717A;font-weight:600;letter-spacing:.5px;text-transform:uppercase;display:block;margin-top:8px;">
                        Código de 8 dígitos
                    </span>
                </div>
            </div>

            {{-- Aviso de expiración --}}
            <div style="margin-bottom:0;text-align:center;">
                <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(220,38,38,.06);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:10px 20px;">
                    <span style="font-size:15px;">⏱</span>
                    <span style="color:#DC2626;font-size:13px;font-weight:700;">
                        Este código expira en <strong>5 minutos</strong>
                    </span>
                </div>
            </div>

        </div>

        {{-- DIVIDER + NOTA DE SEGURIDAD --}}
        <div style="background-color:#FAFAFA;padding:20px 36px;border-top:1px solid #F4F4F5;">
            <p style="color:#A1A1AA;font-size:12px;line-height:1.6;margin:0;text-align:center;">
                Si <strong>tú no solicitaste</strong> este código, puedes ignorar y eliminar este correo con seguridad. Tu cuenta sigue protegida y no se realizó ningún cambio.
            </p>
        </div>

        {{-- FOOTER --}}
        <div style="background-color:#0f0d0a;padding:16px 32px;text-align:center;">
            <p style="color:#52525B;font-size:11px;margin:0;letter-spacing:.3px;">
                La 501 Sports Restaurant &copy; {{ date('Y') }} &mdash; Correo de seguridad automático
            </p>
        </div>

    </div>

</div>

</body>
</html>
