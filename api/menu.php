<?php

require_once 'config.php';

$conn = getConnection();


// La categoría va como parámetro de una consulta preparada (nunca dentro del SQL).
$categoria = isset($_GET['categoria']) ? trim((string) $_GET['categoria']) : '';

if ($categoria !== '') {
    $stmt = $conn->prepare("SELECT name, description, price
            FROM products
            WHERE category = ? AND available = 1
            ORDER BY price ASC
            LIMIT 8");
    $stmt->bind_param('s', $categoria);
} else {
    $stmt = $conn->prepare("SELECT name, price, category
            FROM products
            WHERE available = 1
            ORDER BY category, price ASC");
}

$stmt->execute();
$result = $stmt->get_result();
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

$stmt->close();
$conn->close();