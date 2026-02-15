<?php
$page_id = 'fuel_staff';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();
$msg = '';

// Determine role and access level
// Staff can record, Manager can verify/approve, Admin/Superadmin can finalize and override
$userRole = $me['role'] ?? '';
$isAdmin = in_array($userRole, ['admin', 'superadmin']);
$isManager = in_array($userRole, ['manager', 'admin', 'superadmin']);
$isStaff = in_array($userRole, ['staff', 'operations_staff', 'admin', 'superadmin']);
$isSuper = $userRole === 'superadmin';

// Superadmin can view any station
if ($isSuper && !$station_id) {
    $stations = [];
    try {
        $stations = $pdo->query("SELECT id, name FROM stations ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}
    $station_id = $_GET['station'] ?? '';
}

// Get current tab
$active_tab = $_GET['tab'] ?? 'pump';

// Ensure tables exist (Auto-fix for missing tables)
try {
    // Create fuel_pumps table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_pumps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        station_id INT NOT NULL,
        pump_number VARCHAR(50) NOT NULL,
        fuel_type_id INT,
        status VARCHAR(20) DEFAULT 'active',
        calibration_value DECIMAL(10, 6)
    )");
    
    // Create fuel_daily_readings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_daily_readings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        station_id INT,
        pump_id INT,
        reading_date DATE,
        shift VARCHAR(50),
        previous_reading DECIMAL(10,2),
        current_reading DECIMAL(10,2),
        calibration DECIMAL(10,2),
        sales_liters DECIMAL(10,2),
        user_id INT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_reading (station_id, pump_id, reading_date, shift)
    )");
    
    // Create fuel_deliveries table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_deliveries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        station_id INT,
        delivery_date DATE,
        fuel_type VARCHAR(50),
        supplier VARCHAR(100),
        invoice_no VARCHAR(50),
        delivery_liters DECIMAL(10,2),
        tanker_number VARCHAR(50),
        received_by INT,
        notes TEXT,
        status VARCHAR(20) DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create fuel_adjustments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_adjustments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        station_id INT,
        adjustment_date DATE,
        fuel_type VARCHAR(50),
        adjustment_type VARCHAR(50),
        liters DECIMAL(10,2),
        reason VARCHAR(255),
        user_id INT,
        notes TEXT,
        status VARCHAR(20) DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Auto-populate pumps if none exist for this station
    $pumpCount = $pdo->query("SELECT COUNT(*) FROM fuel_pumps WHERE station_id = $station_id")->fetchColumn();
    if ($pumpCount == 0) {
        // Get existing fuel types
        $fuelTypes = $pdo->query("SELECT id FROM fuel_types LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($fuelTypes)) {
            foreach (range(1, 3) as $i) {
                $fuel_id = $fuelTypes[$i-1] ?? $fuelTypes[0];
                $pdo->prepare("INSERT IGNORE INTO fuel_pumps (station_id, pump_number, fuel_type_id, status) VALUES (?, ?, ?, 'active')")
                    ->execute([$station_id, "Pump $i", $fuel_id]);
            }
        }
    }
} catch (PDOException $e) {}

// Handle Fuel Management Actions (All Roles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ===== STAFF LEVEL OPERATIONS =====
    
    // STAFF: Record Daily Pump Reading
    if ($action === 'record_pump_reading') {
        if (!$isStaff) {
            $msg = "❌ Error: Only authorized users can record pump readings.";
        } else {
            $fuel_station_id = $_POST['fuel_station_id'] ?? $_POST['pump_id'] ?? '';
            $reading_date = $_POST['reading_date'];
            $shift = $_POST['shift'];
            $previous_reading = (float)$_POST['previous_reading'];
            $current_reading = (float)$_POST['current_reading'];
            $calibration = (float)($_POST['calibration'] ?? 0);
            $notes = $_POST['notes'] ?? '';
            
            // Calculate sales liters
            $sales_liters = $current_reading - $previous_reading - $calibration;
            
            if ($fuel_station_id && $reading_date && $shift) {
                try {
                    // Check if fuel_stations table uses 'fuel_station_id' or 'pump_id'
                    $stmt = $pdo->prepare("INSERT INTO fuel_daily_readings (station_id, fuel_station_id, reading_date, shift, previous_reading, current_reading, calibration, sales_liters, user_id, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt->execute([$station_id, $fuel_station_id, $reading_date, $shift, $previous_reading, $current_reading, $calibration, $sales_liters, $me['id'], $notes]);
                    
                    log_activity($pdo, $me['id'], 'Record Pump Reading', "Recorded reading for pump #$fuel_station_id ($shift shift)", 'fuel_management');
                    $msg = "✅ Pump reading recorded successfully. Sales: " . number_format($sales_liters, 2) . " liters";
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry
                        $msg = "❌ Error: Reading already recorded for this pump, date, and shift.";
                    } else {
                        $msg = "❌ Error: " . $e->getMessage();
                    }
                }
            } else {
                $msg = "❌ Error: Please fill all required fields.";
            }
        }
    
    // STAFF: Record Fuel Delivery
    } elseif ($action === 'record_delivery') {
        if (!$isStaff) {
            $msg = "❌ Error: Only authorized users can record deliveries.";
        } else {
            $delivery_date = $_POST['delivery_date'];
            $fuel_type = $_POST['fuel_type'];
            $supplier = $_POST['supplier'];
            $invoice_no = $_POST['invoice_no'] ?? '';
            $delivery_liters = (float)$_POST['delivery_liters'];
            $tanker_number = $_POST['tanker_number'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if ($delivery_date && $fuel_type && $supplier && $delivery_liters > 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO fuel_deliveries (station_id, delivery_date, fuel_type, supplier, invoice_no, delivery_liters, tanker_number, received_by, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Encoded')");
                    $stmt->execute([$station_id, $delivery_date, $fuel_type, $supplier, $invoice_no, $delivery_liters, $tanker_number, $me['id'], $notes]);
                    
                    log_activity($pdo, $me['id'], 'Record Delivery', "Recorded delivery of " . number_format($delivery_liters, 2) . " liters of $fuel_type", 'fuel_management');
                    $msg = "✅ Fuel delivery recorded successfully.";
                } catch (PDOException $e) {
                    $msg = "❌ Error: " . $e->getMessage();
                }
            } else {
                $msg = "❌ Error: Please fill all required fields.";
            }
        }
    
    // STAFF: Record Adjustment
    } elseif ($action === 'record_adjustment') {
        if (!$isStaff) {
            $msg = "❌ Error: Only authorized users can record adjustments.";
        } else {
            $adjustment_date = $_POST['adjustment_date'];
            $fuel_type = $_POST['fuel_type'];
            $adjustment_type = $_POST['adjustment_type'];
            $liters = (float)$_POST['liters'];
            $reason = $_POST['reason'];
            $notes = $_POST['notes'] ?? '';
            
            if ($adjustment_date && $fuel_type && $adjustment_type && $liters != 0 && $reason) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO fuel_adjustments (station_id, adjustment_date, fuel_type, adjustment_type, liters, reason, user_id, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt->execute([$station_id, $adjustment_date, $fuel_type, $adjustment_type, $liters, $reason, $me['id'], $notes]);
                    
                    $adj_type = ucfirst($adjustment_type);
                    log_activity($pdo, $me['id'], 'Record Adjustment', "$adj_type of " . number_format($liters, 2) . " liters ($fuel_type)", 'fuel_management');
                    $msg = "✅ Adjustment recorded successfully.";
                } catch (PDOException $e) {
                    $msg = "❌ Error: " . $e->getMessage();
                }
            } else {
                $msg = "❌ Error: Please fill all required fields.";
            }
        }
    
    // ===== MANAGER LEVEL OPERATIONS =====
    
    // MANAGER: Verify Pump Reading
    } elseif ($action === 'verify_reading') {
        if (!$isManager) {
            $msg = "❌ Error: Only managers can verify readings.";
        } else {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE fuel_daily_readings SET status = ?, notes = CONCAT(COALESCE(notes,''), '\n[Manager Review] ', ?) WHERE id = ?");
                $stmt->execute([$status, $notes, $id]);
                
                if ($stmt->rowCount() > 0) {
                    log_activity($pdo, $me['id'], 'Verify Reading', "Verified pump reading #$id as $status", 'fuel_management');
                    $msg = "✅ Pump reading #$id has been $status.";
                } else {
                    $msg = "❌ Error: Reading not found.";
                }
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    
    // MANAGER: Verify Delivery
    } elseif ($action === 'verify_delivery') {
        if (!$isManager) {
            $msg = "❌ Error: Only managers can verify deliveries.";
        } else {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE fuel_deliveries SET status = ?, verified_by = ?, notes = CONCAT(COALESCE(notes,''), '\n[Manager Verification] ', ?) WHERE id = ?");
                $stmt->execute([$status, $me['id'], $notes, $id]);
                
                if ($stmt->rowCount() > 0) {
                    log_activity($pdo, $me['id'], 'Verify Delivery', "Verified delivery #$id as $status", 'fuel_management');
                    $msg = "✅ Delivery #$id has been $status.";
                } else {
                    $msg = "❌ Error: Delivery not found.";
                }
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    
    // MANAGER: Approve Adjustment
    } elseif ($action === 'approve_adjustment') {
        if (!$isManager) {
            $msg = "❌ Error: Only managers can approve adjustments.";
        } else {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE fuel_adjustments SET status = ?, approved_by = ?, notes = CONCAT(COALESCE(notes,''), '\n[Manager Approval] ', ?) WHERE id = ?");
                $stmt->execute([$status, $me['id'], $notes, $id]);
                
                if ($stmt->rowCount() > 0) {
                    log_activity($pdo, $me['id'], 'Approve Adjustment', "Approved adjustment #$id as $status", 'fuel_management');
                    $msg = "✅ Adjustment #$id has been $status.";
                } else {
                    $msg = "❌ Error: Adjustment not found.";
                }
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    
    // MANAGER: Run Reconciliation
    } elseif ($action === 'run_reconciliation') {
        if (!$isManager) {
            $msg = "❌ Error: Only managers can run reconciliation.";
        } else {
            $reconciliation_date = $_POST['reconciliation_date'];
            $fuel_type = $_POST['fuel_type'];
            $physical_stock = (float)$_POST['physical_stock'];
            $notes = $_POST['notes'] ?? '';
            
            if ($reconciliation_date && $fuel_type && $physical_stock >= 0) {
                try {
                    // Get opening stock (previous day's closing stock)
                    $prev_day = date('Y-m-d', strtotime($reconciliation_date . ' -1 day'));
                    $stmt = $pdo->prepare("SELECT closing_stock FROM fuel_reconciliation WHERE station_id = ? AND fuel_type = ? AND reconciliation_date = ?");
                    $stmt->execute([$station_id, $fuel_type, $prev_day]);
                    $prev_recon = $stmt->fetch();
                    $opening_stock = $prev_recon['closing_stock'] ?? 0;
                    
                    // Get total deliveries for the day
                    $stmt = $pdo->prepare("SELECT SUM(delivery_liters) as total FROM fuel_deliveries WHERE station_id = ? AND fuel_type = ? AND delivery_date = ? AND status IN ('Verified', 'Finalized')");
                    $stmt->execute([$station_id, $fuel_type, $reconciliation_date]);
                    $deliveries_data = $stmt->fetch();
                    $deliveries = $deliveries_data['total'] ?? 0;
                    
                    // Get total sales for the day
                    $stmt = $pdo->prepare("SELECT SUM(sales_liters) as total FROM fuel_daily_readings WHERE station_id = ? AND EXISTS (SELECT 1 FROM fuel_stations WHERE id = fuel_station_id AND fuel_type = ?) AND reading_date = ? AND status IN ('Verified', 'Finalized')");
                    $stmt->execute([$station_id, $fuel_type, $reconciliation_date]);
                    $sales_data = $stmt->fetch();
                    $sales = $sales_data['total'] ?? 0;
                    
                    // Get total adjustments for the day
                    $stmt = $pdo->prepare("SELECT SUM(CASE WHEN adjustment_type = 'Loss' THEN -liters ELSE liters END) as total FROM fuel_adjustments WHERE station_id = ? AND fuel_type = ? AND adjustment_date = ? AND status = 'Approved'");
                    $stmt->execute([$station_id, $fuel_type, $reconciliation_date]);
                    $adjustments_data = $stmt->fetch();
                    $adjustments = $adjustments_data['total'] ?? 0;
                    
                    // Calculate expected closing stock
                    $closing_stock = $opening_stock + $deliveries - $sales + $adjustments;
                    $variance = $physical_stock - $closing_stock;
                    $variance_percent = $closing_stock > 0 ? ($variance / $closing_stock) * 100 : 0;
                    
                    // Insert reconciliation record
                    $stmt = $pdo->prepare("INSERT INTO fuel_reconciliation (station_id, reconciliation_date, fuel_type, opening_stock, deliveries, sales, adjustments, closing_stock, physical_stock, variance, variance_percent, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt->execute([$station_id, $reconciliation_date, $fuel_type, $opening_stock, $deliveries, $sales, $adjustments, $closing_stock, $physical_stock, $variance, $variance_percent, $notes]);
                    
                    // If variance exceeds threshold, create variance report
                    $variance_threshold = 0.05; // 5%
                    if (abs($variance_percent) > $variance_threshold) {
                        $stmt = $pdo->prepare("INSERT INTO fuel_variance_reports (station_id, report_date, fuel_type, expected_stock, actual_stock, variance_liters, variance_percent, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Open')");
                        $stmt->execute([$station_id, $reconciliation_date, $fuel_type, $closing_stock, $physical_stock, $variance, $variance_percent, "Auto-generated from reconciliation"]);
                    }
                    
                    log_activity($pdo, $me['id'], 'Run Reconciliation', "Reconciliation for $fuel_type on $reconciliation_date", 'fuel_management');
                    $msg = "✅ Reconciliation completed. Variance: " . number_format($variance, 2) . " liters (" . number_format($variance_percent, 2) . "%)";
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry
                        $msg = "❌ Error: Reconciliation already done for this date and fuel type.";
                    } else {
                        $msg = "❌ Error: " . $e->getMessage();
                    }
                }
            } else {
                $msg = "❌ Error: Please fill all required fields.";
            }
        }
    
    // ===== PUMP MANAGEMENT (Admin/Superadmin only) =====
    
    // ADD PUMP
     } elseif ($action === 'add_pump') {
         if (!$isAdmin) {
             $msg = "❌ Error: Only admins can manage pumps.";
         } else {
             $pump_number = trim($_POST['pump_number'] ?? '');
             $status = $_POST['status'] ?? 'active';
             
             if ($pump_number) {
                 try {
                     // Check if pump number already exists for this station
                     $stmt = $pdo->prepare("SELECT id FROM fuel_pumps WHERE station_id = ? AND pump_number = ?");
                     $stmt->execute([$station_id, $pump_number]);
                     if ($stmt->rowCount() > 0) {
                         $msg = "❌ Error: Pump number already exists for this station.";
                     } else {
                         // Insert new pump with fuel_type_id = 1 as placeholder
                         $stmt = $pdo->prepare("INSERT INTO fuel_pumps (station_id, pump_number, fuel_type_id, status) VALUES (?, ?, ?, ?)");
                         $stmt->execute([$station_id, $pump_number, 1, $status]);
                         
                         log_activity($pdo, $me['id'], 'Add Pump', "Created Pump $pump_number at station $station_id", 'fuel_management');
                         $msg = "✅ Pump $pump_number created successfully. Now add nozzles to this pump.";
                     }
                 } catch (PDOException $e) {
                     $msg = "❌ Error: " . $e->getMessage();
                 }
             } else {
                 $msg = "❌ Error: Please fill all required fields.";
             }
         }
    
     // EDIT PUMP
     } elseif ($action === 'edit_pump') {
         if (!$isAdmin) {
             $msg = "❌ Error: Only admins can manage pumps.";
         } else {
             $pump_id = $_POST['pump_id'] ?? '';
             $status = $_POST['status'] ?? 'active';
             $calibration_value = trim($_POST['calibration_value'] ?? '');
             
             if ($pump_id) {
                 try {
                     // Check if pump exists
                     $stmt = $pdo->prepare("SELECT pump_number FROM fuel_pumps WHERE id = ? AND station_id = ?");
                     $stmt->execute([$pump_id, $station_id]);
                     $pump = $stmt->fetch();
                     
                     if (!$pump) {
                         $msg = "❌ Error: Pump not found.";
                     } else {
                         // Validate calibration_value if provided
                         $calibration_to_store = null;
                         if ($calibration_value !== '') {
                             if (!is_numeric($calibration_value)) {
                                 $msg = "❌ Error: Calibration value must be a number.";
                             } else {
                                 $calibration_to_store = $calibration_value;
                             }
                         }
                         
                         if (!isset($msg)) {
                             // Update pump status and optional calibration_value
                             $stmt = $pdo->prepare("UPDATE fuel_pumps SET status = ?, calibration_value = ? WHERE id = ?");
                             $stmt->execute([$status, $calibration_to_store, $pump_id]);
                             
                             $log_msg = "Updated Pump " . $pump['pump_number'] . " - Status: $status";
                             if ($calibration_to_store !== null) {
                                 $log_msg .= ", Calibration: $calibration_to_store";
                             }
                             log_activity($pdo, $me['id'], 'Edit Pump', $log_msg, 'fuel_management');
                             $msg = "✅ Pump " . $pump['pump_number'] . " updated successfully.";
                         }
                     }
                 } catch (PDOException $e) {
                     $msg = "❌ Error: " . $e->getMessage();
                 }
             } else {
                 $msg = "❌ Error: Please fill all required fields.";
             }
         }
    
    // DELETE PUMP (Superadmin only)
    } elseif ($action === 'delete_pump') {
        if (!$isSuper) {
            $msg = "❌ Error: Only superadmin can delete pumps.";
        } else {
            $pump_id = $_POST['pump_id'] ?? '';
            
            if ($pump_id) {
                try {
                    // Get pump details
                    $stmt = $pdo->prepare("SELECT pump_number FROM fuel_pumps WHERE id = ?");
                    $stmt->execute([$pump_id]);
                    $pump = $stmt->fetch();
                    
                    if (!$pump) {
                        $msg = "❌ Error: Pump not found.";
                    } else {
                        // Delete pump
                        $stmt = $pdo->prepare("DELETE FROM fuel_pumps WHERE id = ?");
                        $stmt->execute([$pump_id]);
                        
                        log_activity($pdo, $me['id'], 'Delete Pump', "Deleted Pump " . $pump['pump_number'], 'fuel_management');
                        $msg = "✅ Pump " . $pump['pump_number'] . " deleted successfully.";
                    }
                } catch (PDOException $e) {
                    $msg = "❌ Error: " . $e->getMessage();
                }
            } else {
                  $msg = "❌ Error: Pump ID is required.";
              }
          }
      }
     
     // ADD NOZZLE
     elseif ($action === 'add_nozzle') {
        $pump_id = $_POST['pump_id'] ?? '';
        $nozzle_number = trim($_POST['nozzle_number'] ?? '');
        $fuel_type_id = $_POST['fuel_type_id'] ?? '';
        $status = $_POST['status'] ?? 'active';
        
        if (!$pump_id || !$nozzle_number || !$fuel_type_id) {
            $msg = "❌ Error: Pump, nozzle number, and fuel type are required.";
        } else {
            try {
                // Validate pump exists and belongs to this station
                $stmt = $pdo->prepare("SELECT pump_number FROM fuel_pumps WHERE id = ? AND station_id = ?");
                $stmt->execute([$pump_id, $station_id]);
                $pump = $stmt->fetch();
                
                if (!$pump) {
                    $msg = "❌ Error: Pump not found.";
                } else {
                    // Check max 6 nozzles per pump
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM nozzles WHERE pump_id = ?");
                    $stmt->execute([$pump_id]);
                    $result = $stmt->fetch();
                    
                    if ($result['count'] >= 6) {
                        $msg = "❌ Error: Maximum 6 nozzles per pump. Cannot add more.";
                    } else {
                        // Check for duplicate nozzle number in this pump
                        $stmt = $pdo->prepare("SELECT id FROM nozzles WHERE pump_id = ? AND nozzle_number = ?");
                        $stmt->execute([$pump_id, $nozzle_number]);
                        
                        if ($stmt->rowCount() > 0) {
                            $msg = "❌ Error: Nozzle number already exists for this pump.";
                        } else {
                            // Validate fuel type exists
                            $stmt = $pdo->prepare("SELECT id FROM fuel_types WHERE id = ?");
                            $stmt->execute([$fuel_type_id]);
                            
                            if ($stmt->rowCount() === 0) {
                                $msg = "❌ Error: Invalid fuel type selected.";
                            } else {
                                // Insert nozzle (NO calibration_value)
                                $stmt = $pdo->prepare("INSERT INTO nozzles (pump_id, nozzle_number, fuel_type_id, status) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$pump_id, $nozzle_number, $fuel_type_id, $status]);
                                
                                // Get fuel type name for logging
                                $stmt = $pdo->prepare("SELECT name FROM fuel_types WHERE id = ?");
                                $stmt->execute([$fuel_type_id]);
                                $fuelType = $stmt->fetch();
                                
                                log_activity($pdo, $me['id'], 'Add Nozzle', "Added nozzle $nozzle_number to pump " . $pump['pump_number'] . " - Fuel Type: " . $fuelType['name'], 'fuel_management');
                                $msg = "✅ Nozzle $nozzle_number added successfully.";
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    
    // EDIT NOZZLE
    } elseif ($action === 'edit_nozzle') {
        $nozzle_id = $_POST['nozzle_id'] ?? '';
        $nozzle_number = trim($_POST['nozzle_number'] ?? '');
        $fuel_type_id = $_POST['fuel_type_id'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $notes = trim($_POST['notes'] ?? '');
        
        if (!$nozzle_id || !$nozzle_number || !$fuel_type_id) {
            $msg = "❌ Error: Nozzle ID, number, and fuel type are required.";
        } else {
            try {
                // Get nozzle details
                $stmt = $pdo->prepare("SELECT pump_id FROM nozzles WHERE id = ?");
                $stmt->execute([$nozzle_id]);
                $nozzle = $stmt->fetch();
                
                if (!$nozzle) {
                    $msg = "❌ Error: Nozzle not found.";
                } else {
                    // Check for duplicate nozzle number in same pump (excluding current nozzle)
                    $stmt = $pdo->prepare("SELECT id FROM nozzles WHERE pump_id = ? AND nozzle_number = ? AND id != ?");
                    $stmt->execute([$nozzle['pump_id'], $nozzle_number, $nozzle_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        $msg = "❌ Error: Another nozzle with this number already exists for this pump.";
                    } else {
                        // Validate fuel type exists
                        $stmt = $pdo->prepare("SELECT name FROM fuel_types WHERE id = ?");
                        $stmt->execute([$fuel_type_id]);
                        $fuelType = $stmt->fetch();
                        
                        if (!$fuelType) {
                            $msg = "❌ Error: Invalid fuel type selected.";
                        } else {
                            // Update nozzle (NO calibration_value, NO last_calibrated_date)
                            $stmt = $pdo->prepare("UPDATE nozzles SET nozzle_number = ?, fuel_type_id = ?, status = ?, notes = ? WHERE id = ?");
                            $stmt->execute([$nozzle_number, $fuel_type_id, $status, $notes, $nozzle_id]);
                            
                            log_activity($pdo, $me['id'], 'Edit Nozzle', "Updated nozzle $nozzle_number - Fuel Type: " . $fuelType['name'] . ", Status: $status", 'fuel_management');
                            $msg = "✅ Nozzle $nozzle_number updated successfully.";
                        }
                    }
                }
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Helper function to get nozzles for a pump
function getNozzlesForPump($pdo, $pump_id) {
    try {
        $stmt = $pdo->prepare("SELECT n.id, n.pump_id, n.nozzle_number, n.fuel_type_id, n.status, n.notes, ft.name as fuel_type_name FROM nozzles n LEFT JOIN fuel_types ft ON n.fuel_type_id = ft.id WHERE n.pump_id = ? ORDER BY n.nozzle_number");
        $stmt->execute([$pump_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Fetch data based on user role
$fuel_stations = [];
$daily_readings = [];
$my_readings = [];
$deliveries = [];
$my_deliveries = [];
$adjustments = [];
$my_adjustments = [];
$reconciliations = [];
$variance_reports = [];
$fuel_pumps = [];

if ($station_id) {
    try {
        // Fetch fuel stations/pumps
        $stmt = $pdo->prepare("SELECT * FROM fuel_stations WHERE station_id = ? ORDER BY pump_number");
        $stmt->execute([$station_id]);
        $fuel_stations = $stmt->fetchAll();
        
         // Fetch fuel pumps with fuel type info (for Manage Pumps tab)
         $stmt = $pdo->prepare("SELECT fp.id, fp.station_id, fp.pump_number, fp.fuel_type_id, fp.status, fp.calibration_value, ft.name as fuel_type_name FROM fuel_pumps fp LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id WHERE fp.station_id = ? ORDER BY fp.pump_number");
         $stmt->execute([$station_id]);
         $fuel_pumps = $stmt->fetchAll();
        
        // Fetch daily readings with filters
        $filter_date = $_GET['date'] ?? date('Y-m-d');
        $filter_shift = $_GET['shift'] ?? '';
        $filter_status = $_GET['status'] ?? '';
        
        $sql = "SELECT dr.*, fs.pump_number, fs.fuel_type, u.name as user_name 
                FROM fuel_daily_readings dr 
                LEFT JOIN fuel_stations fs ON dr.fuel_station_id = fs.id 
                LEFT JOIN users u ON dr.user_id = u.id 
                WHERE dr.station_id = ?";
        $params = [$station_id];
        
        if ($filter_date) {
            $sql .= " AND dr.reading_date = ?";
            $params[] = $filter_date;
        }
        if ($filter_shift) {
            $sql .= " AND dr.shift = ?";
            $params[] = $filter_shift;
        }
        if ($filter_status) {
            $sql .= " AND dr.status = ?";
            $params[] = $filter_status;
        }
        $sql .= " ORDER BY dr.reading_date DESC, dr.shift, fs.pump_number";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $daily_readings = $stmt->fetchAll();
        
        // Get my recent readings for staff
        $stmt = $pdo->prepare("SELECT dr.*, fs.pump_number, fs.fuel_type, u.name as user_name FROM fuel_daily_readings dr LEFT JOIN fuel_stations fs ON dr.fuel_station_id = fs.id LEFT JOIN users u ON dr.user_id = u.id WHERE dr.station_id = ? AND dr.user_id = ? ORDER BY dr.reading_date DESC LIMIT 20");
        $stmt->execute([$station_id, $me['id']]);
        $my_readings = $stmt->fetchAll();
        
        // Fetch deliveries
        $stmt = $pdo->prepare("SELECT d.*, u.name as receiver_name, v.name as verifier_name 
                              FROM fuel_deliveries d 
                              LEFT JOIN users u ON d.received_by = u.id 
                              LEFT JOIN users v ON d.verified_by = v.id 
                              WHERE d.station_id = ? 
                              ORDER BY d.delivery_date DESC 
                              LIMIT 50");
        $stmt->execute([$station_id]);
        $deliveries = $stmt->fetchAll();
        
        // Get my recent deliveries for staff
        $stmt = $pdo->prepare("SELECT d.*, u.name as receiver_name, v.name as verifier_name FROM fuel_deliveries d LEFT JOIN users u ON d.received_by = u.id LEFT JOIN users v ON d.verified_by = v.id WHERE d.station_id = ? AND d.received_by = ? ORDER BY d.delivery_date DESC LIMIT 20");
        $stmt->execute([$station_id, $me['id']]);
        $my_deliveries = $stmt->fetchAll();
        
        // Fetch adjustments
        $stmt = $pdo->prepare("SELECT a.*, u.name as user_name, ap.name as approver_name 
                              FROM fuel_adjustments a 
                              LEFT JOIN users u ON a.user_id = u.id 
                              LEFT JOIN users ap ON a.approved_by = ap.id 
                              WHERE a.station_id = ? 
                              ORDER BY a.adjustment_date DESC 
                              LIMIT 50");
        $stmt->execute([$station_id]);
        $adjustments = $stmt->fetchAll();
        
        // Get my recent adjustments for staff
        $stmt = $pdo->prepare("SELECT a.*, u.name as user_name, ap.name as approver_name FROM fuel_adjustments a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN users ap ON a.approved_by = ap.id WHERE a.station_id = ? AND a.user_id = ? ORDER BY a.adjustment_date DESC LIMIT 20");
        $stmt->execute([$station_id, $me['id']]);
        $my_adjustments = $stmt->fetchAll();
        
        // Fetch reconciliations (manager/admin view)
        if ($isManager) {
            $stmt = $pdo->prepare("SELECT r.*, v.name as verifier_name 
                                  FROM fuel_reconciliation r 
                                  LEFT JOIN users v ON r.verified_by = v.id 
                                  WHERE r.station_id = ? 
                                  ORDER BY r.reconciliation_date DESC 
                                  LIMIT 30");
            $stmt->execute([$station_id]);
            $reconciliations = $stmt->fetchAll();
        }
        
        // Fetch variance reports (manager/admin view)
        if ($isManager) {
            $stmt = $pdo->prepare("SELECT vr.*, i.name as investigator_name 
                                  FROM fuel_variance_reports vr 
                                  LEFT JOIN users i ON vr.investigated_by = i.id 
                                  WHERE vr.station_id = ? 
                                  ORDER BY vr.report_date DESC 
                                  LIMIT 20");
            $stmt->execute([$station_id]);
            $variance_reports = $stmt->fetchAll();
        }
        
    } catch (Exception $e) {
        error_log("Fuel Management Error: " . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
?>

<style>
.staff-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.input-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}
.my-entries {
    max-height: 300px;
    overflow-y: auto;
}
.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-verified { background: #d4edda; color: #155724; }
.shift-badge {
    padding: 3px 8px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
}
.shift-morning { background: #e3f2fd; color: #0d47a1; }
.shift-afternoon { background: #fff3e0; color: #e65100; }
.shift-evening { background: #f3e5f5; color: #4a148c; }

/* Tab styling */
.tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
}
.tab {
    padding: 10px 20px;
    border: none;
    background: #f8f9fa;
    color: #6c757d;
    cursor: pointer;
    border-radius: 5px 5px 0 0;
    transition: all 0.3s ease;
}
.tab:hover {
    background: #e9ecef;
    color: #495057;
}
.tab.active {
    background: #003d7a;
    color: white;
}
.panel {
    display: block;
}
.panel.hidden {
    display: none !important;
}
</style>

<div class="page">
  <div class="page-head" style="background: linear-gradient(135deg, #003d7a 0%, #002d5c 100%); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px;">
    <div>
      <h1 style="color: white; margin: 0 0 10px 0; font-size: 32px;"><i class="fas fa-tint"></i> Fuel Management</h1>
      <div style="color: rgba(255,255,255,0.9); font-size: 16px;">
        <?php 
        if ($isStaff && !$isManager) {
            echo "Encode daily readings, deliveries, and adjustments";
        } elseif ($isManager && !$isAdmin) {
            echo "Manage fuel operations: Verify, approve, and reconcile";
        } else {
            echo "Complete fuel inventory management system";
        }
        ?>
      </div>
    </div>
    <div class="actions">
      <?php if($isSuper): ?>
        <form method="get" style="display:inline-flex; align-items:center; gap:10px;">
            <label for="station_filter" class="sub">Viewing Station:</label>
            <select name="station" id="station_filter" onchange="this.form.submit()" class="inp">
                <option value="">-- Select a Station --</option>
                <?php foreach($stations as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $station_id == $id ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
      <?php endif; ?>
      <button class="btn dark" onclick="window.location.href='fuel_reports.php'">
        <i class="fas fa-chart-bar"></i> Reports
      </button>
      <button class="btn" onclick="window.location.href='activity_log.php?module=fuel_management'">
        <i class="fas fa-history"></i> Audit Trail
      </button>
    </div>
  </div>

  <!-- WORKFLOW NAVIGATION SECTION (Manager/Admin only) -->
  <?php if($isManager): ?>
  <div class="card" style="padding: 15px; margin-top: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px;">
    <h3 style="margin: 0 0 15px 0; font-size: 18px;">⚙️ Fuel Workflow Management</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
      
      <!-- Manager: Verify Deliveries -->
      <a href="fuel_delivery_verify.php<?php echo $isSuper ? '?station=' . htmlspecialchars($station_id) : ''; ?>" 
         style="display: block; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 6px; color: white; text-decoration: none; border-left: 4px solid #28a745; transition: all 0.3s;">
        <strong style="font-size: 16px;">🚛 Verify Deliveries</strong><br>
        <small>Review and verify recorded fuel deliveries</small>
        <div style="margin-top: 10px; font-size: 12px; opacity: 0.9;">
          <?php 
            try {
              $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM fuel_deliveries WHERE station_id = ? AND status = 'Encoded'");
              $stmt->execute([$station_id]);
              $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
              echo "<span style='background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 3px;'>" . intval($count) . " pending</span>";
            } catch (Exception $e) {}
          ?>
        </div>
      </a>
      
      <!-- Admin: Finalize Deliveries -->
      <?php if($isAdmin): ?>
      <a href="fuel_delivery_finalize.php<?php echo $isSuper ? '?station=' . htmlspecialchars($station_id) : ''; ?>" 
         style="display: block; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 6px; color: white; text-decoration: none; border-left: 4px solid #007bff; transition: all 0.3s;">
        <strong style="font-size: 16px;">🔒 Finalize Deliveries</strong><br>
        <small>Complete verified deliveries & update stock</small>
        <div style="margin-top: 10px; font-size: 12px; opacity: 0.9;">
          <?php 
            try {
              $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM fuel_deliveries WHERE station_id = ? AND status = 'Verified'");
              $stmt->execute([$station_id]);
              $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
              echo "<span style='background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 3px;'>" . intval($count) . " awaiting</span>";
            } catch (Exception $e) {}
          ?>
        </div>
      </a>
      <?php endif; ?>
      
      <!-- Manager: Shift-End Processing -->
      <a href="fuel_shift_processing.php<?php echo $isSuper ? '?station=' . htmlspecialchars($station_id) : ''; ?>" 
         style="display: block; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 6px; color: white; text-decoration: none; border-left: 4px solid #ffc107; transition: all 0.3s;">
        <strong style="font-size: 16px;">⏱️ Shift-End Processing</strong><br>
        <small>Approve pump readings & deduct sales</small>
        <div style="margin-top: 10px; font-size: 12px; opacity: 0.9;">
          <?php 
            try {
              $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM fuel_daily_readings WHERE station_id = ? AND DATE(reading_date) = CURDATE() AND status = 'Pending'");
              $stmt->execute([$station_id]);
              $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
              echo "<span style='background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 3px;'>" . intval($count) . " readings</span>";
            } catch (Exception $e) {}
          ?>
        </div>
      </a>
      
      <!-- View Audit Trail -->
      <a href="activity_logs.php?page=fuel_management<?php echo $isSuper ? '&station=' . htmlspecialchars($station_id) : ''; ?>" 
         style="display: block; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 6px; color: white; text-decoration: none; border-left: 4px solid #dc3545; transition: all 0.3s;">
        <strong style="font-size: 16px;">📋 Audit Trail</strong><br>
        <small>View complete transaction history</small>
      </a>
      
    </div>
  </div>
  <?php endif; ?>

  <?php if($msg): ?>
    <div class="card" style="padding:10px; margin-top:10px; background:#e6f4ea; color:green;">
      <?php echo $msg; ?>
    </div>
  <?php endif; ?>

  <?php if($isSuper && !$station_id): ?>
    <div class="card" style="padding:20px; text-align:center; margin-top:20px;">
        <h2 class="h2">Please select a station</h2>
        <div class="sub">Select a station from the dropdown above to manage its fuel operations.</div>
    </div>
  
  <?php else: ?>
  
  <!-- Quick Stats -->
  <div class="cards four" style="margin-top:18px">
    <div class="card metric">
      <div class="metric-ico blue"><i class="fas fa-gas-pump"></i></div>
      <div class="metric-meta">
        <div class="metric-label">Active Pumps</div>
        <div class="metric-value"><?php echo count($fuel_stations); ?></div>
        <div class="metric-sub">Total fuel stations</div>
      </div>
    </div>
    <div class="card metric">
      <div class="metric-ico green"><i class="fas fa-check-circle"></i></div>
      <div class="metric-meta">
        <div class="metric-label">Today's Readings</div>
        <div class="metric-value">
          <?php 
          $today_readings = array_filter($daily_readings, function($r) {
              return $r['reading_date'] == date('Y-m-d');
          });
          echo count($today_readings);
          ?>
        </div>
        <div class="metric-sub">For <?php echo date('M d, Y'); ?></div>
      </div>
    </div>
    <div class="card metric">
      <div class="metric-ico purple"><i class="fas fa-truck"></i></div>
      <div class="metric-meta">
        <div class="metric-label">Pending Delivery</div>
        <div class="metric-value">
          <?php 
          $pending_deliveries = array_filter($deliveries, function($d) {
              return in_array($d['status'], ['Encoded', 'Pending']);
          });
          echo count($pending_deliveries);
          ?>
        </div>
        <div class="metric-sub">Awaiting action</div>
      </div>
    </div>
    <div class="card metric">
      <div class="metric-ico amber"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="metric-meta">
        <div class="metric-label">Open Variances</div>
        <div class="metric-value">
          <?php 
          $open_variances = array_filter($variance_reports, function($v) {
              return in_array($v['status'], ['Open', 'Under Investigation']);
          });
          echo count($open_variances);
          ?>
        </div>
        <div class="metric-sub">Requires attention</div>
      </div>
    </div>
  </div>

  <!-- Tabs (role-based visibility) -->
  <div class="tabs" style="margin-top:16px">
    <!-- Staff Tabs -->
    <button class="tab <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'pump' ? 'active' : ''; ?>" data-tab="pump">Pump Readings</button>
    <button class="tab <?php echo $_GET['tab'] === 'delivery' ? 'active' : ''; ?>" data-tab="delivery">Deliveries</button>
    <button class="tab <?php echo $_GET['tab'] === 'adjustment' ? 'active' : ''; ?>" data-tab="adjustment">Adjustments</button>
    <button class="tab <?php echo $_GET['tab'] === 'myentries' ? 'active' : ''; ?>" data-tab="myentries">My Entries</button>
    
    <!-- Manager/Admin Tabs -->
    <?php if($isManager): ?>
    <button class="tab <?php echo $_GET['tab'] === 'operations' ? 'active' : ''; ?>" data-tab="operations">Operations</button>
    <button class="tab <?php echo $_GET['tab'] === 'reconciliation' ? 'active' : ''; ?>" data-tab="reconciliation">Reconciliation</button>
    <button class="tab <?php echo $_GET['tab'] === 'variances' ? 'active' : ''; ?>" data-tab="variances">Variances</button>
    <button class="tab <?php echo $_GET['tab'] === 'history' ? 'active' : ''; ?>" data-tab="history">History</button>
    <?php if($isAdmin): ?>
    <button class="tab <?php echo $_GET['tab'] === 'manage_pumps' ? 'active' : ''; ?>" data-tab="manage_pumps">Manage Pumps</button>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- TAB 1: PUMP READINGS -->
  <section class="panel" id="tab-pump">
    <div class="row">
      <div class="col-md-6">
        <div class="staff-card">
          <h4><i class="fas fa-gas-pump text-blue"></i> Record Daily Pump Reading</h4>
          <div class="muted">Encode present, previous, and calibration readings</div>
          
          <form method="post" class="input-form" style="margin-top:20px;">
            <input type="hidden" name="action" value="record_pump_reading">
            
            <div class="mb-3">
              <label class="form-label">Select Pump *</label>
              <select name="pump_id" class="form-control" required>
                <option value="">-- Choose Pump --</option>
                <?php foreach($fuel_stations as $pump): ?>
                  <option value="<?php echo $pump['id']; ?>">
                    Pump <?php echo htmlspecialchars($pump['pump_number']); ?> - <?php echo htmlspecialchars($pump['fuel_type']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Reading Date *</label>
                  <input type="date" name="reading_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Shift *</label>
                  <select name="shift" id="shift_delivery" class="form-control" required>
                    <option value="">-- Select Shift --</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Previous Reading (L) *</label>
                  <input type="number" step="0.01" name="previous_reading" class="form-control" placeholder="0.00" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Current Reading (L) *</label>
                  <input type="number" step="0.01" name="current_reading" class="form-control" placeholder="0.00" required>
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Calibration Adjustment (L)</label>
              <input type="number" step="0.01" name="calibration" class="form-control" placeholder="0.00">
              <small class="text-muted">Enter positive for addition, negative for deduction</small>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Sales Liters (Auto-calculated)</label>
              <input type="text" id="salesCalc" class="form-control" readonly style="background:#e9ecef;">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Any observations..."></textarea>
            </div>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; margin-top: 30px;">
              <button type="submit" style="
                width: 100%;
                padding: 16px;
                font-size: 18px;
                font-weight: 600;
                background: white;
                color: #667eea;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.3s;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
              " 
              onmouseover="this.style.transform='scale(1.05)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';"
              onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                <i class="fas fa-check-circle"></i> SUBMIT PUMP READING
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="staff-card">
          <h5><i class="fas fa-history"></i> Recent Pump Readings</h5>
          <div class="my-entries">
            <?php if(empty($my_readings)): ?>
              <div class="text-center py-4 text-muted">
                <i class="fas fa-gas-pump fa-2x mb-3"></i><br>
                No pump readings recorded yet
              </div>
            <?php else: ?>
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Pump</th>
                    <th>Sales (L)</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($my_readings as $reading): ?>
                  <tr>
                    <td><?php echo date('m/d', strtotime($reading['reading_date'])); ?></td>
                    <td><?php echo htmlspecialchars($reading['pump_number'] ?? 'N/A'); ?></td>
                    <td><b><?php echo number_format($reading['sales_liters'], 1); ?></b></td>
                    <td>
                      <span class="status-badge status-<?php echo strtolower($reading['status']); ?>">
                        <?php echo $reading['status']; ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TAB 2: FUEL DELIVERIES -->
  <section class="panel hidden" id="tab-delivery">
    <div class="row">
      <div class="col-md-6">
        <div class="staff-card">
          <h4><i class="fas fa-truck text-green"></i> Log Fuel Delivery</h4>
          <div class="muted">Record tanker deliveries from suppliers</div>
          
          <form method="post" class="input-form" style="margin-top:20px;">
            <input type="hidden" name="action" value="record_delivery">
            
            <div class="mb-3">
              <label class="form-label">Delivery Date *</label>
              <input type="date" name="delivery_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Fuel Type *</label>
              <select name="fuel_type" id="fuel_type_delivery" class="form-control" required>
                <option value="">-- Select Fuel Type --</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Supplier *</label>
              <input type="text" name="supplier" class="form-control" placeholder="e.g., Petron, Shell, Caltex" required>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Invoice Number</label>
                  <input type="text" name="invoice_no" class="form-control" placeholder="Optional">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Tanker Number</label>
                  <input type="text" name="tanker_number" class="form-control" placeholder="e.g., ABC-123">
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Delivery Liters *</label>
              <input type="number" step="0.01" name="delivery_liters" class="form-control" placeholder="0.00" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Delivery conditions, quality notes..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-success w-100">
              <i class="fas fa-truck-loading"></i> Log Delivery
            </button>
          </form>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="staff-card">
          <h5><i class="fas fa-history"></i> Recent Deliveries</h5>
          <div class="my-entries">
            <?php if(empty($my_deliveries)): ?>
              <div class="text-center py-4 text-muted">
                <i class="fas fa-truck fa-2x mb-3"></i><br>
                No deliveries logged yet
              </div>
            <?php else: ?>
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Fuel</th>
                    <th>Liters</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($my_deliveries as $delivery): ?>
                  <tr>
                    <td><?php echo date('m/d', strtotime($delivery['delivery_date'])); ?></td>
                    <td><?php echo htmlspecialchars($delivery['fuel_type']); ?></td>
                    <td><b><?php echo number_format($delivery['delivery_liters'], 0); ?> L</b></td>
                    <td>
                      <span class="status-badge status-<?php echo strtolower($delivery['status']); ?>">
                        <?php echo $delivery['status']; ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TAB 3: ADJUSTMENTS -->
  <section class="panel hidden" id="tab-adjustment">
    <div class="row">
      <div class="col-md-6">
        <div class="staff-card">
          <h4><i class="fas fa-exchange-alt text-amber"></i> Record Adjustment</h4>
          <div class="muted">Log losses, transfers, consumption, or other adjustments</div>
          
          <form method="post" class="input-form" style="margin-top:20px;">
            <input type="hidden" name="action" value="record_adjustment">
            
            <div class="mb-3">
              <label class="form-label">Adjustment Date *</label>
              <input type="date" name="adjustment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Fuel Type *</label>
                  <select name="fuel_type" id="fuel_type_adjustment" class="form-control" required>
                    <option value="">-- Select Fuel --</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Adjustment Type *</label>
                  <select name="adjustment_type" id="adjustment_type_fuel" class="form-control" required>
                    <option value="">-- Select Type --</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Liters *</label>
              <input type="number" step="0.01" name="liters" class="form-control" placeholder="0.00" required>
              <small class="text-muted">Use negative (-) for losses, positive (+) for gains</small>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Reason *</label>
              <input type="text" name="reason" class="form-control" placeholder="e.g., Spillage, Equipment test, Temperature correction" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Details/Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Additional information..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-warning w-100">
              <i class="fas fa-edit"></i> Submit Adjustment
            </button>
          </form>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="staff-card">
          <h5><i class="fas fa-history"></i> Recent Adjustments</h5>
          <div class="my-entries">
            <?php if(empty($my_adjustments)): ?>
              <div class="text-center py-4 text-muted">
                <i class="fas fa-exchange-alt fa-2x mb-3"></i><br>
                No adjustments recorded yet
              </div>
            <?php else: ?>
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Liters</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($my_adjustments as $adj): ?>
                  <tr>
                    <td><?php echo date('m/d', strtotime($adj['adjustment_date'])); ?></td>
                    <td><?php echo htmlspecialchars($adj['adjustment_type']); ?></td>
                    <td class="<?php echo $adj['liters'] < 0 ? 'text-danger' : 'text-success'; ?>">
                      <b><?php echo ($adj['liters'] > 0 ? '+' : '') . number_format($adj['liters'], 1); ?> L</b>
                    </td>
                    <td>
                      <span class="status-badge status-<?php echo strtolower($adj['status']); ?>">
                        <?php echo $adj['status']; ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

   <!-- TAB 4: ALL MY ENTRIES -->
   <section class="panel hidden" id="tab-myentries">
    <div class="staff-card">
      <h4><i class="fas fa-clipboard-list"></i> All My Entries</h4>
      <div class="muted">Complete history of all your fuel entries</div>
      
      <div class="row mt-4">
        <div class="col-md-4">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title"><?php echo count($my_readings); ?></h5>
              <p class="card-text">Pump Readings</p>
              <div class="text-muted small">
                Pending: <?php 
                $pending_readings = array_filter($my_readings, function($r) { 
                  return $r['status'] == 'Pending'; 
                }); 
                echo count($pending_readings);
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title"><?php echo count($my_deliveries); ?></h5>
              <p class="card-text">Deliveries</p>
              <div class="text-muted small">
                Pending: <?php 
                $pending_del = array_filter($my_deliveries, function($d) { 
                  return $d['status'] == 'Pending'; 
                }); 
                echo count($pending_del);
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title"><?php echo count($my_adjustments); ?></h5>
              <p class="card-text">Adjustments</p>
              <div class="text-muted small">
                Pending: <?php 
                $pending_adj = array_filter($my_adjustments, function($a) { 
                  return $a['status'] == 'Pending'; 
                }); 
                echo count($pending_adj);
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-4">
        <h6>Activity Timeline</h6>
        <div style="max-height: 400px; overflow-y: auto;">
          <?php
          // Get combined activity
          try {
            $sql = "SELECT 'Reading' as type, reading_date as date, CONCAT('Pump Reading: ', sales_liters, 'L') as details, status FROM fuel_daily_readings WHERE user_id = ? AND station_id = ?
                    UNION ALL
                    SELECT 'Delivery' as type, delivery_date as date, CONCAT('Delivery: ', delivery_liters, 'L ', fuel_type) as details, status FROM fuel_deliveries WHERE received_by = ? AND station_id = ?
                    UNION ALL
                    SELECT 'Adjustment' as type, adjustment_date as date, CONCAT(adjustment_type, ': ', liters, 'L ', fuel_type) as details, status FROM fuel_adjustments WHERE user_id = ? AND station_id = ?
                    ORDER BY date DESC LIMIT 30";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$me['id'], $station_id, $me['id'], $station_id, $me['id'], $station_id]);
            $all_entries = $stmt->fetchAll();
            
            if (empty($all_entries)) {
              echo '<div class="text-center py-4 text-muted">No entries found</div>';
            } else {
              echo '<div class="timeline">';
              foreach($all_entries as $entry) {
                $badge_color = $entry['type'] == 'Reading' ? 'primary' : ($entry['type'] == 'Delivery' ? 'success' : 'warning');
                $status_color = $entry['status'] == 'Pending' ? 'warning' : ($entry['status'] == 'Verified' ? 'success' : 'danger');
                echo '
                <div class="timeline-item mb-3">
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <span class="badge bg-'.$badge_color.'">'.$entry['type'].'</span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <div class="d-flex justify-content-between">
                        <strong>'.$entry['details'].'</strong>
                        <span class="badge bg-'.$status_color.'">'.$entry['status'].'</span>
                      </div>
                      <small class="text-muted">'.date('M d, Y', strtotime($entry['date'])).'</small>
                    </div>
                  </div>
                </div>';
              }
              echo '</div>';
            }
          } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error loading timeline: '.htmlspecialchars($e->getMessage()).'</div>';
          }
          ?>
        </div>
      </div>
    </div>
   </section>

   <!-- TAB 5: OPERATIONS (Manager/Admin only) -->
   <?php if($isManager): ?>
   <section class="panel hidden" id="tab-operations">
    <div class="fuel-card">
      <div class="row">
        <div class="col-md-8">
          <h4>Daily Operations</h4>
          <div class="muted">Verify and approve daily fuel readings, deliveries, and adjustments</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="row" style="margin-top:20px;">
        <div class="col-md-3">
          <label>Date</label>
          <input type="date" id="filterDate" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="applyFilters()">
        </div>
        <div class="col-md-3">
          <label>Shift</label>
          <select id="filterShift" class="form-control" onchange="applyFilters()">
            <option value="">All Shifts</option>
            <option value="Morning">Morning</option>
            <option value="Afternoon">Afternoon</option>
            <option value="Evening">Evening</option>
          </select>
        </div>
        <div class="col-md-3">
          <label>Status</label>
          <select id="filterStatus" class="form-control" onchange="applyFilters()">
            <option value="">All Status</option>
            <option value="Pending" <?php echo $filter_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Verified" <?php echo $filter_status == 'Verified' ? 'selected' : ''; ?>>Verified</option>
            <option value="Finalized" <?php echo $filter_status == 'Finalized' ? 'selected' : ''; ?>>Finalized</option>
          </select>
        </div>
        <div class="col-md-3">
          <label>&nbsp;</label>
          <button class="btn form-control" onclick="resetFilters()">Reset Filters</button>
        </div>
      </div>

      <!-- Readings Table -->
      <h5 style="margin-top: 30px;">Pump Readings</h5>
      <div class="table-wrap" style="margin-top:20px;">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Pump</th>
              <th>Shift</th>
              <th>Previous</th>
              <th>Current</th>
              <th>Sales (L)</th>
              <th>Staff</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($daily_readings as $reading): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($reading['reading_date'])); ?></td>
              <td>
                <b><?php echo htmlspecialchars($reading['pump_number']); ?></b><br>
                <small><?php echo htmlspecialchars($reading['fuel_type']); ?></small>
              </td>
              <td>
                <span class="shift-badge shift-<?php echo strtolower($reading['shift']); ?>">
                  <?php echo $reading['shift']; ?>
                </span>
              </td>
              <td><?php echo number_format($reading['previous_reading'], 2); ?></td>
              <td><?php echo number_format($reading['current_reading'], 2); ?></td>
              <td><b><?php echo number_format($reading['sales_liters'], 2); ?> L</b></td>
              <td><?php echo htmlspecialchars($reading['user_name']); ?></td>
              <td>
                <span class="status-badge status-<?php echo strtolower($reading['status']); ?>">
                  <?php echo $reading['status']; ?>
                </span>
              </td>
              <td>
                <?php if($reading['status'] == 'Pending'): ?>
                  <button class="btn small green" onclick="openVerifyReadingModal(<?php echo $reading['id']; ?>)">
                    <i class="fas fa-check"></i> Verify
                  </button>
                <?php endif; ?>
                <button class="btn small" onclick="viewReadingDetails(<?php echo $reading['id']; ?>)">
                  <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($daily_readings)): ?>
            <tr>
              <td colspan="9" style="text-align:center; padding:30px;">
                <div class="empty">
                  <div class="empty-ico"><i class="fas fa-gas-pump"></i></div>
                  <div class="muted">No pump readings found</div>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Deliveries Table -->
      <h5 style="margin-top: 40px;">Fuel Deliveries</h5>
      <div class="table-wrap" style="margin-top:20px;">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Fuel Type</th>
              <th>Supplier</th>
              <th>Liters</th>
              <th>Tanker</th>
              <th>Received By</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($deliveries as $delivery): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($delivery['delivery_date'])); ?></td>
              <td><b><?php echo htmlspecialchars($delivery['fuel_type']); ?></b></td>
              <td><?php echo htmlspecialchars($delivery['supplier']); ?></td>
              <td><b><?php echo number_format($delivery['delivery_liters'], 2); ?> L</b></td>
              <td><?php echo htmlspecialchars($delivery['tanker_number']); ?></td>
              <td><?php echo htmlspecialchars($delivery['receiver_name']); ?></td>
              <td>
                <span class="status-badge status-<?php echo strtolower($delivery['status']); ?>">
                  <?php echo $delivery['status']; ?>
                </span>
              </td>
              <td>
                <?php if($delivery['status'] == 'Encoded'): ?>
                  <button class="btn small green" onclick="openVerifyDeliveryModal(<?php echo $delivery['id']; ?>)">
                    <i class="fas fa-check"></i> Verify
                  </button>
                <?php endif; ?>
                <button class="btn small" onclick="viewDeliveryDetails(<?php echo $delivery['id']; ?>)">
                  <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Adjustments Table -->
      <h5 style="margin-top: 40px;">Fuel Adjustments</h5>
      <div class="table-wrap" style="margin-top:20px;">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Fuel Type</th>
              <th>Type</th>
              <th>Liters</th>
              <th>Reason</th>
              <th>Staff</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($adjustments as $adj): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($adj['adjustment_date'])); ?></td>
              <td><?php echo htmlspecialchars($adj['fuel_type']); ?></td>
              <td>
                <span class="badge <?php echo $adj['adjustment_type'] == 'Loss' ? 'danger' : 'info'; ?>">
                  <?php echo $adj['adjustment_type']; ?>
                </span>
              </td>
              <td class="<?php echo $adj['adjustment_type'] == 'Loss' ? 'variance-negative' : 'variance-positive'; ?>">
                <?php echo ($adj['adjustment_type'] == 'Loss' ? '-' : '+') . number_format($adj['liters'], 2); ?> L
              </td>
              <td><?php echo htmlspecialchars($adj['reason']); ?></td>
              <td><?php echo htmlspecialchars($adj['user_name']); ?></td>
              <td>
                <span class="status-badge status-<?php echo strtolower($adj['status']); ?>">
                  <?php echo $adj['status']; ?>
                </span>
              </td>
              <td>
                <?php if($adj['status'] == 'Pending'): ?>
                  <button class="btn small green" onclick="openApproveAdjustmentModal(<?php echo $adj['id']; ?>)">
                    <i class="fas fa-check"></i> Approve
                  </button>
                <?php endif; ?>
                <button class="btn small" onclick="viewAdjustmentDetails(<?php echo $adj['id']; ?>)">
                  <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
   </section>

   <!-- TAB 6: RECONCILIATION (Manager/Admin only) -->
   <section class="panel hidden" id="tab-reconciliation">
    <div class="fuel-card">
      <div class="row">
        <div class="col-md-8">
          <h4>Fuel Reconciliation</h4>
          <div class="muted">Daily reconciliation of fuel stock vs sales</div>
        </div>
        <div class="col-md-4 text-end">
          <button class="btn dark" data-bs-toggle="modal" data-bs-target="#modalRunReconciliation">
            <i class="fas fa-calculator"></i> Run Reconciliation
          </button>
        </div>
      </div>
      
      <div class="table-wrap" style="margin-top:20px;">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Fuel Type</th>
              <th>Opening</th>
              <th>Deliveries</th>
              <th>Sales</th>
              <th>Adjustments</th>
              <th>Expected</th>
              <th>Physical</th>
              <th>Variance</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($reconciliations as $recon): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($recon['reconciliation_date'])); ?></td>
              <td><b><?php echo htmlspecialchars($recon['fuel_type']); ?></b></td>
              <td><?php echo number_format($recon['opening_stock'], 2); ?> L</td>
              <td><?php echo number_format($recon['deliveries'], 2); ?> L</td>
              <td><?php echo number_format($recon['sales'], 2); ?> L</td>
              <td><?php echo number_format($recon['adjustments'], 2); ?> L</td>
              <td><b><?php echo number_format($recon['closing_stock'], 2); ?> L</b></td>
              <td><?php echo number_format($recon['physical_stock'], 2); ?> L</td>
              <td>
                <?php if($recon['variance'] != 0): ?>
                  <span class="<?php echo $recon['variance'] > 0 ? 'variance-positive' : 'variance-negative'; ?>">
                    <?php echo ($recon['variance'] > 0 ? '+' : '') . number_format($recon['variance'], 2); ?> L
                    <br>
                    <small>(<?php echo ($recon['variance_percent'] > 0 ? '+' : '') . number_format($recon['variance_percent'], 2); ?>%)</small>
                  </span>
                <?php else: ?>
                  <span class="text-muted">0.00 L</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="status-badge status-<?php echo strtolower($recon['status']); ?>">
                  <?php echo $recon['status']; ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
   </section>

   <!-- TAB 7: VARIANCE REPORTS (Manager/Admin only) -->
   <section class="panel hidden" id="tab-variances">
    <div class="fuel-card">
      <h4>Variance Reports</h4>
      <div class="muted">Fuel stock discrepancies requiring investigation</div>
      
      <div class="table-wrap" style="margin-top:20px;">
        <table class="table">
          <thead>
            <tr>
              <th>Report Date</th>
              <th>Fuel Type</th>
              <th>Expected</th>
              <th>Actual</th>
              <th>Variance</th>
              <th>Status</th>
              <th>Investigated By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($variance_reports as $report): ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
              <td><b><?php echo htmlspecialchars($report['fuel_type']); ?></b></td>
              <td><?php echo number_format($report['expected_stock'], 2); ?> L</td>
              <td><?php echo number_format($report['actual_stock'], 2); ?> L</td>
              <td>
                <span class="<?php echo $report['variance_liters'] > 0 ? 'variance-positive' : 'variance-negative'; ?>">
                  <?php echo ($report['variance_liters'] > 0 ? '+' : '') . number_format($report['variance_liters'], 2); ?> L
                  <br>
                  <small>(<?php echo ($report['variance_percent'] > 0 ? '+' : '') . number_format($report['variance_percent'], 2); ?>%)</small>
                </span>
              </td>
              <td>
                <span class="badge <?php 
                  echo $report['status'] == 'Open' ? 'danger' : 
                         ($report['status'] == 'Under Investigation' ? 'warning' : 
                         ($report['status'] == 'Resolved' ? 'success' : 'secondary')); ?>">
                  <?php echo $report['status']; ?>
                </span>
              </td>
              <td><?php echo $report['investigator_name'] ? htmlspecialchars($report['investigator_name']) : '—'; ?></td>
              <td>
                <button class="btn small" onclick="viewVarianceDetails(<?php echo $report['id']; ?>)">
                  <i class="fas fa-eye"></i> View
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
   </section>

   <!-- TAB 8: SHIFT HISTORY (Manager/Admin only) -->
   <section class="panel hidden" id="tab-history">
    <div class="fuel-card">
      <h4>Shift History</h4>
      <div class="muted">Complete audit trail of all shift entries</div>
      
      <div class="row" style="margin-top:20px;">
        <div class="col-md-12">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Date & Time</th>
                  <th>Staff</th>
                  <th>Action</th>
                  <th>Details</th>
                  <th>Shift</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Fetch activity logs for fuel management
                try {
                  $sql = "SELECT al.*, u.name as user_name 
                          FROM activity_logs al 
                          LEFT JOIN users u ON al.user_id = u.id 
                          WHERE al.action LIKE '%fuel%' AND al.module = 'fuel_management'
                          ORDER BY al.created_at DESC 
                          LIMIT 100";
                  $stmt = $pdo->prepare($sql);
                  $stmt->execute();
                  $activity_logs = $stmt->fetchAll();
                  
                  if (!empty($activity_logs)) {
                    foreach($activity_logs as $log) {
                ?>
                <tr>
                  <td>
                    <?php echo date('M d, Y', strtotime($log['created_at'])); ?><br>
                    <small><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                  </td>
                  <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                  <td>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($log['action']); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($log['details']); ?></td>
                  <td>
                    <?php
                    // Extract shift from details if available
                    $shift = 'N/A';
                    if (preg_match('/\((Morning|Afternoon|Evening) shift\)/i', $log['details'], $matches)) {
                        $shift = $matches[1];
                    }
                    echo $shift;
                    ?>
                  </td>
                  <td>
                    <span class="badge bg-success">Logged</span>
                  </td>
                </tr>
                <?php
                    }
                  } else {
                ?>
                <tr>
                  <td colspan="6" style="text-align:center; padding:30px;">
                    <div class="empty">
                      <div class="empty-ico"><i class="fas fa-history"></i></div>
                      <div class="muted">No activity logs found</div>
                    </div>
                  </td>
                </tr>
                <?php
                  }
                } catch (Exception $e) {
                ?>
                <tr>
                  <td colspan="6" style="text-align:center; padding:30px; color:#dc3545;">
                    Error loading activity logs: <?php echo htmlspecialchars($e->getMessage()); ?>
                  </td>
                </tr>
                              <?php
                }
                ?>
               </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
   </section>

   <!-- TAB 9: MANAGE PUMPS (Admin/Superadmin only) -->
   <section class="panel hidden" id="tab-manage_pumps">
    <div class="fuel-card">
      <div class="row">
        <div class="col-md-8">
          <h4><i class="fas fa-sliders-h"></i> Manage Fuel Pumps</h4>
          <div class="muted">Create, edit, or remove fuel pumps for this station</div>
        </div>
        <div class="col-md-4 text-end">
          <button class="btn dark" onclick="openAddPumpModal()">
            <i class="fas fa-plus"></i> Add New Pump
          </button>
        </div>
      </div>
      
       <div style="margin-top:20px;">
         <?php if(!empty($fuel_pumps)): ?>
           <?php foreach($fuel_pumps as $pump): ?>
           <div class="fuel-card" style="margin-bottom:20px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px;">
             <!-- Pump Header -->
             <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
               <div>
                 <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Pump Number</div>
                 <div style="font-size: 20px; font-weight: 700; color: #333; margin-bottom: 10px;">
                   <?php echo htmlspecialchars($pump['pump_number']); ?>
                 </div>
                 <?php if ($pump['calibration_value'] !== null): ?>
                 <div style="font-size: 13px; color: #666;">
                   <strong>Calibration:</strong> <?php echo htmlspecialchars($pump['calibration_value']); ?>
                 </div>
                 <?php endif; ?>
               </div>
               <div style="text-align: right;">
                 <div style="margin-bottom: 10px;">
                   <span class="status-badge status-<?php echo strtolower($pump['status']); ?>">
                     <?php echo ucfirst($pump['status']); ?>
                   </span>
                 </div>
                 <div>
                   <button class="btn small" onclick="openEditPumpModal(<?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($pump['pump_number']); ?>', '<?php echo htmlspecialchars($pump['calibration_value'] ?? ''); ?>', '<?php echo $pump['status']; ?>')">
                     <i class="fas fa-edit"></i> Edit
                   </button>
                   <?php if($isSuper): ?>
                   <button class="btn small red" onclick="openDeletePumpModal(<?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($pump['pump_number']); ?>')">
                     <i class="fas fa-trash"></i> Delete
                   </button>
                   <?php endif; ?>
                 </div>
               </div>
             </div>

             <!-- Nozzles Section -->
             <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
               <div style="margin-bottom: 15px; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                 <div><i class="fas fa-wind"></i> Nozzles</div>
                 <button class="btn small dark" onclick="openAddNozzleModal(<?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($pump['pump_number']); ?>')">
                   <i class="fas fa-plus"></i> Add Nozzle
                 </button>
               </div>
               
               <?php 
               $nozzles = getNozzlesForPump($pdo, $pump['id']);
               if (!empty($nozzles)): 
               ?>
                 <div style="display: grid; gap: 10px;">
                   <?php foreach ($nozzles as $nozzle): ?>
                   <div style="background: white; padding: 12px; border-radius: 4px; border-left: 3px solid #667eea; display: flex; justify-content: space-between; align-items: center;">
                     <div>
                       <div style="font-weight: 600; margin-bottom: 4px;">
                         Nozzle <?php echo htmlspecialchars($nozzle['nozzle_number']); ?>
                       </div>
                       <div style="font-size: 13px; color: #666;">
                         <?php echo htmlspecialchars($nozzle['fuel_type_name'] ?? 'Unknown'); ?> • 
                         <span class="status-badge status-<?php echo strtolower($nozzle['status']); ?>" style="font-size: 12px;">
                           <?php echo ucfirst($nozzle['status']); ?>
                         </span>
                       </div>
                     </div>
                     <div>
                       <button class="btn small" onclick="openEditNozzleModal(<?php echo $nozzle['id']; ?>, <?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($nozzle['nozzle_number']); ?>', <?php echo $nozzle['fuel_type_id']; ?>, '<?php echo $nozzle['status']; ?>', '<?php echo htmlspecialchars($nozzle['notes'] ?? ''); ?>')">
                         <i class="fas fa-edit"></i> Edit
                       </button>
                     </div>
                   </div>
                   <?php endforeach; ?>
                 </div>
               <?php else: ?>
                 <div style="text-align: center; padding: 20px; color: #999;">
                   <i class="fas fa-inbox" style="font-size: 20px; margin-bottom: 10px; display: block;"></i>
                   No nozzles added yet
                 </div>
               <?php endif; ?>
             </div>
           </div>
           <?php endforeach; ?>
         <?php else: ?>
         <div style="text-align: center; padding: 60px 20px;">
           <div style="font-size: 40px; color: #ddd; margin-bottom: 15px;">
             <i class="fas fa-gas-pump"></i>
           </div>
           <div style="color: #999; font-size: 16px;">
             No pumps configured for this station
           </div>
         </div>
         <?php endif; ?>
       </div>
    </div>
   </section>
   <?php endif; ?>

</div> <!-- End main page div -->

<?php endif; ?>

<!-- MODALS (Bootstrap) -->
<!-- Modal: Record Pump Reading -->
<div class="modal fade" id="modalRecordReading" tabindex="-1"></div>

<!-- Modal: Record Delivery -->
<div class="modal fade" id="modalRecordDelivery" tabindex="-1"></div>

<!-- Modal: Record Adjustment -->
<div class="modal fade" id="modalRecordAdjustment" tabindex="-1"></div>

<!-- Modal: Run Reconciliation -->
<div class="modal fade" id="modalRunReconciliation" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Run Reconciliation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="run_reconciliation">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Reconciliation Date *</label>
            <input type="date" name="reconciliation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Fuel Type *</label>
            <select name="fuel_type" class="form-control" required>
              <option value="">-- Select Fuel --</option>
              <option value="Diesel">Diesel</option>
              <option value="Gasoline">Gasoline</option>
              <option value="Premium">Premium</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Physical Stock (L) *</label>
            <input type="number" step="0.01" name="physical_stock" class="form-control" placeholder="0.00" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Run Reconciliation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Verification/Approval Modals -->
<div class="modal fade" id="modalVerifyReading" tabindex="-1"></div>
<div class="modal fade" id="modalVerifyDelivery" tabindex="-1"></div>
<div class="modal fade" id="modalApproveAdjustment" tabindex="-1"></div>
<div class="modal fade" id="modalInvestigateVariance" tabindex="-1"></div>

<!-- Pump Management Modals -->
<!-- Modal: Add Pump -->
<div class="modal fade" id="modalAddPump" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Pump</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="add_pump">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pump Number *</label>
            <input type="text" name="pump_number" class="form-control" placeholder="e.g., 1, 2, 3" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Status *</label>
            <div>
              <label class="form-check">
                <input type="radio" name="status" value="active" class="form-check-input" checked> Active
              </label>
              <label class="form-check">
                <input type="radio" name="status" value="inactive" class="form-check-input"> Inactive
              </label>
            </div>
          </div>
          <p style="color: #666; font-size: 12px;">
            <i class="fas fa-info-circle"></i> After creating the pump, you'll add nozzles to this pump.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Pump</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Pump -->
<div class="modal fade" id="modalEditPump" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Pump</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="edit_pump">
        <input type="hidden" name="pump_id" id="editPumpId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pump Number</label>
            <input type="text" id="editPumpNumber" class="form-control" disabled>
          </div>
          <div class="mb-3">
            <label class="form-label">Status *</label>
            <div>
              <label class="form-check">
                <input type="radio" name="status" value="active" class="form-check-input"> Active
              </label>
              <label class="form-check">
                <input type="radio" name="status" value="inactive" class="form-check-input"> Inactive
              </label>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Calibration Value (Optional)</label>
            <input type="number" name="calibration_value" id="editCalibrationValue" class="form-control" step="0.000001" placeholder="e.g., 0.05 for variance tracking">
            <small style="color: #666;">Used for variance tracking in fuel reconciliation. Leave empty if not needed.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Pump</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Delete Pump (Confirmation) -->
<div class="modal fade" id="modalDeletePump" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Pump</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="delete_pump">
        <input type="hidden" name="pump_id" id="deletePumpId">
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Are you sure you want to delete <strong id="deletePumpName"></strong>?
          </div>
          <p>This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
           <button type="submit" class="btn btn-danger">Delete Pump</button>
         </div>
       </form>
     </div>
   </div>
 </div>

<!-- Modal: Add Nozzle -->
<div class="modal fade" id="modalAddNozzle" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Nozzle to Pump <span id="addNozzlePumpNumber"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="add_nozzle">
        <input type="hidden" name="pump_id" id="addNozzlePumpId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nozzle Number *</label>
            <input type="text" name="nozzle_number" class="form-control" placeholder="e.g., 1, 2, 3, A, B, C" required>
          </div>
           <div class="mb-3">
             <label class="form-label">Fuel Type *</label>
             <select name="fuel_type_id" id="addNozzleFuelTypeId" class="form-control" required>
               <option value="">-- Select Fuel Type --</option>
             </select>
           </div>
          <div class="mb-3">
            <label class="form-label">Status *</label>
            <div>
              <label class="form-check">
                <input type="radio" name="status" value="active" class="form-check-input" checked> Active
              </label>
              <label class="form-check">
                <input type="radio" name="status" value="inactive" class="form-check-input"> Inactive
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Nozzle</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Nozzle -->
<div class="modal fade" id="modalEditNozzle" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Nozzle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="edit_nozzle">
        <input type="hidden" name="nozzle_id" id="editNozzleId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nozzle Number *</label>
            <input type="text" name="nozzle_number" id="editNozzleNumber" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Fuel Type *</label>
            <select name="fuel_type_id" id="editNozzleFuelTypeId" class="form-control" required>
              <option value="">-- Select Fuel Type --</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Status *</label>
            <div>
              <label class="form-check">
                <input type="radio" name="status" value="active" class="form-check-input"> Active
              </label>
              <label class="form-check">
                <input type="radio" name="status" value="inactive" class="form-check-input"> Inactive
              </label>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="editNozzleNotes" class="form-control" placeholder="Optional notes about this nozzle" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Nozzle</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
.variance-positive { color: #28a745; font-weight: bold; }
.variance-negative { color: #dc3545; font-weight: bold; }
</style>

<script src="../assets/js/data_helper.js"></script>

<script>
// Tab switching - more robust version
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing tabs...');

     // Load fuel types dynamically from backend
     DataHelper.populateFuelTypes('fuel_type_delivery', '-- Select Fuel Type --')
         .then(() => DataHelper.populateFuelTypes('fuel_type_adjustment', '-- Select Fuel --'))
         .then(() => DataHelper.populateFuelTypes('addNozzleFuelTypeId', '-- Select Fuel Type --'))
         .then(() => DataHelper.populateFuelTypes('editNozzleFuelTypeId', '-- Select Fuel Type --'))
         .then(() => DataHelper.populateShifts('shift_delivery', '-- Select Shift --'))
         .then(() => DataHelper.populateAdjustmentTypes('adjustment_type_fuel', '-- Select Type --'))
         .then(() => console.log('Fuel types, shifts, and adjustment types loaded'))
         .catch(error => {
             console.error('Failed to load fuel types/shifts/adjustment types:', error);
             DataHelper.showError('Failed to load fuel types/shifts/adjustment types. Please refresh.');
         });

    // Show correct tab based on URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'pump';
    console.log('Active tab from URL:', activeTab);
    
    // Hide all panels and remove active from tabs
    document.querySelectorAll('.panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
    
    // Show the correct tab
    const targetTab = document.querySelector(`[data-tab="${activeTab}"]`);
    const targetPanel = document.getElementById(`tab-${activeTab}`);
    
    console.log('Target tab:', targetTab);
    console.log('Target panel:', targetPanel);
    
    if (targetTab && targetPanel) {
        targetTab.classList.add('active');
        targetPanel.classList.remove('hidden');
        console.log('Tab activated successfully');
    }
    
    // Tab click handlers
    const tabButtons = document.querySelectorAll('.tab');
    console.log('Found tab buttons:', tabButtons.length);
    
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Tab clicked:', btn.dataset.tab);
            
            // Remove active from all tabs
            document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
            // Hide all panels
            document.querySelectorAll('.panel').forEach(p => p.classList.add('hidden'));
            
            // Activate clicked tab and show its panel
            btn.classList.add('active');
            const tabName = btn.dataset.tab;
            const panel = document.getElementById(`tab-${tabName}`);
            
            if (panel) {
                panel.classList.remove('hidden');
                console.log('Panel shown:', tabName);
            }
        });
        
        // Add hover effect
        btn.addEventListener('mouseenter', function() {
            btn.style.transform = 'translateY(-2px)';
        });
        
        btn.addEventListener('mouseleave', function() {
            btn.style.transform = 'translateY(0)';
        });
    });
    
    // Auto-calculate sales liters
    function calculateSales() {
        const prev = parseFloat(document.querySelector('input[name="previous_reading"]')?.value) || 0;
        const curr = parseFloat(document.querySelector('input[name="current_reading"]')?.value) || 0;
        const calib = parseFloat(document.querySelector('input[name="calibration"]')?.value) || 0;
        const sales = curr - prev - calib;
        if (document.getElementById('salesCalc')) {
            document.getElementById('salesCalc').value = sales.toFixed(2) + ' liters';
        }
    }
    
    // Add event listeners for sales calculation
    const inputs = ['previous_reading', 'current_reading', 'calibration'];
    inputs.forEach(name => {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input) {
            input.addEventListener('input', calculateSales);
        }
    });
    
    // Initial calculation
    calculateSales();
});

// Filter Functions
function applyFilters() {
    const date = document.getElementById('filterDate')?.value;
    const shift = document.getElementById('filterShift')?.value;
    const status = document.getElementById('filterStatus')?.value;
    
    let url = 'fuel_staff.php?tab=operations';
    if (date) url += '&date=' + encodeURIComponent(date);
    if (shift) url += '&shift=' + encodeURIComponent(shift);
    if (status) url += '&status=' + encodeURIComponent(status);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = 'fuel_staff.php?tab=operations';
}

// Modal functions (placeholder implementations)
function openVerifyReadingModal(id) {
    alert('Verify reading modal for ID ' + id);
}

function openVerifyDeliveryModal(id) {
    alert('Verify delivery modal for ID ' + id);
}

function openApproveAdjustmentModal(id) {
    alert('Approve adjustment modal for ID ' + id);
}

function viewReadingDetails(id) {
    alert('View reading details for ID ' + id);
}

function viewDeliveryDetails(id) {
    alert('View delivery details for ID ' + id);
}

function viewAdjustmentDetails(id) {
    alert('View adjustment details for ID ' + id);
}

function viewVarianceDetails(id) {
    alert('View variance details for ID ' + id);
}

// Pump Management Functions
function openAddPumpModal() {
    // Clear the form
    document.querySelector('#modalAddPump form').reset();
    // Open modal
    const modal = new bootstrap.Modal(document.getElementById('modalAddPump'));
    modal.show();
}

function openEditPumpModal(pumpId, pumpNumber, calibrationValue, status) {
    // Populate form
    document.getElementById('editPumpId').value = pumpId;
    document.getElementById('editPumpNumber').value = pumpNumber;
    document.getElementById('editCalibrationValue').value = calibrationValue || '';
    document.querySelector('input[name="status"][value="' + status + '"]').checked = true;
    // Open modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditPump'));
    modal.show();
}

function openDeletePumpModal(pumpId, pumpNumber) {
     // Populate form
     document.getElementById('deletePumpId').value = pumpId;
     document.getElementById('deletePumpName').textContent = 'Pump ' + pumpNumber;
     // Open modal
     const modal = new bootstrap.Modal(document.getElementById('modalDeletePump'));
     modal.show();
 }

 // Nozzle Management Functions
 function openAddNozzleModal(pumpId, pumpNumber) {
     // Clear the form
     document.querySelector('#modalAddNozzle form').reset();
     // Set pump_id and update title
     document.getElementById('addNozzlePumpId').value = pumpId;
     document.getElementById('addNozzlePumpNumber').textContent = pumpNumber;
     // Load fuel types for nozzle modal if not already loaded
     loadFuelTypesForNozzle();
     // Open modal
     const modal = new bootstrap.Modal(document.getElementById('modalAddNozzle'));
     modal.show();
 }

 function openEditNozzleModal(nozzleId, pumpId, nozzleNumber, fuelTypeId, status, notes) {
     // Clear the form first
     document.querySelector('#modalEditNozzle form').reset();
     // Populate form
     document.getElementById('editNozzleId').value = nozzleId;
     document.getElementById('editNozzlePumpId').value = pumpId;
     document.getElementById('editNozzleNumber').value = nozzleNumber;
     document.querySelector('input[name="status"][value="' + status + '"]', document.querySelector('#modalEditNozzle')).checked = true;
     if (document.getElementById('editNozzleNotes')) {
         document.getElementById('editNozzleNotes').value = notes || '';
     }
     // Load fuel types and set selected value
     loadFuelTypesForNozzle('editNozzleFuelTypeId', fuelTypeId);
     // Open modal
     const modal = new bootstrap.Modal(document.getElementById('modalEditNozzle'));
     modal.show();
 }

 function loadFuelTypesForNozzle(selectId, selectedId) {
     const targetSelect = selectId ? document.getElementById(selectId) : document.querySelector('#modalAddNozzle select[name="fuel_type_id"]');
     if (!targetSelect) return;
     
     // If fuel types already loaded, just set selected value and return
     if (targetSelect.children.length > 1) {
         if (selectedId) {
             targetSelect.value = selectedId;
         }
         return;
     }
     
     // Load fuel types from DataHelper
     if (typeof DataHelper !== 'undefined' && DataHelper.populateFuelTypes) {
         DataHelper.populateFuelTypes(targetSelect.id || targetSelect.name, '-- Select Fuel Type --').then(() => {
             if (selectedId) {
                 targetSelect.value = selectedId;
             }
         });
     }
 }
 
 // Close all modals on escape key
 document.addEventListener('keydown', function(event) {
     if (event.key === 'Escape') {
         const modals = ['modalAddPump', 'modalEditPump', 'modalDeletePump', 'modalAddNozzle', 'modalEditNozzle'];
         modals.forEach(id => {
             const modal = bootstrap.Modal.getInstance(document.getElementById(id));
             if (modal) modal.hide();
         });
     }
 });
</script>