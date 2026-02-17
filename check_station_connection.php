<?php
/**
 * Check which station fuel readings are connected to
 */

require_once __DIR__ . '/public/db_connect.php';

echo "🔍 CHECKING STATION CONNECTION FOR FUEL READINGS\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Get all readings with station info
    $stmt = $pdo->query("
        SELECT 
            dr.id,
            dr.station_id,
            dr.pump_id,
            dr.reading_date,
            dr.shift,
            dr.sales_liters,
            dr.status,
            u.name as staff_name,
            s.name as station_name
        FROM fuel_daily_readings dr
        LEFT JOIN users u ON dr.user_id = u.id
        LEFT JOIN stations s ON dr.station_id = s.id
        ORDER BY dr.id DESC
        LIMIT 10
    ");
    
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Recent Fuel Readings:\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-5s %-12s %-15s %-12s %-10s %-12s %-15s %-10s\n", 
           "ID", "Station ID", "Station Name", "Pump ID", "Date", "Shift", "Sales (L)", "Status");
    echo str_repeat("-", 100) . "\n";
    
    foreach ($readings as $reading) {
        printf("%-5s %-12s %-15s %-12s %-10s %-12s %-15s %-10s\n",
            $reading['id'],
            $reading['station_id'] ?? 'NULL',
            substr($reading['station_name'] ?? 'N/A', 0, 15),
            $reading['pump_id'] ?? 'NULL',
            $reading['reading_date'],
            $reading['shift'],
            number_format($reading['sales_liters'], 2),
            $reading['status']
        );
    }
    
    echo str_repeat("-", 100) . "\n\n";
    
    // Count readings by station
    $stmt = $pdo->query("
        SELECT 
            dr.station_id,
            s.name as station_name,
            COUNT(*) as count
        FROM fuel_daily_readings dr
        LEFT JOIN stations s ON dr.station_id = s.id
        GROUP BY dr.station_id
        ORDER BY count DESC
    ");
    
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📈 Readings by Station:\n";
    echo str_repeat("-", 50) . "\n";
    printf("%-12s %-25s %-10s\n", "Station ID", "Station Name", "Count");
    echo str_repeat("-", 50) . "\n";
    
    foreach ($stations as $station) {
        printf("%-12s %-25s %-10s\n",
            $station['station_id'] ?? 'NULL',
            substr($station['station_name'] ?? 'Unknown', 0, 25),
            $station['count']
        );
    }
    
    echo str_repeat("-", 50) . "\n\n";
    
    // Check if station 1205 exists
    $stmt = $pdo->prepare("SELECT id, name FROM stations WHERE id = ?");
    $stmt->execute([1205]);
    $station = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($station) {
        echo "✅ Station 1205 exists: {$station['name']}\n";
        
        // Count readings for station 1205
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fuel_daily_readings WHERE station_id = ?");
        $stmt->execute([1205]);
        $count = $stmt->fetchColumn();
        
        echo "📊 Total readings for Station 1205: $count\n";
    } else {
        echo "❌ Station 1205 does NOT exist in the stations table\n";
    }
    
    // Check current user's station
    echo "\n👤 Current User Context:\n";
    $me = current_user();
    echo "   User ID: {$me['id']}\n";
    echo "   User Name: {$me['name']}\n";
    echo "   Station ID: " . user_station_id() . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
?>