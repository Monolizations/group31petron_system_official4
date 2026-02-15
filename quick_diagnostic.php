#!/usr/bin/env php
<?php
/**
 * Quick Database Diagnostic for Users Issue
 * Run from command line: php quick_diagnostic.php
 */

require_once __DIR__ . '/public/db_connect.php';

echo "\n" . str_repeat("=", 80) . "\n";
echo "🔍 QUICK DATABASE DIAGNOSTIC FOR USERS ISSUE\n";
echo str_repeat("=", 80) . "\n\n";

// Check 1: Database connection
echo "1. Checking database connection...\n";
try {
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "   ✅ Connected (MySQL $version)\n";
    echo "   📁 Database: $db\n";
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Check 2: Count users
echo "\n2. Counting users in database...\n";
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "   📊 Total users: $totalUsers\n";

// Check 3: Count stations
echo "\n3. Counting stations...\n";
$stations = $pdo->query("SELECT * FROM stations")->fetchAll(PDO::FETCH_ASSOC);
echo "   📊 Total stations: " . count($stations) . "\n";
foreach ($stations as $s) {
    echo "      - ID {$s['id']}: {$s['name']}\n";
}

// Check 4: Users with NULL station_id
echo "\n4. Checking for users with NULL station_id...\n";
$nullStationUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE station_id IS NULL")->fetchColumn();
if ($nullStationUsers > 0) {
    echo "   ⚠️  Found $nullStationUsers users with NULL station_id!\n";
    $users = $pdo->query("SELECT id, username, name, role FROM users WHERE station_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "      - ID {$u['id']}: {$u['username']} ({$u['role']})\n";
    }
} else {
    echo "   ✅ All users have station_id assigned\n";
}

// Check 5: Users by station
echo "\n5. Users distribution by station...\n";
$stmt = $pdo->query("
    SELECT s.id as station_id, s.name as station_name, COUNT(u.id) as user_count
    FROM stations s
    LEFT JOIN users u ON s.id = u.station_id
    GROUP BY s.id, s.name
    ORDER BY s.name
");
$dist = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($dist as $d) {
    echo "   🏢 Station {$d['station_id']} ({$d['station_name']}): {$d['user_count']} users\n";
}

// Check 6: List all users with station info
echo "\n6. All users with station details...\n";
echo str_repeat("-", 80) . "\n";
printf("%-5s %-20s %-20s %-15s %-10s %-20s %-10s\n",
    "ID", "Name", "Username", "Role", "Station", "Station ID", "Status");
echo str_repeat("-", 80) . "\n";

$users = $pdo->query("
    SELECT u.id, u.name, u.username, u.role, u.station_id,
           s.name as station_name, u.status
    FROM users u
    LEFT JOIN stations s ON u.station_id = s.id
    ORDER BY u.name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    $stationId = $u['station_id'] ?? 'NULL';
    $stationName = $u['station_name'] ?? 'N/A';
    $isNull = ($stationId === 'NULL');

    $role = substr($u['role'], 0, 14);
    $station = substr($stationName, 0, 19);
    $status = substr($u['status'], 0, 9);

    if ($isNull) {
        echo "\033[31m"; // Red for NULL
    }

    printf("%-5s %-20s %-20s %-15s %-10s %-20s %-10s\n",
        $u['id'],
        substr($u['name'], 0, 19),
        substr($u['username'], 0, 19),
        $role,
        $station,
        $stationId,
        $status
    );

    if ($isNull) {
        echo "\033[0m"; // Reset color
    }
}
echo str_repeat("-", 80) . "\n";

// Check 7: Find admin users
echo "\n7. Admin/Manager users...\n";
$adminUsers = $pdo->query("SELECT id, username, name, role, station_id FROM users WHERE role IN ('admin', 'superadmin', 'manager') ORDER BY role, name")->fetchAll(PDO::FETCH_ASSOC);
echo "   📊 Found " . count($adminUsers) . " admin/manager users:\n";
foreach ($adminUsers as $a) {
    $sid = $a['station_id'] ?? 'NULL';
    echo "      - ID {$a['id']}: {$a['username']} ({$a['role']}) - Station: $sid\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Diagnostic complete!\n";
echo str_repeat("=", 80) . "\n\n";

echo "💡 Quick Fix Commands:\n";
echo "   If admin user has NULL station_id:\n";
echo "   UPDATE users SET station_id = 1 WHERE username = 'your_admin_username';\n\n";

echo "   To see diagnostic in browser:\n";
echo "   http://localhost/group31petron_system_official4/diagnostic_users.php\n\n";

echo "   To check PHP error logs:\n";
echo "   tail -f /opt/lampp/logs/php_error_log\n\n";
