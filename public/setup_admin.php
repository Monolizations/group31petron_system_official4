<?php
// Create Admin User Script
// This script creates the admin user with specified credentials

require_once __DIR__ . '/../public/db_connect.php';

$username = 'admin_petroncdok';
$plain_password = 'admin123'; // Plain text password as requested
$hashed_password = '$2y$10$Wl6T1aIUFKVHTLm15ZFs/Ol.drur6.09MDsmtEFWYLPez4VHazFEq'; // Hashed version for DB

try {
    // Check if user already exists
    $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $chk->execute([$username]);
    
    if ($chk->rowCount() > 0) {
        echo "User '$username' already exists.<br>";
        
        // Update existing user password
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $upd->execute([$hashed_password, $username]);
        echo "Password updated for existing user.<br>";
    } else {
        // Create new admin user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role, station_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $username,
            $hashed_password,
            'Petron Admin',
            'admin@petron.com',
            'admin',
            1, // Default station ID
            'active'
        ]);
        echo "Admin user '$username' created successfully.<br>";
    }
    
    echo "<br>Login Credentials:<br>";
    echo "Username: $username<br>";
    echo "Password: $plain_password<br>";
    echo "<br><a href='login.php'>Go to Login</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin User Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .info { background: #d4edda; padding: 20px; border-radius: 5px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; padding: 20px; border-radius: 5px; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>Admin User Setup</h1>
    <div class="info">
        <p><strong>Note:</strong> The login system accepts both plain text and hashed passwords.</p>
        <p>The password you provide during login will be accepted in plain text format.</p>
        <p>If stored as plain text in the database, it will be automatically hashed after first login.</p>
    </div>
</body>
</html>
