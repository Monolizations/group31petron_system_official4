<?php
/**
 * Simple Column Addition Script
 * This script safely adds missing columns without complex checks
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/public/db_connect.php';

echo "<h2>🔧 Add Missing Columns to job_orders Table</h2>";
echo "<hr>";

$station_id = 226;

// Columns to add with their definitions
$columns_to_add = [
    'is_locked' => "ALTER TABLE job_orders ADD COLUMN is_locked TINYINT(1) DEFAULT 0 COMMENT 'Whether job order is locked from further edits'",
    'billing_locked' => "ALTER TABLE job_orders ADD COLUMN billing_locked TINYINT(1) DEFAULT 0 COMMENT 'Whether billing is finalized and locked'"
];

$added = 0;
$skipped = 0;

foreach ($columns_to_add as $column_name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green;'>✅ Added column: <strong>$column_name</strong></p>";
        $added++;
    } catch (PDOException $e) {
        // Check if error is "duplicate column"
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color:orange;'>⚠️ Column <strong>$column_name</strong> already exists - skipping</p>";
            $skipped++;
        } else {
            echo "<p style='color:red;'>❌ Error adding column <strong>$column_name</strong>: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Results Summary</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
echo "<tr style='background:#f0f0f0;'><th>Action</th><th>Count</th></tr>";
echo "<tr><td style='color:green;'>✅ Columns Added</td><td><strong>$added</strong></td></tr>";
echo "<tr><td style='color:orange;'>⚠️ Already Existed</td><td><strong>$skipped</strong></td></tr>";
echo "</table>";

echo "<hr>";
echo "<h3>Verification</h3>";

try {
    // Verify both columns exist now
    $stmt = $pdo->query("DESCRIBE job_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($columns, 'Field');

    $all_exist = true;
    foreach (['is_locked', 'billing_locked'] as $required_col) {
        if (!in_array($required_col, $column_names)) {
            $all_exist = false;
            echo "<p style='color:red;'>❌ Missing column: <strong>$required_col</strong></p>";
        }
    }

    if ($all_exist) {
        echo "<p style='color:green; font-size:18px;'>✅ SUCCESS! All required columns now exist.</p>";
        
        // Show test query
        echo "<p>Test query:</p>";
        $test = $pdo->prepare("SELECT id, job_order_number, status, is_locked, staff_editable, billing_locked FROM job_orders WHERE station_id = ? LIMIT 1");
        $test->execute([$station_id]);
        $result = $test->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            echo "<tr style='background:#e8f5e9;'><th>Column</th><th>Value</th></tr>";
            foreach (['id', 'job_order_number', 'status', 'is_locked', 'staff_editable', 'billing_locked'] as $col) {
                $value = array_key_exists($col, $result) ? $result[$col] : 'NULL';
                echo "<tr><td><strong>$col</strong></td><td>" . var_export($value, true) . "</td></tr>";
            }
            echo "</table>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Verification failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Migration Complete!</h3>";

if ($added > 0) {
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='public/joborder.php?tab=create'>Job Order Creation</a></li>";
    echo "<li>Go to <a href='public/joborder.php?tab=ongoing'>Ongoing Jobs</a> and test creating/completing jobs</li>";
    echo "<li>Verify security validation works correctly</li>";
    echo "</ol>";
} else {
    echo "<p style='color:green;'><strong>🎊 All columns already existed! No changes needed.</strong></p>";
}
