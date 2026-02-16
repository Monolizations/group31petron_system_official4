<?php
/**
 * Comprehensive API and System Testing Framework
 * Tests all Petron POS System features with real database interactions
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/../public/db_connect.php';
require_once __DIR__ . '/../backend/lib.php';

class ComprehensiveTester {
    private $pdo;
    private $testResults = [];
    
    // Test accounts
    private $testAccounts = [
        ['username' => 'testadmin', 'password' => 'test123', 'expected_role' => 'admin'],
        ['username' => 'teststaff', 'password' => 'test123', 'expected_role' => 'staff'],
        ['username' => 'manager', 'password' => 'test123', 'expected_role' => 'manager'],
        ['username' => 'admin', 'password' => 'amie', 'expected_role' => 'admin'],
        ['username' => 'operations', 'password' => 'operations123', 'expected_role' => 'operations_staff']
    ];
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->testResults = [];
    }
    
    /**
     * Run comprehensive system tests
     */
    public function runTests() {
        echo "=== PETRON POS SYSTEM - COMPREHENSIVE TESTING ===\n\n";
        
        // Test database connectivity
        $this->testDatabaseConnectivity();
        
        // Test authentication for all accounts
        $authenticatedUsers = $this->testAuthentication();
        
        // Test API endpoints with authenticated users
        foreach ($authenticatedUsers as $user) {
            if ($user['authenticated']) {
                echo "\n--- Testing APIs with user: {$user['username']} ({$user['role']}) ---\n";
                $this->testAPIEndpoints($user);
                $this->testBusinessWorkflows($user);
            }
        }
        
        // Generate final report
        $this->generateReport();
    }
    
    /**
     * Test database connectivity
     */
    private function testDatabaseConnectivity() {
        echo "Testing database connectivity...\n";
        
        try {
            // Test basic query
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            
            $userCount = $result['count'];
            echo "✅ Database connected - Found {$userCount} users\n";
            
            // Test key tables exist
            $tables = ['users', 'stations', 'customers', 'activity_logs', 'user_sessions'];
            foreach ($tables as $table) {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->fetch()) {
                    echo "✅ Table '{$table}' exists\n";
                } else {
                    echo "❌ Table '{$table}' missing\n";
                }
            }
            
            $this->testResults['database'] = ['status' => 'working', 'tables' => count($tables)];
            
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
            $this->testResults['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test authentication for all test accounts
     */
    private function testAuthentication() {
        echo "\nTesting authentication for all test accounts...\n";
        
        $authenticatedUsers = [];
        
        foreach ($this->testAccounts as $account) {
            $username = $account['username'];
            $password = $account['password'];
            
            try {
                // Test login
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    echo "❌ User '{$username}' not found\n";
                    continue;
                }
                
                // Test password verification
                if (password_verify($password, $user['password'])) {
                    echo "✅ User '{$username}' authenticated - Role: {$user['role']}, Station: " . ($user['station_id'] ?? 'NULL') . "\n";
                    
                    $authenticatedUsers[] = [
                        'username' => $username,
                        'password' => $password,
                        'user_id' => $user['id'],
                        'role' => $user['role'],
                        'station_id' => $user['station_id'],
                        'authenticated' => true,
                        'user_data' => $user
                    ];
                } else {
                    echo "❌ User '{$username}' password verification failed\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Authentication error for '{$username}': " . $e->getMessage() . "\n";
            }
        }
        
        $this->testResults['authentication'] = [
            'status' => 'working', 
            'successful_logins' => count($authenticatedUsers),
            'total_accounts' => count($this->testAccounts)
        ];
        
        return $authenticatedUsers;
    }
    
    /**
     * Test API endpoints with authenticated session
     */
    private function testAPIEndpoints($user) {
        // Simulate authenticated session
        session_start();
        $_SESSION['user'] = $user['user_data'];
        $_SESSION['user_id'] = $user['user_id'];
        
        $apiResults = [];
        
        // Test Users API
        $apiResults['users_api'] = $this->testUsersAPI($user);
        
        // Test Customers API
        $apiResults['customers_api'] = $this->testCustomersAPI($user);
        
        // Test Sales API
        $apiResults['sales_api'] = $this->testSalesAPI($user);
        
        // Test Stations API
        $apiResults['stations_api'] = $this->testStationsAPI($user);
        
        $this->testResults['api_' . $user['username']] = $apiResults;
        
        session_destroy();
    }
    
    private function testUsersAPI($user) {
        try {
            echo "  Testing Users API...\n";
            
            // Simulate API call by setting up environment
            $_GET['action'] = 'list';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            
            ob_start();
            include __DIR__ . '/../backend/api/users.php';
            $output = ob_get_clean();
            
            if (strpos($output, '"success":true') !== false || strpos($output, '"data"') !== false) {
                echo "  ✅ Users API working\n";
                return ['status' => 'working', 'output' => substr($output, 0, 100)];
            } else {
                echo "  ❌ Users API error: " . substr($output, 0, 100) . "\n";
                return ['status' => 'error', 'output' => substr($output, 0, 100)];
            }
            
        } catch (Exception $e) {
            echo "  ❌ Users API exception: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testCustomersAPI($user) {
        try {
            echo "  Testing Customers API...\n";
            
            $_SERVER['REQUEST_METHOD'] = 'GET';
            
            ob_start();
            include __DIR__ . '/../backend/api/customers.php';
            $output = ob_get_clean();
            
            if (strpos($output, '"ok":true') !== false) {
                echo "  ✅ Customers API working\n";
                return ['status' => 'working', 'output' => substr($output, 0, 100)];
            } else {
                echo "  ❌ Customers API error: " . substr($output, 0, 100) . "\n";
                return ['status' => 'error', 'output' => substr($output, 0, 100)];
            }
            
        } catch (Exception $e) {
            echo "  ❌ Customers API exception: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testSalesAPI($user) {
        try {
            echo "  Testing Sales API...\n";
            
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['action' => 'pending']; // Test pending sales retrieval
            
            ob_start();
            include __DIR__ . '/../backend/api/sales.php';
            $output = ob_get_clean();
            
            if (strpos($output, '"ok":true') !== false) {
                echo "  ✅ Sales API working\n";
                return ['status' => 'working', 'output' => substr($output, 0, 100)];
            } else {
                echo "  ❌ Sales API error: " . substr($output, 0, 100) . "\n";
                return ['status' => 'error', 'output' => substr($output, 0, 100)];
            }
            
        } catch (Exception $e) {
            echo "  ❌ Sales API exception: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testStationsAPI($user) {
        try {
            echo "  Testing Stations API...\n";
            
            $_GET['action'] = 'list';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            
            ob_start();
            include __DIR__ . '/../backend/api/stations.php';
            $output = ob_get_clean();
            
            if (strpos($output, '"success":true') !== false || strpos($output, '"ok":true') !== false) {
                echo "  ✅ Stations API working\n";
                return ['status' => 'working', 'output' => substr($output, 0, 100)];
            } else {
                echo "  ❌ Stations API error: " . substr($output, 0, 100) . "\n";
                return ['status' => 'error', 'output' => substr($output, 0, 100)];
            }
            
        } catch (Exception $e) {
            echo "  ❌ Stations API exception: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test business workflows
     */
    private function testBusinessWorkflows($user) {
        echo "  Testing business workflows...\n";
        
        // Test basic database queries the system would use
        $workflowResults = [];
        
        // Test customer management workflow
        $workflowResults['customer_mgmt'] = $this->testCustomerWorkflow($user);
        
        // Test inventory checks
        $workflowResults['inventory'] = $this->testInventoryWorkflow($user);
        
        // Test reporting queries
        $workflowResults['reporting'] = $this->testReportingWorkflow($user);
        
        $this->testResults['workflows_' . $user['username']] = $workflowResults;
    }
    
    private function testCustomerWorkflow($user) {
        try {
            // Test if we can query customers (simulating customer lookup for POS)
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'");
            $result = $stmt->fetch();
            
            echo "  ✅ Customer workflow - Found {$result['count']} active customers\n";
            return ['status' => 'working', 'active_customers' => $result['count']];
            
        } catch (Exception $e) {
            echo "  ❌ Customer workflow error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testInventoryWorkflow($user) {
        try {
            // Check if inventory-related tables exist and have data
            $tables = [];
            
            // Check for common inventory table names
            $possibleTables = ['products', 'fuel_inventory', 'merchandise', 'inventory'];
            foreach ($possibleTables as $table) {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->fetch()) {
                    $countStmt = $this->pdo->query("SELECT COUNT(*) as count FROM {$table}");
                    $count = $countStmt->fetch()['count'];
                    $tables[$table] = $count;
                    echo "  ✅ Inventory table '{$table}' has {$count} records\n";
                }
            }
            
            if (empty($tables)) {
                echo "  ⚠️  No standard inventory tables found\n";
                return ['status' => 'warning', 'message' => 'No inventory tables found'];
            }
            
            return ['status' => 'working', 'inventory_tables' => $tables];
            
        } catch (Exception $e) {
            echo "  ❌ Inventory workflow error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testReportingWorkflow($user) {
        try {
            // Test common reporting queries
            
            // Check activity logs
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM activity_logs");
            $logCount = $stmt->fetch()['count'];
            
            // Check user activity
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            $userLogCount = $stmt->fetch()['count'];
            
            echo "  ✅ Reporting - {$logCount} total logs, {$userLogCount} for user\n";
            
            return [
                'status' => 'working', 
                'total_logs' => $logCount,
                'user_logs' => $userLogCount
            ];
            
        } catch (Exception $e) {
            echo "  ❌ Reporting workflow error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate comprehensive test report
     */
    private function generateReport() {
        echo "\n=== COMPREHENSIVE TEST RESULTS ===\n\n";
        
        // Count overall results
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $warningTests = 0;
        
        foreach ($this->testResults as $category => $results) {
            if (is_array($results)) {
                foreach ($results as $test => $result) {
                    $totalTests++;
                    if (isset($result['status'])) {
                        switch ($result['status']) {
                            case 'working':
                                $passedTests++;
                                break;
                            case 'error':
                                $failedTests++;
                                break;
                            case 'warning':
                                $warningTests++;
                                break;
                        }
                    }
                }
            }
        }
        
        echo "SUMMARY:\n";
        echo "- Total Tests: {$totalTests}\n";
        echo "- Passed: {$passedTests} ✅\n";
        echo "- Failed: {$failedTests} ❌\n";
        echo "- Warnings: {$warningTests} ⚠️\n\n";
        
        echo "DETAILED RESULTS:\n";
        foreach ($this->testResults as $category => $results) {
            echo "Category: {$category}\n";
            if (is_array($results)) {
                foreach ($results as $test => $result) {
                    $status = $result['status'] ?? 'unknown';
                    $icon = $status === 'working' ? '✅' : ($status === 'error' ? '❌' : '⚠️');
                    echo "  {$icon} {$test}: {$status}\n";
                }
            } else {
                echo "  Result: " . json_encode($results) . "\n";
            }
            echo "\n";
        }
    }
}

// Run the tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new ComprehensiveTester();
    $tester->runTests();
}
?>