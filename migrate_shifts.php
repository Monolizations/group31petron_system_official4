<?php
/**
 * Shifts Database Migration Script
 * Creates shifts table with default Petron shift values
 */

require_once __DIR__ . '/public/db_connect.php';

echo "=== Shifts Table Migration ===\n\n";

try {
    // Check if shifts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'shifts'");
    $tableExists = $stmt->fetch() !== false;

    if (!$tableExists) {
        echo "Creating shifts table...\n";
        
        $sql = "
        CREATE TABLE `shifts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(50) NOT NULL,
          `start_time` time NOT NULL,
          `end_time` time NOT NULL,
          `description` varchar(255) DEFAULT NULL,
          `sort_order` int(11) DEFAULT 0,
          `is_active` tinyint(1) DEFAULT 1,
          `created_at` datetime DEFAULT current_timestamp(),
          `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $pdo->exec($sql);
        echo "✓ Shifts table created successfully\n";
    } else {
        echo "⚠️ Shifts table already exists\n";
    }

    // Check if shifts table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM shifts");
    $shiftCount = $stmt->fetch()['count'];

    if ($shiftCount === 0) {
        echo "Populating default shifts...\n";

        // Insert default Petron shifts
        $defaultShifts = [
            [
                'name' => 'Morning',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'description' => 'Morning shift (6:00 AM - 2:00 PM)',
                'sort_order' => 1
            ],
            [
                'name' => 'Afternoon',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'description' => 'Afternoon shift (2:00 PM - 10:00 PM)',
                'sort_order' => 2
            ],
            [
                'name' => 'Evening',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'description' => 'Evening shift (10:00 PM - 6:00 AM)',
                'sort_order' => 3
            ]
        ];

        foreach ($defaultShifts as $shift) {
            $stmt = $pdo->prepare("INSERT INTO shifts (name, start_time, end_time, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $shift['name'],
                $shift['start_time'],
                $shift['end_time'],
                $shift['description'],
                $shift['sort_order']
            ]);
            echo "✓ Added: {$shift['name']}\n";
        }
    } else {
        echo "⚠️ Shifts table already has $shiftCount shift(s)\n";
    }

    echo "\n=== Current Shifts in Database ===\n";
    $stmt = $pdo->query("SELECT id, name, start_time, end_time, description, is_active FROM shifts ORDER BY sort_order");
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($shifts as $shift) {
        $status = $shift['is_active'] ? '✓ Active' : '○ Inactive';
        echo "[{$shift['id']}] {$status} | {$shift['name']} | {$shift['start_time']} - {$shift['end_time']} | {$shift['description']}\n";
    }

    echo "\n=== Migration Complete ===\n";
    echo "Total shifts: " . count($shifts) . "\n";

} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
