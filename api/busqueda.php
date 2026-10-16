<?php
require_once 'config.php';
$conn = getConnection();

$query = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';

if (empty($query)) {
    echo json_encode(['encontrado' => false]);
    exit;
}

$respuesta_final = "";
$encontrado = false;

// 1. Buscar en el Menú (Productos)
$sql_menu = "SELECT name, price, description FROM products WHERE available = 1 AND name LIKE '%$query%' LIMIT 1";
$res_menu = $conn->query($sql_menu);
if ($row = $res_menu->fetch_assoc()) {
    $respuesta_final = "En nuestro menú tenemos " . $row['name'] . " por un precio de $" . number_format($row['price'], 0) . ".";
    $encontrado = true;
}

// 2. Si no está en el menú, buscar en Promociones
if (!$encontrado) {
    $sql_promo = "SELECT title, description FROM promotions WHERE active = 1 AND (title LIKE '%$query%' OR description LIKE '%$query%') LIMIT 1";
    $res_promo = $conn->query($sql_promo);
    if ($row = $res_promo->fetch_assoc()) {
        $respuesta_final = "Sí, tenemos una promoción relacionada: " . $row['title'] . ". " . $row['description'];
        $encontrado = true;
    }
}


if (!$encontrado) {
    $sql_faq = "SELECT respuesta FROM faqs WHERE pregunta LIKE '%$query%' OR palabras_clave LIKE '%$query%' LIMIT 1";
    $res_faq = $conn->query($sql_faq);
    if ($row = $res_faq->fetch_assoc()) {
        $respuesta_final = $row['respuesta'];
        $encontrado = true;
    }
}


echo json_encode([
    'encontrado' => $encontrado,
    'respuesta' => $respuesta_final
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>