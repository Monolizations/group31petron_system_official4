<?php
/**
 * API Endpoint Testing - Test actual API calls with simulated sessions
 */

require_once __DIR__ . '/../public/db_connect.php';
require_once __DIR__ . '/../backend/lib.php';

class APIEndpointTester {
    private $pdo;
    private $testUsers;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        
        // Get our test users
        $this->testUsers = [
            'testadmin' => ['id' => null, 'role' => 'admin', 'station_id' => 1205],
            'teststaff' => ['id' => null, 'role' => 'staff', 'station_id' => 1205],
            'manager' => ['id' => null, 'role' => 'manager', 'station_id' => 1205]
        ];
        
        // Get actual user IDs
        foreach ($this->testUsers as $username => &$userData) {
            $stmt = $this->pdo->prepare("SELECT id, role, station_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user) {
                $userData = array_merge($userData, $user);
            }
        }
    }
    
    public function testAllAPIs() {
        echo "=== API ENDPOINT TESTING ===\n\n";
        
        foreach ($this->testUsers as $username => $userData) {
            if ($userData['id']) {
                echo "--- Testing APIs as {$username} ({$userData['role']}) ---\n";
                $this->testAPIsForUser($userData);
                echo "\n";
            }
        }
    }
    
    private function testAPIsForUser($user) {
        // Set up session simulation
        session_start();
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
        
        // Test each API endpoint
        $this->testUsersAPI($user);
        $this->testCustomersAPI($user);
        $this->testSalesAPI($user);
        $this->testStationsAPI($user);
        
        session_destroy();
    }
    
    private function testUsersAPI($user) {
        echo "  Testing Users API:\n";
        
        try {
            // Simulate GET request to users API
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['action'] = 'list';
            
            // Clear any previous output
            if (ob_get_level()) ob_clean();
            ob_start();
            
            // Include the API file to simulate the request
            include __DIR__ . '/../backend/api/users.php';
            
            $output = ob_get_clean();
            
            // Parse the JSON response
            $response = json_decode($output, true);
            
            if ($response && isset($response['success']) && $response['success']) {
                $userCount = count($response['data'] ?? []);
                echo "    ✅ Users API working - Retrieved {$userCount} users\n";
                
                // Show some user data
                if (!empty($response['data']) && is_array($response['data'])) {
                    $firstUser = reset($response['data']);
                    echo "    📝 Sample user: {$firstUser['username']} ({$firstUser['role']})\n";
                }
                
                return ['status' => 'working', 'users_count' => $userCount];
            } else {
                echo "    ❌ Users API failed - Response: " . substr($output, 0, 100) . "\n";
                return ['status' => 'failed', 'response' => substr($output, 0, 200)];
            }
            
        } catch (Exception $e) {
            echo "    ❌ Users API error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testCustomersAPI($user) {
        echo "  Testing Customers API:\n";
        
        try {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            unset($_GET['action']); // Customers API doesn't need action for GET
            
            if (ob_get_level()) ob_clean();
            ob_start();
            
            include __DIR__ . '/../backend/api/customers.php';
            
            $output = ob_get_clean();
            $response = json_decode($output, true);
            
            if ($response && isset($response['ok']) && $response['ok']) {
                $customerCount = count($response['data']['customers'] ?? []);
                echo "    ✅ Customers API working - Retrieved {$customerCount} customers\n";
                
                if (!empty($response['data']['customers'])) {
                    $firstCustomer = reset($response['data']['customers']);
                    echo "    📝 Sample customer: {$firstCustomer['name']} ({$firstCustomer['type']})\n";
                }
                
                return ['status' => 'working', 'customers_count' => $customerCount];
            } else {
                echo "    ❌ Customers API failed - Response: " . substr($output, 0, 100) . "\n";
                return ['status' => 'failed', 'response' => substr($output, 0, 200)];
            }
            
        } catch (Exception $e) {
            echo "    ❌ Customers API error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testSalesAPI($user) {
        echo "  Testing Sales API:\n";
        
        try {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['action'] = 'pending';
            
            if (ob_get_level()) ob_clean();
            ob_start();
            
            include __DIR__ . '/../backend/api/sales.php';
            
            $output = ob_get_clean();
            $response = json_decode($output, true);
            
            if ($response && isset($response['ok']) && $response['ok']) {
                $pendingSalesCount = count($response['data'] ?? []);
                echo "    ✅ Sales API working - Found {$pendingSalesCount} pending sales\n";
                return ['status' => 'working', 'pending_sales' => $pendingSalesCount];
            } else {
                echo "    ❌ Sales API failed - Response: " . substr($output, 0, 100) . "\n";
                return ['status' => 'failed', 'response' => substr($output, 0, 200)];
            }
            
        } catch (Exception $e) {
            echo "    ❌ Sales API error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function testStationsAPI($user) {
        echo "  Testing Stations API:\n";
        
        try {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['action'] = 'list';
            
            if (ob_get_level()) ob_clean();
            ob_start();
            
            include __DIR__ . '/../backend/api/stations.php';
            
            $output = ob_get_clean();
            $response = json_decode($output, true);
            
            if ($response && (isset($response['success']) || isset($response['ok'])) && 
                ($response['success'] || $response['ok'])) {
                $stationsCount = count($response['data'] ?? $response['stations'] ?? []);
                echo "    ✅ Stations API working - Retrieved {$stationsCount} stations\n";
                return ['status' => 'working', 'stations_count' => $stationsCount];
            } else {
                echo "    ❌ Stations API failed - Response: " . substr($output, 0, 100) . "\n";
                return ['status' => 'failed', 'response' => substr($output, 0, 200)];
            }
            
        } catch (Exception $e) {
            echo "    ❌ Stations API error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function testPOSTransaction() {
        echo "\n=== POS TRANSACTION TESTING ===\n";
        
        $user = $this->testUsers['teststaff'];
        if (!$user['id']) {
            echo "❌ Test user not available\n";
            return;
        }
        
        // Set up session
        session_start();
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
        
        echo "Testing POS transaction creation as staff user...\n";
        
        try {
            // Simulate a sales transaction POST
            $_SERVER['REQUEST_METHOD'] = 'POST';
            
            // Create sample transaction data
            $transactionData = [
                'cart' => [
                    [
                        'id' => 1,
                        'name' => 'Gasoline Premium',
                        'price' => 55.00,
                        'quantity' => 10,
                        'type' => 'fuel'
                    ],
                    [
                        'id' => 2, 
                        'name' => 'Engine Oil 5W-30',
                        'price' => 350.00,
                        'quantity' => 1,
                        'type' => 'merchandise'
                    ]
                ],
                'payment_method' => 'Cash',
                'amount_received' => 1000.00,
                'customer' => 'Walk-in',
                'status' => 'Pending (Staff)'
            ];
            
            if (ob_get_level()) ob_clean();
            ob_start();
            
            // Simulate sending JSON data
            $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($transactionData);
            
            include __DIR__ . '/../backend/api/sales.php';
            
            $output = ob_get_clean();
            $response = json_decode($output, true);
            
            if ($response && isset($response['ok']) && $response['ok']) {
                echo "✅ POS Transaction created successfully\n";
                echo "   Transaction ID: " . ($response['id'] ?? 'Generated') . "\n";
                echo "   Total: ₱" . array_sum(array_map(function($item) {
                    return $item['price'] * $item['quantity'];
                }, $transactionData['cart'])) . "\n";
                
                return ['status' => 'working', 'transaction_created' => true];
            } else {
                echo "❌ POS Transaction failed - Response: " . substr($output, 0, 200) . "\n";
                return ['status' => 'failed', 'response' => substr($output, 0, 200)];
            }
            
        } catch (Exception $e) {
            echo "❌ POS Transaction error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        } finally {
            session_destroy();
        }
    }
    
    public function testCustomerCRUD() {
        echo "\n=== CUSTOMER CRUD TESTING ===\n";
        
        $user = $this->testUsers['testadmin'];
        if (!$user['id']) {
            echo "❌ Test admin user not available\n";
            return;
        }
        
        session_start();
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
        
        echo "Testing customer creation as admin user...\n";
        
        try {
            // Test creating a new customer
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'name' => 'Test Customer ' . date('Y-m-d H:i:s'),
                'contact_person' => 'John Doe',
                'phone' => '09123456789',
                'email' => 'test@example.com',
                'address' => '123 Test Street',
                'type' => 'credit',
                'credit_limit' => 50000.00,
                'status' => 'active'
            ];
            
            if (ob_get_level()) ob_clean();
            ob_start();
            
            include __DIR__ . '/../backend/api/customers.php';
            
            $output = ob_get_clean();
            $response = json_decode($output, true);
            
            if ($response && isset($response['ok']) && $response['ok']) {
                echo "✅ Customer created successfully\n";
                echo "   Customer ID: " . ($response['id'] ?? 'Generated') . "\n";
                echo "   Name: {$_POST['name']}\n";
                echo "   Type: {$_POST['type']} (Credit Limit: ₱{$_POST['credit_limit']})\n";
                
                return ['status' => 'working', 'customer_created' => true, 'customer_id' => $response['id'] ?? null];
            } else {
                echo "❌ Customer creation failed - Response: " . substr($output, 0, 200) . "\n";
                return ['status' => 'failed', 'response' => substr($output, 0, 200)];
            }
            
        } catch (Exception $e) {
            echo "❌ Customer creation error: " . $e->getMessage() . "\n";
            return ['status' => 'error', 'message' => $e->getMessage()];
        } finally {
            session_destroy();
        }
    }
}

// Run the tests
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new APIEndpointTester();
    $tester->testAllAPIs();
    $tester->testPOSTransaction();
    $tester->testCustomerCRUD();
    
    echo "\n=== API TESTING COMPLETE ===\n";
}
?>