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
        <h1>Términos y <span>Condiciones</span></h1>
        <p>Reglas de uso del sitio web, pedidos, reservaciones y servicios digitales</p>
    </div>

    <div class="policy-content">
        <div class="policy-card">

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">1. Identidad del proveedor</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Los presentes Términos y Condiciones regulan el uso del sitio web, los servicios de pedidos y reservaciones en línea, y los demás servicios digitales del restaurante-bar deportivo “La 501 Sports Restaurant”, operado por:</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Nombre comercial</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">La 501 Sports Restaurant</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Responsable / titular</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Andrés Rivera Jiménez</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Domicilio</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hidalgo, C.P. 43000, México</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Correo de contacto</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Equipolapapa501@gmail.com</p>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Teléfono / WhatsApp</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">771 109 7827</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">2. Aceptación y alcance</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Al acceder al sitio, crear una cuenta, realizar un pedido o una reservación, usted acepta estos Términos en su totalidad. Si no está de acuerdo con ellos, le pedimos abstenerse de utilizar nuestros servicios digitales.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">El sitio puede ser consultado por cualquier persona; sin embargo, la creación de cuentas, los pedidos en línea, los pagos y la facturación están dirigidos a personas mayores de 18 años con capacidad legal para contratar.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Estos Términos cubren la consulta del menú, el registro de cuentas, las reservaciones, los pedidos para consumo en mesa o para recoger, los pagos, la facturación, las promociones y el formulario de contacto.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Estos Términos aplicarán igualmente a la aplicación móvil o de escritorio de La 501 Sports que, en su caso, se publique en el futuro, así como a los asistentes de voz o de inteligencia artificial que habilitemos para clientes.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">El tratamiento de sus datos personales se rige por nuestro Aviso de Privacidad, disponible en el sitio web, que forma parte integral de estos Términos.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">3. Cuentas de usuario</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Para crear una cuenta debe ser mayor de 18 años y proporcionar información veraz, exacta y actualizada. Usted es responsable de la veracidad de los datos que registre.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Usted es responsable de mantener la confidencialidad de su contraseña y de toda actividad realizada desde su cuenta. Si detecta un uso no autorizado, deberá notificarlo de inmediato a Equipolapapa501@gmail.com.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Podemos suspender o cancelar cuentas que incumplan estos Términos, presenten actividad fraudulenta, suplanten la identidad de terceros o abusen del sistema (por ejemplo, reservaciones o pedidos falsos reiterados).</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Usted puede solicitar la baja de su cuenta en cualquier momento escribiendo al correo de contacto; la eliminación de sus datos se realizará conforme al Aviso de Privacidad.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">4. Menú y disponibilidad</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300 font-semibold">El menú, sus precios y presentaciones pueden cambiar sin previo aviso; el precio aplicable será el vigente al momento de confirmar su pedido.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Los platillos y bebidas están sujetos a disponibilidad. Si un producto se agota después de confirmado su pedido, se lo notificaremos para ofrecerle una sustitución equivalente o la cancelación de ese producto con el ajuste o reembolso correspondiente.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Las fotografías del menú son ilustrativas; la presentación real del platillo puede variar razonablemente sin que ello constituya un incumplimiento.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">5. Precios e impuestos</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Todos los precios se expresan en pesos mexicanos (MXN) e incluyen el Impuesto al Valor Agregado (IVA), salvo que se indique expresamente lo contrario.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Hacemos esfuerzos razonables por mantener los precios actualizados. En caso de un error evidente de captura o de sistema (por ejemplo, un precio notoriamente desproporcionado), podremos cancelar el pedido afectado antes de su preparación, reembolsando cualquier cargo realizado.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">6. Confirmación de Pedidos</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Un pedido se considera confirmado cuando el sistema le muestra o envía la confirmación correspondiente (en pantalla, por correo o por el canal utilizado) y, en su caso, se acredita el pago.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Nos reservamos el derecho de rechazar o cancelar pedidos por causas justificadas: indisponibilidad de producto, errores evidentes de precio, sospecha razonable de fraude, datos de contacto incorrectos o imposibilidad de validar el pago.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">7. Formas de pago</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Aceptamos pago en efectivo en el restaurante, tarjeta de crédito o débito, y en su caso, pago en línea a través de la pasarela de pago habilitada en el sitio (Mercado Pago).</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Los pagos con tarjeta y en línea son procesados por un proveedor de pagos externo; La 501 Sports no almacena los números completos de su tarjeta. La seguridad de la transacción se rige también por los términos del proveedor.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">8. Propinas</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Las propinas son voluntarias y no están incluidas en los precios del menú ni en la cuenta.</strong> Conforme a la normativa de protección al consumidor en México, ningún cargo por propina puede exigirse ni agregarse a la cuenta sin su consentimiento.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">9. Servicio en mesa y recolección</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Los pedidos en línea están disponibles para consumo en mesa dentro del restaurante o para recoger en el establecimiento (Pick Up). Los tiempos de preparación mostrados son estimados y pueden variar según la demanda del restaurante.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">10. Cancelaciones de pedidos</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Usted puede cancelar un pedido sin costo mientras este no haya entrado a preparación en cocina —es decir, antes de que su estatus cambie a “En cocina”—. Una vez iniciada la preparación, el pedido no podrá cancelarse por tratarse de alimentos perecederos elaborados al momento.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">11. Cambios, devoluciones y reembolsos</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Si recibe un producto incorrecto, incompleto o en mal estado, repórtelo de inmediato al personal. En casos procedentes, ofreceremos, a su elección: la reposición del platillo, un ajuste en la cuenta, o la devolución de la cantidad pagada.</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Por tratarse de alimentos perecederos elaborados al momento y entregados para consumo inmediato, no aplica un derecho general de retracto o devolución por cambio de opinión una vez que el producto fue entregado y aceptado en buen estado.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">12. Reservaciones</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Tolerancia:</strong> la mesa se mantendrá reservada durante un periodo de tolerancia de 20 minutos contados a partir de la hora reservada; transcurrido este plazo, la reservación podrá ser cancelada de forma automática (No-Show).</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Grupos:</strong> para reservaciones de 10 personas o más, o eventos especiales, podremos solicitar confirmación previa o un depósito en garantía.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">13. Promociones</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Las promociones tienen una vigencia determinada y aplican únicamente durante el periodo publicado y hasta agotar existencias. No son acumulables entre sí ni canjeables por dinero en efectivo.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">14. Alergias y preferencias alimentarias</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Nuestra cocina maneja múltiples ingredientes (incluyendo gluten, lácteos, huevo, mariscos, frutos secos y picantes) en un mismo espacio, por lo que no podemos garantizar la ausencia total de trazas por contacto cruzado. La información de alergias se trata de forma confidencial y se usa únicamente para la preparación segura de sus alimentos.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">15. Facturación</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">La factura (CFDI) debe solicitarse proporcionando sus datos fiscales completos dentro del mismo mes calendario en que se realizó el consumo, conforme a las disposiciones fiscales vigentes en México.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">16. Uso permitido del sitio y conductas prohibidas</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Queda prohibido realizar pedidos o reservaciones falsas, intentar vulnerar la seguridad del sitio, o usar los servicios para actividades ilícitas. El incumplimiento derivará en la suspensión o cancelación de la cuenta y las acciones legales pertinentes.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">17. Propiedad intelectual</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">El logotipo, el nombre comercial “La 501 Sports Restaurant”, los textos, fotografías y el menú son propiedad del Proveedor y están protegidos por las leyes de propiedad intelectual en México. Queda prohibida su reproducción sin consentimiento.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">18. Atención de quejas y aclaraciones</h2>
            <h3 class="text-md font-bold text-zinc-800 dark:text-zinc-200 mt-6 mb-2 font-['Oswald']">Canales de soporte</h3>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Teléfono / WhatsApp:</strong> 771 109 7827  ·  <strong>Correo:</strong> Equipolapapa501@gmail.com</p>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"><strong>Horario de atención:</strong> Todos los días de 1:00 p.m. a 10:00 p.m.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">19. Legislación aplicable</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">Estos Términos se rigen por las leyes de los Estados Unidos Mexicanos, en particular por la Ley Federal de Protección al Consumidor y el Código de Comercio. Para controversias judiciales, las partes se someten a los tribunales competentes en Huejutla de Reyes, Hidalgo.</p>

            <h2 class="text-xl font-bold text-orange-500 mt-8 mb-4 font-['Oswald'] border-b border-orange-500/10 pb-2">20. Aceptación</h2>
            <p class="mb-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300 font-medium text-center mt-6">Al utilizar el sitio web, realizar un pedido o una reservación con La 501 Sports Restaurant, usted declara haber leído, entendido y aceptado los presentes Términos y Condiciones.</p>

        </div>
    </div>
</div>

@endsection
