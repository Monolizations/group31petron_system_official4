<?php
/**
 * Migration Verification Script for Pump Tracking
 * 
 * This script verifies that all required tables and columns exist
 * and are properly configured for the fuel POS pump tracking feature.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';

$status = [];
$errors = [];
$warnings = [];

try {
    // Check 1: nozzles table exists
    $result = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='petron_pos_db_secure' AND table_name='nozzles'");
    if ($result->fetch()) {
        $status['nozzles_table'] = 'EXISTS';
        
        // Check nozzles columns
        $nozzles_columns = [];
        $result = $pdo->query("DESCRIBE nozzles");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $nozzles_columns[$row['Field']] = $row['Type'];
        }
        
        $required_nozzles = ['id', 'pump_id', 'nozzle_number', 'fuel_type_id', 'calibration_value', 'status'];
        foreach ($required_nozzles as $col) {
            if (!isset($nozzles_columns[$col])) {
                $errors[] = "nozzles table missing column: $col";
            }
        }
        
        if (empty($errors)) {
            $status['nozzles_columns'] = 'VALID';
        }
    } else {
        $errors[] = "nozzles table does NOT exist. Run: sql/add_nozzles_table.sql";
    }
    
    // Check 2: sales table pump_id column
    $result = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='petron_pos_db_secure' AND table_name='sales' AND column_name='pump_id'");
    if ($result->fetch()) {
        $status['sales_pump_id'] = 'EXISTS';
    } else {
        $warnings[] = "sales table missing pump_id column. Run: sql/add_pump_id_to_sales.sql";
        $status['sales_pump_id'] = 'MISSING';
    }
    
    // Check 3: sale_items table pump_id and nozzle_id columns
    $result = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='petron_pos_db_secure' AND table_name='sale_items' AND column_name='pump_id'");
    $has_pump_id = $result->fetch() ? true : false;
    
    $result = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='petron_pos_db_secure' AND table_name='sale_items' AND column_name='nozzle_id'");
    $has_nozzle_id = $result->fetch() ? true : false;
    
    if ($has_pump_id && $has_nozzle_id) {
        $status['sale_items_pump_nozzle'] = 'EXISTS';
    } else {
        if (!$has_pump_id) {
            $warnings[] = "sale_items table missing pump_id column.";
        }
        if (!$has_nozzle_id) {
            $warnings[] = "sale_items table missing nozzle_id column.";
        }
        $warnings[] = "Run: sql/add_pump_tracking_to_sale_items.sql";
        $status['sale_items_pump_nozzle'] = 'MISSING';
    }
    
    // Check 4: fuel_pumps table exists
    $result = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='petron_pos_db_secure' AND table_name='fuel_pumps'");
    if ($result->fetch()) {
        $status['fuel_pumps_table'] = 'EXISTS';
    } else {
        $errors[] = "fuel_pumps table does NOT exist";
    }
    
    // Check 5: fuel_types table exists
    $result = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='petron_pos_db_secure' AND table_name='fuel_types'");
    if ($result->fetch()) {
        $status['fuel_types_table'] = 'EXISTS';
    } else {
        $errors[] = "fuel_types table does NOT exist";
    }
    
    // Check 6: Test data - count records
    $result = $pdo->query("SELECT COUNT(*) as count FROM fuel_pumps");
    $pump_count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    $status['fuel_pumps_count'] = $pump_count;
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM nozzles");
    $nozzle_count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    $status['nozzles_count'] = $nozzle_count;
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM sales");
    $sales_count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    $status['sales_count'] = $sales_count;
    
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Output results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pump Tracking Migration Verification</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 4px; max-width: 800px; }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 10px; border-left: 4px solid #ccc; }
        .status { padding: 10px; margin: 5px 0; border-radius: 3px; }
        .exists { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .valid { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .missing { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Pump Tracking Migration Status</h1>
        <p>Database: <code>petron_pos_db_secure</code></p>
        
        <div class="section">
            <h2>Status Summary</h2>
            <?php foreach ($status as $key => $value): ?>
                <div class="status <?php 
                    if (is_numeric($value)) echo 'info';
                    elseif ($value === 'EXISTS' || $value === 'VALID') echo 'exists';
                    else echo 'missing';
                ?>">
                    <strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="section">
                <h2>❌ Critical Errors</h2>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li class="error"><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($warnings)): ?>
            <div class="section">
                <h2>⚠️ Warnings/Required Actions</h2>
                <ul>
                    <?php foreach ($warnings as $warning): ?>
                        <li class="status missing"><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($errors) && empty($warnings)): ?>
            <div class="section">
                <h2>✅ All Checks Passed!</h2>
                <p>The database is fully configured for pump tracking integration.</p>
                <p>Ready to proceed with Phase 2: Backend APIs</p>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Next Steps</h2>
            <ol>
                <?php if ($status['sales_pump_id'] === 'MISSING'): ?>
                    <li>Run migration: <code>sql/add_pump_id_to_sales.sql</code></li>
                <?php endif; ?>
                <?php if ($status['sale_items_pump_nozzle'] === 'MISSING'): ?>
                    <li>Run migration: <code>sql/add_pump_tracking_to_sale_items.sql</code></li>
                <?php endif; ?>
                <li>Re-run this verification script to confirm all migrations</li>
                <li>Proceed to Phase 2: Create backend APIs (pumps.php, nozzles.php)</li>
            </ol>
        </div>
    </div>
</body>
</html>
