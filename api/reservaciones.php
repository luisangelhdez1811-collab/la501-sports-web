<?php

require_once 'config.php';

$conn = getConnection();

$fecha = isset($_GET['fecha'])
    ? $conn->real_escape_string($_GET['fecha'])
    : date('Y-m-d');

$sql = "SELECT hora_reservacion, cantidad_personas, zona, status
        FROM reservations
        WHERE fecha_reservacion = '$fecha'
          AND status IN ('pendiente','confirmada')
        ORDER BY hora_reservacion ASC";

$result = $conn->query($sql);
$reservaciones = [];

while ($row = $result->fetch_assoc()) {
    $reservaciones[] = [
        'hora'     => substr($row['hora_reservacion'], 0, 5),
        'personas' => $row['cantidad_personas'],
        'zona'     => $row['zona'],
        'estatus'  => $row['status'],
    ];
}

// Respuesta JSON (config.php envía Content-Type: application/json), no HTML: no hay XSS.
// nosemgrep: php.lang.security.injection.echoed-request.echoed-request
echo json_encode([
    'fecha'        => $fecha,
    'reservaciones_activas' => count($reservaciones),
    'detalle'      => $reservaciones,
], JSON_UNESCAPED_UNICODE);

$conn->close();