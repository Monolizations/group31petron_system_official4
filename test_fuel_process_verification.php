<?php
/**
 * Test: Direct call to fuel_process_verification.php
 */

echo "=== TESTING FUEL PROCESS VERIFICATION ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Set up test POST data
$post_data = [
    'action' => 'verify_reading',
    'id' => '12',
    'status' => 'Verified',
    'notes' => 'Test verification',
];

echo "POST Data:\n";
print_r($post_data);
echo "\n";

// Use curl to test
$url = 'http://localhost/group31petron_system_official4/backend/fuel_process_verification.php';

echo "Testing URL: $url\n\n";

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test_session'); // Add session cookie

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response:\n";

// Separate headers and body
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

echo "--- HEADERS ---\n";
echo $headers;
echo "\n--- BODY ---\n";
echo $body;
echo "\n";

echo "\n=== TEST COMPLETE ===\n";
?>
