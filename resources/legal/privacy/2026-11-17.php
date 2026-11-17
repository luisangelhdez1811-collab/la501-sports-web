<?php

/*
 * Aviso de Privacidad Integral publicado el 17 de noviembre de 2026. Esta copia NO se edita: si el texto
 * cambia de fondo se crea un archivo con la fecha nueva y se sube privacy_version en config/legal.php
 * (B-32: hay que poder mostrar después la versión exacta que cada persona aceptó). La app usa el mismo
 * texto (src/content/legal.ts) con la misma versión.
 *
 * Bloques: h2, h3, p (con «strong» opcional al inicio), list, table (filas [título, texto]).
 */

$correo = 'Equipolapapa501@gmail.com';

return [
    'title' => 'Aviso de Privacidad',
    'lead' => 'Aviso de',
    'accent' => 'Privacidad',
    'description' => 'Cómo tratamos y protegemos tus datos personales.',
    'updated' => '17 de noviembre de 2026',
    'changes' => [
        'Lista completa de proveedores que tratan sus datos, incluidos los que están fuera de México.',
        'Plazos concretos de conservación y borrado automático.',
        'Las recomendaciones según sus pedidos son voluntarias y puede desactivarlas.',
        'Cookies: solo usamos las técnicas; decimos qué servicios reciben su IP.',
        'Plazos para responder sus derechos ARCO y aviso si hay una vulneración.',
    ],
    'blocks' => [
        ['type' => 'h2', 'text' => 'Introducción'],
        ['type' => 'p', 'text' => 'Andrés Rivera Jiménez, que opera el restaurante-bar deportivo bajo el nombre comercial «La 501 Sports Restaurant», es responsable del tratamiento de los datos personales que usted nos proporciona como cliente, visitante, usuario registrado o persona interesada en nuestros servicios, en el sitio web y en la aplicación móvil.'],
        ['type' => 'p', 'text' => 'Este Aviso de Privacidad Integral se pone a su disposición en cumplimiento de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares, publicada en el Diario Oficial de la Federación el 20 de marzo de 2025, y de las demás disposiciones aplicables. Mientras no se publique su nuevo reglamento, aplicamos el de 2011 en lo que no contradiga a la Ley. Al final encontrará la versión simplificada.'],

        ['type' => 'h2', 'text' => '1. Identidad y domicilio del Responsable'],
        ['type' => 'table', 'rows' => [
            ['Responsable del tratamiento', 'Andrés Rivera Jiménez'],
            ['Nombre comercial', 'La 501 Sports Restaurant'],
            ['Domicilio', 'Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hidalgo, C.P. 43000, México'],
            ['Correo para privacidad y derechos ARCO', $correo],
            ['Teléfono', '771 109 7827'],
            ['Área que atiende solicitudes', 'Encargado de servicio'],
        ]],

        ['type' => 'h2', 'text' => '2. Alcance de este aviso'],
        ['type' => 'p', 'text' => 'Este aviso aplica a los datos personales que recabamos a través de:'],
        ['type' => 'list', 'items' => [
            'Este sitio web: registro, pedidos a domicilio, reservaciones, contacto y promociones.',
            'La aplicación móvil La 501 Sports.',
            'Nuestra skill de Alexa, que solo responde consultas sobre el menú, promociones y datos del restaurante.',
            'Interacciones directas (llamadas, WhatsApp, redes sociales o en el restaurante) cuando impliquen recabar datos personales.',
        ]],
        ['type' => 'p', 'text' => 'Los datos del personal del restaurante se rigen por un aviso aparte, el Aviso de Privacidad para Personal.'],

        ['type' => 'h2', 'text' => '3. Datos personales que recabamos'],
        ['type' => 'p', 'text' => 'Solo recabamos lo necesario para cada finalidad:'],
        ['type' => 'table', 'rows' => [
            ['Registro e inicio de sesión', 'Nombre, correo electrónico, teléfono, contraseña (guardada cifrada), confirmación de mayoría de edad, versión del aviso y de los términos que aceptó y fecha de aceptación.'],
            ['Foto de perfil (opcional)', 'La imagen que usted elija subir.'],
            ['Pedido a domicilio', 'Nombre, teléfono, correo (opcional), dirección de entrega, ubicación GPS solo si usted decide compartirla, productos con sus opciones, ingredientes excluidos, notas, y la fecha en que aceptó el cobro del envío, confirmó su mayoría de edad (si pidió alcohol) y autorizó el uso de sus notas.'],
            ['Pagos', 'Forma de pago y número de operación de Mercado Pago. Nunca recibimos ni guardamos los datos de su tarjeta: el pago se hace en la página segura de Mercado Pago.'],
            ['Cancelaciones y devoluciones', 'Fecha, motivo (si lo indica) y estado de la devolución.'],
            ['Reservaciones', 'Nombre, teléfono, correo electrónico, fecha y hora, número de personas y zona.'],
            ['Contacto', 'Nombre, teléfono, correo electrónico, tipo de mensaje, asunto y mensaje.'],
            ['Promociones', 'Correo electrónico, solo si usted lo autoriza.'],
            ['Factura', 'RFC, nombre o razón social, régimen fiscal, código postal y uso del CFDI, solo si nos los envía para pedir factura.'],
            ['Uso del sitio', 'Dirección IP y registros técnicos del servidor (fecha, página visitada, errores), necesarios para la seguridad del sitio.'],
        ]],
        ['type' => 'h3', 'text' => '3.1 Datos sensibles'],
        ['type' => 'p', 'strong' => 'Alergias y restricciones alimentarias. ', 'text' => 'No pedimos datos sensibles. Si usted escribe alergias u otros datos de salud en las notas de un pedido o reservación, le pedimos autorizarlo de forma expresa con una casilla. Los usamos solo para preparar sus alimentos de forma segura y solo los ve el personal de cocina y servicio que atiende su pedido.'],
        ['type' => 'p', 'text' => 'No recabamos datos patrimoniales ni financieros: el pago con tarjeta lo procesa Mercado Pago.'],

        ['type' => 'h2', 'text' => '4. Finalidades del tratamiento'],
        ['type' => 'h3', 'text' => '4.1 Finalidades necesarias'],
        ['type' => 'p', 'text' => 'Son indispensables para darle el servicio. Si se opone a ellas, no podremos atender su pedido, reservación o cuenta:'],
        ['type' => 'list', 'items' => [
            'Crear y administrar su cuenta y sus puntos.',
            'Recibir, preparar, cobrar y entregar sus pedidos, y atender cancelaciones y devoluciones.',
            'Gestionar sus reservaciones.',
            'Enviarle avisos sobre su pedido o reservación y códigos de verificación.',
            'Atender mensajes, quejas, aclaraciones y solicitudes de derechos ARCO.',
            'Emitir su factura cuando la solicite.',
            'Prevenir fraude, abuso y accesos no autorizados.',
            'Cumplir obligaciones legales, fiscales y administrativas.',
        ]],
        ['type' => 'h3', 'text' => '4.2 Finalidades voluntarias'],
        ['type' => 'p', 'text' => 'No son necesarias para el servicio. Puede negarse a ellas en cualquier momento sin que esto afecte su relación con nosotros:'],
        ['type' => 'list', 'items' => [
            'Enviarle promociones y novedades por correo.',
            'Recomendarle productos según sus pedidos anteriores. El cálculo se hace en nuestro propio servidor; sin este permiso, ve las mismas sugerencias que cualquier visitante.',
        ]],
        ['type' => 'p', 'text' => "Para negarse: en el sitio web, en Configuración → Privacidad; en la app, en Mis datos y privacidad → Oposición; con el enlace de baja de cada correo de promociones; o escribiendo a {$correo}."],
        ['type' => 'h3', 'text' => '4.3 Lo que no hacemos'],
        ['type' => 'list', 'items' => [
            'No vendemos, rentamos ni compartimos sus datos para publicidad de terceros.',
            'No tomamos decisiones que le produzcan efectos jurídicos de forma automatizada, sin intervención humana.',
            'No usamos datos de salud para nada distinto a preparar sus alimentos.',
            'No enviamos promociones por SMS.',
        ]],

        ['type' => 'h2', 'text' => '5. Consentimiento'],
        ['type' => 'list', 'items' => [
            'Cuenta: al registrarse marca una casilla, nunca premarcada, aceptando este aviso y los Términos y Condiciones. Guardamos la fecha y la versión que aceptó.',
            'Promociones: casilla separada y opcional, nunca premarcada.',
            'Notas con datos de salud: casilla expresa en cada pedido que lleve notas.',
            'Mayoría de edad: se confirma al crear la cuenta y, de nuevo, al pedir bebidas con alcohol.',
            'Si cambiamos este aviso de fondo, le pediremos aceptar la versión nueva antes de seguir usando su cuenta.',
        ]],

        ['type' => 'h2', 'id' => 'cookies', 'text' => '6. Cookies y servicios de terceros en el sitio'],
        ['type' => 'p', 'text' => 'Este sitio usa solo cookies técnicas, indispensables para que funcione: la de su sesión (para mantenerlo identificado y guardar su carrito) y la de protección contra falsificación de solicitudes (XSRF-TOKEN). No usamos cookies de analítica, publicidad ni píxeles de rastreo, por lo que no necesitamos pedirle permiso para cookies.'],
        ['type' => 'p', 'text' => 'Su navegador guarda localmente algunas preferencias, como el tema claro u oscuro. Esa información no se envía a nuestro servidor.'],
        ['type' => 'p', 'text' => 'Al cargar el sitio, estos servicios reciben su dirección IP y datos técnicos de su navegador para entregar su contenido:'],
        ['type' => 'list', 'items' => [
            'Google Fonts, que entrega las tipografías.',
            'Google Maps, solo en la página de Ubicación.',
            'Cloudinary, que entrega las imágenes del menú y de las promociones.',
            'Las redes de distribución unpkg y Tailwind, que entregan parte del código del sitio.',
            'Pusher, que actualiza el menú en tiempo real.',
            'Mercado Pago, solo cuando usted elige pagar con tarjeta.',
        ]],
        ['type' => 'p', 'text' => 'Si algún día agregamos herramientas de analítica o publicidad, primero le pediremos su permiso y actualizaremos este aviso.'],

        ['type' => 'h2', 'text' => '7. Encargados y transferencias'],
        ['type' => 'p', 'text' => 'Estos proveedores tratan datos por nuestra cuenta, solo para operar el restaurante y bajo obligación de confidencialidad. Algunos guardan o procesan la información fuera de México (principalmente en Estados Unidos).'],
        ['type' => 'table', 'rows' => [
            ['Hostinger', 'Servidor donde se guardan cuentas, pedidos, reservaciones y mensajes.'],
            ['Mercado Pago', 'Cobro con tarjeta y devoluciones.'],
            ['Twilio', 'Envío de códigos de verificación por SMS.'],
            ['Google', 'Envío de correos, mapas y tipografías.'],
            ['Pusher', 'Avisos en tiempo real del menú y para el personal.'],
            ['Cloudinary', 'Almacenamiento de imágenes (menú, promociones y fotos de perfil).'],
            ['Amazon (Alexa)', 'Skill de voz que consulta el menú y las promociones; no recaba datos personales.'],
        ]],
        ['type' => 'p', 'text' => 'No transferimos sus datos a terceros, salvo a las autoridades cuando una ley o una orden lo exija.'],

        ['type' => 'h2', 'text' => '8. Cómo limitar el uso o divulgación de sus datos'],
        ['type' => 'list', 'items' => [
            'Desactive promociones y recomendaciones en Configuración (web) o en Mis datos y privacidad (app).',
            'Use el enlace de baja de cualquier correo de promociones; funciona sin iniciar sesión y se aplica de inmediato.',
            "Escriba a {$correo} pidiendo la limitación.",
        ]],

        ['type' => 'h2', 'text' => '9. Derechos ARCO'],
        ['type' => 'p', 'text' => 'Usted puede conocer qué datos suyos tratamos (Acceso), corregirlos (Rectificación), pedir que los eliminemos (Cancelación) y oponerse a su uso para fines específicos, incluidas las decisiones automatizadas (Oposición).'],
        ['type' => 'h3', 'text' => 'Por su cuenta, en cualquier momento'],
        ['type' => 'table', 'rows' => [
            ['Acceso', 'Descargue una copia completa de sus datos en Configuración (web) o Mis datos y privacidad (app).'],
            ['Rectificación', 'Corrija su nombre, correo, teléfono o foto de perfil en las mismas pantallas.'],
            ['Cancelación', 'Elimine su cuenta desde Configuración, desde la app o en la página «Eliminar mi cuenta».'],
            ['Oposición', 'Active o desactive promociones y recomendaciones.'],
        ]],
        ['type' => 'h3', 'text' => 'Por correo'],
        ['type' => 'p', 'text' => "Envíe su solicitud a {$correo} con: su nombre y un medio para responderle; un documento que acredite su identidad (o la de su representante y el poder correspondiente); el derecho que quiere ejercer y los datos a los que se refiere; y, si pide una corrección, el dato correcto y su respaldo."],
        ['type' => 'p', 'text' => 'Le responderemos en un máximo de 20 días hábiles y, si procede, lo haremos efectivo en los 15 días hábiles siguientes. El trámite es gratuito; solo podríamos cobrar el costo de reproducción o envío si pide copias en un medio físico.'],

        ['type' => 'h2', 'text' => '10. Revocación del consentimiento'],
        ['type' => 'p', 'text' => "Puede revocar su consentimiento en cualquier momento, sin efectos retroactivos, eliminando su cuenta o escribiendo a {$correo}. Si revoca el consentimiento de una finalidad necesaria, ya no podremos darle ese servicio."],

        ['type' => 'h2', 'text' => '11. Medidas de seguridad y vulneraciones'],
        ['type' => 'p', 'text' => 'Aplicamos medidas administrativas, técnicas y físicas: conexión cifrada (HTTPS), contraseñas cifradas, acceso restringido por rol, límites de intentos, respaldos protegidos y borrado automático de datos al cumplir su plazo.'],
        ['type' => 'p', 'text' => 'Si ocurre una vulneración que afecte de forma significativa sus derechos, le avisaremos sin demora por correo o en la app, con lo que pasó, los datos afectados, lo que hicimos y lo que usted puede hacer para protegerse.'],

        ['type' => 'h2', 'text' => '12. Plazos de conservación'],
        ['type' => 'p', 'text' => 'Conservamos cada dato solo el tiempo necesario. Al cumplirse el plazo, lo borramos de forma automática:'],
        ['type' => 'table', 'rows' => [
            ['Cuenta', 'Mientras esté activa. Al eliminarla se borran sus datos de identificación y sus pedidos quedan sin nombre, teléfono, correo, dirección ni notas.'],
            ['Pedidos y comprobantes', '10 años, por las obligaciones de conservación del Código de Comercio (art. 49) y del Código Fiscal de la Federación (art. 30).'],
            ['Ubicación GPS de la entrega', '30 días después de entregado o cancelado el pedido.'],
            ['Notas del pedido', '90 días.'],
            ['Reservaciones', '1 año.'],
            ['Mensajes de contacto', '1 año.'],
            ['Códigos de verificación', 'Hasta que vencen o se usan.'],
            ['Registros técnicos del servidor', '14 días.'],
            ['Respaldos de la base de datos', '30 días.'],
        ]],
        ['type' => 'p', 'text' => 'Mientras un dato se conserva solo para cumplir una obligación legal, queda bloqueado: no se usa para ninguna otra finalidad.'],

        ['type' => 'h2', 'text' => '13. Menores de edad'],
        ['type' => 'p', 'text' => 'El registro, los pedidos en línea y los pagos son solo para mayores de 18 años. Las bebidas con alcohol solo se venden a mayores de edad y al entregarlas se pide una identificación oficial. Si un dato de un menor va ligado a un pedido (por ejemplo, una alergia), debe proporcionarlo el adulto responsable.'],

        ['type' => 'h2', 'text' => '14. Cambios a este aviso'],
        ['type' => 'p', 'text' => 'Si cambiamos este aviso de fondo, publicaremos la versión nueva con su fecha en el sitio y en la app, se lo avisaremos y le pediremos aceptarla al iniciar sesión. Conservamos cada versión publicada.'],

        ['type' => 'h2', 'text' => '15. Autoridad competente'],
        ['type' => 'p', 'text' => "Si tiene una inconformidad, escríbanos primero a {$correo}. La autoridad en materia de protección de datos personales en México es la Secretaría Anticorrupción y Buen Gobierno."],

        ['type' => 'h2', 'id' => 'simplificado', 'text' => 'Aviso de Privacidad Simplificado'],
        ['type' => 'p', 'text' => "La 501 Sports Restaurant (Andrés Rivera Jiménez), con domicilio en Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hidalgo, usará sus datos para atender sus pedidos, reservaciones, cuenta y mensajes, y, solo si usted lo autoriza, para enviarle promociones y recomendaciones. Puede negarse a estas últimas en Configuración o escribiendo a {$correo}. Consulte el aviso completo en esta misma página."],
    ],
];
