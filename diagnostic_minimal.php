<?php
/**
 * Minimal Web Diagnostic for Users Issue
 * Access via: http://localhost/group31petron_system_official4/diagnostic_minimal.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', '/tmp/diagnostic.log');

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Minimal Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffff00; }
        .success { color: #00ff00; }
        .info { color: #00ffff; }
        pre { background: #2a2a2a; padding: 10px; border: 1px solid #444; }
    </style>
</head>
<body>
    <h1>🔍 MINIMAL DIAGNOSTIC</h1>
";

echo "<div class='info'>PHP Version: " . PHP_VERSION . "</div>";

// Check PDO
echo "<h2>PDO Drivers</h2>";
$drivers = PDO::getAvailableDrivers();
echo "<pre>";
foreach ($drivers as $driver) {
    echo "  - $driver\n";
}
echo "</pre>";

if (!in_array('mysql', $drivers)) {
    echo "<div class='error'>❌ MySQL PDO driver NOT installed!</div>";
} else {
    echo "<div class='success'>✅ MySQL PDO driver is available</div>";

    // Try database connection
    echo "<h2>Database Connection Test</h2>";
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=petron_pos_db_secure;charset=utf8mb4",
            "root",
            "",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "<div class='success'>✅ Database connected successfully</div>";

        // Get version
        $version = $pdo->query("SELECT VERSION()")->fetchColumn();
        echo "<div class='info'>MySQL Version: $version</div>";

        // Count users
        echo "<h2>Users in Database</h2>";
        $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<div class='info'>Total users: $count</div>";

        // Check NULL station_id
        $nullCount = $pdo->query("SELECT COUNT(*) FROM users WHERE station_id IS NULL")->fetchColumn();
        if ($nullCount > 0) {
            echo "<div class='warning'>⚠️ $nullCount users have NULL station_id</div>";
        } else {
            echo "<div class='success'>✅ All users have station_id</div>";
        }

        // List users
        echo "<h2>All Users</h2>";
        echo "<pre>";
        $users = $pdo->query("SELECT id, username, name, role, station_id, status FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            $sid = $u['station_id'] ?? 'NULL';
            $isNull = ($sid === 'NULL');
            if ($isNull) {
                echo "<span class='error'>";
            }
            echo "ID: {$u['id']} | Username: {$u['username']} | Role: {$u['role']} | Station: $sid | Status: {$u['status']}\n";
            if ($isNull) {
                echo "</span>";
            }
        }
        echo "</pre>";

        // Count stations
        echo "<h2>Stations in Database</h2>";
        $stations = $pdo->query("SELECT * FROM stations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='info'>Total stations: " . count($stations) . "</div>";
        echo "<pre>";
        foreach ($stations as $s) {
            echo "ID: {$s['id']} | Name: {$s['name']} | Location: {$s['location']} | Status: {$s['status']}\n";
        }
        echo "</pre>";

        // Current session
        echo "<h2>Current Session</h2>";
        session_start();
        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            echo "<pre>";
            echo "Logged in: YES\n";
            echo "ID: " . ($user['id'] ?? 'NULL') . "\n";
            echo "Name: " . ($user['name'] ?? 'NULL') . "\n";
            echo "Username: " . ($user['username'] ?? 'NULL') . "\n";
            echo "Role: " . ($user['role'] ?? 'NULL') . "\n";
            echo "Station ID (Session): " . (isset($user['station_id']) ? var_export($user['station_id'], true) : 'NULL') . "\n";
            echo "Status: " . ($user['status'] ?? 'NULL') . "\n";
            echo "</pre>";

            // Check if station_id in session matches database
            if (isset($user['id'])) {
                $dbUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $dbUser->execute([$user['id']]);
                $dbData = $dbUser->fetch(PDO::FETCH_ASSOC);

                if ($dbData) {
                    echo "<h2>Session vs Database Comparison</h2>";
                    $sessionStationId = $user['station_id'] ?? null;
                    $dbStationId = $dbData['station_id'] ?? null;

                    echo "<pre>";
                    echo "Session station_id: " . var_export($sessionStationId, true) . "\n";
                    echo "Database station_id: " . var_export($dbStationId, true) . "\n";

                    if ($sessionStationId !== $dbStationId) {
                        echo "<span class='warning'>⚠️ MISMATCH DETECTED!</span>\n";
                    } else {
                        echo "<span class='success'>✅ Values match</span>\n";
                    }

                    if ($dbStationId === null) {
                        echo "\n<span class='error'>❌ CRITICAL: User has NULL station_id in database!</span>\n";
                        echo "This is why users.php shows no users - the query filters by station_id but it's NULL.\n";
                    }
                    echo "</pre>";

                    // Simulate the exact query from users.php
                    echo "<h2>Simulated users.php Query</h2>";
                    $role = $user['role'] ?? '';
                    $normalizedRole = strtolower(trim($role));
                    $isSuperAdmin = ($normalizedRole === 'superadmin');

                    echo "<pre>";
                    echo "Normalized Role: $normalizedRole\n";
                    echo "Is Superadmin: " . ($isSuperAdmin ? 'YES' : 'NO') . "\n";
                    echo "Station ID used in query: " . var_export($dbStationId, true) . "\n";
                    echo "</pre>";

                    if ($isSuperAdmin) {
                        echo "<div class='success'>✅ Superadmin would see ALL users</div>";
                        $allUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                        echo "<div class='info'>Total users that would be shown: $allUsers</div>";
                    } else {
                        if ($dbStationId === null || $dbStationId === '') {
                            echo "<div class='error'>❌ Station ID is NULL or empty!</div>";
                            echo "<div class='warning'>⚠️ Query: SELECT * FROM users WHERE station_id = NULL</div>";
                            echo "<div class='warning'>⚠️ This query will return ZERO results!</div>";
                        } else {
                            $stationUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE station_id = ?");
                            $stationUsers->execute([$dbStationId]);
                            $count = $stationUsers->fetchColumn();
                            echo "<div class='info'>Users in station {$dbStationId}: $count</div>";

                            if ($count == 0) {
                                echo "<div class='warning'>⚠️ No other users in your station - this is why the table is empty!</div>";
                            } else {
                                echo "<div class='success'>✅ Found $count users in your station</div>";

                                // List them
                                echo "<h3>Users in Your Station</h3>";
                                echo "<pre>";
                                $usersList = $pdo->prepare("SELECT id, name, username, role, status FROM users WHERE station_id = ? AND id != ? ORDER BY name");
                                $usersList->execute([$dbStationId, $user['id']]);
                                $usersInStation = $usersList->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($usersInStation as $u) {
                                    echo "- ID {$u['id']}: {$u['name']} (@{$u['username']}) - {$u['role']} - {$u['status']}\n";
                                }
                                echo "</pre>";
                            }
                        }
                    }
                }
            }
        } else {
            echo "<div class='warning'>⚠️ No user logged in</div>";
            echo "<div class='info'>Please login first, then refresh this page to see session data.</div>";
        }

    } catch (PDOException $e) {
        echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "</body>
</html>";
