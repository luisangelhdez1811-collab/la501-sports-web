<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{{ $esNueva ? 'Nueva promoción' : 'Promoción actualizada' }} — La 501</title>
</head>
<body style="margin:0;padding:0;background-color:#F5F3EF;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">

<div style="width:100%;background-color:#F5F3EF;padding:40px 16px;text-align:center;box-sizing:border-box;">

    <div style="width:100%;max-width:520px;margin:0 auto;border-radius:12px;overflow:hidden;
                box-shadow:0 8px 30px rgba(0,0,0,.08);border:1px solid #E4E4E7;background-color:#FFFFFF;">

        {{-- HEADER --}}
        <div style="background-color:#0f0d0a;padding:28px 32px;text-align:center;position:relative;">
            <div style="height:4px;background:linear-gradient(90deg,#F97316,#16A34A);margin:-28px -32px 24px;"></div>
            <span style="display:inline-block;background:linear-gradient(135deg,#F97316,#EA580C);width:48px;height:48px;border-radius:10px;line-height:48px;text-align:center;font-size:22px;box-shadow:0 4px 12px rgba(249,115,22,.4);margin-bottom:12px;">🔥</span>
            <h1 style="color:#FFFFFF;font-size:24px;font-weight:900;margin:0 0 4px;letter-spacing:1.5px;font-family:'Trebuchet MS',sans-serif;">
                LA 501 <span style="color:#F97316;">SPORTS</span>
            </h1>
            <p style="color:#71717A;font-size:12px;margin:0;font-weight:500;letter-spacing:.5px;text-transform:uppercase;">
                {{ $esNueva ? 'Nueva promoción' : 'Promoción actualizada' }}
            </p>
        </div>

        {{-- CUERPO --}}
        <div style="background-color:#FFFFFF;padding:36px 36px 28px;">

            <h2 style="color:#18181B;font-size:20px;font-weight:800;margin:0 0 12px;text-align:center;">
                {{ $esNueva ? '¡Tenemos una promoción nueva!' : '¡Una de nuestras promociones cambió!' }}
            </h2>

            @if($promotion->image)
            <div style="margin-bottom:20px;text-align:center;">
                <img src="{{ (str_starts_with($promotion->image, 'http://') || str_starts_with($promotion->image, 'https://')) ? $promotion->image : asset('storage/' . $promotion->image) }}"
                     alt="{{ $promotion->title }}"
                     style="width:100%;max-width:448px;border-radius:10px;display:block;margin:0 auto;">
            </div>
            @endif

            <div style="background:#F5F3EF;border:2px dashed #E4E4E7;border-radius:12px;padding:20px 24px;text-align:center;margin-bottom:20px;">
                <span style="font-size:20px;font-weight:900;color:#18181B;display:block;margin-bottom:6px;">{{ $promotion->title }}</span>
                <span style="font-size:28px;font-weight:900;color:#16A34A;display:block;">{{ $promotion->price_text }}</span>
            </div>

            <p style="color:#71717A;font-size:14px;line-height:1.7;margin:0 0 28px;text-align:center;">
                {{ $promotion->description }}
            </p>

            <div style="text-align:center;">
                <a href="{{ route('promociones') }}" style="display:inline-block;background:#F97316;color:#fff;text-decoration:none;font-weight:700;font-size:13px;letter-spacing:1px;text-transform:uppercase;padding:14px 28px;border-radius:10px;">
                    Ver Promoción →
                </a>
            </div>

        </div>

        {{-- FOOTER --}}
        <div style="background-color:#FAFAFA;padding:16px 36px;border-top:1px solid #F4F4F5;">
            <p style="color:#A1A1AA;font-size:11px;line-height:1.6;margin:0;text-align:center;">
                Recibiste este correo porque te suscribiste a las ofertas de La 501 Sports.<br>
                <a href="{{ route('newsletter.unsubscribe', $unsubscribeToken) }}" style="color:#71717A;">Dejar de recibir estos correos</a>
            </p>
        </div>
        <div style="background-color:#0f0d0a;padding:16px 32px;text-align:center;">
            <p style="color:#52525B;font-size:11px;margin:0;letter-spacing:.3px;">
                La 501 Sports Restaurant &copy; {{ date('Y') }}
            </p>
        </div>

    </div>

</div>

</body>
</html>
