<?php
/**
 * One-Click Fix Tool
 * Assigns station to admin user immediately
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

// Destroy old session to force refresh after fix
session_destroy();
session_start();

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=petron_pos_db_secure;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

// Apply fix
$fixApplied = false;
$error = '';

if (isset($_POST['apply_fix'])) {
    try {
        // Get admin user
        $adminUser = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
        
        if (!$adminUser) {
            $error = "Admin user not found";
        } elseif (!is_null($adminUser['station_id'])) {
            $fixApplied = true;
            $error = "Admin user already has station_id: {$adminUser['station_id']}";
        } else {
            // Get station ID 1 or first available station
            $station = $pdo->query("SELECT * FROM stations WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
            
            if (!$station) {
                $station = $pdo->query("SELECT * FROM stations LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($station) {
                // Update admin user
                $stmt = $pdo->prepare("UPDATE users SET station_id = ? WHERE id = ?");
                $stmt->execute([$station['id'], $adminUser['id']]);
                $fixApplied = true;
                $error = "Success! Admin user assigned to station: {$station['name']} (ID: {$station['id']})";
            } else {
                $error = "No stations found in database";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>One-Click Fix - Users Issue</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #002F6C; text-align: center; margin-top: 0; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 5px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 5px solid #17a2b8; }
        .btn { display: block; width: 100%; padding: 15px; margin: 20px 0; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; }
        .btn-primary { background: #002F6C; color: white; }
        .btn-primary:hover { background: #001f4d; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        a { color: #002F6C; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 One-Click Fix Tool</h1>
        
        <?php if ($error): ?>
            <?php if ($fixApplied): ?>
                <div class="success">
                    <h2>✅ Fix Applied!</h2>
                    <p><?php echo htmlspecialchars($error); ?></p>
                    <h3>Next Steps:</h3>
                    <ol style="line-height: 1.8;">
                        <li><strong>Click here to go to Login page:</strong> <a href="login.php" class="btn btn-primary">Go to Login →</a></li>
                        <li>Login as <strong>admin</strong></li>
                        <li>Go to <strong>users.php</strong> to verify users are showing</li>
                    </ol>
                    <p><em>✨ Your session has been cleared. You will need to login again.</em></p>
                </div>
            <?php else: ?>
                <div class="error">
                    <h2>❌ Error</h2>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="info">
                <h2>Ready to Apply Fix</h2>
                <p>This tool will:</p>
                <ul style="line-height: 1.8;">
                    <li>Assign <strong>station_id = 1</strong> to the <strong>admin</strong> user</li>
                    <li>Clear your current session</li>
                    <li>Allow you to login again with the corrected station_id</li>
                </ul>
                <p><strong>After applying this fix:</strong></p>
                <ol style="line-height: 1.8;">
                    <li>Login as admin</li>
                    <li>Go to users.php</li>
                    <li>You will see users from station 1 (currently 2-3 other users)</li>
                </ol>
            </div>
            
            <form method="post">
                <button type="submit" name="apply_fix" class="btn btn-primary">🚀 Apply Fix Now</button>
            </form>
            
            <div class="info">
                <h3>📊 Current Status (Before Fix)</h3>
                <?php
                $adminUser = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
                $station = $pdo->query("SELECT * FROM stations WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
                ?>
                
                <p><strong>Admin User:</strong> <?php echo $adminUser['name'] ?> (@<?php echo $adminUser['username'] ?>)</p>
                <p><strong>Current Station ID:</strong> <span style="color: red; font-weight: bold;"><?php echo $adminUser['station_id'] ?? 'NULL' ?></span></p>
                <p><strong>Target Station:</strong> <?php echo $station ? $station['name'] : 'Not found'; ?> (ID: 1)</p>
                <p><strong>Users in Station 1:</strong> 
                <?php
                $count = $pdo->query("SELECT COUNT(*) FROM users WHERE station_id = 1")->fetchColumn();
                echo $count;
                ?>
                users
                </p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="FIX_INSTRUCTIONS.md" style="color: #666;">📄 View Full Instructions</a> | 
            <a href="diagnostic_minimal.php" style="color: #666;">🔍 Run Diagnostic</a>
        </div>
    </div>
</body>
</html>
