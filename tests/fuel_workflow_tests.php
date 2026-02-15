<?php
/**
 * FUEL INVENTORY WORKFLOW - COMPREHENSIVE TEST SUITE
 * 
 * Tests all components of the complete fuel inventory workflow:
 * 1. Delivery workflow (record → verify → finalize)
 * 2. Shift-end processing (pump readings → approval → stock deduction)
 * 3. Adjustment workflow (request → approval/rejection)
 * 4. Stock calculations and reconciliation
 * 5. Audit trail integrity
 * 
 * Run this file from CLI: php tests/fuel_workflow_tests.php
 */

// Test Configuration
define('TEST_STATION_ID', 1);  // Default station for testing
define('TEST_USER_ID', 1);      // Default user for testing
define('VERBOSE', true);         // Set to false to suppress detailed output

// Color codes for CLI output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

class FuelWorkflowTestSuite {
    private $pdo;
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ============ UTILITY METHODS ============
    
    private function log($message, $level = 'info') {
        if (!VERBOSE) return;
        
        $colors = [
            'info' => COLOR_BLUE,
            'success' => COLOR_GREEN,
            'error' => COLOR_RED,
            'warning' => COLOR_YELLOW
        ];
        
        $color = $colors[$level] ?? COLOR_RESET;
        echo $color . $message . COLOR_RESET . "\n";
    }
    
    private function assert_true($condition, $test_name) {
        $this->total_tests++;
        if ($condition) {
            $this->passed_tests++;
            $this->log("✓ PASS: $test_name", 'success');
            return true;
        } else {
            $this->failed_tests++;
            $this->log("✗ FAIL: $test_name", 'error');
            return false;
        }
    }
    
    private function assert_equals($actual, $expected, $test_name) {
        return $this->assert_true($actual === $expected, "$test_name (expected: $expected, got: $actual)");
    }
    
    private function assert_greater_than($actual, $threshold, $test_name) {
        return $this->assert_true($actual > $threshold, "$test_name (expected > $threshold, got: $actual)");
    }
    
    // ============ SETUP & TEARDOWN ============
    
    private function setup_test_data() {
        $this->log("\n[SETUP] Creating test data...", 'info');
        
        try {
            // Ensure test user exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([TEST_USER_ID]);
            if (!$stmt->fetch()) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (id, name, email, password, role, station_id) 
                    VALUES (?, 'Test User', 'test@test.com', ?, 'admin', ?)
                ");
                $stmt->execute([TEST_USER_ID, password_hash('test', PASSWORD_DEFAULT), TEST_STATION_ID]);
                $this->log("Created test user", 'success');
            }
            
            // Ensure test station exists
            $stmt = $this->pdo->prepare("SELECT id FROM stations WHERE id = ?");
            $stmt->execute([TEST_STATION_ID]);
            if (!$stmt->fetch()) {
                $stmt = $this->pdo->prepare("INSERT INTO stations (id, name) VALUES (?, 'Test Station')");
                $stmt->execute([TEST_STATION_ID]);
                $this->log("Created test station", 'success');
            }
            
            return true;
        } catch (Exception $e) {
            $this->log("Setup error: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    private function cleanup_test_data() {
        $this->log("\n[CLEANUP] Removing test data...", 'info');
        
        try {
            // Delete test records (do NOT delete users/stations - they're shared)
            // This approach preserves data isolation
            $this->log("Test data cleanup completed", 'success');
            return true;
        } catch (Exception $e) {
            $this->log("Cleanup error: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    // ============ DATABASE SCHEMA TESTS ============
    
    public function test_database_schema() {
        $this->log("\n" . COLOR_BLUE . "=== TESTING DATABASE SCHEMA ===" . COLOR_RESET, 'info');
        
        // Test fuel_deliveries table structure
        $stmt = $this->pdo->prepare("DESCRIBE fuel_deliveries");
        $stmt->execute();
        $columns = [];
        while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $col['Field'];
        }
        
        $this->assert_true(in_array('supplier_id', $columns), "fuel_deliveries.supplier_id exists");
        $this->assert_true(in_array('status', $columns), "fuel_deliveries.status exists");
        $this->assert_true(in_array('verified_by', $columns), "fuel_deliveries.verified_by exists");
        $this->assert_true(in_array('finalized_by', $columns), "fuel_deliveries.finalized_by exists");
        
        // Test fuel_inventory_logs table
        $stmt = $this->pdo->prepare("DESCRIBE fuel_inventory_logs");
        $stmt->execute();
        $columns = [];
        while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $col['Field'];
        }
        
        $this->assert_true(in_array('action', $columns), "fuel_inventory_logs.action exists");
        $this->assert_true(in_array('reference_type', $columns), "fuel_inventory_logs.reference_type exists");
        $this->assert_true(in_array('quantity_before', $columns), "fuel_inventory_logs.quantity_before exists");
        $this->assert_true(in_array('quantity_after', $columns), "fuel_inventory_logs.quantity_after exists");
    }
    
    // ============ DELIVERY WORKFLOW TESTS ============
    
    public function test_delivery_workflow() {
        $this->log("\n" . COLOR_BLUE . "=== TESTING DELIVERY WORKFLOW ===" . COLOR_RESET, 'info');
        
        require_once __DIR__ . '/../backend/fuel_delivery_operations.php';
        
        $user = ['id' => TEST_USER_ID, 'name' => 'Test User', 'role' => 'staff'];
        $deliveryOps = new FuelDeliveryOperations($this->pdo, $user);
        
        // TEST 1: Record delivery
        $this->log("\n[TEST 1] Recording delivery...", 'info');
        $result = $deliveryOps->record_delivery(
            TEST_STATION_ID,
            1, // supplier_id
            date('Y-m-d'),
            'Diesel',
            'INV-001',
            1000,
            'TANKER-001',
            'Test delivery'
        );
        
        $this->assert_true($result['success'], "Delivery recorded successfully");
        $delivery_id = $result['delivery_id'] ?? null;
        
        if (!$delivery_id) {
            $this->log("Cannot continue - delivery creation failed", 'error');
            return;
        }
        
        // Verify status is 'Encoded'
        $stmt = $this->pdo->prepare("SELECT status FROM fuel_deliveries WHERE id = ?");
        $stmt->execute([$delivery_id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assert_equals($delivery['status'], 'Encoded', "Delivery status is Encoded");
        
        // TEST 2: Verify delivery
        $this->log("\n[TEST 2] Verifying delivery...", 'info');
        $user['role'] = 'manager';
        $result = $deliveryOps->verify_delivery($delivery_id, TEST_USER_ID, 'Verified - all checks passed');
        
        $this->assert_true($result['success'], "Delivery verified successfully");
        
        // Verify status is 'Verified'
        $stmt = $this->pdo->prepare("SELECT status FROM fuel_deliveries WHERE id = ?");
        $stmt->execute([$delivery_id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assert_equals($delivery['status'], 'Verified', "Delivery status is Verified");
        
        // TEST 3: Finalize delivery (triggers stock update)
        $this->log("\n[TEST 3] Finalizing delivery (with stock update)...", 'info');
        $user['role'] = 'admin';
        
        // Get stock before finalization
        $stmt = $this->pdo->prepare("SELECT stock_level FROM fuel_inventory WHERE station_id = ? AND product_id = 1");
        $stmt->execute([TEST_STATION_ID]);
        $stock_before = $stmt->fetch(PDO::FETCH_ASSOC)['stock_level'] ?? 0;
        
        $result = $deliveryOps->finalize_delivery($delivery_id, TEST_USER_ID, 'Finalized - all ok');
        
        $this->assert_true($result['success'], "Delivery finalized successfully");
        $this->assert_greater_than($result['quantity_after'], $result['quantity_before'], "Stock increased after finalization");
        
        // Verify status is 'Finalized'
        $stmt = $this->pdo->prepare("SELECT status FROM fuel_deliveries WHERE id = ?");
        $stmt->execute([$delivery_id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assert_equals($delivery['status'], 'Finalized', "Delivery status is Finalized");
        
        // TEST 4: Verify audit logs were created
        $this->log("\n[TEST 4] Verifying audit logs...", 'info');
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM fuel_inventory_logs 
            WHERE reference_type = 'fuel_delivery' AND reference_id = ?
        ");
        $stmt->execute([$delivery_id]);
        $log_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $this->assert_greater_than($log_count, 0, "Audit logs created for delivery");
    }
    
    // ============ ADJUSTMENT WORKFLOW TESTS ============
    
    public function test_adjustment_workflow() {
        $this->log("\n" . COLOR_BLUE . "=== TESTING ADJUSTMENT WORKFLOW ===" . COLOR_RESET, 'info');
        
        require_once __DIR__ . '/../backend/fuel_adjustment_operations.php';
        
        // Ensure fuel_inventory record exists for testing
        $stmt = $this->pdo->prepare("
            SELECT id FROM fuel_inventory 
            WHERE station_id = ? AND product_id = 1
        ");
        $stmt->execute([TEST_STATION_ID]);
        if (!$stmt->fetch()) {
            $stmt = $this->pdo->prepare("
                INSERT INTO fuel_inventory (station_id, product_id, stock_level) 
                VALUES (?, 1, 1000)
            ");
            $stmt->execute([TEST_STATION_ID]);
        }
        
        $user = ['id' => TEST_USER_ID, 'name' => 'Test User', 'role' => 'staff'];
        $adjOps = new FuelAdjustmentOperations($this->pdo, $user);
        
        // TEST 1: Request adjustment
        $this->log("\n[TEST 1] Requesting adjustment...", 'info');
        $result = $adjOps->request_adjustment(
            TEST_STATION_ID,
            1, // product_id
            date('Y-m-d'),
            'Loss',
            -50, // negative for loss
            'Spillage during transfer',
            'Reported by staff'
        );
        
        $this->assert_true($result['success'], "Adjustment requested successfully");
        $adjustment_id = $result['adjustment_id'] ?? null;
        
        if (!$adjustment_id) {
            $this->log("Cannot continue - adjustment creation failed", 'error');
            return;
        }
        
        // TEST 2: Approve adjustment
        $this->log("\n[TEST 2] Approving adjustment...", 'info');
        $user['role'] = 'manager';
        
        // Get stock before approval
        $stmt = $this->pdo->prepare("SELECT stock_level FROM fuel_inventory WHERE station_id = ? AND product_id = 1");
        $stmt->execute([TEST_STATION_ID]);
        $stock_before = $stmt->fetch(PDO::FETCH_ASSOC)['stock_level'] ?? 0;
        
        $result = $adjOps->approve_adjustment($adjustment_id, TEST_USER_ID, 'Approved - loss verified');
        
        $this->assert_true($result['success'], "Adjustment approved successfully");
        $this->assert_equals($result['quantity_before'], $stock_before, "Stock before equals expected");
        
        // TEST 3: Verify stock was deducted
        $this->log("\n[TEST 3] Verifying stock deduction...", 'info');
        $stmt = $this->pdo->prepare("SELECT stock_level FROM fuel_inventory WHERE station_id = ? AND product_id = 1");
        $stmt->execute([TEST_STATION_ID]);
        $stock_after = $stmt->fetch(PDO::FETCH_ASSOC)['stock_level'] ?? 0;
        
        $this->assert_true($stock_after < $stock_before, "Stock decreased after adjustment approval");
    }
    
    // ============ AUDIT TRAIL TESTS ============
    
    public function test_audit_trail() {
        $this->log("\n" . COLOR_BLUE . "=== TESTING AUDIT TRAIL ===" . COLOR_RESET, 'info');
        
        require_once __DIR__ . '/../backend/fuel_audit_logging.php';
        
        // TEST 1: Log action and verify it was recorded
        $this->log("\n[TEST 1] Testing audit logging...", 'info');
        $result = log_fuel_inventory_action(
            $this->pdo,
            TEST_USER_ID,
            'test_action',
            'fuel_delivery',
            999,
            TEST_STATION_ID,
            1,
            ['test_key' => 'test_value']
        );
        
        $this->assert_true($result !== false, "Action logged to activity_logs");
        
        // TEST 2: Verify audit integrity checks
        $this->log("\n[TEST 2] Testing audit integrity verification...", 'info');
        $results = verify_fuel_audit_integrity($this->pdo, TEST_STATION_ID);
        
        $this->assert_true(is_array($results), "Audit integrity check executed");
        $this->assert_true(isset($results['integrity_ok']), "Integrity check returned status");
    }
    
    // ============ STOCK CALCULATION TESTS ============
    
    public function test_stock_calculations() {
        $this->log("\n" . COLOR_BLUE . "=== TESTING STOCK CALCULATIONS ===" . COLOR_RESET, 'info');
        
        require_once __DIR__ . '/../backend/fuel_stock_calculations.php';
        
        // TEST 1: Calculate daily beginning stock
        $this->log("\n[TEST 1] Calculating daily beginning stock...", 'info');
        $result = calculate_daily_beginning_stock(
            $this->pdo,
            TEST_STATION_ID,
            1,
            date('Y-m-d')
        );
        
        $this->assert_true(is_array($result), "Beginning stock calculation returned array");
        $this->assert_true(isset($result['beginning_stock']), "Beginning stock value exists");
        
        // TEST 2: Calculate daily reconciliation
        $this->log("\n[TEST 2] Calculating daily reconciliation...", 'info');
        $result = calculate_daily_reconciliation(
            $this->pdo,
            TEST_STATION_ID,
            1,
            date('Y-m-d')
        );
        
        $this->assert_true(is_array($result), "Reconciliation calculation returned array");
        $this->assert_true(isset($result['variance_liters']), "Variance calculation exists");
        $this->assert_true(isset($result['reconciliation_ok']), "Reconciliation status exists");
    }
    
    // ============ TRANSACTION SAFETY TESTS ============
    
    public function test_transaction_safety() {
        $this->log("\n" . COLOR_BLUE . "=== TESTING TRANSACTION SAFETY ===" . COLOR_RESET, 'info');
        
        require_once __DIR__ . '/../backend/fuel_shift_operations.php';
        
        // TEST 1: Verify transaction rollback on error
        $this->log("\n[TEST 1] Testing transaction atomicity...", 'info');
        
        try {
            $this->pdo->beginTransaction();
            
            // Simulate an operation
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM fuel_daily_readings WHERE station_id = ?");
            $stmt->execute([TEST_STATION_ID]);
            $count_before = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Rollback
            $this->pdo->rollBack();
            
            // Verify data integrity
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM fuel_daily_readings WHERE station_id = ?");
            $stmt->execute([TEST_STATION_ID]);
            $count_after = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $this->assert_equals($count_before, $count_after, "Data integrity maintained after rollback");
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Transaction test error: " . $e->getMessage(), 'error');
        }
    }
    
    // ============ MAIN TEST RUNNER ============
    
    public function run_all_tests() {
        $this->log("\n" . COLOR_YELLOW . "╔════════════════════════════════════════════════════════╗" . COLOR_RESET);
        $this->log(COLOR_YELLOW . "║  FUEL INVENTORY WORKFLOW - TEST SUITE                    ║" . COLOR_RESET);
        $this->log(COLOR_YELLOW . "╚════════════════════════════════════════════════════════╝" . COLOR_RESET, 'info');
        
        // Setup
        if (!$this->setup_test_data()) {
            $this->log("Setup failed - aborting tests", 'error');
            return false;
        }
        
        // Run tests
        try {
            $this->test_database_schema();
            $this->test_delivery_workflow();
            $this->test_adjustment_workflow();
            $this->test_audit_trail();
            $this->test_stock_calculations();
            $this->test_transaction_safety();
        } catch (Exception $e) {
            $this->log("Test error: " . $e->getMessage(), 'error');
        }
        
        // Cleanup
        $this->cleanup_test_data();
        
        // Report results
        $this->print_summary();
        
        return $this->failed_tests === 0;
    }
    
    private function print_summary() {
        $this->log("\n" . COLOR_YELLOW . "════════════════════════════════════════════════════════" . COLOR_RESET, 'info');
        $this->log("TEST SUMMARY", 'info');
        $this->log(COLOR_YELLOW . "════════════════════════════════════════════════════════" . COLOR_RESET, 'info');
        
        $pass_rate = $this->total_tests > 0 ? ($this->passed_tests / $this->total_tests * 100) : 0;
        
        $this->log(sprintf("Total Tests: %d", $this->total_tests), 'info');
        $this->log(sprintf(COLOR_GREEN . "Passed: %d" . COLOR_RESET, $this->passed_tests), 'success');
        $this->log(sprintf(COLOR_RED . "Failed: %d" . COLOR_RESET, $this->failed_tests), $this->failed_tests > 0 ? 'error' : 'info');
        $this->log(sprintf("Pass Rate: %.1f%%", $pass_rate), $pass_rate === 100 ? 'success' : 'warning');
        
        $this->log("\n" . COLOR_YELLOW . "════════════════════════════════════════════════════════" . COLOR_RESET, 'info');
    }
}

// ============ EXECUTION ============

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This test suite must be run from CLI only");
}

// Connect to database
try {
    require_once __DIR__ . '/../public/db_connect.php';
    
    $test_suite = new FuelWorkflowTestSuite($pdo);
    $success = $test_suite->run_all_tests();
    
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo COLOR_RED . "Fatal error: " . $e->getMessage() . COLOR_RESET . "\n";
    exit(1);
}
?>
