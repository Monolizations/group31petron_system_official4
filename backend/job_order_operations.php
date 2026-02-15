<?php
/**
 * Job Order Management Backend
 * Staff-driven workflow with admin supervision
 * 
 * Flow:
 * 1. Staff encodes job order
 * 2. Admin reviews and validates
 * 3. Admin approves (if high-value/sensitive)
 * 4. Job execution
 * 5. Inventory deduction
 * 6. Billing calculation
 */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/../public/db_connect.php';

class JobOrderOperations {
    
    private $pdo;
    private $station_id;
    private $user;
    
    public function __construct($pdo, $user, $station_id) {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->station_id = $station_id;
    }
    
    /**
     * Create Job Order (Staff Action)
     * Staff encodes all job order details
     */
    public function createJobOrder($data) {
        try {
            $this->pdo->beginTransaction();

            $role = role_key($this->user['role'] ?? '');
            if ($role !== 'staff') {
                throw new Exception('Only operations staff can create job orders');
            }

            if (empty($data['service_category_id'])) {
                throw new Exception('Please select a service type');
            }
            
            // Generate job order number
            $job_order_number = $this->generateJobOrderNumber();

            // Resolve or create customer (optional)
            $customer_id = $data['customer_id'] ?? null;
            $customer_name = trim((string)($data['customer_name'] ?? ''));
            if (!$customer_id && $customer_name !== '') {
                $stmt = $this->pdo->prepare("SELECT id FROM customers WHERE name = ? AND station_id = ? LIMIT 1");
                $stmt->execute([$customer_name, $this->station_id]);
                $existingCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingCustomer) {
                    $customer_id = $existingCustomer['id'];
                } else {
                    $ins = $this->pdo->prepare("INSERT INTO customers (name, station_id, status, type) VALUES (?, ?, 'active', 'cash')");
                    $ins->execute([$customer_name, $this->station_id]);
                    $customer_id = $this->pdo->lastInsertId();
                }
            }

            // Resolve or create mechanic (required)
            $assigned_mechanic_id = $data['assigned_mechanic_id'] ?? null;
            $mechanic_name = trim((string)($data['mechanic_name'] ?? ''));
            if (!$assigned_mechanic_id && $mechanic_name !== '') {
                $stmt = $this->pdo->prepare("SELECT id FROM mechanics WHERE full_name = ? AND station_id = ? LIMIT 1");
                $stmt->execute([$mechanic_name, $this->station_id]);
                $existingMech = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingMech) {
                    $assigned_mechanic_id = $existingMech['id'];
                } else {
                    $ins = $this->pdo->prepare("INSERT INTO mechanics (station_id, full_name, status) VALUES (?, ?, 'active')");
                    $ins->execute([$this->station_id, $mechanic_name]);
                    $assigned_mechanic_id = $this->pdo->lastInsertId();
                }
            }

            if (!$assigned_mechanic_id) {
                throw new Exception('Please select or enter a mechanic');
            }
            
            // Validate mechanic availability (duty roster check)
            if (!$this->validateMechanicAvailability($assigned_mechanic_id)) {
                throw new Exception('Selected mechanic is not available or on duty');
            }
            
            // Determine if admin approval is required
            $requires_approval = $this->requiresAdminApproval($data);
            $initial_status = $requires_approval ? 'Pending' : 'Pending';
            
            // Calculate estimated costs
            $estimated_costs = $this->calculateEstimatedCosts($data);
            
            // Insert job order
            $stmt = $this->pdo->prepare("
                INSERT INTO job_orders
                (job_order_number, station_id, customer_id, vehicle_plate, vehicle_type,
                 service_category_id, assigned_mechanic_id, assigned_by, service_description, 
                 estimated_duration, status, notes, created_at, requires_approval,
                 estimated_labor_cost, estimated_parts_cost)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
            ");
            
            $stmt->execute([
                $job_order_number,
                $this->station_id,
                $customer_id ?: null,
                $data['vehicle_plate'] ?? null,
                $data['vehicle_type'] ?? null,
                $data['service_category_id'],
                $assigned_mechanic_id,
                $this->user['id'],
                $data['service_description'] ?? 'General Service',
                (int)($data['estimated_duration'] ?? 60),
                $initial_status,
                $data['notes'] ?? null,
                $requires_approval ? 1 : 0,
                $estimated_costs['labor'],
                $estimated_costs['parts']
            ]);
            
            $job_id = $this->pdo->lastInsertId();
            
            // Log activity
            log_activity(
                $this->pdo,
                $this->user['id'], 
                'Create Job Order', 
                'Job order created by staff' . ($requires_approval ? ' - Admin approval required' : '')
            );
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Job order created successfully' . ($requires_approval ? '. Awaiting admin approval.' : ''),
                'job_id' => $job_id,
                'job_order_number' => $job_order_number,
                'requires_approval' => $requires_approval
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Manager Review and Approval
     * Manager approves job order and validates service charges
     * ENFORCES: Staff cannot override totals after approval
     */
    public function managerApproveJobOrder($job_id, $action, $remarks = null) {
        try {
            $this->pdo->beginTransaction();
            
            // RBAC: Manager, Admin, or Super Admin
            $role = role_key($this->user['role'] ?? '');
            if (!in_array($role, ['manager', 'admin', 'superadmin'])) {
                throw new Exception('Manager or admin privileges required for job order approval');
            }
            
            $job = $this->getJobOrderDetails($job_id);
            if (!$job) {
                throw new Exception('Job order not found');
            }
            
            if ($job['status'] !== 'Pending') {
                throw new Exception('Job order must be in Pending status to approve');
            }
            
            if (!$this->validateMechanicAvailability($job['assigned_mechanic_id'])) {
                throw new Exception('Assigned mechanic is no longer available');
            }
            
             if ($action === 'approve') {
                 // APPROVAL: Move directly to In Progress (day-to-day manager operation)
                 $stmt = $this->pdo->prepare("
                     UPDATE job_orders
                     SET status = 'In Progress',
                         reviewed_by = ?,
                         reviewed_at = NOW(),
                         started_at = NOW(),
                         staff_editable = 0,
                         manager_remarks = ?
                     WHERE id = ?
                 ");
                 $stmt->execute([$this->user['id'], $remarks, $job_id]);
                 
                 log_activity(
                     $this->pdo,
                     $this->user['id'],
                     'Job Order Approved',
                     sprintf('Job %s approved and started by manager. Total: ₱%.2f', $job['job_order_number'], $job['estimated_labor_cost'] + $job['estimated_parts_cost'])
                 );
                 
                 $message = 'Job order approved and started!';
                
            } elseif ($action === 'reject') {
                // REJECTION: Return to pending
                $stmt = $this->pdo->prepare("
                    UPDATE job_orders
                    SET status = 'Rejected',
                        rejected_by = ?,
                        rejected_at = NOW(),
                        manager_remarks = ?
                    WHERE id = ?
                ");
                $stmt->execute([$this->user['id'], $remarks, $job_id]);
                
                log_activity(
                    $this->pdo,
                    $this->user['id'],
                    'Job Order Rejected',
                    'Rejected reason: ' . ($remarks ?? 'Not specified')
                );
                
                $message = 'Job order rejected and returned to staff.';
            }
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => $message];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Manager Finalize Job Order
     * Manager views approved jobs (no staff can create)
     * Requires manager password for security checkpoint
     */
    public function managerFinalApproval($job_id, $manager_password) {
        try {
            $this->pdo->beginTransaction();
            
            // RBAC: Manager or Super Admin only (Admin is read-only for hierarchy compliance)
            $role = role_key($this->user['role'] ?? '');
            if (!in_array($role, ['manager', 'superadmin'])) {
                throw new Exception('Manager privileges required');
            }
            
            $job = $this->getJobOrderDetails($job_id);
            if (!$job) {
                throw new Exception('Job order not found');
            }
            
            if ($job['status'] !== 'Reviewed') {
                throw new Exception('Job order must be in Reviewed status to finalize');
            }
            
            // SECURITY: Super Admin bypasses password verification
            if ($role === 'superadmin') {
                // Super Admin bypass - no password check needed
            } else {
                // MANAGER: Verify manager password
                $stmt = $this->pdo->prepare("
                    SELECT u.password FROM users u
                    WHERE u.id = ?
                    LIMIT 1
                ");
                $stmt->execute([$this->user['id']]);
                $manager = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$manager || !password_verify($manager_password, $manager['password'])) {
                    throw new Exception('Invalid manager password verification');
                }
            }
            
             // FINALIZE: Move to In Progress and lock all edits
             $stmt = $this->pdo->prepare("
                 UPDATE job_orders
                 SET status = 'In Progress',
                     finalized_by = ?,
                     finalized_at = NOW(),
                     staff_editable = 0,
                     started_at = NOW()
                 WHERE id = ? AND status = 'Reviewed'
             ");
            $stmt->execute([$this->user['id'], $job_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Job order must be manager-approved before admin finalization');
            }
            
            log_activity(
                $this->pdo,
                $this->user['id'],
                'Job Order Finalized',
                sprintf('Job %s finalized by %s. Ready for execution. Manager password verified.', $job['job_order_number'], $role)
            );
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Job order finalized and ready for execution'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Start Job Order (For non-approval required jobs)
     */
    public function startJobOrder($job_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE job_orders
                SET status = 'In Progress',
                    started_at = NOW()
                WHERE id = ? AND status IN ('Pending', 'Reviewed')
            ");
            $stmt->execute([$job_id]);
            
            log_activity($this->pdo, $this->user['id'], 'Job Order Started', 'Job execution started');
            
            return ['success' => true, 'message' => 'Job order started'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Complete Job Order with Inventory Deduction
     * ENFORCES: Auto-deduct parts from inventory
     * ENFORCES: Fail if stock insufficient
     * ENFORCES: Billing total locked (cannot be overridden by staff)
     */
    public function completeJobOrder($job_id, $parts_used = [], $actual_labor_hours = 0) {
        try {
            $this->pdo->beginTransaction();
            
            $job = $this->getJobOrderDetails($job_id);
            if (!$job) {
                throw new Exception('Job order not found');
            }
            
            if ($job['status'] !== 'In Progress') {
                throw new Exception('Job order must be in progress to complete');
            }
            
            // INVENTORY DEDUCTION: Check stock before processing
             foreach ($parts_used as $part) {
                 // Get product type to use correct inventory table
                 $typeStmt = $this->pdo->prepare("SELECT type_id FROM products WHERE id = ?");
                 $typeStmt->execute([$part['product_id']]);
                 $product = $typeStmt->fetch(PDO::FETCH_ASSOC);
                 
                 if (!$product) {
                     throw new Exception('Product not found: ID ' . $part['product_id']);
                 }
                 
                 // Note: Job orders typically use merchandise parts, not fuel
                 // But we check based on product type to be safe
                 if ($product['type_id'] == 1) {
                     // Fuel - check fuel_inventory
                     $stmt = $this->pdo->prepare("
                         SELECT stock_level FROM fuel_inventory
                         WHERE station_id = ? AND product_id = ?
                     ");
                 } else {
                     // Merchandise - check station_inventory
                     $stmt = $this->pdo->prepare("
                         SELECT stock_level FROM station_inventory
                         WHERE station_id = ? AND product_id = ?
                     ");
                 }
                 $stmt->execute([$this->station_id, $part['product_id']]);
                 $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
                 
                 if (!$inventory) {
                     throw new Exception('Product not in inventory: ID ' . $part['product_id']);
                 }
                 
                 if ($inventory['stock_level'] < $part['quantity']) {
                     throw new Exception(
                         sprintf('Insufficient stock. Need %d but only %d available.',
                         $part['quantity'], $inventory['stock_level'])
                     );
                 }
             }
            
            // DEDUCTION: Process all parts (now safe since stock verified)
             $total_parts_cost = 0;
             foreach ($parts_used as $part) {
                 // Get product type to use correct inventory table
                 $typeStmt = $this->pdo->prepare("SELECT type_id FROM products WHERE id = ?");
                 $typeStmt->execute([$part['product_id']]);
                 $product = $typeStmt->fetch(PDO::FETCH_ASSOC);
                 
                 // Auto-deduct from appropriate inventory table based on product type
                 if ($product['type_id'] == 1) {
                     // Fuel - deduct from fuel_inventory
                     $stmt = $this->pdo->prepare("
                         UPDATE fuel_inventory
                         SET stock_level = stock_level - ?
                         WHERE station_id = ? AND product_id = ?
                     ");
                 } else {
                     // Merchandise - deduct from station_inventory
                     $stmt = $this->pdo->prepare("
                         UPDATE station_inventory
                         SET stock_level = stock_level - ?
                         WHERE station_id = ? AND product_id = ?
                     ");
                 }
                 $stmt->execute([$part['quantity'], $this->station_id, $part['product_id']]);
                 
                 // Record parts used
                 $stmt = $this->pdo->prepare("
                     INSERT INTO job_order_parts
                     (job_order_id, product_id, quantity_used, unit_cost, total_cost, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())
                 ");
                 
                 $part_total = $part['quantity'] * $part['unit_cost'];
                 $stmt->execute([
                     $job_id,
                     $part['product_id'],
                     $part['quantity'],
                     $part['unit_cost'],
                     $part_total
                 ]);
                 
                 $total_parts_cost += $part_total;
                 
                 log_activity(
                     $this->pdo,
                     $this->user['id'],
                     'Inventory Deduction',
                     sprintf('Job %s: %d units deducted for product ID %d', $job['job_order_number'], $part['quantity'], $part['product_id'])
                 );
             }
            
            // BILLING: Calculate and lock total (staff cannot override)
            $labor_cost = $this->calculateLaborCost($job, $actual_labor_hours);
            $total_cost = $total_parts_cost + $labor_cost;
            
            // LOCK: Update job order with final billing
            $stmt = $this->pdo->prepare("
                UPDATE job_orders
                SET status = 'Completed',
                    completed_at = NOW(),
                    actual_parts_cost = ?,
                    actual_labor_cost = ?,
                    total_cost = ?,
                    actual_duration = ?,
                    staff_editable = 0,
                    billing_locked = 1
                WHERE id = ?
            ");
            
            $actual_duration = $this->calculateActualDuration($job);
            $stmt->execute([
                $total_parts_cost,
                $labor_cost,
                $total_cost,
                $actual_duration,
                $job_id
            ]);
            
            log_activity(
                $this->pdo,
                $this->user['id'],
                'Job Order Completed',
                sprintf('Total locked: ₱%.2f (Parts: ₱%.2f, Labor: ₱%.2f)', $total_cost, $total_parts_cost, $labor_cost)
            );
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Job order completed. Billing locked.',
                'billing' => [
                    'parts_cost' => $total_parts_cost,
                    'labor_cost' => $labor_cost,
                    'total_cost' => $total_cost
                ]
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
     }
     
     /**
      * Update Job Status (for manager updates)
      */
     public function updateJobStatus($job_id, $status, $notes = '') {
         try {
             $this->pdo->beginTransaction();
             
             $job = $this->getJobOrderDetails($job_id);
             if (!$job) {
                 throw new Exception('Job order not found');
             }
             
             // Validate status is in enum
             $valid_statuses = ['Pending', 'Reviewed', 'In Progress', 'Completed', 'Verified', 'finalized', 'Cancelled', 'Rejected'];
             if (!in_array($status, $valid_statuses)) {
                 throw new Exception('Invalid status: ' . $status);
             }
             
             // Simple status update
             $stmt = $this->pdo->prepare("
                 UPDATE job_orders
                 SET status = ?,
                     updated_at = NOW()
                 WHERE id = ?
             ");
             $stmt->execute([$status, $job_id]);
             
             // Log the status change
             log_activity(
                 $this->pdo,
                 $this->user['id'],
                 'Job Status Updated',
                 sprintf('Job %s status changed to %s. Notes: %s', $job['job_order_number'], $status, $notes ?: 'None')
             );
             
             $this->pdo->commit();
             
             return ['success' => true, 'message' => 'Job status updated successfully'];
             
         } catch (Exception $e) {
             $this->pdo->rollBack();
             return ['success' => false, 'message' => $e->getMessage()];
         }
      }
      
      /**
       * Confirm Parts Used (Record parts without completing job)
       */
      public function confirmPartsUsed($job_id, $parts_used = [], $notes = '') {
          try {
              $this->pdo->beginTransaction();
              
              $job = $this->getJobOrderDetails($job_id);
              if (!$job) {
                  throw new Exception('Job order not found');
              }
              
              // Parts can be added to jobs in progress
              if ($job['status'] !== 'In Progress') {
                  throw new Exception('Parts can only be added to jobs in progress');
              }
              
              // Record all parts
              foreach ($parts_used as $part) {
                  // Insert parts using part_name instead of product_id
                  $stmt = $this->pdo->prepare("
                      INSERT INTO job_order_parts
                      (job_order_id, part_name, quantity_used, unit_cost, total_cost, created_at)
                      VALUES (?, ?, ?, ?, ?, NOW())
                  ");
                  
                  $part_total = $part['quantity'] * $part['unit_cost'];
                  $stmt->execute([
                      $job_id,
                      $part['part_name'],
                      $part['quantity'],
                      $part['unit_cost'],
                      $part_total
                  ]);
                  
                  log_activity(
                      $this->pdo,
                      $this->user['id'],
                      'Parts Added',
                      sprintf('Job %s: %s (Qty: %d)', $job['job_order_number'], $part['part_name'], $part['quantity'])
                  );
              }
              
              // Update job notes if provided
              if ($notes) {
                  $stmt = $this->pdo->prepare("
                      UPDATE job_orders
                      SET notes = CONCAT(IFNULL(notes, ''), '\n', ?)
                      WHERE id = ?
                  ");
                  $stmt->execute([$notes, $job_id]);
              }
              
              $this->pdo->commit();
              
              return [
                  'success' => true,
                  'message' => sprintf('Parts recorded for job #%d', $job_id)
              ];
              
          } catch (Exception $e) {
              $this->pdo->rollBack();
              return ['success' => false, 'message' => $e->getMessage()];
          }
      }
      
      /**
       * Validate Mechanic Availability Based on Duty Roster
       */
    private function validateMechanicAvailability($mechanic_id) {
        // Check if mechanic exists and is active
        $stmt = $this->pdo->prepare("
            SELECT id, status FROM mechanics WHERE id = ?
        ");
        $stmt->execute([$mechanic_id]);
        $mechanic = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mechanic || $mechanic['status'] !== 'active') {
            return false;
        }
        
        // Check current workload (not overloaded)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as active_jobs
            FROM job_orders
            WHERE assigned_mechanic_id = ?
              AND status = 'In Progress'
              AND station_id = ?
        ");
        $stmt->execute([$mechanic_id, $this->station_id]);
        $workload = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Limit: Max 3 active jobs per mechanic
        if ($workload['active_jobs'] >= 3) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Determine if Admin Approval is Required
     * Based on service type and estimated cost
     */
    private function requiresAdminApproval($data) {
        if (empty($data['service_category_id'])) {
            return false;
        }

        // Get service category details
        $stmt = $this->pdo->prepare("
            SELECT default_parts_cost, default_labor_cost
            FROM service_categories
            WHERE id = ?
        ");
        $stmt->execute([$data['service_category_id']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            return false;
        }
        
        $estimated_total = $service['default_parts_cost'] + $service['default_labor_cost'];
        
        // Require approval for high-value jobs (> 5000 PHP)
        if ($estimated_total > 5000) {
            return true;
        }
        
        // Require approval for sensitive service types
        $sensitive_services = ['Engine Tune-up', 'Transmission Service', 'Major Repair'];
        $stmt = $this->pdo->prepare("SELECT name FROM service_categories WHERE id = ?");
        $stmt->execute([$data['service_category_id']]);
        $service_name = $stmt->fetchColumn();
        
        if (in_array($service_name, $sensitive_services)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate Estimated Costs
     */
    private function calculateEstimatedCosts($data) {
        $stmt = $this->pdo->prepare("
            SELECT default_parts_cost, default_labor_cost
            FROM service_categories
            WHERE id = ?
        ");
        $stmt->execute([$data['service_category_id']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            return ['parts' => 0, 'labor' => 0];
        }
        
        return [
            'parts' => $service['default_parts_cost'] ?? 0,
            'labor' => $service['default_labor_cost'] ?? 0
        ];
    }
    
    /**
     * Deduct Inventory
     */
    private function deductInventory($product_id, $quantity) {
        $stmt = $this->pdo->prepare("
            UPDATE station_inventory
            SET stock_level = stock_level - ?
            WHERE station_id = ? AND product_id = ? AND stock_level >= ?
        ");
        $stmt->execute([$quantity, $this->station_id, $product_id, $quantity]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Insufficient inventory for product ID: ' . $product_id);
        }
        
        // Log inventory deduction
        $stmt = $this->pdo->prepare("
            INSERT INTO inventory_transactions
            (station_id, product_id, transaction_type, quantity, notes, created_at)
            VALUES (?, ?, 'deduction', ?, 'Job order parts usage', NOW())
        ");
        $stmt->execute([$this->station_id, $product_id, $quantity]);
    }
    
    /**
     * Calculate Labor Cost
     */
    private function calculateLaborCost($job, $actual_hours) {
        // Get mechanic hourly rate or use default
        $stmt = $this->pdo->prepare("
            SELECT hourly_rate FROM users WHERE id = ?
        ");
        $stmt->execute([$job['assigned_mechanic_id']]);
        $rate = $stmt->fetchColumn() ?: 150; // Default 150 PHP/hour
        
        if ($actual_hours > 0) {
            return $actual_hours * $rate;
        }
        
        // Use estimated duration
        $hours = ($job['estimated_duration'] ?? 60) / 60;
        return $hours * $rate;
    }
    
    /**
     * Calculate Actual Duration
     */
    private function calculateActualDuration($job) {
        if ($job['started_at']) {
            $start = new DateTime($job['started_at']);
            $end = new DateTime();
            $interval = $start->diff($end);
            return ($interval->h * 60) + $interval->i; // Convert to minutes
        }
        return $job['estimated_duration'] ?? 60;
    }
    
    /**
     * Generate Job Order Number
     */
    private function generateJobOrderNumber() {
        $date = date('Y-m-d');
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) + 1 as next_number
            FROM job_orders
            WHERE DATE(created_at) = CURDATE()
              AND station_id = ?
        ");
        $stmt->execute([$this->station_id]);
        $next = $stmt->fetchColumn();
        
        return 'JO-' . $date . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get Job Order Details
     */
    private function getJobOrderDetails($job_id) {
        $stmt = $this->pdo->prepare("
            SELECT jo.*, 
                   c.name as customer_name,
                   m.full_name as mechanic_name,
                   sc.name as service_category_name,
                   u.name as assigned_by_name
            FROM job_orders jo
            LEFT JOIN customers c ON c.id = jo.customer_id
            LEFT JOIN mechanics m ON m.id = jo.assigned_mechanic_id
            LEFT JOIN service_categories sc ON sc.id = jo.service_category_id
            LEFT JOIN users u ON u.id = jo.assigned_by
            WHERE jo.id = ? AND jo.station_id = ?
        ");
        $stmt->execute([$job_id, $this->station_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get Job Orders by Status
     */
    public function getJobOrdersByStatus($status) {
        $stmt = $this->pdo->prepare("
            SELECT jo.*,
                   c.name as customer_name,
                   m.full_name as mechanic_name,
                   sc.name as service_category_name,
                   u.name as assigned_by_name
            FROM job_orders jo
            LEFT JOIN customers c ON c.id = jo.customer_id
            LEFT JOIN mechanics m ON m.id = jo.assigned_mechanic_id
            LEFT JOIN service_categories sc ON sc.id = jo.service_category_id
            LEFT JOIN users u ON u.id = jo.assigned_by
            WHERE jo.station_id = ? AND jo.status = ?
            ORDER BY jo.created_at DESC
        ");
        $stmt->execute([$this->station_id, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Complete Job Order Details with Parts Used
     * Hybrid approach: Product name + inventory details
     */
    public function getJobDetailsWithParts($job_id) {
        // Get basic job order details
        $stmt = $this->pdo->prepare("
            SELECT jo.*,
                   c.name as customer_name,
                   c.phone as customer_phone,
                   c.email as customer_email,
                   m.full_name as mechanic_name,
                   sc.name as service_name,
                   u.name as created_by_name,
                   r.name as reviewed_by_name
            FROM job_orders jo
            LEFT JOIN customers c ON c.id = jo.customer_id
            LEFT JOIN mechanics m ON m.id = jo.assigned_mechanic_id
            LEFT JOIN service_categories sc ON sc.id = jo.service_category_id
            LEFT JOIN users u ON u.id = jo.assigned_by
            LEFT JOIN users r ON r.id = jo.reviewed_by
            WHERE jo.id = ? AND jo.station_id = ?
        ");
        $stmt->execute([$job_id, $this->station_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            return null;
        }

         // Get parts used with hybrid product information
         $stmt = $this->pdo->prepare("
             SELECT jop.*,
                    p.name as product_name,
                    p.type_id,
                    COALESCE(si.stock_level, fi.stock_level, 0) as current_stock
             FROM job_order_parts jop
             LEFT JOIN products p ON p.id = jop.product_id
             LEFT JOIN station_inventory si ON si.station_id = ? AND si.product_id = jop.product_id AND p.type_id = 2
             LEFT JOIN fuel_inventory fi ON fi.station_id = ? AND fi.product_id = jop.product_id AND p.type_id = 1
             WHERE jop.job_order_id = ?
             ORDER BY jop.id ASC
         ");
         $stmt->execute([$this->station_id, $this->station_id, $job_id]);
        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add parts to job array
        $job['parts_used'] = $parts;

        // Calculate totals for breakdown
        $total_parts_cost = 0;
        foreach ($parts as $part) {
            $total_parts_cost += ($part['total_cost'] ?? 0);
        }
        $job['total_parts_cost'] = $total_parts_cost;

        return $job;
    }
}

// Handle API requests if called directly
if (basename($_SERVER['PHP_SELF']) === 'job_order_operations.php') {
    require_login();
    
    $user = current_user();
    $station_id = user_station_id();
    
    $jobOrderOps = new JobOrderOperations($pdo, $user, $station_id);
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_job_order':
                $result = $jobOrderOps->createJobOrder($_POST);
                break;
                
            case 'manager_review_approve':
                $result = $jobOrderOps->managerApproveJobOrder(
                    $_POST['job_id'],
                    'approve',
                    $_POST['remarks'] ?? null
                );
                break;
                
            case 'manager_review_reject':
                $result = $jobOrderOps->managerApproveJobOrder(
                    $_POST['job_id'],
                    'reject',
                    $_POST['remarks'] ?? null
                );
                break;
                
                
            case 'start_job_order':
                $result = $jobOrderOps->startJobOrder($_POST['job_id']);
                break;
                
            case 'complete_job_order':
                $parts_used = json_decode($_POST['parts_used'] ?? '[]', true);
                $result = $jobOrderOps->completeJobOrder(
                    $_POST['job_id'],
                    $parts_used,
                    $_POST['actual_labor_hours'] ?? 0
                );
                break;

            case 'get_job_details':
                $result = $jobOrderOps->getJobDetailsWithParts($_POST['job_id']);
                break;

            default:
                $result = ['success' => false, 'message' => 'Invalid action'];
        }
        
        json_response($result);
        
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()]);
    }
}
