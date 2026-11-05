@extends('layouts.app')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;600;700&family=Inter:wght@400;500;600&display=swap');

.policy-page {
    --or:  #F97316; --or2: #EA580C;
    --bg:      #0A0A0A;
    --card:    #141414;
    --txt:     #FFFFFF;
    --sub:     #A1A1AA;
    --bdr:     rgba(255,255,255,.07);
    --glow:    rgba(249,115,22,.18);
    --hex: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49'%3E%3Cg fill='%23ffffff' fill-opacity='0.025'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15L13.99 9.25z'/%3E%3C/g%3E%3C/svg%3E");
}
html:not(.dark) .policy-page {
    --bg:   #F5F3EF; --card: #FFFFFF;
    --txt:  #18181B; --sub:  #71717A;
    --bdr:  rgba(0,0,0,.09); --glow: rgba(249,115,22,.10);
    --hex: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49'%3E%3Cg fill='%23000000' fill-opacity='0.025'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15L13.99 9.25z'/%3E%3C/g%3E%3C/svg%3E");
}

.policy-page {
    background: var(--bg);
    color: var(--txt);
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    transition: background .3s, color .3s;
}

.policy-hero {
    position: relative;
    background: var(--card);
    background-image: var(--hex);
    border-bottom: 3px solid var(--or);
    padding: 64px 32px 56px;
    text-align: center;
    overflow: hidden;
    transition: background .3s;
}
.policy-hero::before {
    content: '';
    position: absolute;
    top: -80px; left: 50%; transform: translateX(-50%);
    width: 600px; height: 300px;
    background: radial-gradient(ellipse, var(--glow) 0%, transparent 70%);
    pointer-events: none;
}
.policy-hero-badge {
    display: inline-block;
    background: var(--or); color: #fff;
    font-family: 'Oswald', sans-serif;
    font-size: 10px; font-weight: 700;
    letter-spacing: 3px; text-transform: uppercase;
    padding: 5px 18px;
    clip-path: polygon(10px 0%,100% 0%,calc(100% - 10px) 100%,0% 100%);
    box-shadow: 0 4px 15px var(--glow);
    margin-bottom: 16px;
}
.policy-hero h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(38px, 6vw, 64px);
    letter-spacing: 3px;
    color: var(--txt);
    margin: 0; line-height: 1;
}
.policy-hero h1 span { color: var(--or); }
.policy-hero p {
    font-size: 14px; color: var(--sub);
    margin: 8px 0 0; line-height: 1.5;
}

.policy-content {
    max-width: 900px;
    margin: 0 auto;
    padding: 48px 24px 96px;
}
.policy-card {
    background: var(--card);
    border: 1px solid var(--bdr);
    border-radius: 20px;
    padding: 40px 48px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    transition: background .3s, border-color .3s;
}

@media (max-width: 640px) {
    .policy-hero { padding: 48px 20px 40px; }
    .policy-card { padding: 24px 20px; }
}
</style>

@php
    $h2 = "text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2";
    $p  = 'mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300';
    $ul = 'list-disc pl-6 mb-4 space-y-2 text-sm text-zinc-700 dark:text-zinc-300';
@endphp

<div class="policy-page">

    <div class="policy-hero">
        <div class="policy-hero-badge">La 501 Sports</div>
        <h1>Eliminar <span>mi cuenta</span></h1>
        <p>Cómo borrar tu cuenta y tus datos de La 501 Sports, con o sin la app instalada</p>
    </div>

    <div class="policy-content">
        <div class="policy-card">

            <p class="{{ $p }}">
                Esta página es pública: puedes leerla sin iniciar sesión. Aplica a la cuenta de <strong>La 501 Sports</strong>
                (aplicación móvil y sitio web), que es la misma. Eliminar tu cuenta es tu derecho de <strong>Cancelación</strong>
                conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.
            </p>

            <h2 class="{{ $h2 }}">1. Cómo eliminar tu cuenta</h2>

            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Desde la app (inmediato)</h3>
            <p class="{{ $p }}">Abre la app de La 501 Sports, entra a tu perfil, ve a <strong>Configuración</strong> y elige <strong>Eliminar mi cuenta</strong>. Para confirmar que eres tú te pediremos tu contraseña y que escribas la palabra ELIMINAR.</p>

            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Desde el sitio web (inmediato)</h3>
            <p class="{{ $p }}">
                Inicia sesión en <a href="{{ route('login') }}" class="text-orange-500 underline">nuestro sitio</a>, entra a
                <a href="{{ route('configuracion') }}" class="text-orange-500 underline">Configuración</a> y usa
                <strong>Eliminar mi cuenta</strong>. Escribe tu contraseña y la palabra ELIMINAR para confirmar.
            </p>

            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Por correo (si ya no puedes entrar a tu cuenta)</h3>
            <p class="{{ $p }}">
                Escribe a <a href="mailto:Equipolapapa501@gmail.com?subject=Eliminar%20mi%20cuenta" class="text-orange-500 underline">Equipolapapa501@gmail.com</a>
                desde el correo con el que te registraste, con el asunto «Eliminar mi cuenta», o llama al 771 109 7827.
                Podemos pedirte datos para comprobar que la cuenta es tuya. Responderemos en un máximo de <strong>20 días hábiles</strong>.
            </p>

            <h2 class="{{ $h2 }}">2. Qué se borra</h2>
            <p class="{{ $p }}">Al eliminar tu cuenta se borran de forma definitiva:</p>
            <ul class="{{ $ul }}">
                <li>Tu nombre, correo electrónico, teléfono y contraseña.</li>
                <li>Tu foto de perfil, tu dirección favorita y tus preferencias (recomendaciones y promociones por correo).</li>
                <li>Tus puntos y logros.</li>
                <li>Tu suscripción a promociones.</li>
                <li>Todas tus sesiones abiertas en la app y en el sitio: se cierran en todos tus dispositivos.</li>
            </ul>

            <h2 class="{{ $h2 }}">3. Qué se anonimiza</h2>
            <p class="{{ $p }}">Estos registros se conservan, pero <strong>sin ningún dato que te identifique</strong>:</p>
            <ul class="{{ $ul }}">
                <li>Tus pedidos: se quitan tu nombre, teléfono, correo, dirección de entrega, ubicación y las notas que hayas escrito (que podían incluir alergias u otros datos de salud).</li>
                <li>Tus reservaciones y mensajes de contacto hechos con tu correo: quedan como «Cliente eliminado».</li>
            </ul>

            <h2 class="{{ $h2 }}">4. Qué se conserva y por cuánto tiempo</h2>
            <p class="{{ $p }}">
                Por obligaciones fiscales y contables, del pedido se conservan el folio, los productos, los montos, las fechas, la forma de pago y el número de operación del pago durante
                <strong>5 años</strong>. Sin tu nombre ni ningún otro dato personal, ya no es información tuya y no se puede relacionar contigo.
            </p>
            <p class="{{ $p }}">
                Si pagaste con tarjeta, <strong>Mercado Pago</strong> conserva por su cuenta la información que la ley le obliga a guardar, conforme a su propio aviso de privacidad. La 501 Sports nunca almacena el número de tu tarjeta.
            </p>

            <h2 class="{{ $h2 }}">5. Antes de eliminar</h2>
            <ul class="{{ $ul }}">
                <li>La eliminación <strong>no se puede deshacer</strong>: perderás tus puntos y no podremos recuperar tu cuenta.</li>
                <li>Si quieres una copia de tus datos, puedes descargarla antes en <a href="{{ route('configuracion') }}" class="text-orange-500 underline">Configuración</a> (derecho de Acceso).</li>
                <li>Puedes volver a registrarte cuando quieras con una cuenta nueva.</li>
            </ul>

            <h2 class="{{ $h2 }}">6. Más información</h2>
            <p class="{{ $p }}">
                Consulta cómo tratamos tus datos en el <a href="{{ route('aviso-privacidad') }}" class="text-orange-500 underline">Aviso de Privacidad</a>
                y las reglas del servicio en los <a href="{{ route('terminos-condiciones') }}" class="text-orange-500 underline">Términos y Condiciones</a>.
            </p>

        </div>
    </div>
</div>

@endsection
