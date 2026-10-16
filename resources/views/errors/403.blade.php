<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISTEMA RASP - BLOQUEO</title>
    <style>
        body { background: #000; color: #ff0000; font-family: 'Courier New', monospace; text-align: center; padding-top: 10%; }
        .alert-box { border: 2px solid #ff0000; display: inline-block; padding: 20px; background: #1a0000; }
        h1 { font-size: 50px; margin-bottom: 0; }
        p { font-size: 20px; color: #fff; }
        .details { color: #888; font-size: 14px; margin-top: 20px; }
    </style>
</head>
<body style="background:#000; color:#f00; font-family:monospace; padding:50px; text-align:center;">
    <div style="border:5px solid #f00; padding:20px; display:inline-block;">
        <h1 style="font-size:40px;">⚠️ ATAQUE DETECTADO ⚠️</h1>
        <p style="color:#fff; font-size:20px;">El agente RASP ha bloqueado esta petición en tiempo real.</p>
        <p style="color:#666;">ID de Evento: {{ rand(1000, 9999) }} | Host: Hostinger_501</p>
    </div>
</body>
</html>
