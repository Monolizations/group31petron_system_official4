<?php
/**
 * Web-based Migration Runner for fuel_type_id column
 * Visit this page in browser to run the migration
 */

require_once __DIR__ . '/db_connect.php';

$step = isset($_GET['step']) ? $_GET['step'] : '1';
$message = '';
$success = false;
$details = [];
$error = '';

try {
    // Step 1: Check if column exists
    if ($step === '1') {
        try {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'fuel_type_id'");
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $message = "Column 'fuel_type_id' already exists. Migration complete!";
                $success = true;
            } else {
                $message = "Column does not exist. Ready to migrate. Click below to proceed.";
            }
        } catch (Exception $e) {
            $message = "Column 'fuel_type_id' likely already exists or check failed.";
            $success = false;
        }
    }
    
    // Step 2: Add column
    else if ($step === '2') {
        try {
            // First, check if column already exists
            $check = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'fuel_type_id'");
            $check->execute();
            
            if ($check->rowCount() > 0) {
                $message = "Column 'fuel_type_id' already exists!";
                $success = true;
            } else {
                // Add column without foreign key first (to avoid constraint issues)
                $pdo->exec("ALTER TABLE products ADD COLUMN fuel_type_id INT(11) NULL COMMENT 'Link to fuel_types for fuel products' AFTER type_id");
                $message = "Column added successfully. Click below to update values.";
                $success = true;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            $message = "Error adding column: " . $error;
            $success = false;
        }
    }
    
    // Step 3: Update values
    else if ($step === '3') {
        try {
            $updates = array(
                1 => array('gasoline', 'premium'),
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
                        $details[] = "Updated $count products with keyword '$keyword' → fuel_type_id=$fuel_type_id";
                        $totalUpdated += $count;
                    }
                }
            }
            
            // Set default fuel_type_id = 1 for any remaining fuel products without assignment
            $stmt = $pdo->prepare("UPDATE products SET fuel_type_id = 1 WHERE type_id = 1 AND fuel_type_id IS NULL");
            $stmt->execute();
            $defaultCount = $stmt->rowCount();
            if ($defaultCount > 0) {
                $details[] = "Set default fuel_type_id=1 for $defaultCount remaining fuel products";
                $totalUpdated += $defaultCount;
            }
            
            $message = "Updated $totalUpdated fuel products. Migration complete!";
            $success = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            $message = "Error updating values: " . $error;
            $success = false;
        }
    }
    
    // Step 4: Verify
    else if ($step === '4') {
        try {
            $stmt = $pdo->prepare("SELECT p.id, p.name, p.fuel_type_id, ft.name as fuel_type_name 
                                   FROM products p 
                                   LEFT JOIN fuel_types ft ON p.fuel_type_id = ft.id 
                                   WHERE p.type_id = 1 
                                   ORDER BY p.id");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = "Fuel Products Configured:\n\n";
            foreach ($results as $product) {
                $ft = $product['fuel_type_name'] ? $product['fuel_type_name'] : 'NOT SET';
                $html .= "ID {$product['id']}: {$product['name']} → fuel_type_id={$product['fuel_type_id']} ({$ft})\n";
            }
            $details[] = $html;
            
            $message = "Verification complete. Total fuel products: " . count($results);
            $success = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            $message = "Error verifying: " . $error;
            $success = false;
        }
    }
    
} catch (Exception $e) {
    $message = "Unexpected error: " . $e->getMessage();
    $success = false;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fuel Type ID Migration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; border-left: 4px solid #28a745; padding-left: 10px; margin: 10px 0; padding: 10px; }
        .error { color: #dc3545; border-left: 4px solid #dc3545; padding-left: 10px; margin: 10px 0; padding: 10px; }
        .details { background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 4px; white-space: pre-wrap; font-size: 12px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #0056b3; }
        .step-info { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fuel Type ID Migration</h1>
        
        <div class="step-info">
            <strong>Current Step:</strong> <?php echo htmlspecialchars($step); ?>/4
        </div>
        
        <div class="<?php echo $success ? 'success' : 'error'; ?>">
            <strong><?php echo $success ? '✓' : '✗'; ?> <?php echo htmlspecialchars($message); ?></strong>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error" style="margin-top: 10px;">
            <strong>Technical Error:</strong><br/>
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
        
        <div>
            <?php if ($step === '1' && !$success): ?>
                <button onclick="location.href='?step=2'">→ Add Column</button>
            <?php elseif ($step === '2' && $success): ?>
                <button onclick="location.href='?step=3'">→ Update Values</button>
            <?php elseif ($step === '3' && $success): ?>
                <button onclick="location.href='?step=4'">→ Verify Results</button>
            <?php elseif ($step === '4' && $success): ?>
                <button onclick="location.href='/group31petron_system_official4/public/pos.php'">→ Go to POS</button>
            <?php elseif ($success): ?>
                <button onclick="location.href='?step=<?php echo intval($step) + 1; ?>'">→ Next Step</button>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
