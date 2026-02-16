<?php
/**
 * Quick Fix: Assign station to admin user
 * This fixes the "No users found" issue
 */

require_once __DIR__ . '/public/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Station ID</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        h1 { color: #002F6C; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #002F6C; color: white; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-primary { background: #002F6C; color: white; }
        .btn-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Fix Admin Station ID</h1>
";

// Get current admin user info
echo "<h2>Current Admin User Status</h2>";
$adminUser = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);

if ($adminUser) {
    echo "<table>
        <tr><th>ID</th><td>" . $adminUser['id'] . "</td></tr>
        <tr><th>Username</th><td>" . $adminUser['username'] . "</td></tr>
        <tr><th>Name</th><td>" . $adminUser['name'] . "</td></tr>
        <tr><th>Role</th><td>" . $adminUser['role'] . "</td></tr>
        <tr><th>Current Station ID</th><td style='color: " . (is_null($adminUser['station_id']) ? 'red' : 'green') . "; font-weight: bold;'>" . (is_null($adminUser['station_id']) ? 'NULL ❌' : $adminUser['station_id']) . "</td></tr>
        <tr><th>Status</th><td>" . $adminUser['status'] . "</td></tr>
    </table>";
    
    // Get stations
    echo "<h2>Available Stations</h2>";
    $stations = $pdo->query("SELECT id, name FROM stations ORDER BY name LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<form method='post'>";
    echo "<table>
        <tr>
            <th>Select Station</th>
            <td>
                <select name='station_id' required style='padding: 10px; width: 100%; font-size: 14px;'>";
    
    foreach ($stations as $s) {
        $selected = (isset($_POST['station_id']) && $_POST['station_id'] == $s['id']) ? 'selected' : '';
        echo "<option value='{$s['id']}' $selected>{$s['id']} - {$s['name']}</option>";
    }
    
    echo "  </select>
            </td>
        </tr>
    </table>";
    
    echo "<div style='margin-top: 20px;'>
        <button type='submit' name='fix' class='btn btn-primary'>✅ Assign Station to Admin</button>
        <a href='users.php' class='btn btn-primary' style='display: inline-block; text-decoration: none;'>🔄 Go to Users Page</a>
        <a href='logout.php' class='btn btn-danger' style='display: inline-block; text-decoration: none;'>🚪 Logout</a>
    </div>";
    echo "</form>";
    
    // Handle form submission
    if (isset($_POST['fix'])) {
        $stationId = intval($_POST['station_id']);
        $userId = $adminUser['id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET station_id = ? WHERE id = ?");
            $stmt->execute([$stationId, $userId]);
            
            echo "<div class='success'>
                <h3>✅ Successfully Updated!</h3>
                <p>Admin user (ID: $userId) has been assigned to station ID: $stationId</p>
                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li><a href='logout.php'>Logout</a> from your current session</li>
                    <li>Login again to refresh your session with the new station_id</li>
                    <li>Go to <a href='users.php'>Users Page</a> to verify users are now showing</li>
                </ol>
            </div>";
        } catch (Exception $e) {
            echo "<div class='error'>
                <h3>❌ Error!</h3>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
            </div>";
        }
    }
    
    // Show SQL fix command
    echo "<h2>Manual SQL Fix (Alternative)</h2>";
    echo "<div class='info'>
        <p>If the form doesn't work, you can run this SQL directly in phpMyAdmin or MySQL CLI:</p>
        <pre style='background: #2a2a2a; padding: 15px; border-radius: 5px; color: #00ff00;'>UPDATE users SET station_id = 1 WHERE username = 'admin';</pre>
        <p>Then logout and login again.</p>
    </div>";
    
    // Check if there are users in the target station
    $stationId = $adminUser['station_id'] ?? (isset($_POST['station_id']) ? $_POST['station_id'] : null);
    if ($stationId) {
        echo "<h2>Users in Selected Station</h2>";
        $stmt = $pdo->prepare("SELECT id, username, name, role, status FROM users WHERE station_id = ? AND id != ? ORDER BY name");
        $stmt->execute([$stationId, $userId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "<div class='success'><p>Found " . count($users) . " users in this station (excluding admin user):</p></div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Status</th></tr>";
            foreach ($users as $u) {
                echo "<tr>
                    <td>{$u['id']}</td>
                    <td>{$u['username']}</td>
                    <td>{$u['name']}</td>
                    <td>{$u['role']}</td>
                    <td>{$u['status']}</td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'><p>⚠️ No other users found in this station!</p></div>";
            echo "<div class='info'><p>This means after assigning this station, you will still see an empty users table. You may want to assign users to this station or choose a station that already has users.</p></div>";
        }
    } else {
        echo "<div class='error'><p>❌ Admin has no station assigned. Please select a station above and click the button.</p></div>";
    }
    
} else {
    echo "<div class='error'><h3>❌ Admin user not found!</h3><p>Make sure the admin user exists in the database.</p></div>";
}

echo "</body>
</html>";
