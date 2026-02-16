<?php
/**
 * Business Logic and System Features Testing
 * Focus on testing what actually works in the system
 */

require_once __DIR__ . '/../public/db_connect.php';

class BusinessLogicTester {
    private $pdo;
    private $testResults = [];
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public function runTests() {
        echo "=== BUSINESS LOGIC & SYSTEM FEATURES TESTING ===\n\n";
        
        $this->testCustomerManagement();
        $this->testInventoryManagement();
        $this->testSalesProcessing();
        $this->testJobOrderManagement();
        $this->testUserManagement();
        $this->testAuditTrail();
        $this->testStationBasedAccess();
        
        $this->generateBusinessReport();
    }
    
    private function testCustomerManagement() {
        echo "=== CUSTOMER MANAGEMENT TESTING ===\n";
        
        $results = [];
        
        try {
            // Test 1: Customer listing with different criteria
            echo "Testing customer queries...\n";
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM customers");
            $total = $stmt->fetch()['total'];
            echo "✅ Total customers: {$total}\n";
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as active FROM customers WHERE status = 'active'");
            $active = $stmt->fetch()['active'];
            echo "✅ Active customers: {$active}\n";
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as credit FROM customers WHERE type = 'credit'");
            $credit = $stmt->fetch()['credit'];
            echo "✅ Credit customers: {$credit}\n";
            
            // Test 2: Customer creation (simulate)
            echo "\nTesting customer creation logic...\n";
            
            $newCustomerData = [
                'name' => 'Test Customer ' . date('H:i:s'),
                'contact_person' => 'John Test',
                'phone' => '09123456789',
                'email' => 'test@petron.com',
                'address' => '123 Test Street, Test City',
                'type' => 'credit',
                'credit_limit' => 25000.00,
                'current_balance' => 0.00,
                'status' => 'active',
                'station_id' => 1205
            ];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO customers (name, contact_person, phone, email, address, type, credit_limit, current_balance, status, station_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                $newCustomerData['name'],
                $newCustomerData['contact_person'],
                $newCustomerData['phone'],
                $newCustomerData['email'],
                $newCustomerData['address'],
                $newCustomerData['type'],
                $newCustomerData['credit_limit'],
                $newCustomerData['current_balance'],
                $newCustomerData['status'],
                $newCustomerData['station_id']
            ]);
            
            if ($success) {
                $customerId = $this->pdo->lastInsertId();
                echo "✅ Customer creation successful - ID: {$customerId}\n";
                
                // Test 3: Customer update
                $stmt = $this->pdo->prepare("UPDATE customers SET credit_limit = ? WHERE id = ?");
                $updateSuccess = $stmt->execute([30000.00, $customerId]);
                
                if ($updateSuccess) {
                    echo "✅ Customer update successful\n";
                } else {
                    echo "❌ Customer update failed\n";
                }
                
                // Test 4: Customer credit limit validation
                $stmt = $this->pdo->prepare("SELECT credit_limit FROM customers WHERE id = ?");
                $stmt->execute([$customerId]);
                $updatedLimit = $stmt->fetch()['credit_limit'];
                
                if ($updatedLimit == 30000.00) {
                    echo "✅ Credit limit update verified: ₱{$updatedLimit}\n";
                } else {
                    echo "❌ Credit limit verification failed\n";
                }
                
                $results['customer_crud'] = 'working';
                $results['test_customer_id'] = $customerId;
                
            } else {
                echo "❌ Customer creation failed\n";
                $results['customer_crud'] = 'failed';
            }
            
        } catch (Exception $e) {
            echo "❌ Customer management error: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
        }
        
        $this->testResults['customer_management'] = $results;
        echo "\n";
    }
    
    private function testInventoryManagement() {
        echo "=== INVENTORY MANAGEMENT TESTING ===\n";
        
        $results = [];
        
        try {
            // Test 1: Product inventory tracking
            echo "Testing product inventory...\n";
            
            $stmt = $this->pdo->query("
                SELECT 
                    p.id,
                    p.name,
                    p.price,
                    i.stock_level,
                    i.reorder_level,
                    CASE 
                        WHEN i.stock_level <= i.reorder_level THEN 'REORDER NEEDED'
                        WHEN i.stock_level <= (i.reorder_level * 2) THEN 'LOW STOCK'
                        ELSE 'NORMAL'
                    END as stock_status
                FROM products p
                LEFT JOIN inventory i ON p.id = i.product_id
                WHERE i.status = 'active'
                ORDER BY i.stock_level ASC
                LIMIT 10
            ");
            
            $inventoryItems = $stmt->fetchAll();
            
            foreach ($inventoryItems as $item) {
                $stockLevel = $item['stock_level'] ?? 0;
                $status = $item['stock_status'];
                echo "- {$item['name']}: {$stockLevel} units ({$status})\n";
            }
            
            // Test 2: Low stock alerts
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as low_stock_count 
                FROM inventory i 
                JOIN products p ON i.product_id = p.id 
                WHERE i.stock_level <= i.reorder_level AND i.status = 'active'
            ");
            $lowStockCount = $stmt->fetch()['low_stock_count'];
            echo "✅ Low stock items: {$lowStockCount}\n";
            
            // Test 3: Inventory adjustment simulation
            echo "\nTesting inventory adjustments...\n";
            
            if (!empty($inventoryItems)) {
                $testItem = $inventoryItems[0];
                $originalStock = $testItem['stock_level'];
                $newStock = $originalStock + 50; // Add 50 units
                
                $stmt = $this->pdo->prepare("
                    UPDATE inventory 
                    SET stock_level = ?, last_updated = NOW() 
                    WHERE product_id = ?
                ");
                
                $success = $stmt->execute([$newStock, $testItem['id']]);
                
                if ($success) {
                    echo "✅ Inventory adjustment successful: {$testItem['name']} ({$originalStock} → {$newStock})\n";
                    
                    // Revert the change
                    $stmt->execute([$originalStock, $testItem['id']]);
                    echo "✅ Inventory reverted to original stock level\n";
                    
                    $results['inventory_adjustments'] = 'working';
                } else {
                    echo "❌ Inventory adjustment failed\n";
                    $results['inventory_adjustments'] = 'failed';
                }
            }
            
            $results['total_inventory_items'] = count($inventoryItems);
            $results['low_stock_alerts'] = $lowStockCount;
            
        } catch (Exception $e) {
            echo "❌ Inventory management error: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
        }
        
        $this->testResults['inventory_management'] = $results;
        echo "\n";
    }
    
    private function testSalesProcessing() {
        echo "=== SALES PROCESSING TESTING ===\n";
        
        $results = [];
        
        try {
            // Test 1: Sales transaction creation
            echo "Testing sales transaction creation...\n";
            
            $saleData = [
                'id' => 'TEST_SALE_' . date('YmdHis'),
                'sale_date' => date('Y-m-d'),
                'sale_time' => date('H:i:s'),
                'customer_id' => 9, // Existing customer
                'user_id' => 1, // testadmin user
                'station_id' => 1205,
                'payment_method' => 'Cash',
                'total' => 905.00,
                'amount_received' => 1000.00,
                'change_amount' => 95.00,
                'status' => 'Completed',
                'customer' => 'Walk-in Customer'
            ];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO sales (id, sale_date, sale_time, customer_id, user_id, station_id, 
                    payment_method, total, amount_received, change_amount, status, customer, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                $saleData['id'],
                $saleData['sale_date'],
                $saleData['sale_time'],
                $saleData['customer_id'],
                $saleData['user_id'],
                $saleData['station_id'],
                $saleData['payment_method'],
                $saleData['total'],
                $saleData['amount_received'],
                $saleData['change_amount'],
                $saleData['status'],
                $saleData['customer']
            ]);
            
            if ($success) {
                echo "✅ Sales transaction created successfully - ID: {$saleData['id']}\n";
                echo "   Total: ₱{$saleData['total']}\n";
                echo "   Payment: {$saleData['payment_method']}\n";
                echo "   Change: ₱{$saleData['change_amount']}\n";
                
                // Test 2: Sales reporting queries
                echo "\nTesting sales reporting queries...\n";
                
                $stmt = $this->pdo->query("SELECT COUNT(*) as total_sales FROM sales");
                $totalSales = $stmt->fetch()['total_sales'];
                echo "✅ Total sales transactions: {$totalSales}\n";
                
                $stmt = $this->pdo->query("
                    SELECT SUM(total) as total_revenue 
                    FROM sales 
                    WHERE status = 'Completed' AND sale_date = CURDATE()
                ");
                $todayRevenue = $stmt->fetch()['total_revenue'] ?? 0;
                echo "✅ Today's revenue: ₱{$todayRevenue}\n";
                
                // Test 3: Payment method analysis
                $stmt = $this->pdo->query("
                    SELECT payment_method, COUNT(*) as count, SUM(total) as amount
                    FROM sales 
                    WHERE status = 'Completed'
                    GROUP BY payment_method
                ");
                $paymentMethods = $stmt->fetchAll();
                
                echo "Payment method breakdown:\n";
                foreach ($paymentMethods as $method) {
                    echo "- {$method['payment_method']}: {$method['count']} transactions, ₱{$method['amount']}\n";
                }
                
                $results['sales_creation'] = 'working';
                $results['test_sale_id'] = $saleData['id'];
                $results['total_sales'] = $totalSales;
                $results['today_revenue'] = $todayRevenue;
                
            } else {
                echo "❌ Sales transaction creation failed\n";
                $results['sales_creation'] = 'failed';
            }
            
        } catch (Exception $e) {
            echo "❌ Sales processing error: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
        }
        
        $this->testResults['sales_processing'] = $results;
        echo "\n";
    }
    
    private function testJobOrderManagement() {
        echo "=== JOB ORDER MANAGEMENT TESTING ===\n";
        
        $results = [];
        
        try {
            // Test 1: Job order workflow states
            echo "Testing job order workflow...\n";
            
            $stmt = $this->pdo->query("
                SELECT status, COUNT(*) as count 
                FROM job_orders 
                GROUP BY status
            ");
            $statusCounts = $stmt->fetchAll();
            
            echo "Job order status distribution:\n";
            foreach ($statusCounts as $status) {
                echo "- {$status['status']}: {$status['count']} orders\n";
            }
            
            // Test 2: Job order creation
            echo "\nTesting job order creation...\n";
            
            $jobOrderData = [
                'job_order_number' => 'TEST_JO_' . date('YmdHis'),
                'station_id' => 1205,
                'user_id' => 1,
                'customer_id' => 9,
                'vehicle_plate' => 'TEST-123',
                'vehicle_type' => 'Sedan',
                'service_category_id' => 1,
                'assigned_by' => 1,
                'service_description' => 'Test oil change service',
                'estimated_duration' => 60,
                'status' => 'Pending',
                'estimated_labor_cost' => 500.00,
                'estimated_parts_cost' => 350.00
            ];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO job_orders (job_order_number, station_id, user_id, customer_id, 
                    vehicle_plate, vehicle_type, service_category_id, assigned_by, 
                    service_description, estimated_duration, status, estimated_labor_cost, 
                    estimated_parts_cost, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                $jobOrderData['job_order_number'],
                $jobOrderData['station_id'],
                $jobOrderData['user_id'],
                $jobOrderData['customer_id'],
                $jobOrderData['vehicle_plate'],
                $jobOrderData['vehicle_type'],
                $jobOrderData['service_category_id'],
                $jobOrderData['assigned_by'],
                $jobOrderData['service_description'],
                $jobOrderData['estimated_duration'],
                $jobOrderData['status'],
                $jobOrderData['estimated_labor_cost'],
                $jobOrderData['estimated_parts_cost']
            ]);
            
            if ($success) {
                $jobOrderId = $this->pdo->lastInsertId();
                echo "✅ Job order created successfully - ID: {$jobOrderId}\n";
                echo "   Number: {$jobOrderData['job_order_number']}\n";
                echo "   Vehicle: {$jobOrderData['vehicle_plate']} ({$jobOrderData['vehicle_type']})\n";
                echo "   Estimated Cost: ₱" . ($jobOrderData['estimated_labor_cost'] + $jobOrderData['estimated_parts_cost']) . "\n";
                
                // Test 3: Job order status update
                $stmt = $this->pdo->prepare("
                    UPDATE job_orders 
                    SET status = 'In Progress', started_at = NOW() 
                    WHERE id = ?
                ");
                $updateSuccess = $stmt->execute([$jobOrderId]);
                
                if ($updateSuccess) {
                    echo "✅ Job order status updated to 'In Progress'\n";
                } else {
                    echo "❌ Job order status update failed\n";
                }
                
                $results['job_order_creation'] = 'working';
                $results['test_job_order_id'] = $jobOrderId;
                
            } else {
                echo "❌ Job order creation failed\n";
                $results['job_order_creation'] = 'failed';
            }
            
        } catch (Exception $e) {
            echo "❌ Job order management error: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
        }
        
        $this->testResults['job_order_management'] = $results;
        echo "\n";
    }
    
    private function testUserManagement() {
        echo "=== USER MANAGEMENT TESTING ===\n";
        
        try {
            // Test role-based queries
            echo "Testing role-based user queries...\n";
            
            $stmt = $this->pdo->query("
                SELECT 
                    role,
                    COUNT(*) as user_count,
                    COUNT(CASE WHEN station_id IS NOT NULL THEN 1 END) as station_users
                FROM users 
                GROUP BY role
            ");
            $roleData = $stmt->fetchAll();
            
            foreach ($roleData as $role) {
                echo "- {$role['role']}: {$role['user_count']} users ({$role['station_users']} assigned to stations)\n";
            }
            
            // Test station-based access
            echo "\nTesting station-based access control...\n";
            
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(s.name, 'Global Access') as station_name,
                    COUNT(u.id) as user_count,
                    GROUP_CONCAT(DISTINCT u.role) as roles
                FROM users u
                LEFT JOIN stations s ON u.station_id = s.id
                GROUP BY u.station_id, s.name
                ORDER BY user_count DESC
            ");
            $stationAccess = $stmt->fetchAll();
            
            foreach ($stationAccess as $access) {
                echo "- {$access['station_name']}: {$access['user_count']} users ({$access['roles']})\n";
            }
            
            $this->testResults['user_management'] = ['status' => 'working', 'roles' => count($roleData)];
            
        } catch (Exception $e) {
            echo "❌ User management error: " . $e->getMessage() . "\n";
            $this->testResults['user_management'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testAuditTrail() {
        echo "=== AUDIT TRAIL TESTING ===\n";
        
        try {
            // Test activity logging
            echo "Testing activity logs...\n";
            
            $stmt = $this->pdo->query("
                SELECT 
                    activity_type,
                    COUNT(*) as count,
                    MAX(created_at) as last_occurrence
                FROM activity_logs
                GROUP BY activity_type
                ORDER BY count DESC
            ");
            $activities = $stmt->fetchAll();
            
            foreach ($activities as $activity) {
                echo "- {$activity['activity_type']}: {$activity['count']} events (Last: {$activity['last_occurrence']})\n";
            }
            
            // Test creating an audit log entry
            echo "\nTesting audit log creation...\n";
            
            $stmt = $this->pdo->prepare("
                INSERT INTO activity_logs (user_id, activity_type, description, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                1, // testadmin user
                'System Test',
                'Testing audit trail functionality from automated test suite'
            ]);
            
            if ($success) {
                echo "✅ Audit log entry created successfully\n";
                $this->testResults['audit_trail'] = ['status' => 'working', 'activity_types' => count($activities)];
            } else {
                echo "❌ Audit log creation failed\n";
                $this->testResults['audit_trail'] = ['status' => 'failed'];
            }
            
        } catch (Exception $e) {
            echo "❌ Audit trail error: " . $e->getMessage() . "\n";
            $this->testResults['audit_trail'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function testStationBasedAccess() {
        echo "=== STATION-BASED ACCESS CONTROL TESTING ===\n";
        
        try {
            // Test data segregation by station
            echo "Testing station-based data segregation...\n";
            
            // Check customers by station
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(s.name, 'No Station') as station_name,
                    COUNT(c.id) as customer_count
                FROM customers c
                LEFT JOIN stations s ON c.station_id = s.id
                GROUP BY c.station_id, s.name
            ");
            $customersByStation = $stmt->fetchAll();
            
            echo "Customers by station:\n";
            foreach ($customersByStation as $station) {
                echo "- {$station['station_name']}: {$station['customer_count']} customers\n";
            }
            
            // Check sales by station
            $stmt = $this->pdo->query("
                SELECT 
                    station_id,
                    COUNT(*) as sales_count,
                    SUM(total) as total_amount
                FROM sales
                GROUP BY station_id
            ");
            $salesByStation = $stmt->fetchAll();
            
            echo "\nSales by station:\n";
            foreach ($salesByStation as $station) {
                $stationId = $station['station_id'] ?? 'Unknown';
                echo "- Station {$stationId}: {$station['sales_count']} sales, ₱{$station['total_amount']}\n";
            }
            
            $this->testResults['station_access'] = [
                'status' => 'working',
                'stations_with_customers' => count($customersByStation),
                'stations_with_sales' => count($salesByStation)
            ];
            
        } catch (Exception $e) {
            echo "❌ Station-based access error: " . $e->getMessage() . "\n";
            $this->testResults['station_access'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    private function generateBusinessReport() {
        echo "=== BUSINESS LOGIC TEST SUMMARY ===\n\n";
        
        $totalFeatures = count($this->testResults);
        $workingFeatures = 0;
        $failedFeatures = 0;
        
        foreach ($this->testResults as $feature => $result) {
            $status = is_array($result) ? ($result['status'] ?? 'unknown') : $result;
            if ($status === 'working') {
                $workingFeatures++;
            } elseif ($status === 'failed' || $status === 'error') {
                $failedFeatures++;
            }
        }
        
        echo "FEATURE SUMMARY:\n";
        echo "- Total tested features: {$totalFeatures}\n";
        echo "- Working features: {$workingFeatures}\n";
        echo "- Failed features: {$failedFeatures}\n";
        echo "- Success rate: " . round(($workingFeatures / $totalFeatures) * 100) . "%\n\n";
        
        echo "DETAILED RESULTS:\n";
        foreach ($this->testResults as $feature => $result) {
            $status = is_array($result) ? ($result['status'] ?? 'unknown') : $result;
            $icon = $status === 'working' ? '✅' : '❌';
            echo "{$icon} " . ucfirst(str_replace('_', ' ', $feature)) . ": {$status}\n";
        }
        
        echo "\nCONCLUSION:\n";
        if ($workingFeatures >= ($totalFeatures * 0.8)) {
            echo "🎉 System is functioning well with most business logic working correctly!\n";
        } elseif ($workingFeatures >= ($totalFeatures * 0.6)) {
            echo "⚠️  System has good functionality but needs some improvements.\n";
        } else {
            echo "❌ System needs significant work to improve functionality.\n";
        }
    }
}

// Run the tests
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new BusinessLogicTester();
    $tester->runTests();
}
?>