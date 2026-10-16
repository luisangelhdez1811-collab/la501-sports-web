<?php

require_once 'config.php';

$conn = getConnection();

$sql = "SELECT title, description, price_text, tag, end_date
        FROM promotions
        WHERE active = 1
        ORDER BY id ASC";

$result = $conn->query($sql);
$promos = [];

while ($row = $result->fetch_assoc()) {
    $promos[] = [
        'titulo'      => $row['title'],
        'descripcion' => $row['description'],
        'precio'      => $row['price_text'],
        'etiqueta'    => $row['tag'],
        'vigencia'    => $row['end_date'] ?? 'Sin fecha límite',
    ];
}

echo json_encode([
    'total'       => count($promos),
    'promociones' => $promos
], JSON_UNESCAPED_UNICODE);

$conn->close();