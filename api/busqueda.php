<?php
require_once 'config.php';
$conn = getConnection();

// La búsqueda va siempre como parámetro de una consulta preparada: nunca se pega
// el texto del usuario dentro del SQL. Los comodines de LIKE (% y _) se escapan
// para que se busquen como texto normal.
$query = isset($_GET['q']) ? mb_substr(trim((string) $_GET['q']), 0, 100) : '';
$patron = '%' . addcslashes($query, '%_\\') . '%';

/** Primera fila del resultado de una consulta preparada, o null (también si la tabla no existe). */
if (!function_exists('buscarUno')) {
    function buscarUno(mysqli $conn, string $sql, string $tipos, string ...$valores): ?array
    {
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param($tipos, ...$valores);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $fila ?: null;
        } catch (mysqli_sql_exception $e) {
            return null;
        }
    }
}

if ($query === '') {
    echo json_encode(['encontrado' => false]);
    exit;
}

$respuesta_final = "";
$encontrado = false;

// 1. Buscar en el Menú (Productos)
$row = buscarUno($conn, "SELECT name, price, description FROM products WHERE available = 1 AND name LIKE ? LIMIT 1", 's', $patron);
if ($row) {
    $respuesta_final = "En nuestro menú tenemos " . $row['name'] . " por un precio de $" . number_format($row['price'], 0) . ".";
    $encontrado = true;
}

// 2. Si no está en el menú, buscar en Promociones
if (!$encontrado) {
    $row = buscarUno($conn, "SELECT title, description FROM promotions WHERE active = 1 AND (title LIKE ? OR description LIKE ?) LIMIT 1", 'ss', $patron, $patron);
    if ($row) {
        $respuesta_final = "Sí, tenemos una promoción relacionada: " . $row['title'] . ". " . $row['description'];
        $encontrado = true;
    }
}


if (!$encontrado) {
    $row = buscarUno($conn, "SELECT respuesta FROM faqs WHERE pregunta LIKE ? OR palabras_clave LIKE ? LIMIT 1", 'ss', $patron, $patron);
    if ($row) {
        $respuesta_final = $row['respuesta'];
        $encontrado = true;
    }
}


// Respuesta JSON (config.php envía Content-Type: application/json), no HTML: no hay XSS.
// nosemgrep: php.lang.security.injection.echoed-request.echoed-request
echo json_encode([
    'encontrado' => $encontrado,
    'respuesta' => $respuesta_final
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>