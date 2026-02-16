<?php
// Quick script to check database schema
require 'db_connect.php';

try {
    echo "=== SALES TABLE ===\n";
    $result = $pdo->query("DESCRIBE sales");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
    }
    
    echo "\n=== SALE_ITEMS TABLE ===\n";
    $result = $pdo->query("DESCRIBE sale_items");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
    }
    
    echo "\n=== NOZZLES TABLE ===\n";
    $result = $pdo->query("DESCRIBE nozzles");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
