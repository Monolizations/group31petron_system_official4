<?php
/**
 * Diagnostic Script for Users Issue
 * Checks database for users, stations, and station_id assignments
 */

session_start();
require_once __DIR__ . '/public/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Users Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #002F6C; border-bottom: 3px solid #002F6C; padding-bottom: 10px; }
        h2 { color: #333; margin-top: 30px; background: #e9ecef; padding: 10px; border-radius: 4px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .info { background: #d1ecf1; border-left: 5px solid #17a2b8; padding: 10px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 5px solid #ffc107; padding: 10px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 5px solid #dc3545; padding: 10px; margin: 10px 0; }
        .success { background: #d4edda; border-left: 5px solid #28a745; padding: 10px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
        th { background: #002F6C; color: white; padding: 10px; text-align: left; }
        td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f0f0f0; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .badge-super { background: #6f42c1; color: white; }
        .badge-admin { background: #007bff; color: white; }
        .badge-manager { background: #28a745; color: white; }
        .badge-staff { background: #6c757d; color: white; }
        .null { color: red; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Users Diagnostic Tool</h1>
        <p>This tool helps diagnose why no users are showing in the users.php page.</p>
";

// ============ CHECK 1: DATABASE CONNECTION ============
echo "<h2>📊 Check 1: Database Connection</h2>
<div class='section'>";

try {
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    echo "<div class='success'>✅ Database connected successfully (MySQL: $version)</div>";
    
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<div class='info'>📁 Current Database: <strong>$db</strong></div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// ============ CHECK 2: CURRENT SESSION ============
echo "<h2>👤 Check 2: Current Session (Logged-in User)</h2>
<div class='section'>";

if (isset($_SESSION['user'])) {
    $currentUser = $_SESSION['user'];
    echo "<div class='success'>✅ User is logged in</div>";
    echo "<pre>";
    echo "ID: " . ($currentUser['id'] ?? 'NULL') . "\n";
    echo "Name: " . ($currentUser['name'] ?? 'NULL') . "\n";
    echo "Username: " . ($currentUser['username'] ?? 'NULL') . "\n";
    echo "Role: " . ($currentUser['role'] ?? 'NULL') . "\n";
    echo "Station ID: " . (isset($currentUser['station_id']) ? var_export($currentUser['station_id'], true) : 'NULL') . "\n";
    echo "Status: " . ($currentUser['status'] ?? 'NULL') . "\n";
    echo "</pre>";
    
    if (!isset($currentUser['station_id']) || $currentUser['station_id'] === null || $currentUser['station_id'] === '') {
        echo "<div class='error'>❌ CRITICAL: Logged-in user has NO station_id in session!</div>";
        echo "<div class='warning'>⚠️ This is likely why no users are showing - the query filters by station_id but it's NULL.</div>";
    }
} else {
    echo "<div class='warning'>⚠️ No user logged in. Please login first.</div>";
}

echo "</div>";

// ============ CHECK 3: TOTAL USERS IN DATABASE ============
echo "<h2>👥 Check 3: Total Users in Database</h2>
<div class='section'>";

try {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<div class='info'>📊 Total users in database: <strong>$totalUsers</strong></div>";
    
    if ($totalUsers == 0) {
        echo "<div class='error'>❌ No users found in database!</div>";
    } else {
        echo "<div class='success'>✅ Found $totalUsers users</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============ CHECK 4: STATIONS IN DATABASE ============
echo "<h2>🏢 Check 4: Stations in Database</h2>
<div class='section'>";

try {
    $stations = $pdo->query("SELECT * FROM stations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='info'>📊 Total stations: <strong>" . count($stations) . "</strong></div>";
    
    if (count($stations) > 0) {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Location</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>";
        foreach ($stations as $station) {
            echo "<tr>
                <td>" . htmlspecialchars($station['id']) . "</td>
                <td>" . htmlspecialchars($station['name']) . "</td>
                <td>" . htmlspecialchars($station['location'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($station['status'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($station['created_at'] ?? 'N/A') . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No stations found in database!</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============ CHECK 5: USERS WITH STATION INFO ============
echo "<h2>👥 Check 5: All Users with Station Details</h2>
<div class='section'>";

try {
    $users = $pdo->query("
        SELECT u.*, s.name as station_name 
        FROM users u 
        LEFT JOIN stations s ON u.station_id = s.id 
        ORDER BY u.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>📊 Found " . count($users) . " users</div>";
    
    if (count($users) > 0) {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Station ID</th>
                <th>Station Name</th>
                <th>Status</th>
                <th>Created</th>
            </tr>";
        
        $nullStationCount = 0;
        foreach ($users as $user) {
            $stationId = $user['station_id'] ?? null;
            $stationName = $user['station_name'] ?? 'N/A';
            $isStationNull = ($stationId === null || $stationId === '');
            
            if ($isStationNull) $nullStationCount++;
            
            $roleClass = '';
            $role = strtolower($user['role'] ?? '');
            if (strpos($role, 'super') !== false) $roleClass = 'badge-super';
            elseif (strpos($role, 'admin') !== false) $roleClass = 'badge-admin';
            elseif (strpos($role, 'manager') !== false) $roleClass = 'badge-manager';
            else $roleClass = 'badge-staff';
            
            echo "<tr>
                <td>" . htmlspecialchars($user['id']) . "</td>
                <td>" . htmlspecialchars($user['name']) . "</td>
                <td>" . htmlspecialchars($user['username']) . "</td>
                <td><span class='badge $roleClass'>" . htmlspecialchars($user['role']) . "</span></td>
                <td class='" . ($isStationNull ? 'null' : '') . "'>" . 
                    ($isStationNull ? '<span class="null">NULL</span>' : htmlspecialchars($stationId)) . 
                "</td>
                <td>" . htmlspecialchars($stationName) . "</td>
                <td>" . htmlspecialchars($user['status'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($user['created_at'] ?? 'N/A') . "</td>
            </tr>";
        }
        echo "</table>";
        
        if ($nullStationCount > 0) {
            echo "<div class='error'>❌ Found <strong>$nullStationCount users</strong> with NULL station_id!</div>";
            echo "<div class='warning'>⚠️ Users with NULL station_id will NOT appear in admin/manager views.</div>";
        } else {
            echo "<div class='success'>✅ All users have station_id assigned</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============ CHECK 6: STATION DISTRIBUTION ============
echo "<h2>📊 Check 6: Users by Station</h2>
<div class='section'>";

try {
    $stationDist = $pdo->query("
        SELECT 
            s.id as station_id,
            s.name as station_name,
            COUNT(u.id) as user_count,
            SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active_count,
            GROUP_CONCAT(u.username ORDER BY u.username SEPARATOR ', ') as users_list
        FROM stations s
        LEFT JOIN users u ON s.id = u.station_id
        GROUP BY s.id, s.name
        ORDER BY s.name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>
        <tr>
            <th>Station ID</th>
            <th>Station Name</th>
            <th>Total Users</th>
            <th>Active Users</th>
            <th>Usernames</th>
        </tr>";
    
    foreach ($stationDist as $dist) {
        echo "<tr>
            <td>" . htmlspecialchars($dist['station_id']) . "</td>
            <td>" . htmlspecialchars($dist['station_name']) . "</td>
            <td>" . htmlspecialchars($dist['user_count']) . "</td>
            <td>" . htmlspecialchars($dist['active_count']) . "</td>
            <td>" . htmlspecialchars($dist['users_list'] ?? 'No users') . "</td>
        </tr>";
    }
    echo "</table>";
    
    $nullUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE station_id IS NULL")->fetchColumn();
    if ($nullUsers > 0) {
        echo "<div class='warning'>⚠️ There are <strong>$nullUsers users</strong> with NULL station_id</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============ CHECK 7: LOGGED-IN USER'S STATION ============
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    echo "<h2>🎯 Check 7: Logged-in User's Station Details</h2>
    <div class='section'>";
    
    $currentUserId = $_SESSION['user']['id'];
    
    try {
        $userWithStation = $pdo->prepare("
            SELECT u.*, s.name as station_name, s.location as station_location
            FROM users u
            LEFT JOIN stations s ON u.station_id = s.id
            WHERE u.id = ?
        ");
        $userWithStation->execute([$currentUserId]);
        $userData = $userWithStation->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "<pre>";
            echo "User ID: " . htmlspecialchars($userData['id']) . "\n";
            echo "Name: " . htmlspecialchars($userData['name']) . "\n";
            echo "Username: " . htmlspecialchars($userData['username']) . "\n";
            echo "Role: " . htmlspecialchars($userData['role']) . "\n";
            echo "Station ID (DB): " . (isset($userData['station_id']) ? var_export($userData['station_id'], true) : 'NULL') . "\n";
            echo "Station ID (Session): " . (isset($_SESSION['user']['station_id']) ? var_export($_SESSION['user']['station_id'], true) : 'NULL') . "\n";
            echo "Station Name: " . htmlspecialchars($userData['station_name'] ?? 'N/A') . "\n";
            echo "Station Location: " . htmlspecialchars($userData['station_location'] ?? 'N/A') . "\n";
            echo "Status: " . htmlspecialchars($userData['status']) . "\n";
            echo "</pre>";
            
            // Check for mismatch
            $dbStationId = $userData['station_id'] ?? null;
            $sessionStationId = $_SESSION['user']['station_id'] ?? null;
            
            if ($dbStationId !== $sessionStationId) {
                echo "<div class='warning'>⚠️ Session station_id doesn't match database!</div>";
                echo "<div class='info'>Database: " . var_export($dbStationId, true) . " | Session: " . var_export($sessionStationId, true) . "</div>";
            }
            
            if ($dbStationId === null || $dbStationId === '') {
                echo "<div class='error'>❌ CRITICAL: Your user has NO station assigned in database!</div>";
                echo "<div class='warning'>⚠️ This is why you see no users - the query filters by station_id but yours is NULL.</div>";
                echo "<div class='info'>💡 Solution: Assign this user to a station in the database or use a user that has a station_id.</div>";
            } else {
                // Check if there are other users in the same station
                $stationUsers = $pdo->prepare("
                    SELECT COUNT(*) FROM users WHERE station_id = ? AND id != ?
                ");
                $stationUsers->execute([$dbStationId, $currentUserId]);
                $count = $stationUsers->fetchColumn();
                
                if ($count == 0) {
                    echo "<div class='warning'>⚠️ No other users found in your station (ID: $dbStationId)</div>";
                    echo "<div class='info'>💡 This is why the users table is empty - there are no other users assigned to your station.</div>";
                } else {
                    echo "<div class='success'>✅ Found $count other user(s) in your station</div>";
                    
                    // List them
                    $otherUsers = $pdo->prepare("
                        SELECT id, name, username, role, status 
                        FROM users 
                        WHERE station_id = ? AND id != ?
                        ORDER BY name
                    ");
                    $otherUsers->execute([$dbStationId, $currentUserId]);
                    $users = $otherUsers->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<table>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>";
                    foreach ($users as $u) {
                        echo "<tr>
                            <td>" . htmlspecialchars($u['id']) . "</td>
                            <td>" . htmlspecialchars($u['name']) . "</td>
                            <td>" . htmlspecialchars($u['username']) . "</td>
                            <td>" . htmlspecialchars($u['role']) . "</td>
                            <td>" . htmlspecialchars($u['status']) . "</td>
                        </tr>";
                    }
                    echo "</table>";
                }
            }
        } else {
            echo "<div class='error'>❌ User ID $currentUserId not found in database!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
}

// ============ CHECK 8: SIMULATED USERS.PHP QUERY ============
echo "<h2>🔍 Check 8: Simulated users.php Query</h2>
<div class='section'>";

if (isset($_SESSION['user'])) {
    $currentUser = $_SESSION['user'];
    $role = $currentUser['role'] ?? 'staff';
    $stationId = $currentUser['station_id'] ?? null;
    
    echo "<div class='info'>📋 Simulating the exact query from users.php...</div>";
    echo "<pre>";
    echo "Current User Role: " . htmlspecialchars($role) . "\n";
    echo "Current Station ID: " . var_export($stationId, true) . "\n";
    
    $normalizedRole = strtolower(trim($role));
    $isSuperAdmin = ($normalizedRole === 'superadmin');
    
    echo "Is Superadmin: " . ($isSuperAdmin ? 'YES' : 'NO') . "\n";
    echo "</pre>";
    
    try {
        if ($isSuperAdmin) {
            $sql = "SELECT u.*, s.name as station_name FROM users u LEFT JOIN stations s ON u.station_id = s.id ORDER BY u.created_at DESC";
            echo "<div class='success'>✅ Query (Superadmin):</div>";
            echo "<pre>" . htmlspecialchars($sql) . "</pre>";
            
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='info'>📊 Query returned " . count($results) . " users</div>";
        } else {
            $sql = "SELECT * FROM users WHERE station_id = ? ORDER BY role, name";
            echo "<div class='success'>✅ Query (Admin/Manager):</div>";
            echo "<pre>" . htmlspecialchars($sql) . "</pre>";
            echo "<pre>Parameter: " . var_export($stationId, true) . "</pre>";
            
            if ($stationId === null || $stationId === '') {
                echo "<div class='error'>❌ Station ID is NULL or empty!</div>";
                echo "<div class='warning'>⚠️ This query will return NO results because: station_id IS NULL</div>";
                echo "<div class='info'>💡 Solution: Assign a station to the logged-in user in the database.</div>";
            } else {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$stationId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='info'>📊 Query returned " . count($results) . " users</div>";
                
                if (count($results) > 0) {
                    echo "<table>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>";
                    foreach ($results as $r) {
                        echo "<tr>
                            <td>" . htmlspecialchars($r['id']) . "</td>
                            <td>" . htmlspecialchars($r['name']) . "</td>
                            <td>" . htmlspecialchars($r['username']) . "</td>
                            <td>" . htmlspecialchars($r['role']) . "</td>
                            <td>" . htmlspecialchars($r['status']) . "</td>
                        </tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<div class='warning'>⚠️ No results from query!</div>";
                    echo "<div class='info'>💡 This means there are no other users in your station (ID: $stationId).</div>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='warning'>⚠️ No user logged in - cannot simulate query</div>";
}

echo "</div>";

// ============ RECOMMENDATIONS ============
echo "<h2>💡 Recommendations</h2>
<div class='section'>
    <h3>Based on the diagnostic results, here are possible solutions:</h3>
    
    <div class='info'>
        <strong>If your user has NULL station_id:</strong><br>
        1. Check the database and assign your user to a valid station<br>
        2. Logout and login again to refresh the session<br>
        3. Verify the station_id is set correctly in the users table
    </div>
    
    <div class='info'>
        <strong>If no other users are in your station:</strong><br>
        1. Create new users and assign them to your station<br>
        2. Or assign existing users to your station<br>
        3. Check if users should be in a different station
    </div>
    
    <div class='info'>
        <strong>If session station_id doesn't match database:</strong><br>
        1. Logout and login again to refresh session<br>
        2. Clear browser cookies if needed<br>
        3. Check for session corruption issues
    </div>
    
    <div class='success'>
        <strong>Quick Fix:</strong><br>
        <pre>UPDATE users SET station_id = 1 WHERE id = YOUR_USER_ID;</pre>
        Replace YOUR_USER_ID with your actual user ID and 1 with a valid station ID.
    </div>
</div>";

echo "<div class='info'>
    <strong>📋 Check the PHP error log:</strong><br>
    tail -f /opt/lampp/logs/php_error_log<br>
    or<br>
    tail -f /var/log/apache2/error.log
</div>";

echo "</div>
</body>
</html>";
