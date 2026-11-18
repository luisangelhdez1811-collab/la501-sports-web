<?php

/*
 * Aviso de Privacidad para Personal (meseros, cocina y administradores), publicado el 17 de noviembre de
 * 2026 (B-39). Esta copia NO se edita: un cambio de fondo es un archivo nuevo y staff_privacy_version nueva.
 */

$correo = 'Equipolapapa501@gmail.com';

return [
    'title' => 'Aviso de Privacidad para Personal',
    'lead' => 'Aviso de Privacidad',
    'accent' => 'para Personal',
    'description' => 'Cómo tratamos los datos de quienes trabajan en La 501 Sports.',
    'updated' => '17 de noviembre de 2026',
    'changes' => [
        'Primera versión del aviso para el personal del restaurante.',
    ],
    'blocks' => [
        ['type' => 'h2', 'text' => '1. Responsable'],
        ['type' => 'p', 'text' => "Andrés Rivera Jiménez («La 501 Sports Restaurant»), con domicilio en Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hidalgo, C.P. 43000, es responsable de los datos personales de su personal. Correo: {$correo}."],

        ['type' => 'h2', 'text' => '2. Datos que tratamos'],
        ['type' => 'table', 'rows' => [
            ['Cuenta', 'Nombre, correo, contraseña (guardada cifrada), rol (mesero, cocina o administrador) y PIN de acceso.'],
            ['Actividad en el sistema', 'Pedidos que toma, cobra o prepara, cobros por revisar y fecha y hora de cada acción.'],
            ['Desempeño', 'Indicadores calculados con esa actividad (por ejemplo, pedidos atendidos y ventas por turno).'],
        ]],
        ['type' => 'p', 'text' => 'No tratamos datos sensibles del personal en este sistema.'],

        ['type' => 'h2', 'text' => '3. Finalidades'],
        ['type' => 'list', 'items' => [
            'Darle acceso al sistema según su puesto.',
            'Registrar quién tomó, preparó y cobró cada pedido, para cuadrar la caja y atender aclaraciones de clientes.',
            'Medir el desempeño del servicio.',
            'Proteger el sistema contra accesos no autorizados.',
        ]],
        ['type' => 'p', 'text' => 'Todas son necesarias para la relación de trabajo. No usamos sus datos para publicidad ni los transferimos a terceros, salvo a las autoridades cuando la ley lo exija. Los proveedores que nos dan el servidor y los avisos en tiempo real (Hostinger y Pusher) los tratan solo por nuestra cuenta.'],

        ['type' => 'h2', 'text' => '4. Plazos de conservación'],
        ['type' => 'list', 'items' => [
            'Su cuenta, mientras trabaje con nosotros. Al terminar la relación se desactiva el mismo día.',
            'Su actividad en pedidos y cobros, el mismo tiempo que los pedidos (10 años), porque forma parte del registro de cada venta.',
        ]],

        ['type' => 'h2', 'text' => '5. Sus derechos'],
        ['type' => 'p', 'text' => "Puede pedir el acceso, la rectificación, la cancelación o la oposición respecto de sus datos escribiendo a {$correo}. Le respondemos en un máximo de 20 días hábiles y lo hacemos efectivo en los 15 días hábiles siguientes, sin costo."],

        ['type' => 'h2', 'text' => '6. Cambios'],
        ['type' => 'p', 'text' => 'Si este aviso cambia de fondo, le pediremos aceptar la versión nueva al iniciar sesión.'],
    ],
];
