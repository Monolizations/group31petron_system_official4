<?php
/**
 * TIER 2 Data Generation
 * Generates: sales, sale_items, and more job_orders
 */

require_once __DIR__ . '/../public/db_connect.php';

echo "=== TIER 2 DATA GENERATION ===\n\n";

// 1. Generate Sales and Sale Items
echo "1. Generating Sales and Sale Items...\n";

try {
    $existing_sales = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
    echo "   Current sales: $existing_sales\n";
    
    if ($existing_sales < 100) {
        $sales_to_add = 100 - $existing_sales;
        
        // Get users and customers and products
        $users = $pdo->query("SELECT id, station_id FROM users WHERE role IN ('manager', 'staff')")->fetchAll(PDO::FETCH_ASSOC);
        $customers = $pdo->query("SELECT id FROM customers")->fetchAll(PDO::FETCH_COLUMN);
        $products = $pdo->query("SELECT id, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users) || empty($customers) || empty($products)) {
            echo "   ! Skipping: Missing users, customers, or products\n\n";
        } else {
            $sale_count = 0;
            $sale_item_count = 0;
            
            for ($i = 0; $i < $sales_to_add; $i++) {
                // Random past 30 days
                $days_ago = rand(0, 30);
                $hours = rand(6, 23);
                $minutes = rand(0, 59);
                
                $sale_date = date('Y-m-d', strtotime("-$days_ago days"));
                $sale_time = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':00';
                
                // Random user and customer
                $user = $users[array_rand($users)];
                $customer_id = $customers[array_rand($customers)];
                $station_id = $user['station_id'];
                
                // Generate unique sale ID (sales.id is VARCHAR)
                $sale_id = 'SALE-' . time() . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
                
                $payment_methods = ['cash', 'card', 'gcash'];
                $payment_method = $payment_methods[array_rand($payment_methods)];
                
                $total = 0;
                $num_items = rand(1, 4);
                $items = [];
                
                // Generate 1-4 items per sale
                for ($j = 0; $j < $num_items; $j++) {
                    $product = $products[array_rand($products)];
                    $quantity = rand(1, 10);
                    $unit_price = $product['price'];
                    $item_total = $quantity * $unit_price;
                    $total += $item_total;
                    
                    $items[] = [
                        'product_id' => $product['id'],
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'total_amount' => $item_total
                    ];
                }
                
                // Insert sale
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO sales (id, sale_date, sale_time, customer_id, user_id, station_id, payment_method, total, amount_received, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $amount_received = $total;
                    if (rand(1, 100) > 95) {
                        $amount_received += rand(100, 5000); // Some overpayments
                    }
                    
                    $stmt->execute([
                        $sale_id,
                        $sale_date,
                        $sale_time,
                        $customer_id,
                        $user['id'],
                        $station_id,
                        $payment_method,
                        $total,
                        $amount_received,
                        'completed'
                    ]);
                    
                    $sale_count++;
                    
                    // Insert sale items
                    foreach ($items as $item) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_amount)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $sale_id,
                                $item['product_id'],
                                $item['quantity'],
                                $item['unit_price'],
                                $item['total_amount']
                            ]);
                            $sale_item_count++;
                        } catch (Exception $e) {
                            // Skip if error
                        }
                    }
                    
                } catch (Exception $e) {
                    // Skip on error
                }
                
                if ($sale_count % 20 == 0 && $sale_count > 0) {
                    echo "   + $sale_count sales generated...\n";
                }
            }
            
            echo "   ✓ Sales generation complete. Added: $sale_count sales, $sale_item_count sale items\n\n";
        }
    }
} catch (Exception $e) {
    echo "   ! Error: " . $e->getMessage() . "\n\n";
}

// 2. Generate More Job Orders
echo "2. Generating Job Orders...\n";

try {
    $existing_jobs = $pdo->query("SELECT COUNT(*) FROM job_orders")->fetchColumn();
    echo "   Current job orders: $existing_jobs\n";
    
    if ($existing_jobs < 50) {
        $jobs_to_add = 50 - $existing_jobs;
        
        $users = $pdo->query("SELECT id, station_id FROM users WHERE role IN ('manager', 'staff')")->fetchAll(PDO::FETCH_ASSOC);
        $customers = $pdo->query("SELECT id FROM customers")->fetchAll(PDO::FETCH_COLUMN);
        $service_categories = $pdo->query("SELECT id FROM service_categories")->fetchAll(PDO::FETCH_COLUMN);
        $mechanics = $pdo->query("SELECT id FROM mechanics")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($users) || empty($customers) || empty($service_categories)) {
            echo "   ! Skipping: Missing required data\n\n";
        } else {
            $job_statuses = ['Pending', 'In Progress', 'Completed'];
            $vehicle_types = ['Sedan', 'SUV', 'Truck', 'Van', 'Pickup', 'Motorcycle', 'Bus'];
            
            $added = 0;
            
            for ($i = 0; $i < $jobs_to_add; $i++) {
                $user = $users[array_rand($users)];
                $customer_id = $customers[array_rand($customers)];
                $service_cat_id = $service_categories[array_rand($service_categories)];
                $mechanic_id = !empty($mechanics) ? $mechanics[array_rand($mechanics)] : null;
                $status = $job_statuses[array_rand($job_statuses)];
                $vehicle_type = $vehicle_types[array_rand($vehicle_types)];
                
                // Random plate (PH format)
                $letters = chr(65 + rand(0, 25)) . chr(65 + rand(0, 25)) . chr(65 + rand(0, 25));
                $numbers = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $vehicle_plate = "$letters-$numbers";
                
                $days_ago = rand(0, 30);
                $created_at = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
                $started_at = $status != 'Pending' ? date('Y-m-d H:i:s', strtotime("-$days_ago days + 1 hour")) : null;
                $completed_at = $status == 'Completed' ? date('Y-m-d H:i:s', strtotime("-$days_ago days + 4 hours")) : null;
                
                $estimated_labor_cost = rand(500, 5000);
                $estimated_parts_cost = rand(0, 10000);
                $actual_labor_cost = $status == 'Completed' ? $estimated_labor_cost + rand(-500, 1000) : null;
                $actual_parts_cost = $status == 'Completed' ? $estimated_parts_cost + rand(-1000, 2000) : null;
                
                try {
                    // Generate unique job order number (JO-YYYYMMDD-####)
                    $jo_number = 'JO-' . date('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
                    $service_descriptions = ['Oil change', 'Tire replacement', 'Brake service', 'Engine diagnostics', 'Battery replacement', 'General maintenance'];
                    $service_desc = $service_descriptions[array_rand($service_descriptions)];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO job_orders (
                            job_order_number, station_id, user_id, customer_id, vehicle_plate, vehicle_type,
                            service_category_id, assigned_mechanic_id, assigned_by, service_description,
                            status, created_at, started_at, completed_at,
                            estimated_labor_cost, estimated_parts_cost,
                            actual_labor_cost, actual_parts_cost
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $jo_number,
                        $user['station_id'],
                        $user['id'],
                        $customer_id,
                        $vehicle_plate,
                        $vehicle_type,
                        $service_cat_id,
                        $mechanic_id,
                        $user['id'],
                        $service_desc,
                        $status,
                        $created_at,
                        $started_at,
                        $completed_at,
                        $estimated_labor_cost,
                        $estimated_parts_cost,
                        $actual_labor_cost,
                        $actual_parts_cost
                    ]);
                    
                    $added++;
                    if ($added % 10 == 0) {
                        echo "   + $added job orders added...\n";
                    }
                } catch (Exception $e) {
                    // Skip on error
                }
            }
            
            $new_job_count = $pdo->query("SELECT COUNT(*) FROM job_orders")->fetchColumn();
            echo "   ✓ Job order generation complete. Added: $added, Total: $new_job_count\n\n";
        }
    }
} catch (Exception $e) {
    echo "   ! Error: " . $e->getMessage() . "\n\n";
}

echo "=== TIER 2 COMPLETE ===\n";
?>
