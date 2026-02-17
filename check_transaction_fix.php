<?php
/**
 * Test: Simulate form submission to fuel_process_verification.php
 * This simulates what the browser does when clicking "Submit Verification"
 */

// Include the actual backend file (but not execute it)
$backend_code = file_get_contents(__DIR__ . '/backend/fuel_process_verification.php');

echo "=== CHECKING FUEL PROCESS VERIFICATION CODE ===\n\n";

// Check for nested transaction issue
if (strpos($backend_code, "\$pdo->beginTransaction()") !== false) {
    echo "✓ Outer transaction found in fuel_process_verification.php\n";
}

if (strpos($backend_code, "function recordStockMovement") !== false) {
    echo "✓ recordStockMovement function found in inventory_automation.php\n";
}

// Check if inventory_automation.php has its own transaction
$inv_auto_code = file_get_contents(__DIR__ . '/backend/inventory_automation.php');
$has_transaction_start = strpos($inv_auto_code, "\$pdo->beginTransaction()") !== false;
$has_transaction_commit = strpos($inv_auto_code, "\$pdo->commit()") !== false;
$has_transaction_rollback = strpos($inv_auto_code, "\$pdo->rollBack()") !== false;

echo "\n=== TRANSACTION ANALYSIS ===\n";
if ($has_transaction_start) {
    echo "❌ inventory_automation.php STARTS transaction\n";
    echo "   This creates NESTED TRANSACTION issue!\n";
    echo "   fuel_process_verification.php already starts a transaction\n";
    echo "   Then recordStockMovement() tries to start another one\n";
} else {
    echo "✓ inventory_automation.php does NOT start transaction\n";
    echo "  This prevents nested transaction issue\n";
}

if ($has_transaction_commit && $has_transaction_rollback) {
    echo "❌ inventory_automation.php has commit/rollback\n";
    echo "  This will cause issues with outer transaction\n";
} else {
    echo "✓ inventory_automation.php has NO commit/rollback\n";
    echo "  Outer transaction handles commit/rollback\n";
}

echo "\n=== EXPECTED BEHAVIOR ===\n";
if ($has_transaction_start) {
    echo "ERROR: Nested transactions cause 500 error\n";
    echo "FIX: Remove beginTransaction/commit/rollback from recordStockMovement\n";
} else {
    echo "OK: Single transaction flow, should work correctly\n";
}

echo "\n=== END ===\n";
?>
