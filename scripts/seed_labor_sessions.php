<?php
// Seed labor_sessions table with realistic shift data for staff members
$dbname = 'petron_pos_db_secure';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:unix_socket=/opt/lampp/var/mysql/mysql.sock;dbname=" . $dbname . ";charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== Labor Sessions Seed Data Generation ===\n\n";
    
    // Fetch all staff users (role_id = 5 for staff)
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'staff' ORDER BY id");
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($staff) . " staff members\n";
    
    if (empty($staff)) {
        echo "No staff members found. Exiting.\n";
        exit;
    }
    
    // Clear existing data
    $pdo->exec("TRUNCATE TABLE labor_sessions");
    echo "Cleared existing labor_sessions data\n\n";
    
    // Define shift patterns (Morning, Afternoon, Night)
    $shifts = [
        ['name' => 'Morning', 'start' => '06:00', 'end' => '14:00', 'hours' => 8],
        ['name' => 'Afternoon', 'start' => '14:00', 'end' => '22:00', 'hours' => 8],
        ['name' => 'Night', 'start' => '22:00', 'end' => '06:00', 'hours' => 8],
    ];
    
    $inserted = 0;
    $base_date = new DateTime('2026-01-20'); // Start from past date
    
    // For each staff member, create 10 past shifts and 5 upcoming shifts
    foreach ($staff as $staff_member) {
        $staff_id = $staff_member['id'];
        
        // Get station_id for this staff (from users table)
        $stmt = $pdo->prepare("SELECT station_id FROM users WHERE id = ?");
        $stmt->execute([$staff_id]);
        $station_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $station_id = $station_data['station_id'] ?? 1;
        
        // Create 10 past completed shifts (over last 30 days)
        for ($i = 30; $i >= 1; $i--) {
            $shift_date = clone $base_date;
            $shift_date->modify("+{$i} days");
            
            $shift = $shifts[$i % 3]; // Rotate through shifts
            
            $start_time = $shift_date->format('Y-m-d') . ' ' . $shift['start'];
            
            // Handle night shift that goes to next day
            $end_date = clone $shift_date;
            if ($shift['name'] === 'Night') {
                $end_date->modify('+1 day');
            }
            $end_time = $end_date->format('Y-m-d') . ' ' . $shift['end'];
            
            $stmt = $pdo->prepare(
                "INSERT INTO labor_sessions (user_id, station_id, start_time, end_time, hours_worked, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute([
                $staff_id,
                $station_id,
                $start_time,
                $end_time,
                $shift['hours']
            ]);
            
            $inserted++;
        }
        
        // Create 5 upcoming scheduled shifts (next 30 days)
        for ($i = 1; $i <= 5; $i++) {
            $shift_date = clone $base_date;
            $shift_date->modify("+{$i} days");
            
            $shift = $shifts[$i % 3];
            
            $start_time = $shift_date->format('Y-m-d') . ' ' . $shift['start'];
            
            $end_date = clone $shift_date;
            if ($shift['name'] === 'Night') {
                $end_date->modify('+1 day');
            }
            $end_time = $end_date->format('Y-m-d') . ' ' . $shift['end'];
            
            $stmt = $pdo->prepare(
                "INSERT INTO labor_sessions (user_id, station_id, start_time, end_time, hours_worked, created_at) 
                VALUES (?, ?, ?, ?, NULL, NOW())"
            );
            
            $stmt->execute([
                $staff_id,
                $station_id,
                $start_time,
                $end_time
            ]);
            
            $inserted++;
        }
        
        // Create 1 active/current shift for some staff (just started, no end time)
        if (rand(0, 1) === 1) {
            $current_time = date('Y-m-d H:i:s');
            $current_hour = (int)date('H');
            
            // Determine which shift they're in
            $shift = $shifts[0]; // Default to morning
            if ($current_hour >= 14 && $current_hour < 22) {
                $shift = $shifts[1]; // Afternoon
            } elseif ($current_hour >= 22 || $current_hour < 6) {
                $shift = $shifts[2]; // Night
            }
            
            $start_time = date('Y-m-d') . ' ' . $shift['start'];
            
            // If start time is in future, adjust to now
            if (strtotime($start_time) > strtotime($current_time)) {
                $start_time = $current_time;
            }
            
            $stmt = $pdo->prepare(
                "INSERT INTO labor_sessions (user_id, station_id, start_time, end_time, hours_worked, created_at) 
                VALUES (?, ?, ?, NULL, NULL, NOW())"
            );
            
            $stmt->execute([
                $staff_id,
                $station_id,
                $start_time
            ]);
            
            $inserted++;
        }
    }
    
    echo "✅ Inserted $inserted labor session records\n\n";
    
    // Verify data
    $count = $pdo->query("SELECT COUNT(*) FROM labor_sessions")->fetchColumn();
    echo "Total labor_sessions in database: $count\n";
    
    // Show sample data
    echo "\n=== SAMPLE DATA ===\n";
    $result = $pdo->query(
        "SELECT u.name as staff_name, ls.start_time, ls.end_time, ls.hours_worked 
         FROM labor_sessions ls 
         JOIN users u ON ls.user_id = u.id 
         ORDER BY ls.start_time DESC 
         LIMIT 10"
    );
    
    foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['staff_name'] . " | " . 
             substr($row['start_time'], 0, 10) . " " . substr($row['start_time'], 11, 5) . " - " . 
             ($row['end_time'] ? substr($row['end_time'], 11, 5) : "Active") . " | " .
             ($row['hours_worked'] ? $row['hours_worked'] . "h" : "In Progress") . "\n";
    }
    
    echo "\n✅ Labor sessions seed data generated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
