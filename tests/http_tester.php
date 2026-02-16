<?php
/**
 * HTTP-based Comprehensive API and System Testing Framework
 * Tests all Petron POS System features via actual HTTP requests
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

class HTTPTester {
    private $baseUrl;
    private $testResults = [];
    
    // Test accounts
    private $testAccounts = [
        ['username' => 'testadmin', 'password' => 'test123', 'expected_role' => 'admin'],
        ['username' => 'teststaff', 'password' => 'test123', 'expected_role' => 'staff'],
        ['username' => 'manager', 'password' => 'test123', 'expected_role' => 'manager'],
        ['username' => 'admin', 'password' => 'amie', 'expected_role' => 'admin'],
        ['username' => 'operations', 'password' => 'operations123', 'expected_role' => 'operations_staff']
    ];
    
    public function __construct($baseUrl = 'http://localhost') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->testResults = [];
    }
    
    /**
     * Run comprehensive system tests via HTTP
     */
    public function runTests() {
        echo "=== PETRON POS SYSTEM - HTTP TESTING ===\n\n";
        
        // Test if web server is accessible
        if (!$this->testWebServerAccess()) {
            echo "❌ Cannot access web server. Make sure XAMPP is running.\n";
            return;
        }
        
        // Test authentication and get session cookies for each user
        $authenticatedSessions = $this->testHTTPAuthentication();
        
        // Test system functionality with authenticated sessions
        foreach ($authenticatedSessions as $session) {
            if ($session['authenticated']) {
                echo "\n--- Testing with user: {$session['username']} ({$session['role']}) ---\n";
                $this->testSystemFeatures($session);
            }
        }
        
        // Generate final report
        $this->generateReport();
    }
    
    /**
     * Test web server accessibility
     */
    private function testWebServerAccess() {
        $testUrl = $this->baseUrl . '/group31petron_system_official4/public/login.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ Web server accessible\n";
            return true;
        } else {
            echo "❌ Web server not accessible (HTTP {$httpCode})\n";
            return false;
        }
    }
    
    /**
     * Test HTTP authentication for all accounts
     */
    private function testHTTPAuthentication() {
        echo "Testing HTTP authentication...\n";
        
        $authenticatedSessions = [];
        
        foreach ($this->testAccounts as $account) {
            $session = $this->attemptLogin($account['username'], $account['password']);
            if ($session['authenticated']) {
                echo "✅ User '{$account['username']}' authenticated via HTTP\n";
                $authenticatedSessions[] = $session;
            } else {
                echo "❌ User '{$account['username']}' authentication failed\n";
            }
        }
        
        return $authenticatedSessions;
    }
    
    /**
     * Attempt to login via HTTP and return session info
     */
    private function attemptLogin($username, $password) {
        $loginUrl = $this->baseUrl . '/group31petron_system_official4/public/login.php';
        
        // First, get the login page to establish a session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . "/cookies_$username.txt");
        curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . "/cookies_$username.txt");
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Now attempt login
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'username' => $username,
            'password' => $password,
            'login' => 'Login'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . "/cookies_$username.txt");
        curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . "/cookies_$username.txt");
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        // Check if login was successful (usually redirects to dashboard)
        $authenticated = false;
        $role = 'unknown';
        
        if ($httpCode === 200 && (
            strpos($finalUrl, 'dashboard') !== false || 
            strpos($finalUrl, 'index') !== false ||
            strpos($response, 'dashboard') !== false ||
            strpos($response, 'Welcome') !== false
        )) {
            $authenticated = true;
            
            // Try to extract role information from the response
            if (preg_match('/role["\']?\s*:\s*["\']?(\w+)["\']?/i', $response, $matches)) {
                $role = $matches[1];
            } elseif (preg_match('/admin|manager|staff|operations/i', $response, $matches)) {
                $role = strtolower($matches[0]);
            }
        }
        
        return [
            'username' => $username,
            'authenticated' => $authenticated,
            'role' => $role,
            'cookie_file' => sys_get_temp_dir() . "/cookies_$username.txt",
            'http_code' => $httpCode,
            'final_url' => $finalUrl
        ];
    }
    
    /**
     * Test system features with authenticated session
     */
    private function testSystemFeatures($session) {
        $features = [];
        
        // Test dashboard access
        $features['dashboard'] = $this->testDashboardAccess($session);
        
        // Test user management
        $features['user_management'] = $this->testUserManagement($session);
        
        // Test customer management
        $features['customer_management'] = $this->testCustomerManagement($session);
        
        // Test API endpoints
        $features['api_endpoints'] = $this->testAPIEndpoints($session);
        
        // Test reports access
        $features['reports'] = $this->testReportsAccess($session);
        
        $this->testResults[$session['username']] = $features;
    }
    
    private function testDashboardAccess($session) {
        $dashboardUrl = $this->baseUrl . '/group31petron_system_official4/public/index.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $session['cookie_file']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && strlen($response) > 100) {
            echo "  ✅ Dashboard accessible\n";
            return ['status' => 'working', 'http_code' => $httpCode];
        } else {
            echo "  ❌ Dashboard not accessible\n";
            return ['status' => 'error', 'http_code' => $httpCode];
        }
    }
    
    private function testUserManagement($session) {
        $userMgmtUrl = $this->baseUrl . '/group31petron_system_official4/app/master_data/users/users.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userMgmtUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $session['cookie_file']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && (strpos($response, 'user') !== false || strpos($response, 'management') !== false)) {
            echo "  ✅ User management accessible\n";
            return ['status' => 'working', 'http_code' => $httpCode];
        } else {
            echo "  ❌ User management not accessible\n";
            return ['status' => 'error', 'http_code' => $httpCode];
        }
    }
    
    private function testCustomerManagement($session) {
        // Test both possible customer management locations
        $customerUrls = [
            $this->baseUrl . '/group31petron_system_official4/app/master_data/customers/customers.php',
            $this->baseUrl . '/group31petron_system_official4/public/customers.php'
        ];
        
        foreach ($customerUrls as $customerUrl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $customerUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $session['cookie_file']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && strlen($response) > 100) {
                echo "  ✅ Customer management accessible\n";
                return ['status' => 'working', 'http_code' => $httpCode, 'url' => $customerUrl];
            }
        }
        
        echo "  ❌ Customer management not accessible\n";
        return ['status' => 'error', 'tested_urls' => $customerUrls];
    }
    
    private function testAPIEndpoints($session) {
        $apiResults = [];
        
        // Test Users API
        $apiResults['users'] = $this->testAPI('/group31petron_system_official4/backend/api/users.php?action=list', $session);
        
        // Test Customers API
        $apiResults['customers'] = $this->testAPI('/group31petron_system_official4/backend/api/customers.php', $session);
        
        // Test Sales API
        $apiResults['sales'] = $this->testAPI('/group31petron_system_official4/backend/api/sales.php?action=pending', $session);
        
        // Test Stations API
        $apiResults['stations'] = $this->testAPI('/group31petron_system_official4/backend/api/stations.php?action=list', $session);
        
        return $apiResults;
    }
    
    private function testAPI($endpoint, $session) {
        $apiUrl = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $session['cookie_file']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $endpointName = basename(parse_url($endpoint, PHP_URL_PATH), '.php');
        
        if ($httpCode === 200) {
            $isValidJson = json_decode($response) !== null;
            if ($isValidJson && (strpos($response, '"success":true') !== false || strpos($response, '"ok":true') !== false)) {
                echo "  ✅ API {$endpointName} working (valid JSON response)\n";
                return ['status' => 'working', 'http_code' => $httpCode, 'valid_json' => true];
            } elseif ($isValidJson) {
                echo "  ⚠️  API {$endpointName} responding (JSON but may have errors)\n";
                return ['status' => 'warning', 'http_code' => $httpCode, 'response' => substr($response, 0, 100)];
            } else {
                echo "  ❌ API {$endpointName} returning non-JSON\n";
                return ['status' => 'error', 'http_code' => $httpCode, 'response' => substr($response, 0, 100)];
            }
        } else {
            echo "  ❌ API {$endpointName} HTTP error ({$httpCode})\n";
            return ['status' => 'error', 'http_code' => $httpCode];
        }
    }
    
    private function testReportsAccess($session) {
        // Test common report URLs
        $reportUrls = [
            $this->baseUrl . '/group31petron_system_official4/app/reports/sales_report.php',
            $this->baseUrl . '/group31petron_system_official4/public/reports.php',
            $this->baseUrl . '/group31petron_system_official4/app/reports/'
        ];
        
        foreach ($reportUrls as $reportUrl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $reportUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $session['cookie_file']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && strlen($response) > 100) {
                echo "  ✅ Reports accessible\n";
                return ['status' => 'working', 'http_code' => $httpCode, 'url' => $reportUrl];
            }
        }
        
        echo "  ❌ Reports not accessible\n";
        return ['status' => 'error', 'tested_urls' => $reportUrls];
    }
    
    /**
     * Generate comprehensive test report
     */
    private function generateReport() {
        echo "\n=== COMPREHENSIVE HTTP TEST RESULTS ===\n\n";
        
        // Count overall results
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $warningTests = 0;
        
        foreach ($this->testResults as $user => $features) {
            foreach ($features as $feature => $result) {
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
        
        echo "SUMMARY:\n";
        echo "- Total Tests: {$totalTests}\n";
        echo "- Passed: {$passedTests} ✅\n";
        echo "- Failed: {$failedTests} ❌\n";
        echo "- Warnings: {$warningTests} ⚠️\n\n";
        
        echo "DETAILED RESULTS BY USER:\n";
        foreach ($this->testResults as $user => $features) {
            echo "User: {$user}\n";
            foreach ($features as $feature => $result) {
                $status = $result['status'] ?? 'unknown';
                $icon = $status === 'working' ? '✅' : ($status === 'error' ? '❌' : '⚠️');
                echo "  {$icon} {$feature}: {$status}";
                if (isset($result['http_code'])) {
                    echo " (HTTP {$result['http_code']})";
                }
                echo "\n";
            }
            echo "\n";
        }
        
        // Clean up cookie files
        foreach ($this->testAccounts as $account) {
            $cookieFile = sys_get_temp_dir() . "/cookies_{$account['username']}.txt";
            if (file_exists($cookieFile)) {
                unlink($cookieFile);
            }
        }
    }
}

// Run the tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // Auto-detect base URL
    $baseUrl = 'http://localhost';
    
    $tester = new HTTPTester($baseUrl);
    $tester->runTests();
}
?>