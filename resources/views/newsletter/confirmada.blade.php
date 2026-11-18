<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suscripción a promociones - La 501 Sports</title>
    <style>
        body { margin:0; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#0f0d0a; color:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; box-sizing:border-box; }
        .card { max-width:420px; text-align:center; background:#161310; border:1px solid rgba(255,255,255,.08); border-radius:16px; padding:40px 32px; }
        h1 { font-size:20px; margin:0 0 10px; }
        p { color:#A8A29E; font-size:14px; line-height:1.6; margin:0 0 24px; }
        a { display:inline-block; background:#C2410C; color:#fff; text-decoration:none; font-weight:700; font-size:13px; letter-spacing:1px; text-transform:uppercase; padding:12px 24px; border-radius:10px; }
    </style>
</head>
<body>
    <div class="card">
        @if($success)
            <h1>Suscripción confirmada</h1>
            <p>Te avisaremos por correo cuando haya nuevas promociones. Puedes darte de baja desde el enlace de cada correo.</p>
        @else
            <h1>Este enlace ya no es válido</h1>
            <p>Es posible que ya hayas confirmado tu suscripción. Si no, vuelve a inscribirte desde la página de promociones.</p>
        @endif
        <a href="{{ route('promociones') }}">Ver promociones</a>
    </div>
</body>
</html>
