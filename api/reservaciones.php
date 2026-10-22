<?php

require_once 'config.php';

$conn = getConnection();

// Solo se acepta una fecha AAAA-MM-DD válida; cualquier otra cosa usa la de hoy.
// Además va como parámetro de una consulta preparada (nunca dentro del SQL).
$fecha = isset($_GET['fecha']) ? trim((string) $_GET['fecha']) : '';
$valida = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$valida || $valida->format('Y-m-d') !== $fecha) {
    $fecha = date('Y-m-d');
}

$stmt = $conn->prepare("SELECT hora_reservacion, cantidad_personas, zona, status
        FROM reservations
        WHERE fecha_reservacion = ?
          AND status IN ('pendiente','confirmada')
        ORDER BY hora_reservacion ASC");
$stmt->bind_param('s', $fecha);
$stmt->execute();
$result = $stmt->get_result();
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

$stmt->close();
$conn->close();