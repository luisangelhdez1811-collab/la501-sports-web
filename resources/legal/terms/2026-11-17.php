<?php

/*
 * Términos y Condiciones publicados el 17 de noviembre de 2026. Esta copia NO se edita: si el texto cambia
 * de fondo se crea un archivo con la fecha nueva y se sube terms_version en config/legal.php (B-32). La app
 * usa el mismo texto con la misma versión.
 */

$correo = 'Equipolapapa501@gmail.com';

return [
    'title' => 'Términos y Condiciones',
    'lead' => 'Términos y',
    'accent' => 'Condiciones',
    'description' => 'Reglas de uso del sitio web, la app, los pedidos y las reservaciones.',
    'updated' => '17 de noviembre de 2026',
    'changes' => [
        'Reglas del programa de puntos.',
        'Cómo pedir factura de un pedido en línea.',
        'Venta de alcohol solo cuando la licencia lo permite y nunca en ley seca.',
        'Plazo para resolver cambios y devoluciones, y sin depósito en reservaciones en línea.',
        'Teléfonos de la PROFECO.',
    ],
    'blocks' => [
        ['type' => 'h2', 'text' => '1. Identidad del proveedor'],
        ['type' => 'p', 'text' => 'Estos Términos regulan el uso del sitio web, la aplicación móvil, los pedidos y reservaciones en línea y los demás servicios digitales de «La 501 Sports Restaurant», operado por:'],
        ['type' => 'table', 'rows' => [
            ['Nombre comercial', 'La 501 Sports Restaurant'],
            ['Titular', 'Andrés Rivera Jiménez'],
            ['Domicilio', 'Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hidalgo, C.P. 43000, México'],
            ['Correo', $correo],
            ['Teléfono / WhatsApp', '771 109 7827'],
        ]],

        ['type' => 'h2', 'text' => '2. Aceptación y alcance'],
        ['type' => 'p', 'text' => 'Al crear una cuenta, hacer un pedido o una reservación, usted acepta estos Términos. Si no está de acuerdo, le pedimos no usar nuestros servicios digitales. El menú puede consultarse sin cuenta.'],
        ['type' => 'p', 'text' => 'Las cuentas, los pedidos en línea y los pagos son solo para mayores de 18 años con capacidad legal para contratar. El tratamiento de sus datos se rige por nuestro Aviso de Privacidad.'],

        ['type' => 'h2', 'text' => '3. Cuentas de usuario'],
        ['type' => 'list', 'items' => [
            'Debe proporcionar información verdadera y mantenerla actualizada.',
            "Usted cuida la confidencialidad de su contraseña. Si detecta un uso no autorizado, avísenos a {$correo}.",
            'Podemos suspender cuentas que hagan pedidos o reservaciones falsas reiteradas, intenten fraude o abusen del sistema, y le diremos el motivo.',
            'Puede eliminar su cuenta en cualquier momento desde Configuración, desde la app o en la página «Eliminar mi cuenta».',
        ]],
        ['type' => 'p', 'text' => 'Cómo eliminar su cuenta, aunque no tenga la app instalada: ', 'link' => ['label' => 'Eliminar mi cuenta', 'route' => 'eliminar-cuenta']],

        ['type' => 'h2', 'text' => '4. Menú y disponibilidad'],
        ['type' => 'p', 'text' => 'El menú, sus precios y presentaciones pueden cambiar. Respetamos el precio que aparecía al confirmar su pedido. Las fotografías son ilustrativas: algunas son de bancos de imágenes con licencia y el platillo puede verse distinto.'],
        ['type' => 'p', 'text' => 'Si un producto se agota después de confirmado su pedido, le ofreceremos un cambio por uno equivalente o quitarlo de su pedido con el ajuste o la devolución correspondiente.'],

        ['type' => 'h2', 'id' => 'compras', 'text' => '5. Precios e impuestos'],
        ['type' => 'p', 'text' => 'Los precios están en pesos mexicanos e incluyen IVA. El total que ve antes de confirmar es lo que se cobra por los productos; el envío se informa aparte (sección 9). Si hubiera un error evidente de precio, se lo diremos antes de preparar su pedido y podrá cancelarlo sin costo.'],

        ['type' => 'h2', 'text' => '6. Confirmación de pedidos'],
        ['type' => 'p', 'text' => 'Un pedido queda confirmado cuando el sitio o la app le muestran su folio y, si pagó con tarjeta, cuando Mercado Pago confirma el pago. Podemos rechazar un pedido por falta de existencias, error evidente de precio, sospecha razonable de fraude o datos de contacto incorrectos; si ya pagó, le devolvemos el total.'],

        ['type' => 'h2', 'text' => '7. Formas de pago'],
        ['type' => 'p', 'text' => 'Aceptamos efectivo al recibir y tarjeta de crédito o débito en una sola exhibición, sin comisión. El pago con tarjeta se hace en la página segura de Mercado Pago; no vemos ni guardamos los datos de su tarjeta. Un pedido con tarjeta se prepara solo cuando el pago se confirma.'],

        ['type' => 'h2', 'text' => '8. Propinas y cargos'],
        ['type' => 'p', 'text' => 'Las propinas son voluntarias y no se incluyen en los precios ni en la cuenta. No cobramos ningún cargo que usted no haya aceptado antes de confirmar.'],

        ['type' => 'h2', 'text' => '9. Envío a domicilio'],
        ['type' => 'p', 'text' => 'El total del pedido no incluye el envío. Lo realiza un repartidor y se le paga directamente al recibir. Antes de confirmar cada pedido le mostramos cómo se cobra y cuánto cuesta, y le pedimos aceptarlo. Los tiempos de preparación y entrega son estimados.'],

        ['type' => 'h2', 'text' => '10. Cancelación de pedidos'],
        ['type' => 'p', 'text' => 'Puede cancelar sin costo en cualquier momento desde la pantalla de seguimiento, mientras su pedido no haya entrado a cocina. Un pedido entra a cocina cuando aparece en la pantalla de preparación de la cocina o de la barra; desde ese momento el ticket le muestra «Tu pedido ya entró a cocina» y ya no se puede cancelar ni devolver el pago, porque son alimentos preparados al momento. Si pagó con tarjeta y canceló a tiempo, la devolución a la misma tarjeta se hace en automático; según su banco puede tardar de 5 a 10 días hábiles en verse.'],

        ['type' => 'h2', 'text' => '11. Bebidas con alcohol'],
        ['type' => 'list', 'items' => [
            'Solo se venden a mayores de 18 años. Al pedirlas lo confirma y al recibirlas debe mostrar una identificación oficial; sin ella, el repartidor no las entrega y le devolvemos su importe.',
            'Solo se venden en línea cuando la licencia del restaurante lo permite y en el horario autorizado. Durante una ley seca decretada por la autoridad no se venden.',
            'El abuso en el consumo de este producto es nocivo para la salud.',
        ]],

        ['type' => 'h2', 'text' => '12. Cambios, devoluciones y reembolsos'],
        ['type' => 'p', 'text' => 'Si recibe un producto equivocado, incompleto o en mal estado, avísenos el mismo día por teléfono, WhatsApp o correo. Usted elige entre la reposición del producto, un ajuste en su cuenta o la devolución de lo que pagó por él. Resolvemos su reporte en un máximo de 5 días hábiles.'],
        ['type' => 'p', 'text' => 'Si se le cobró dos veces o algo que no pidió, se le devuelve completo. No hay devolución por cambio de opinión en alimentos entregados en buen estado.'],

        ['type' => 'h2', 'text' => '13. Reservaciones'],
        ['type' => 'list', 'items' => [
            'Las reservaciones en línea son solicitudes sujetas a disponibilidad y a la confirmación del personal.',
            'La mesa se guarda 20 minutos después de la hora reservada.',
            'No cobramos depósito por reservaciones en línea. Para grupos de más de 10 personas o eventos, las condiciones se acuerdan por WhatsApp y se le confirman por escrito antes de cualquier pago.',
        ]],

        ['type' => 'h2', 'text' => '14. Promociones'],
        ['type' => 'p', 'text' => 'Cada promoción muestra su vigencia y sus condiciones antes de agregarla a su pedido, y se respeta tal como se anunció durante esa vigencia. Salvo que la promoción diga otra cosa, no son acumulables entre sí ni canjeables por efectivo. Algunas solo son válidas en el restaurante.'],

        ['type' => 'h2', 'id' => 'programa-de-puntos', 'text' => '15. Programa de puntos y logros'],
        ['type' => 'list', 'items' => [
            'Las cuentas registradas ganan 1 punto por cada $10 de cada pedido pagado.',
            'Por ahora los puntos no se pueden canjear ni tienen valor en dinero. Si se habilita el canje, publicaremos sus reglas antes en el sitio y en la app.',
            'Si un pedido se cancela o se devuelve, se descuentan los puntos que dio.',
            'Los puntos y logros se pierden al eliminar la cuenta.',
            'Ningún punto ni logro se gana por pedir bebidas con alcohol.',
            'Podemos terminar el programa avisando con al menos 30 días de anticipación.',
        ]],

        ['type' => 'h2', 'text' => '16. Alergias'],
        ['type' => 'p', 'text' => 'En nuestra cocina se manejan gluten, lácteos, huevo, mariscos, frutos secos y chiles en el mismo espacio. Aunque quite ingredientes al pedir, no podemos garantizar que no haya trazas por contacto cruzado.'],

        ['type' => 'h2', 'text' => '17. Facturación'],
        ['type' => 'p', 'text' => "Si necesita factura (CFDI) de un pedido en línea, escríbanos a {$correo} dentro del mismo mes del consumo con su folio, RFC, nombre o razón social, régimen fiscal, código postal y uso del CFDI. Se la enviaremos al mismo correo."],

        ['type' => 'h2', 'text' => '18. Uso permitido'],
        ['type' => 'p', 'text' => 'Está prohibido hacer pedidos o reservaciones falsas, intentar vulnerar la seguridad del sitio o de la app, o usarlos para actividades ilícitas.'],

        ['type' => 'h2', 'text' => '19. Propiedad intelectual'],
        ['type' => 'p', 'text' => 'El nombre comercial, el logotipo, los textos y el menú son del proveedor. Las fotografías propias también; las de bancos de imágenes se usan con su licencia. Las marcas de terceros que aparecen (por ejemplo, de bebidas) solo identifican lo que se vende y no implican patrocinio.'],

        ['type' => 'h2', 'text' => '20. Quejas y aclaraciones'],
        ['type' => 'table', 'rows' => [
            ['Teléfono / WhatsApp', '771 109 7827'],
            ['Correo', $correo],
            ['Horario de atención', 'Todos los días de 1:00 p.m. a 10:00 p.m.'],
        ]],
        ['type' => 'p', 'text' => 'Si no resolvemos su queja, puede acudir a la Procuraduría Federal del Consumidor (PROFECO): Teléfono del Consumidor 55 5568 8722 o 800 468 8722, y en profeco.gob.mx.'],

        ['type' => 'h2', 'text' => '21. Cambios a estos Términos'],
        ['type' => 'p', 'text' => 'Si cambiamos estos Términos de fondo, publicaremos la versión nueva con su fecha y le pediremos aceptarla al iniciar sesión. Los pedidos ya confirmados se rigen por la versión que aceptó al hacerlos.'],

        ['type' => 'h2', 'text' => '22. Legislación aplicable'],
        ['type' => 'p', 'text' => 'Estos Términos se rigen por las leyes de México, en particular la Ley Federal de Protección al Consumidor y el Código de Comercio. Para cualquier controversia, las partes se someten a los tribunales de Huejutla de Reyes, Hidalgo, sin perjuicio de su derecho a acudir a la PROFECO.'],
    ],
];
