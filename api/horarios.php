<?php

require_once 'config.php';

$conn = getConnection();

$sql = "SELECT \`key\`, value
        FROM settings
        WHERE \`key\` IN (
            'schedule_lunes', 'schedule_martes', 'schedule_miercoles',
            'schedule_jueves', 'schedule_viernes', 'schedule_sabado', 'schedule_domingo',
            'address_line1', 'address_line2', 'address_line3'
        )";

$result = $conn->query($sql);
$datos  = [];
$horarios_dias = [
    'lunes','martes','miercoles','jueves','viernes','sabado','domingo'
];

while ($row = $result->fetch_assoc()) {
    $datos[$row['key']] = $row['value'];
}

$horarios = [];
foreach ($horarios_dias as $dia) {
    $key = "schedule_$dia";
    if (isset($datos[$key])) {
        $horarios[ucfirst($dia)] = $datos[$key];
    }
}

$direccion = trim(
    ($datos['address_line1'] ?? '') . ', ' .
    ($datos['address_line2'] ?? '') . ', ' .
    ($datos['address_line3'] ?? '')
, ' ,');

echo json_encode([
    'horarios'  => $horarios,
    'direccion' => $direccion,
], JSON_UNESCAPED_UNICODE);

$conn->close();