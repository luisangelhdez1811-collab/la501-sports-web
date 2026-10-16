<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Baja de promociones - La 501 Sports</title>
    <style>
        body { margin:0; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#0f0d0a; color:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; box-sizing:border-box; }
        .card { max-width:420px; text-align:center; background:#161310; border:1px solid rgba(255,255,255,.08); border-radius:16px; padding:40px 32px; }
        .icon { font-size:48px; margin-bottom:16px; display:block; }
        h1 { font-size:20px; margin:0 0 10px; }
        p { color:#A8A29E; font-size:14px; line-height:1.6; margin:0 0 24px; }
        a { display:inline-block; background:#F97316; color:#fff; text-decoration:none; font-weight:700; font-size:13px; letter-spacing:1px; text-transform:uppercase; padding:12px 24px; border-radius:10px; }
    </style>
</head>
<body>
    <div class="card">
        @if($success)
            <h1>Listo, ya no recibirás más correos de promociones</h1>
            <p>Puedes volver a suscribirte cuando quieras desde nuestra página de promociones.</p>
        @else
            <h1>Este enlace ya no es válido</h1>
            <p>Es posible que ya te hayas dado de baja anteriormente.</p>
        @endif
        <a href="{{ url('/') }}">Volver al inicio</a>
    </div>
</body>
</html>
