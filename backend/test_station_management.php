<?php
/**
 * Station Management Test Script
 * Tests all the StationManager functionality to ensure it works correctly
 */

require_once __DIR__ . '/station_management.php';
require_once __DIR__ . '/../public/db_connect.php';

echo "<h2>Station Management Test Results</h2>\n";
echo "<style>
.test { margin: 10px 0; padding: 10px; border-radius: 5px; }
.pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
</style>\n";

// Test 1: Manager Station Assignment
echo "<div class='test info'><strong>Test 1: Manager Station Assignment</strong></div>\n";
try {
    $result = StationManager::getTargetStationForUserCreation('manager', 1205, null);
    if ($result === 1205) {
        echo "<div class='test pass'>✅ PASS: Manager correctly inherits their station (1205)</div>\n";
    } else {
        echo "<div class='test fail'>❌ FAIL: Manager got station $result instead of 1205</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='test fail'>❌ FAIL: Manager test threw exception: " . $e->getMessage() . "</div>\n";
}

// Test 2: Manager Security Violation
echo "<div class='test info'><strong>Test 2: Manager Security Violation Test</strong></div>\n";
try {
    $result = StationManager::getTargetStationForUserCreation('manager', 1205, 9999);
    echo "<div class='test fail'>❌ FAIL: Manager was allowed to assign different station ($result)</div>\n";
} catch (Exception $e) {
    echo "<div class='test pass'>✅ PASS: Manager correctly blocked from assigning different station: " . $e->getMessage() . "</div>\n";
}

// Test 3: Superadmin Station Selection Required
echo "<div class='test info'><strong>Test 3: Superadmin Station Selection Required</strong></div>\n";
try {
    $result = StationManager::getTargetStationForUserCreation('superadmin', 1205, null);
    echo "<div class='test fail'>❌ FAIL: Superadmin was allowed to create user without selecting station</div>\n";
} catch (Exception $e) {
    echo "<div class='test pass'>✅ PASS: Superadmin correctly required to select station: " . $e->getMessage() . "</div>\n";
}

// Test 4: Superadmin Valid Station Assignment
echo "<div class='test info'><strong>Test 4: Superadmin Valid Station Assignment</strong></div>\n";
try {
    // First, check if we have any active stations
    $active_stations = StationManager::getActiveStations();
    if (empty($active_stations)) {
        echo "<div class='test info'>ℹ️ INFO: No active stations found in database</div>\n";
    } else {
        $test_station = $active_stations[0]['id'];
        $result = StationManager::getTargetStationForUserCreation('superadmin', 1205, $test_station);
        if ($result === (int)$test_station) {
            echo "<div class='test pass'>✅ PASS: Superadmin correctly assigned to selected station ($test_station)</div>\n";
        } else {
            echo "<div class='test fail'>❌ FAIL: Superadmin got station $result instead of $test_station</div>\n";
        }
    }
} catch (Exception $e) {
    echo "<div class='test fail'>❌ FAIL: Superadmin valid assignment test failed: " . $e->getMessage() . "</div>\n";
}

// Test 5: UI Configuration for Different Roles
echo "<div class='test info'><strong>Test 5: UI Configuration Tests</strong></div>\n";

// Test Manager UI Config
try {
    $config = StationManager::getStationUIConfig('manager', 1205, 'Station 1205');
    if ($config['type'] === 'readonly_field' && $config['readonly'] === true) {
        echo "<div class='test pass'>✅ PASS: Manager UI shows read-only field</div>\n";
    } else {
        echo "<div class='test fail'>❌ FAIL: Manager UI config incorrect: " . json_encode($config) . "</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='test fail'>❌ FAIL: Manager UI config test failed: " . $e->getMessage() . "</div>\n";
}

// Test Superadmin UI Config
try {
    $config = StationManager::getStationUIConfig('superadmin', 1205, 'Station 1205');
    if ($config['type'] === 'dropdown' && $config['required'] === true && $config['default_selected'] === null) {
        echo "<div class='test pass'>✅ PASS: Superadmin UI shows dropdown with no default selection</div>\n";
    } else {
        echo "<div class='test fail'>❌ FAIL: Superadmin UI config incorrect: " . json_encode($config) . "</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='test fail'>❌ FAIL: Superadmin UI config test failed: " . $e->getMessage() . "</div>\n";
}

// Test 6: Station Validation
echo "<div class='test info'><strong>Test 6: Station Validation Tests</strong></div>\n";
$valid = StationManager::validateStationAssignment('manager', 1205, 1205);
if ($valid) {
    echo "<div class='test pass'>✅ PASS: Manager's own station assignment validated correctly</div>\n";
} else {
    echo "<div class='test fail'>❌ FAIL: Manager's own station assignment failed validation</div>\n";
}

$invalid = StationManager::validateStationAssignment('manager', 1205, 9999);
if (!$invalid) {
    echo "<div class='test pass'>✅ PASS: Manager's invalid station assignment correctly rejected</div>\n";
} else {
    echo "<div class='test fail'>❌ FAIL: Manager's invalid station assignment was allowed</div>\n";
}

// Test 7: Active Stations Retrieval
echo "<div class='test info'><strong>Test 7: Active Stations Retrieval</strong></div>\n";
$stations = StationManager::getActiveStations();
echo "<div class='test info'>ℹ️ INFO: Found " . count($stations) . " active stations in database</div>\n";
if (count($stations) > 0) {
    echo "<div class='test pass'>✅ PASS: Active stations retrieved successfully</div>\n";
    foreach ($stations as $station) {
        echo "<div class='test info'>ℹ️ Station: {$station['id']} - {$station['name']}</div>\n";
    }
} else {
    echo "<div class='test info'>ℹ️ INFO: No active stations found (this might be expected in a test environment)</div>\n";
}

// Test 8: Default Station Retrieval
echo "<div class='test info'><strong>Test 8: Default Station Retrieval</strong></div>\n";
$default_station = StationManager::getDefaultStation();
if ($default_station) {
    echo "<div class='test pass'>✅ PASS: Default station retrieved: $default_station</div>\n";
} else {
    echo "<div class='test info'>ℹ️ INFO: No default station available (this might be expected in a test environment)</div>\n";
}

// Test 9: Invalid Role Handling
echo "<div class='test info'><strong>Test 9: Invalid Role Handling</strong></div>\n";
try {
    $result = StationManager::getTargetStationForUserCreation('invalid_role', 1205, 1205);
    echo "<div class='test fail'>❌ FAIL: Invalid role was allowed</div>\n";
} catch (Exception $e) {
    echo "<div class='test pass'>✅ PASS: Invalid role correctly rejected: " . $e->getMessage() . "</div>\n";
}

echo "<div class='test info'><strong>All Tests Completed!</strong></div>\n";
echo "<p>This test verifies that the StationManager class correctly implements:</p>\n";
echo "<ul>\n";
echo "<li>✅ Managers can only create staff for their assigned station</li>\n";
echo "<li>✅ Superadmins must manually select station (no default selection)</li>\n";
echo "<li>✅ Managers see read-only field showing their station</li>\n";
echo "<li>✅ System completely prevents managers from selecting different stations</li>\n";
echo "</ul>\n";
?>