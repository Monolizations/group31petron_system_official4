<?php
require_once 'db_connect.php';

$username = 'manager_petroncd';
$password_hash = '$2y$10$WDBNHzBac8LUs8u1qI5ZLuwNHnqaIy6hxrVdXjF8Gem1NsIpqS1ou';
$role = 'manager';
$status = 'active';

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role), status = VALUES(status)");
    $stmt->execute([$username, $password_hash, $role, $status]);
    echo "Manager user inserted/updated successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
