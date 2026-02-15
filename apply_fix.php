<?php
/**
 * Apply Fix: Assign station to admin user
 * Run this once to fix the "No users found" issue
 */

require_once __DIR__ . '/public/db_connect.php';

echo "=== APPLYING FIX TO USERS ISSUE ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Get current admin user
echo "1. Checking current admin user...\n";
$adminUser = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);

if (!$adminUser) {
    echo "❌ ERROR: Admin user not found in database!\n";
    exit(1);
}

echo "   Found admin user:\n";
echo "   ID: {$adminUser['id']}\n";
echo "   Username: {$adminUser['username']}\n";
echo "   Name: {$adminUser['name']}\n";
echo "   Role: {$adminUser['role']}\n";
echo "   Current Station ID: " . ($adminUser['station_id'] ?? 'NULL') . "\n\n";

// Check if station_id is already assigned
if (!is_null($adminUser['station_id'])) {
    echo "✅ Admin user already has station_id: {$adminUser['station_id']}\n";
    echo "   No fix needed!\n";
    exit(0);
}

echo "   ⚠️  Admin user has NULL station_id - FIXING...\n\n";

// Check if station_id = 1 exists
echo "2. Checking if station ID 1 exists...\n";
$station = $pdo->query("SELECT * FROM stations WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

if (!$station) {
    echo "   ❌ ERROR: Station ID 1 not found!\n";
    echo "   Trying to find first available station...\n";
    
    $firstStation = $pdo->query("SELECT * FROM stations LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$firstStation) {
        echo "   ❌ ERROR: No stations found in database!\n";
        echo "   Please create a station first.\n";
        exit(1);
    }
    
    $stationId = $firstStation['id'];
    echo "   Found station ID: {$firstStation['id']} - {$firstStation['name']}\n\n";
} else {
    $stationId = $station['id'];
    echo "   ✅ Station found: ID {$station['id']} - {$station['name']}\n\n";
}

// Apply the fix
echo "3. Assigning station ID $stationId to admin user...\n";
try {
    $stmt = $pdo->prepare("UPDATE users SET station_id = ? WHERE id = ?");
    $stmt->execute([$stationId, $adminUser['id']]);
    echo "   ✅ Successfully updated!\n\n";
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Verify the fix
echo "4. Verifying the fix...\n";
$updatedUser = $pdo->query("SELECT * FROM users WHERE id = " . $adminUser['id'])->fetch(PDO::FETCH_ASSOC);

echo "   Updated station_id: {$updatedUser['station_id']}\n";
echo "   Status: " . ($updatedUser['station_id'] == $stationId ? '✅ CORRECT' : '❌ FAILED') . "\n\n";

// Check how many users will be visible to admin
echo "5. Checking users that will be visible to admin...\n";
$visibleUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE station_id = ? AND id != ?");
$visibleUsers->execute([$stationId, $adminUser['id']]);
$count = $visibleUsers->fetchColumn();

echo "   Other users in station $stationId: $count\n";

if ($count > 0) {
    echo "   Listing users:\n";
    $users = $pdo->prepare("SELECT id, username, name, role, status FROM users WHERE station_id = ? AND id != ? ORDER BY name");
    $users->execute([$stationId, $adminUser['id']]);
    
    foreach ($users->fetchAll(PDO::FETCH_ASSOC) as $u) {
        echo "      - ID {$u['id']}: {$u['name']} (@{$u['username']}) - {$u['role']} - {$u['status']}\n";
    }
} else {
    echo "   ⚠️  No other users in this station!\n";
    echo "   Users table will still show only the admin user.\n";
}

echo "\n=== FIX APPLIED SUCCESSFULLY ===\n\n";

echo "NEXT STEPS:\n";
echo "1. Logout from the application\n";
echo "2. Login again as admin\n";
echo "3. Go to users.php\n";
echo "4. You should now see users in the table!\n\n";

echo "✅ ALL DONE!\n";
