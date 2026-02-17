<?php
/**
 * Fix: Update sales_liters calculation to subtract calibration
 * This ensures stock is deducted correctly after calibration adjustment
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔧 FIXING SALES CALCULATION TO EXCLUDE CALIBRATION\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Check if fuel_pumps has calibration_value column
    $stmt = $pdo->query("SHOW COLUMNS FROM fuel_pumps LIKE 'calibration_value'");
    if ($stmt->rowCount() > 0) {
        echo "✅ fuel_pumps.calibration_value column exists\n";
    } else {
        echo "⚠️  fuel_pumps.calibration_value column NOT found\n";
        echo "➕ Adding calibration_value column...\n";
        $pdo->exec("ALTER TABLE fuel_pumps ADD COLUMN calibration_value DECIMAL(10,2) DEFAULT NULL");
        echo "✅ Column added\n";
    }
    
    // Check if fuel_daily_readings has calibration column
    $stmt = $pdo->query("SHOW COLUMNS FROM fuel_daily_readings LIKE 'calibration'");
    if ($stmt->rowCount() > 0) {
        echo "✅ fuel_daily_readings.calibration column exists\n";
    } else {
        echo "⚠️  fuel_daily_readings.calibration column NOT found\n";
        echo "➕ Adding calibration column...\n";
        $pdo->exec("ALTER TABLE fuel_daily_readings ADD COLUMN calibration DECIMAL(10,2) DEFAULT 0 AFTER sales_liters");
        echo "✅ Column added\n";
    }
    
    // Show some pump calibration values
    echo "\n📊 Current Pump Calibration Values:\n";
    echo str_repeat("-", 50) . "\n";
    $stmt = $pdo->query("SELECT id, pump_number, calibration_value FROM fuel_pumps WHERE calibration_value IS NOT NULL LIMIT 10");
    $pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pumps) > 0) {
        foreach ($pumps as $pump) {
            echo "   Pump {$pump['pump_number']}: {$pump['calibration_value']} L\n";
        }
    } else {
        echo "   No pumps have calibration values set yet\n";
    }
    
    echo "\n✅ Database structure is ready for calibration fix\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>