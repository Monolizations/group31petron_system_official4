<?php
/**
 * Add Missing Columns to job_orders Table
 *
 * This script adds columns that are referenced in security_validator.php
 * but are missing from the actual database schema:
 * - is_locked
 * - staff_editable
 * - billing_locked
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/public/db_connect.php';

echo "<h2>🔧 Add Missing Columns to job_orders Table</h2>";
echo "<p>This script adds columns required by security_validator.php</p>";
echo "<hr>";

echo "<h3>Step 1: Check Current Table Structure</h3>";

try {
    // Get current columns
    $stmt = $pdo->query("DESCRIBE job_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $existing_columns = array_column($columns, 'Field');

    echo "<p>Current columns in job_orders table:</p>";
    echo "<ul style='font-family:monospace;'>";
    foreach ($existing_columns as $col) {
        $exists = in_array($col, $existing_columns) ? '✅' : '❌';
        echo "<li>$exists $col</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";

echo "<h3>Step 2: Add Missing Columns</h3>";

try {
    $added_columns = [];
    $existing_columns = [];

    // Column 1: is_locked
    if (!in_array('is_locked', $existing_columns)) {
        echo "<p>Adding column: <strong>is_locked</strong>...</p>";
        $sql = "ALTER TABLE job_orders ADD COLUMN is_locked TINYINT(1) DEFAULT 0 COMMENT 'Whether job order is locked from further edits'";
        $pdo->exec($sql);
        $added_columns[] = 'is_locked';
        echo "<p style='color:green;'>✅ Added: is_locked</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Already exists: is_locked</p>";
        $existing_columns[] = 'is_locked';
    }

    // Column 2: staff_editable
    if (!in_array('staff_editable', $existing_columns)) {
        echo "<p>Adding column: <strong>staff_editable</strong>...</p>";
        $sql = "ALTER TABLE job_orders ADD COLUMN staff_editable TINYINT(1) DEFAULT 1 COMMENT 'Whether staff can still edit the job order'";
        $pdo->exec($sql);
        $added_columns[] = 'staff_editable';
        echo "<p style='color:green;'>✅ Added: staff_editable</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Already exists: staff_editable</p>";
        $existing_columns[] = 'staff_editable';
    }

    // Column 3: billing_locked
    if (!in_array('billing_locked', $existing_columns)) {
        echo "<p>Adding column: <strong>billing_locked</strong>...</p>";
        $sql = "ALTER TABLE job_orders ADD COLUMN billing_locked TINYINT(1) DEFAULT 0 COMMENT 'Whether billing is finalized and locked'";
        $pdo->exec($sql);
        $added_columns[] = 'billing_locked';
        echo "<p style='color:green;'>✅ Added: billing_locked</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Already exists: billing_locked</p>";
        $existing_columns[] = 'billing_locked';
    }

    echo "<hr>";
    echo "<h3>Step 3: Results Summary</h3>";

    echo "<p><strong>" . count($added_columns) . "</strong> columns added:</p>";
    echo "<ul style='font-family:monospace;'>";
    foreach ($added_columns as $col) {
        echo "<li style='color:green;'>✅ $col</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color:red; font-size:18px;'>❌ Migration Failed!</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";

echo "<h3>Step 4: Verification</h3>";

try {
    // Verify columns were added
    $stmt = $pdo->query("DESCRIBE job_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $final_columns = array_column($columns, 'Field');

    $all_exist = true;
    $missing = [];

    foreach (['is_locked', 'staff_editable', 'billing_locked'] as $required_col) {
        if (!in_array($required_col, $final_columns)) {
            $all_exist = false;
            $missing[] = $required_col;
        }
    }

    if ($all_exist) {
        echo "<p style='color:green; font-size:18px;'>✅ SUCCESS! All required columns exist.</p>";

        echo "<p>Final column list:</p>";
        echo "<ul style='font-family:monospace;'>";
        foreach (['is_locked', 'staff_editable', 'billing_locked'] as $col) {
            $exists = in_array($col, $final_columns) ? '✅' : '❌';
            echo "<li>$exists $col</li>";
        }
        echo "</ul>";

    } else {
        echo "<p style='color:red;'>❌ ERROR! Missing columns:</p>";
        echo "<ul style='font-family:monospace;'>";
        foreach ($missing as $col) {
            echo "<li>❌ $col</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    exit(1);
}

echo "<hr>";
echo "<h3>Step 5: Test Job Order Query</h3>";

try {
    $station_id = 226;

    // Test a simple query to job_orders
    $stmt = $pdo->prepare("SELECT id, job_order_number, status, is_locked, staff_editable, billing_locked FROM job_orders WHERE station_id = ? LIMIT 1");
    $stmt->execute([$station_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "<p style='color:green;'>✅ Query successful!</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#e8f5e9;'><th>Column</th><th>Value</th></tr>";
        foreach (['id', 'job_order_number', 'status', 'is_locked', 'staff_editable', 'billing_locked'] as $col) {
            $value = array_key_exists($col, $result) ? $result[$col] : 'NULL';
            echo "<tr>";
            echo "<td><strong>$col</strong></td>";
            echo "<td>" . var_export($value, true) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No job orders found for station 226 (this is expected if no jobs exist)</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Query Failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Migration Complete!</h3>";

if (count($added_columns) > 0) {
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='public/joborder.php?tab=create'>Job Order Creation</a></li>";
    echo "<li>Go to <a href='public/joborder.php?tab=ongoing'>Ongoing Jobs</a></li>";
    echo "<li>Test creating and completing job orders</li>";
    echo "<li>Verify security validations work correctly</li>";
    echo "</ol>";
} else {
    echo "<p style='color:green;'><strong>🎊 All columns already existed! No changes needed.</strong></p>";
}
