<?php
require_once __DIR__ . '/public/db_connect.php';

$stmt = $pdo->query("
    SELECT p.id, p.name, pt.name as type_name, p.type_id 
    FROM products p 
    INNER JOIN product_types pt ON p.type_id = pt.id 
    WHERE pt.name = 'fuel'
");
$fuels = $stmt->fetchAll();

echo "Fuel Products:\n";
echo json_encode($fuels, JSON_PRETTY_PRINT);
echo "\n\n";

// Also check fuel_types
$stmt = $pdo->query("SELECT id, name FROM fuel_types");
$fuel_types = $stmt->fetchAll();
echo "Fuel Types:\n";
echo json_encode($fuel_types, JSON_PRETTY_PRINT);
?>
