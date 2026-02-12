<?php
// Fix manager role in database
require_once __DIR__ . '/../public/db_connect.php';

try {
    // Update the manager_petroncd user to have 'manager' role
    $stmt = $pdo->prepare("UPDATE users SET role = 'manager' WHERE username = 'manager_petroncd'");
    $result = $stmt->execute();

    if ($result) {
        echo "Successfully updated role for user 'manager_petroncd' to 'manager'.\n";
    } else {
        echo "Failed to update user role.\n";
    }

    // Verify the update
    $stmt = $pdo->prepare("SELECT id, username, name, role FROM users WHERE username = 'manager_petroncd'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Verification: User '" . $user['username'] . "' now has role: '" . $user['role'] . "'\n";
    } else {
        echo "User not found after update.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
