<?php
// Simple Login Fix Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Petron Login Fix</h2>";

// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=petron_pos_db_secure", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your XAMPP MySQL service and database name.</p>";
    exit;
}

// Check if users table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Users table doesn't exist</p>";
        echo "<p>Creating users table...</p>";
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            role VARCHAR(20) DEFAULT 'staff',
            station_id INT DEFAULT 1,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<p style='color: green;'>✓ Users table created</p>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>Table check failed: " . $e->getMessage() . "</p>";
}

// Delete existing admin user if exists
try {
    $pdo->prepare("DELETE FROM users WHERE username = ?")->execute(['admin_petroncdok']);
    echo "<p style='color: orange;'>Removed existing admin user</p>";
} catch(PDOException $e) {
    echo "<p style='color: orange;'>No existing user to remove</p>";
}

// Create new admin user with plain text password
try {
    $username = 'admin_petroncdok';
    $password = 'admin123'; // Plain text password
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role, station_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        'Petron Admin',
        'admin@petron.com',
        'admin',
        1,
        'active'
    ]);
    
    echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
    echo "<br><h3>Login Credentials:</h3>";
    echo "<p><strong>Username:</strong> admin_petroncdok</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<br><p><a href='login.php' style='background: #002F6C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Failed to create user: " . $e->getMessage() . "</p>";
}

// Show all users
try {
    $result = $pdo->query("SELECT username, name, role, status FROM users");
    echo "<br><h3>All Users in Database:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Username</th><th>Name</th><th>Role</th><th>Status</th></tr>";
    
    while($row = $result->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Could not fetch users: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin-top: 10px; }
th, td { padding: 8px; text-align: left; }
</style>
