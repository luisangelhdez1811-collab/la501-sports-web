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

<div class="policy-page">
    <div class="policy-hero">
        <div class="policy-hero-badge">La 501 Sports</div>
        <h1>Aviso de <span>Privacidad</span></h1>
        <p>Conoce cómo protegemos y tratamos tus datos personales de manera segura</p>
    </div>

    <div class="policy-content">
        <div class="policy-card">
            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">Introducción</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Andrés Rivera Jiménez, que opera el restaurante-bar deportivo bajo el nombre comercial “La 501 Sports Restaurant”, es responsable del tratamiento de los datos personales que usted nos proporciona como cliente, visitante, usuario registrado o persona interesada en nuestros servicios.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Este Aviso de Privacidad Integral se pone a su disposición en cumplimiento de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares, publicada en el Diario Oficial de la Federación el 20 de marzo de 2025 y vigente desde el 21 de marzo de 2025, la cual abrogó la ley homónima de 2010, así como en las demás disposiciones reglamentarias y lineamientos que resulten aplicables.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Le recomendamos leer este documento completo antes de proporcionarnos su información. Al final encontrará también la versión simplificada, pensada para mostrarse en los puntos de captura de datos (formularios, registro, checkout).</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">1. Identidad y domicilio del Responsable</h2>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Responsable del tratamiento</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Andrés Rivera Jiménez</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Nombre comercial</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">La 501 Sports Restaurant</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Domicilio para efectos del aviso</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hidalgo, C.P. 43000, México</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Correo para privacidad y derechos ARCO</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Equipolapapa501@gmail.com</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Teléfono de contacto</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">771 109 7827</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Persona o área que atenderá solicitudes</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Encargado de servicio</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">2. Fundamento legal y alcance de este aviso</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Este aviso aplica a los datos personales que recabamos a través de:</p>
            <ul class="list-disc pl-6 mb-4 space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                <li>Nuestro sitio web y sus formularios (registro, reservaciones, pedidos, contacto, facturación, promociones).</li>
                <li>Cualquier aplicación móvil o de escritorio de La 501 Sports que se lance en el futuro, desde el momento de su publicación.</li>
                <li>Nuestro asistente de voz en Alexa y cualquier asistente conversacional o de inteligencia artificial que habilitemos para clientes o personal.</li>
                <li>Interacciones directas: llamadas, WhatsApp, redes sociales o trato en el restaurante, cuando impliquen recabar datos personales.</li>
            </ul>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">El tratamiento se rige por los principios de licitud, finalidad, lealtad, consentimiento, calidad, proporcionalidad, información y responsabilidad previstos en la Ley. Esto significa que solo recabamos los datos necesarios para las finalidades aquí descritas, se lo informamos antes de tratarlos, y usted conserva el control sobre su información en todo momento.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">3. Datos personales que recabamos</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Dependiendo de cómo interactúe con nosotros, podemos recabar los siguientes datos, siempre limitados a lo necesario para cada finalidad:</p>
            
            <div class="overflow-x-auto my-6">
                <table class="w-full text-left border-collapse text-sm text-zinc-700 dark:text-zinc-300">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-white/10 text-orange-500 font-bold font-['Oswald']">
                            <th class="py-2 pr-4">Módulo / servicio</th>
                            <th class="py-2">Datos que se recaban</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Registro e inicio de sesión</td>
                            <td class="py-3">Nombre, correo electrónico, teléfono, contraseña e historial de acceso.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Reservaciones</td>
                            <td class="py-3">Nombre, teléfono, correo electrónico, fecha y hora, número de personas y notas adicionales.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Pedido</td>
                            <td class="py-3">Nombre, datos de contacto, productos solicitados, instrucciones especiales y datos de entrega o número de mesa.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Pagos</td>
                            <td class="py-3">Referencia o folio de pago. La 501 Sports no almacena números completos de tarjeta; su procesamiento corre a cargo del proveedor de la pasarela.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Facturación</td>
                            <td class="py-3">RFC, razón social, régimen fiscal, código postal fiscal, uso de CFDI y correo electrónico.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Contacto</td>
                            <td class="py-3">Nombre, teléfono, correo electrónico, asunto y mensaje.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Promociones</td>
                            <td class="py-3">Correo electrónico o teléfono, únicamente si usted lo autoriza expresamente.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Administrativo</td>
                            <td class="py-3">Nombre, datos de contacto, usuario, rol asignado, PIN de acceso y bitácora de actividad.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Cookies y analítica</td>
                            <td class="py-3">Dirección IP, tipo de dispositivo y navegador, páginas visitadas y preferencias.</td>
                        </tr>
                        <tr>
                            <td class="py-3 pr-4 font-semibold">Asistente virtual/IA</td>
                            <td class="py-3">Contenido de la consulta, utilizado únicamente para generar la respuesta o ejecutar la acción.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">3.1 Datos personales sensibles y patrimoniales</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Como regla general, La 501 Sports no recaba datos personales sensibles (los que, de divulgarse indebidamente, afecten la esfera más íntima de su titular, como origen étnico o racial, estado de salud presente o futuro, información genética, creencias religiosas, filosóficas o morales, afiliación sindical o política, preferencia sexual).</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Excepción – alergias y restricciones alimentarias:</strong> si usted decide compartir voluntariamente información sobre alergias, intolerancias o restricciones alimentarias en el campo de notas de una reservación o pedido, dicho dato se considera sensible por tratarse de información relacionada con su salud. En ese caso solicitaremos su consentimiento expreso y por escrito, lo utilizaremos exclusivamente para la preparación segura de sus alimentos, y limitaremos su acceso al personal de cocina y servicio directamente involucrado.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Los datos financieros o patrimoniales (por ejemplo, RFC y datos de facturación, o el folio de un pago) requieren su consentimiento expreso conforme al Artículo 7 de la Ley, mismo que se recaba mediante una casilla de aceptación al momento de facturar o pagar.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">4. Finalidades del tratamiento</h2>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">4.1 Finalidades necesarias (primarias)</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Son indispensables para la relación que usted tiene con nosotros. Si se opone a ellas, no podremos prestarle el servicio solicitado:</p>
            <ul class="list-disc pl-6 mb-4 space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                <li>Crear y administrar su cuenta de usuario.</li>
                <li>Gestionar reservaciones, pedidos, pagos, entregas o el servicio en mesa.</li>
                <li>Emitir comprobantes fiscales digitales (CFDI) y facturas.</li>
                <li>Atender mensajes, quejas, aclaraciones o solicitudes.</li>
                <li>Enviar confirmaciones y notificaciones operativas.</li>
                <li>Prevenir fraude, abuso, accesos no autorizados y proteger la seguridad del sistema.</li>
                <li>Cumplir obligaciones legales, fiscales, administrativas o contractuales.</li>
                <li>Administrar a los usuarios empleados, sus permisos y las bitácoras de actividad.</li>
                <li>Generar estadísticas y mejorar el servicio de forma anonimizada.</li>
            </ul>

            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">4.2 Finalidades secundarias (voluntarias)</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">No son necesarias para el servicio y usted puede negarse a ellas, ahora o en cualquier momento, sin que esto afecte la relación con La 501 Sports:</p>
            <ul class="list-disc pl-6 mb-4 space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                <li>Enviar promociones, encuestas de satisfacción o comunicaciones comerciales sobre nuestro restaurante.</li>
            </ul>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Si no desea que sus datos se utilicen para esta finalidad secundaria, puede indicarlo escribiendo a Equipolapapa501@gmail.com, o dando de baja su suscripción desde el enlace incluido en cada comunicación.</p>

            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">4.3 Lo que no hacemos con sus datos</h3>
            <ul class="list-disc pl-6 mb-4 space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                <li>No vendemos, rentamos ni compartimos su información con terceros para fines de mercadotecnia ajenos a la operación de La 501 Sports.</li>
                <li>No tomamos decisiones automatizadas que produzcan efectos jurídicos sobre usted sin intervención humana.</li>
                <li>No utilizamos datos de salud (alergias) para ningún fin distinto a la preparación segura de sus alimentos.</li>
            </ul>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">5. Consentimiento</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Regla general (datos de identificación y contacto):</strong> consentimiento tácito, al poner a su disposición este aviso y usted continuar utilizando nuestros servicios.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Datos financieros o patrimoniales:</strong> consentimiento expreso mediante una casilla de aceptación al pagar o facturar.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Datos sensibles (alergias):</strong> consentimiento expreso y por escrito mediante una casilla específica al momento de proporcionarlos.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Finalidades secundarias (promociones):</strong> consentimiento expreso mediante una casilla de opción, nunca premarcada.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">6. Cookies, tecnologías de rastreo e inicio de sesión con terceros</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Nuestro sitio web y aplicaciones futuras utilizan cookies y píxeles para mantener su sesión iniciada, medir el uso de forma estadística, mostrar mapas (Google Maps) e iniciar sesión con Google.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Puede deshabilitar las cookies desde la configuración de su navegador; algunas funciones podrían dejar de funcionar correctamente.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">7. Encargados del tratamiento y transferencias de datos</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">La 501 Sports se apoya en proveedores de tecnología que tratan datos personales por nuestra cuenta y bajo nuestras instrucciones (como Hostinger para base de datos y alojamiento, Mercado Pago para transacciones, y Pusher para notificaciones en tiempo real). Estos proveedores se obligan contractualmente a tratar los datos únicamente para los fines de operación y guardar confidencialidad.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">8. Medios para limitar el uso o divulgación de sus datos</h2>
            <ul class="list-disc pl-6 mb-4 space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                <li>Escribir a Equipolapapa501@gmail.com solicitando la limitación del uso.</li>
                <li>Utilizar el enlace para darse de baja en cada correo promocional.</li>
            </ul>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">9. Derechos ARCO (Acceso, Rectificación, Cancelación y Oposición)</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Usted tiene derecho a conocer qué datos suyos tratamos y para qué (Acceso), a corregirlos cuando sean inexactos o incompletos (Rectificación), a solicitar que los eliminemos (Cancelación), y a oponerse al tratamiento de los mismos para fines específicos (Oposición).</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>¿Cómo ejercer sus derechos ARCO?</strong> Envíe su solicitud a Equipolapapa501@gmail.com incluyendo su nombre completo, documento de identidad, y descripción clara del derecho a ejercer. Responderemos en un plazo máximo de 20 días hábiles.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">10. Revocación del consentimiento</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Usted puede revocar el consentimiento otorgado para el tratamiento de sus datos en cualquier momento y sin efectos retroactivos escribiendo a Equipolapapa501@gmail.com. Para ciertas finalidades necesarias, la revocación podría impedir que continuemos prestándole el servicio.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">11. Medidas de seguridad y procedimiento ante vulneraciones</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Hemos implementado medidas de seguridad administrativas, técnicas y físicas, incluyendo contraseñas y PINs cifrados, accesos restringidos por roles de usuario, y respaldos periódicos. En caso de vulneraciones significativas, le notificaremos sin dilación conforme a la Ley.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">12. Plazos de conservación y eliminación</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Los datos se conservan solo por el tiempo necesario operativo. Por ejemplo, los comprobantes y facturas fiscales se guardan por 5 años conforme al Código Fiscal de la Federación, las cuentas de usuario se conservan activas mientras el usuario no solicite su baja, y los datos de reservaciones/pedidos se conservan durante el tiempo requerido para el servicio y aclaraciones.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">13. Tratamiento de datos personales de menores de edad</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Nuestros servicios de pedidos en línea, registro y pagos están dirigidos a mayores de 18 años. Si un menor asiste al restaurante, sus datos vinculados a la orden (como alergias) deben ser proporcionados por el adulto responsable en su representación.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">14. Canales digitales futuros y asistentes de voz / IA</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Este aviso cubre desde su publicación cualquier aplicación futura y asistentes de voz/IA que implementemos. Las consultas por IA o Alexa solo se usan de forma conversacional operativa y no con fines de elaboración de perfiles comerciales.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">15. Cambios y actualizaciones a este aviso</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Podemos modificar este Aviso de Privacidad. Cualquier cambio sustantivo se anunciará mediante la web o por correo antes de que surta efectos. El uso continuado del servicio implica la aceptación de los cambios.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">16. Autoridad competente y medios de defensa</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Ante cualquier inconformidad, contáctanos primero en Equipolapapa501@gmail.com. De ser necesario, la Secretaría Anticorrupción y Buen Gobierno (SABG) es la autoridad competente en la materia en México, tras las reformas de simplificación orgánica que abrogaron el INAI.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">17. Aceptación</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300 font-medium text-center mt-6">Al proporcionarnos sus datos personales a través de nuestros canales, usted manifiesta que ha leído, entendido y aceptado los términos del presente Aviso de Privacidad Integral.</p>

        </div>
    </div>
</div>

@endsection
