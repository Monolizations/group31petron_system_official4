<?php
/**
 * Complete Fuel Setup Script
 * Sets up fuel_type_id, fuel_inventory, and fuel_pricing all at once
 */

require_once __DIR__ . '/db_connect.php';

session_start();
$station_id = isset($_GET['station_id']) ? intval($_GET['station_id']) : (isset($_SESSION['station_id']) ? $_SESSION['station_id'] : 1);

$step = isset($_GET['step']) ? $_GET['step'] : '1';
$message = '';
$success = false;
$details = [];
$error = '';

try {
    if ($step === '1') {
        // Step 1: Add fuel_type_id column if missing
        try {
            $check = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'fuel_type_id'");
            $check->execute();
            
            if ($check->rowCount() === 0) {
                $pdo->exec("ALTER TABLE products ADD COLUMN fuel_type_id INT(11) NULL COMMENT 'Link to fuel_types for fuel products' AFTER type_id");
                $message = "Added fuel_type_id column. Click next to continue.";
            } else {
                $message = "Column fuel_type_id already exists. Click next to continue.";
            }
            $success = true;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $message = "Column fuel_type_id already exists. Click next to continue.";
                $success = true;
            } else {
                throw $e;
            }
        }
    }
    
    else if ($step === '2') {
        // Step 2: Assign fuel_type_id to fuel products
        $updates = array(
            1 => array('gasoline', 'premium gasoline'),
            2 => array('diesel'),
            3 => array('lpg'),
            5 => array('unleaded')
        );
        
        $totalUpdated = 0;
        foreach ($updates as $fuel_type_id => $keywords) {
            foreach ($keywords as $keyword) {
                $stmt = $pdo->prepare("UPDATE products SET fuel_type_id = ? WHERE type_id = 1 AND LOWER(name) LIKE ? AND fuel_type_id IS NULL");
                $stmt->execute(array($fuel_type_id, "%$keyword%"));
                $count = $stmt->rowCount();
                if ($count > 0) {
                    $details[] = "Assigned {$count} products to fuel_type_id={$fuel_type_id} (keyword: '{$keyword}')";
                    $totalUpdated += $count;
                }
            }
        }
        
        // Default to fuel_type_id = 1 for remaining unassigned fuel products
        $stmt = $pdo->prepare("UPDATE products SET fuel_type_id = 1 WHERE type_id = 1 AND fuel_type_id IS NULL");
        $stmt->execute();
        $defaultCount = $stmt->rowCount();
        if ($defaultCount > 0) {
            $details[] = "Set default fuel_type_id=1 for {$defaultCount} remaining fuel products";
            $totalUpdated += $defaultCount;
        }
        
        $message = "Assigned fuel_type_id to {$totalUpdated} fuel products. Click next to continue.";
        $success = true;
    }
    
    else if ($step === '3') {
        // Step 3: Create fuel_inventory records if missing
        $stmt = $pdo->prepare("SELECT p.id, p.name FROM products p WHERE p.type_id = 1 AND p.fuel_type_id IS NOT NULL");
        $stmt->execute();
        $fuel_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $created = 0;
        foreach ($fuel_products as $fp) {
            // Check if inventory exists for this station
            $check = $pdo->prepare("SELECT id FROM fuel_inventory WHERE station_id = ? AND product_id = ?");
            $check->execute(array($station_id, $fp['id']));
            
            if ($check->rowCount() === 0) {
                // Create with default stock of 500 liters
                $insert = $pdo->prepare("INSERT INTO fuel_inventory (station_id, product_id, stock_level, unit, capacity, status, last_updated) VALUES (?, ?, 500.00, 'liters', 5000.00, 'active', NOW())");
                $insert->execute(array($station_id, $fp['id']));
                $created++;
                $details[] = "Created fuel_inventory for '{$fp['name']}' (500L default stock)";
            }
        }
        
        $message = "Created {$created} fuel_inventory records for station {$station_id}. Click next to continue.";
        $success = true;
    }
    
    else if ($step === '4') {
        // Step 4: Create fuel_pricing records if missing
        $stmt = $pdo->prepare("SELECT DISTINCT fuel_type_id FROM products WHERE type_id = 1 AND fuel_type_id IS NOT NULL ORDER BY fuel_type_id");
        $stmt->execute();
        $fuel_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $created = 0;
        $prices = array(
            1 => 55.00,  // Gasoline
            2 => 60.00,  // Diesel
            3 => 45.00,  // LPG
            5 => 58.00   // Unleaded
        );
        
        foreach ($fuel_types as $ft) {
            $fuel_type_id = $ft['fuel_type_id'];
            
            // Check if pricing exists
            $check = $pdo->prepare("SELECT id FROM fuel_pricing WHERE station_id = ? AND fuel_type_id = ?");
            $check->execute(array($station_id, $fuel_type_id));
            
            if ($check->rowCount() === 0) {
                $price = isset($prices[$fuel_type_id]) ? $prices[$fuel_type_id] : 50.00;
                $insert = $pdo->prepare("INSERT INTO fuel_pricing (station_id, fuel_type_id, price_per_liter, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
                $insert->execute(array($station_id, $fuel_type_id, $price));
                $created++;
                
                // Get fuel type name
                $nameStmt = $pdo->prepare("SELECT name FROM fuel_types WHERE id = ?");
                $nameStmt->execute(array($fuel_type_id));
                $nameResult = $nameStmt->fetch(PDO::FETCH_ASSOC);
                $fuelName = $nameResult ? $nameResult['name'] : "Type {$fuel_type_id}";
                
                $details[] = "Created fuel_pricing for '{$fuelName}' at ₱{$price}/L";
            }
        }
        
        $message = "Created {$created} fuel_pricing records for station {$station_id}. Click next to verify!";
        $success = true;
    }
    
    else if ($step === '5') {
        // Step 5: Verify everything is set up
        $stmt = $pdo->prepare("
            SELECT ft.name, COUNT(DISTINCT fi.id) as inv_count, 
                   MAX(fp.price_per_liter) as price
            FROM fuel_types ft
            LEFT JOIN products p ON p.fuel_type_id = ft.id AND p.type_id = 1
            LEFT JOIN fuel_inventory fi ON fi.product_id = p.id AND fi.station_id = ?
            LEFT JOIN fuel_pricing fp ON fp.fuel_type_id = ft.id AND fp.station_id = ? AND fp.is_active = 1
            GROUP BY ft.id
            ORDER BY ft.name
        ");
        $stmt->execute(array($station_id, $station_id));
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html = "Fuel Configuration for Station {$station_id}:\n\n";
        $html .= "Fuel Type          | Inventory | Price/Liter\n";
        $html .= str_repeat("-", 50) . "\n";
        
        $count = 0;
        foreach ($results as $r) {
            $invCount = $r['inv_count'] ? "✓" : "✗";
            $price = $r['price'] ? "₱" . $r['price'] : "✗";
            $html .= str_pad($r['name'], 18) . " | " . str_pad($invCount, 9) . " | " . $price . "\n";
            if ($r['price'] && $r['inv_count'] > 0) $count++;
        }
        
        $details[] = $html;
        $message = "Setup complete! {$count} fuel types fully configured for station {$station_id}.";
        $success = true;
    }
    
} catch (Exception $e) {
    $message = "Error in Step {$step}";
    $error = $e->getMessage();
    $success = false;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fuel Configuration Setup</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .step-info { background: #e7f3ff; padding: 12px; margin: 15px 0; border-left: 4px solid #007bff; border-radius: 4px; }
        .success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; margin: 15px 0; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; margin: 15px 0; border-radius: 4px; }
        .details { background: #f9f9f9; padding: 15px; margin: 15px 0; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; white-space: pre-wrap; font-size: 13px; max-height: 400px; overflow-y: auto; }
        .buttons { margin-top: 20px; display: flex; gap: 10px; }
        button { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #0056b3; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .status { font-size: 12px; color: #666; margin-top: 20px; padding: 10px; background: #f9f9f9; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fuel System Complete Setup</h1>
        <p style="color: #666; margin-bottom: 20px;">Configures fuel_type_id, inventory, and pricing all in one</p>
        
        <div class="step-info">
            <strong>Progress:</strong> Step <?php echo htmlspecialchars($step); ?>/5
            <div style="margin-top: 8px; background: white; border-radius: 3px; overflow: hidden; height: 6px;">
                <div style="background: #28a745; height: 100%; width: <?php echo ($step/5)*100; ?>%;"></div>
            </div>
        </div>
        
        <div class="<?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? '✓' : '✗'; ?> <?php echo htmlspecialchars($message); ?>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error">
            <strong>Technical Details:</strong><br/>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($details)): ?>
        <div class="details">
            <?php foreach ($details as $detail): ?>
                <?php echo htmlspecialchars($detail); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="buttons">
            <?php if ($step < 5): ?>
                <button onclick="location.href='?step=<?php echo intval($step) + 1; ?>&station_id=<?php echo $station_id; ?>' <?php echo $success ? '' : 'disabled'; ?>>
                    Next Step →
                </button>
            <?php else: ?>
                <button onclick="location.href='/group31petron_system_official4/public/pos.php'">
                    Go to POS ✓
                </button>
                <button onclick="location.href='debug_fuel_config.php'">
                    Verify Configuration
                </button>
            <?php endif; ?>
        </div>
        
        <div class="status">
            <strong>Station ID:</strong> <?php echo htmlspecialchars($station_id); ?>
            | <strong>Step:</strong> <?php echo htmlspecialchars($step); ?>/5
            | <strong>Status:</strong> <?php echo $success ? '✓ Ready' : '✗ Failed'; ?>
        </div>
    </div>
</body>
</html>
