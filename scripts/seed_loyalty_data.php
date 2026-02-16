<?php
// Seed loyalty_transactions and rewards tables with realistic data
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
    
    echo "=== Loyalty Program Seed Data Generation ===\n\n";
    
    // First, seed rewards
    echo "Setting up rewards...\n";
    $pdo->exec("TRUNCATE TABLE rewards");
    
    $rewards = [
        ['name' => 'Free Car Wash', 'description' => 'One free car wash service at any station', 'points' => 500, 'category' => 'Service'],
        ['name' => '10% Discount', 'description' => 'Get 10% discount on next purchase', 'points' => 300, 'category' => 'Discount'],
        ['name' => 'Free Oil Change', 'description' => 'Complimentary oil change service', 'points' => 800, 'category' => 'Service'],
        ['name' => 'Fuel Voucher ₱100', 'description' => 'Worth ₱100 in fuel', 'points' => 1000, 'category' => 'Fuel'],
        ['name' => 'Fuel Voucher ₱500', 'description' => 'Worth ₱500 in fuel', 'points' => 4000, 'category' => 'Fuel'],
        ['name' => 'Free Tire Rotation', 'description' => 'Free tire rotation service', 'points' => 350, 'category' => 'Service'],
        ['name' => 'Windshield Cleaning', 'description' => 'Complimentary windshield cleaning', 'points' => 200, 'category' => 'Service'],
        ['name' => '20% Discount', 'description' => 'Get 20% discount on next purchase', 'points' => 600, 'category' => 'Discount'],
    ];
    
    $stmt = $pdo->prepare(
        "INSERT INTO rewards (name, description, points_required, category, is_active, created_at) 
         VALUES (?, ?, ?, ?, 1, NOW())"
    );
    
    foreach ($rewards as $reward) {
        $stmt->execute([
            $reward['name'],
            $reward['description'],
            $reward['points'],
            $reward['category']
        ]);
    }
    
    echo "✅ Inserted " . count($rewards) . " rewards\n\n";
    
    // Now seed loyalty transactions for existing customers
    echo "Generating loyalty transactions...\n";
    $pdo->exec("TRUNCATE TABLE loyalty_transactions");
    
    // Fetch all customers
    $result = $pdo->query("SELECT id FROM customers ORDER BY id");
    $customers = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($customers) . " customers\n";
    
    $transaction_count = 0;
    $today = new DateTime();
    
    // For each customer, create 5-15 transactions over past 30 days
    foreach ($customers as $customer) {
        $customer_id = $customer['id'];
        $num_transactions = rand(5, 15);
        
        for ($i = 0; $i < $num_transactions; $i++) {
            $days_ago = rand(1, 30);
            $date = clone $today;
            $date->modify("-{$days_ago} days");
            $time = rand(6, 21) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00';
            $created_at = $date->format('Y-m-d') . ' ' . $time;
            
            // 70% earn, 30% redeem
            $is_redeem = rand(1, 100) <= 30;
            $type = $is_redeem ? 'redeem' : 'earn';
            
            if ($is_redeem) {
                // Redeem points (use specific reward-based amounts)
                $points = $rewards[rand(0, count($rewards) - 1)]['points'];
                $reference = 'REWARD-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            } else {
                // Earn points (varies from 10 to 500 points per transaction)
                $points = rand(1, 50) * 10; // 10, 20, 30, ... 500
                $reference = 'SALE-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            $stmt = $pdo->prepare(
                "INSERT INTO loyalty_transactions (customer_id, type, points, reference_id, created_at) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            
            $stmt->execute([
                $customer_id,
                $type,
                $points,
                $reference,
                $created_at
            ]);
            
            $transaction_count++;
        }
    }
    
    echo "✅ Inserted $transaction_count loyalty transactions\n\n";
    
    // Update customer points to reflect transactions
    echo "Updating customer points balances...\n";
    
    $result = $pdo->query(
        "SELECT c.id, 
                COALESCE(SUM(CASE WHEN lt.type='earn' THEN lt.points ELSE 0 END), 0) as earned,
                COALESCE(SUM(CASE WHEN lt.type='redeem' THEN lt.points ELSE 0 END), 0) as redeemed
         FROM customers c
         LEFT JOIN loyalty_transactions lt ON c.id = lt.customer_id
         GROUP BY c.id"
    );
    
    $update_stmt = $pdo->prepare("UPDATE customers SET points = ? WHERE id = ?");
    
    foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $total_points = max(0, $row['earned'] - $row['redeemed']);
        $update_stmt->execute([$total_points, $row['id']]);
    }
    
    echo "✅ Updated customer points balances\n\n";
    
    // Verify data
    $reward_count = $pdo->query("SELECT COUNT(*) FROM rewards")->fetchColumn();
    $transaction_count = $pdo->query("SELECT COUNT(*) FROM loyalty_transactions")->fetchColumn();
    
    echo "=== VERIFICATION ===\n";
    echo "Total rewards: $reward_count\n";
    echo "Total loyalty transactions: $transaction_count\n";
    
    // Show sample data
    echo "\n=== SAMPLE REWARDS ===\n";
    $result = $pdo->query(
        "SELECT id, name, points_required, category FROM rewards ORDER BY points_required DESC LIMIT 5"
    );
    
    foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['name'] . " | " . $row['points_required'] . " pts | " . $row['category'] . "\n";
    }
    
    echo "\n=== SAMPLE TRANSACTIONS ===\n";
    $result = $pdo->query(
        "SELECT c.name as customer, lt.type, lt.points, lt.reference_id, lt.created_at 
         FROM loyalty_transactions lt
         JOIN customers c ON lt.customer_id = c.id
         ORDER BY lt.created_at DESC
         LIMIT 10"
    );
    
    foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['customer'] . " | " . 
             strtoupper($row['type']) . " | " . 
             $row['points'] . " pts | " . 
             $row['reference_id'] . " | " . 
             date('M d H:i', strtotime($row['created_at'])) . "\n";
    }
    
    echo "\n=== SAMPLE CUSTOMER POINTS ===\n";
    $result = $pdo->query(
        "SELECT name, points FROM customers WHERE points > 0 ORDER BY points DESC LIMIT 5"
    );
    
    foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['name'] . " | " . number_format($row['points']) . " points\n";
    }
    
    echo "\n✅ Loyalty program seed data generated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
