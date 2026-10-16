<body style="background:#000; color:#fff; font-family:sans-serif; padding:50px; text-align:center;">
    <div style="border:2px solid #3498db; padding:30px; display:inline-block; border-radius:15px;">
        <h1 style="color:#3498db; font-size:50px; margin:0;">⏳ 419</h1>
        <h2 style="text-transform:uppercase;">Tu sesión se tomó un descanso</h2>
        <p style="font-size:18px;">Por tu seguridad, el formulario de pedido ha expirado.</p>
        <p style="color:#888;">Solo necesitas refrescar la página para continuar con tu orden.</p>
        <button onclick="window.location.href = window.location.pathname.endsWith('/logout') ? (window.location.origin + window.location.pathname.replace(/\/logout$/, '/')) : window.location.href;" style="background:#3498db; color:#fff; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; font-weight:bold;">REFRESCAR AHORA</button>
    </div>
</body>
