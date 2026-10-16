<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>¿Ya te llegó tu pedido? — La 501</title>
</head>
<body style="margin:0;padding:0;background-color:#F5F3EF;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">

<div style="width:100%;background-color:#F5F3EF;padding:40px 16px;text-align:center;box-sizing:border-box;">

    <div style="width:100%;max-width:520px;margin:0 auto;border-radius:12px;overflow:hidden;
                box-shadow:0 8px 30px rgba(0,0,0,.08);border:1px solid #E4E4E7;background-color:#FFFFFF;">

        <div style="background-color:#0f0d0a;padding:28px 32px;text-align:center;position:relative;">
            <div style="height:4px;background:linear-gradient(90deg,#F97316,#16A34A);margin:-28px -32px 24px;"></div>
            <span style="display:inline-block;background:linear-gradient(135deg,#F97316,#EA580C);width:48px;height:48px;border-radius:10px;line-height:48px;text-align:center;font-size:22px;box-shadow:0 4px 12px rgba(249,115,22,.4);margin-bottom:12px;"></span>
            <h1 style="color:#FFFFFF;font-size:24px;font-weight:900;margin:0 0 4px;letter-spacing:1.5px;font-family:'Trebuchet MS',sans-serif;">
                LA 501 <span style="color:#F97316;">SPORTS</span>
            </h1>
            <p style="color:#71717A;font-size:12px;margin:0;font-weight:500;letter-spacing:.5px;text-transform:uppercase;">
                Confirmación de Entrega
            </p>
        </div>

        <div style="background-color:#FFFFFF;padding:36px 36px 28px;text-align:left;">

            <h2 style="color:#18181B;font-size:20px;font-weight:800;margin:0 0 12px;text-align:center;">
                ¿Ya te llegó tu pedido?
            </h2>
            <p style="color:#52525B;font-size:14px;line-height:1.6;margin:0 0 20px;text-align:center;">
                Hola <strong>{{ $nombreDestinatario }}</strong>, tu pedido #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} salió a entrega hace un rato. Entra al enlace de abajo y pulsa <strong>"Ya recibí mi pedido"</strong> para confirmarlo — así dejamos de recordártelo.
            </p>

            <div style="text-align:center;margin-bottom:20px;">
                <a href="{{ route('order.track', $order->trackingToken()) }}"
                   style="display:inline-block;background-color:#16A34A;color:#FFFFFF;font-size:13px;font-weight:bold;text-transform:uppercase;text-decoration:none;padding:14px 28px;border-radius:8px;box-shadow:0 4px 12px rgba(22,163,74,.3);transition:background-color .2s;">
                    Confirmar que ya llegó
                </a>
            </div>

        </div>

        <div style="background-color:#FAFAFA;padding:20px 36px;border-top:1px solid #F4F4F5;">
            <p style="color:#A1A1AA;font-size:12px;line-height:1.6;margin:0;text-align:center;">
                Si algo salió mal con tu entrega, contáctanos directamente al teléfono local de la sucursal.
            </p>
        </div>

        <div style="background-color:#0f0d0a;padding:16px 32px;text-align:center;">
            <p style="color:#52525B;font-size:11px;margin:0;letter-spacing:.3px;">
                La 501 Sports Restaurant &copy; {{ date('Y') }} &mdash; Notificación automática de pedido
            </p>
        </div>

    </div>

</div>

</body>
</html>
