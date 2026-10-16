<?php

require_once 'config.php';

$conn = getConnection();


$categoria = isset($_GET['categoria'])
    ? $conn->real_escape_string(trim($_GET['categoria']))
    : null;

if ($categoria) {
    $sql = "SELECT name, description, price
            FROM products
            WHERE category = '$categoria' AND available = 1
            ORDER BY price ASC
            LIMIT 8";
} else {
    $sql = "SELECT name, price, category
            FROM products
            WHERE available = 1
            ORDER BY category, price ASC";
}

$result = $conn->query($sql);
$platillos = [];

while ($row = $result->fetch_assoc()) {
    $platillos[] = [
        'nombre'    => $row['name'],
        'precio'    => '$' . number_format($row['price'], 0),
        'categoria' => $row['category'] ?? null,
        'descripcion' => isset($row['description']) ? $row['description'] : null,
    ];
}

echo json_encode([
    'total'    => count($platillos),
    'platillos' => $platillos
], JSON_UNESCAPED_UNICODE);

$conn->close();