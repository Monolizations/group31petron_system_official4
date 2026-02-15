<?php
$page_id = 'admin_pump_management';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$msg = '';

// Admin/Superadmin only
$role = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));
$isAdmin = in_array($role, ['admin', 'superadmin']);
$isSuper = ($role === 'superadmin');

if (!$isAdmin) {
    header("Location: dashboard.php");
    exit;
}

// Get all stations (superadmin sees all, admin sees only their station)
$selected_station = $_GET['station'] ?? '';
// Default to Kauswagan station (ID 1) for superadmin if no station selected
if ($selected_station === '' && isset($isSuper) && $isSuper) {
     $selected_station = 1;
}
$stations = [];
$pumps = [];
$fuel_types = [];

try {
     // Fetch stations
     if ($isSuper) {
         $stmt = $pdo->query("SELECT id, name FROM stations ORDER BY name");
     } else {
         $station_id = user_station_id();
         $stmt = $pdo->prepare("SELECT id, name FROM stations WHERE id = ?");
         $stmt->execute([$station_id]);
         $selected_station = $station_id;
     }
     $stations = $stmt->fetchAll();
    
    // Fetch fuel types for dropdowns
    $stmt = $pdo->query("SELECT id, name FROM fuel_types ORDER BY name");
    $fuel_types = $stmt->fetchAll();
    
    // Fetch pumps for selected station
    if ($selected_station) {
        $stmt = $pdo->prepare("
            SELECT fp.id, fp.pump_number, fp.status, fp.calibration_value, s.name as station_name
            FROM fuel_pumps fp
            LEFT JOIN stations s ON fp.station_id = s.id
            WHERE fp.station_id = ?
            ORDER BY fp.pump_number
        ");
        $stmt->execute([$selected_station]);
        $pumps = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $msg = "Error loading data: " . $e->getMessage();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $station_id = $_POST['station_id'] ?? $selected_station;
    
    // ADD PUMP (NO LONGER requires fuel_type_id)
    if ($action === 'add_pump') {
        $pump_number = trim($_POST['pump_number'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (!$pump_number) {
            $msg = "❌ Error: Pump number is required.";
        } else {
            try {
                // Check if pump number already exists for this station
                $stmt = $pdo->prepare("SELECT id FROM fuel_pumps WHERE station_id = ? AND pump_number = ?");
                $stmt->execute([$station_id, $pump_number]);
                
                if ($stmt->rowCount() > 0) {
                    $msg = "❌ Error: Pump number already exists for this station.";
                } else {
                    // Insert new pump (without fuel_type_id)
                    $stmt = $pdo->prepare("INSERT INTO fuel_pumps (station_id, pump_number, fuel_type_id, status) VALUES (?, ?, ?, ?)");
                    // Use NULL for fuel_type_id since it's now managed via nozzles
                    $stmt->execute([$station_id, $pump_number, 1, $status]);
                    $pump_id = $pdo->lastInsertId();
                    
                    log_activity($pdo, $me['id'], 'Add Pump', "Created Pump $pump_number at station $station_id", 'fuel_management');
                    $msg = "✅ Pump $pump_number created successfully. Now add nozzles to this pump.";
                }
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    }
    
    // EDIT PUMP (status and optional calibration_value for variance tracking)
    elseif ($action === 'edit_pump') {
        $pump_id = $_POST['pump_id'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $calibration_value = trim($_POST['calibration_value'] ?? '');
        
        if (!$pump_id) {
            $msg = "❌ Error: Pump ID is required.";
        } else {
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
        }
    }
    
    // DELETE PUMP (Superadmin only)
    elseif ($action === 'delete_pump') {
        if (!$isSuper) {
            $msg = "❌ Error: Only superadmin can delete pumps.";
        } else {
            $pump_id = $_POST['pump_id'] ?? '';
            
            if (!$pump_id) {
                $msg = "❌ Error: Pump ID is required.";
            } else {
                try {
                    // Get pump details
                    $stmt = $pdo->prepare("SELECT pump_number FROM fuel_pumps WHERE id = ?");
                    $stmt->execute([$pump_id]);
                    $pump = $stmt->fetch();
                    
                    if (!$pump) {
                        $msg = "❌ Error: Pump not found.";
                    } else {
                        // Delete pump (cascades to nozzles)
                        $stmt = $pdo->prepare("DELETE FROM fuel_pumps WHERE id = ?");
                        $stmt->execute([$pump_id]);
                        
                        log_activity($pdo, $me['id'], 'Delete Pump', "Deleted Pump " . $pump['pump_number'], 'fuel_management');
                        $msg = "✅ Pump " . $pump['pump_number'] . " deleted successfully.";
                    }
                } catch (PDOException $e) {
                    $msg = "❌ Error: " . $e->getMessage();
                }
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
    }
    
    // EDIT NOZZLE
    elseif ($action === 'edit_nozzle') {
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
    
    // Refresh pumps list after action
    if ($selected_station) {
        try {
            $stmt = $pdo->prepare("
                SELECT fp.id, fp.pump_number, fp.status, fp.calibration_value, s.name as station_name
                FROM fuel_pumps fp
                LEFT JOIN stations s ON fp.station_id = s.id
                WHERE fp.station_id = ?
                ORDER BY fp.pump_number
            ");
            $stmt->execute([$selected_station]);
            $pumps = $stmt->fetchAll();
        } catch (Exception $e) {}
    }
}

// Function to get nozzles for a pump
function getNozzlesForPump($pdo, $pump_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.id, n.pump_id, n.nozzle_number, n.fuel_type_id, n.status, n.notes, ft.name as fuel_type_name
            FROM nozzles n
            LEFT JOIN fuel_types ft ON n.fuel_type_id = ft.id
            WHERE n.pump_id = ?
            ORDER BY n.nozzle_number
        ");
        $stmt->execute([$pump_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

require_once __DIR__ . '/../partials/header.php';
?>

<style>
.pump-card { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.pump-table { width: 100%; border-collapse: collapse; }
.pump-table th { background: #667eea; color: white; padding: 12px; text-align: left; }
.pump-table td { padding: 12px; border-bottom: 1px solid #ddd; }
.pump-table tr:hover { background: #f5f5f5; }
.btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; }
.btn-primary { background: #667eea; color: white; }
.btn-primary:hover { background: #5568d3; }
.btn-danger { background: #dc3545; color: white; }
.btn-danger:hover { background: #c82333; }
.btn-success { background: #28a745; color: white; }
.btn-success:hover { background: #218838; }
.btn-warning { background: #ffc107; color: black; }
.btn-warning:hover { background: #e0a800; }
.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #5a6268; }
.btn-sm { padding: 6px 10px; font-size: 12px; }
.status-active { color: #28a745; font-weight: bold; }
.status-inactive { color: #dc3545; font-weight: bold; }
.alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
.form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
.modal.show { display: block; }
.modal-content { background-color: #fefefe; margin: auto; margin-top: 10%; padding: 20px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 8px; }
.modal-header { border-bottom: 1px solid #ddd; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.modal-footer { border-top: 1px solid #ddd; margin-top: 15px; padding-top: 15px; text-align: right; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.close:hover { color: black; }
.nozzle-nested { margin-left: 20px; margin-top: 10px; border-left: 3px solid #667eea; padding-left: 15px; background: #f9f9f9; padding: 10px; border-radius: 4px; }
.nozzle-item { padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
.nozzle-item:last-child { border-bottom: none; }
.nozzle-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 10px; }
.nozzle-label { font-weight: 600; color: #555; }
.nozzle-value { color: #333; }
</style>

<div style="padding: 20px;">
    <h1><i class="fas fa-gas-pump"></i> Pump Management</h1>
    <p>Configure and manage fuel pumps and nozzles for stations</p>
    
    <?php if ($msg): ?>
        <div class="alert <?php echo strpos($msg, '✅') === 0 ? 'alert-success' : 'alert-error'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>
    
    <!-- Station Selection (for Superadmin) -->
    <?php if ($isSuper): ?>
    <div class="pump-card">
        <form method="GET" style="display: flex; gap: 10px;">
            <div style="flex: 1;">
                <label>Select Station:</label>
                <select name="station" onchange="this.form.submit()">
                    <?php foreach ($stations as $st): ?>
                        <option value="<?php echo $st['id']; ?>" <?php echo $selected_station == $st['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($st['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if ($selected_station): ?>
    <!-- Station Name -->
    <div class="pump-card">
        <h3>
            <i class="fas fa-building"></i>
            <?php 
                $station_name = '';
                foreach ($stations as $st) {
                    if ($st['id'] == $selected_station) {
                        $station_name = $st['name'];
                        break;
                    }
                }
                echo htmlspecialchars($station_name);
            ?>
        </h3>
    </div>
    
    <!-- Add Pump Button -->
    <div style="margin-bottom: 20px;">
        <button class="btn btn-primary" onclick="openAddPumpModal()">
            <i class="fas fa-plus"></i> Add New Pump
        </button>
    </div>
    
    <!-- Pumps Table with Nested Nozzles -->
    <div class="pump-card">
        <h3>Pumps & Nozzles</h3>
        <?php if (!empty($pumps)): ?>
            <div style="overflow-x: auto;">
                <?php foreach ($pumps as $pump): 
                    $nozzles = getNozzlesForPump($pdo, $pump['id']);
                ?>
                <div style="margin-bottom: 20px; background: white; padding: 15px; border-radius: 6px; border: 1px solid #ddd;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <strong style="font-size: 16px;">Pump #<?php echo htmlspecialchars($pump['pump_number']); ?></strong>
                            <span class="status-<?php echo strtolower($pump['status']); ?>" style="margin-left: 10px;">
                                <?php echo ucfirst($pump['status']); ?>
                            </span>
                            <?php if ($pump['calibration_value'] !== null): ?>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                Calibration: <strong><?php echo htmlspecialchars($pump['calibration_value']); ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-sm btn-primary" onclick="openEditPumpModal(<?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($pump['pump_number']); ?>', '<?php echo htmlspecialchars($pump['calibration_value'] ?? ''); ?>', '<?php echo $pump['status']; ?>')">
                                <i class="fas fa-edit"></i> Edit Pump
                            </button>
                            <?php if ($isSuper): ?>
                            <button class="btn btn-sm btn-danger" onclick="openDeletePumpModal(<?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($pump['pump_number']); ?>')">
                                <i class="fas fa-trash"></i> Delete Pump
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Nozzles Section -->
                    <div class="nozzle-nested">
                        <div style="margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-wind"></i> Nozzles (<?php echo count($nozzles); ?>/6)
                        </div>
                        
                        <?php if (!empty($nozzles)): ?>
                            <?php foreach ($nozzles as $nozzle): ?>
                            <div class="nozzle-item">
                                <div class="nozzle-info">
                                    <div>
                                        <div class="nozzle-label">Nozzle Number</div>
                                        <div class="nozzle-value"><?php echo htmlspecialchars($nozzle['nozzle_number']); ?></div>
                                    </div>
                                    <div>
                                        <div class="nozzle-label">Fuel Type</div>
                                        <div class="nozzle-value"><?php echo htmlspecialchars($nozzle['fuel_type_name'] ?? 'Unknown'); ?></div>
                                    </div>
                                    <div>
                                        <div class="nozzle-label">Status</div>
                                        <div class="nozzle-value">
                                            <span class="status-<?php echo strtolower($nozzle['status']); ?>">
                                                <?php echo ucfirst($nozzle['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top: 8px;">
                                    <button class="btn btn-sm btn-primary" onclick="openEditNozzleModal(<?php echo $nozzle['id']; ?>, <?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($nozzle['nozzle_number']); ?>', <?php echo $nozzle['fuel_type_id']; ?>, '<?php echo $nozzle['status']; ?>', '<?php echo htmlspecialchars($nozzle['notes'] ?? ''); ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm <?php echo $nozzle['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>" onclick="toggleNozzleStatus(<?php echo $nozzle['id']; ?>, <?php echo $pump['id']; ?>, '<?php echo $nozzle['status']; ?>')">
                                        <i class="fas fa-toggle-<?php echo $nozzle['status'] === 'active' ? 'on' : 'off'; ?>"></i> 
                                        <?php echo $nozzle['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; padding: 10px; color: #999;">No nozzles configured yet</p>
                        <?php endif; ?>
                        
                        <!-- Add Nozzle Button -->
                        <?php if (count($nozzles) < 6): ?>
                        <button class="btn btn-sm btn-primary" style="margin-top: 10px; width: 100%;" onclick="openAddNozzleModal(<?php echo $pump['id']; ?>, '<?php echo htmlspecialchars($pump['pump_number']); ?>')">
                            <i class="fas fa-plus"></i> Add Nozzle
                        </button>
                        <?php else: ?>
                        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; color: #856404; border-radius: 4px; text-align: center;">
                            <strong>Maximum nozzles (6) reached</strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #999;">No pumps configured for this station</p>
        <?php endif; ?>
    </div>
    
    <?php else: ?>
    <div class="pump-card" style="text-align: center; padding: 40px;">
        <i class="fas fa-info-circle" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
        <p>Please select a station to manage its pumps</p>
    </div>
    <?php endif; ?>
</div>

<!-- Add Pump Modal -->
<div id="addPumpModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Pump</h2>
            <span class="close" onclick="closeAddPumpModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_pump">
            <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($selected_station); ?>">
            
            <div class="form-group">
                <label>Pump Number *</label>
                <input type="text" name="pump_number" placeholder="e.g., 1, 2, 3" required>
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <p style="color: #666; font-size: 12px; margin-bottom: 15px;">
                <i class="fas fa-info-circle"></i> After creating the pump, you'll add nozzles and their calibration values.
            </p>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddPumpModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Pump</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Pump Modal -->
<div id="editPumpModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Pump</h2>
            <span class="close" onclick="closeEditPumpModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_pump">
            <input type="hidden" name="pump_id" id="editPumpId">
            <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($selected_station); ?>">
            
            <div class="form-group">
                <label>Pump Number</label>
                <input type="text" id="editPumpNumber" disabled>
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" id="editStatus" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Calibration Value (Optional)</label>
                <input type="number" name="calibration_value" id="editCalibrationValue" step="0.000001" placeholder="e.g., 0.05 for variance tracking">
                <small style="color: #666;">Used for variance tracking in fuel reconciliation. Leave empty if not needed.</small>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditPumpModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Pump</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Pump Modal -->
<div id="deletePumpModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Delete Pump</h2>
            <span class="close" onclick="closeDeletePumpModal()">&times;</span>
        </div>
        <p style="margin-bottom: 20px;">Are you sure you want to delete <strong id="deletePumpName"></strong>?</p>
        <p style="color: #dc3545; font-weight: bold;">This will also delete all nozzles associated with this pump. This action cannot be undone.</p>
        
        <form method="POST">
            <input type="hidden" name="action" value="delete_pump">
            <input type="hidden" name="pump_id" id="deletePumpId">
            <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($selected_station); ?>">
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeletePumpModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete Pump</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Nozzle Modal -->
<div id="addNozzleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Nozzle to Pump <span id="addNozzlePumpNumber"></span></h2>
            <span class="close" onclick="closeAddNozzleModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_nozzle">
            <input type="hidden" name="pump_id" id="addNozzlePumpId">
            <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($selected_station); ?>">
            
            <div class="form-group">
                <label>Nozzle Number *</label>
                <input type="text" name="nozzle_number" placeholder="e.g., 1, 2, 3, A, B, C" required>
            </div>
            
            <div class="form-group">
                <label>Fuel Type *</label>
                <select name="fuel_type_id" required>
                    <option value="">-- Select Fuel Type --</option>
                    <?php foreach ($fuel_types as $ft): ?>
                        <option value="<?php echo $ft['id']; ?>"><?php echo htmlspecialchars($ft['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddNozzleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Nozzle</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Nozzle Modal -->
<div id="editNozzleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Nozzle</h2>
            <span class="close" onclick="closeEditNozzleModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_nozzle">
            <input type="hidden" name="nozzle_id" id="editNozzleId">
            <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($selected_station); ?>">
            
            <div class="form-group">
                <label>Nozzle Number *</label>
                <input type="text" name="nozzle_number" id="editNozzleNumber" required>
            </div>
            
            <div class="form-group">
                <label>Fuel Type *</label>
                <select name="fuel_type_id" id="editNozzleFuelTypeId" required>
                    <option value="">-- Select Fuel Type --</option>
                    <?php foreach ($fuel_types as $ft): ?>
                        <option value="<?php echo $ft['id']; ?>"><?php echo htmlspecialchars($ft['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" id="editNozzleStatus" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" id="editNozzleNotes" placeholder="Optional notes about this nozzle" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 60px;"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditNozzleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Nozzle</button>
            </div>
        </form>
    </div>
</div>

<script>
// Pump Modal Functions
function openAddPumpModal() {
    document.getElementById('addPumpModal').classList.add('show');
}

function closeAddPumpModal() {
    document.getElementById('addPumpModal').classList.remove('show');
}

function openEditPumpModal(pumpId, pumpNumber, calibrationValue, status) {
    document.getElementById('editPumpId').value = pumpId;
    document.getElementById('editPumpNumber').value = pumpNumber;
    document.getElementById('editStatus').value = status;
    document.getElementById('editCalibrationValue').value = calibrationValue || '';
    document.getElementById('editPumpModal').classList.add('show');
}

function closeEditPumpModal() {
    document.getElementById('editPumpModal').classList.remove('show');
}

function openDeletePumpModal(pumpId, pumpNumber) {
    document.getElementById('deletePumpId').value = pumpId;
    document.getElementById('deletePumpName').textContent = 'Pump ' + pumpNumber;
    document.getElementById('deletePumpModal').classList.add('show');
}

function closeDeletePumpModal() {
    document.getElementById('deletePumpModal').classList.remove('show');
}

// Nozzle Modal Functions
function openAddNozzleModal(pumpId, pumpNumber) {
    document.getElementById('addNozzlePumpId').value = pumpId;
    document.getElementById('addNozzlePumpNumber').textContent = '#' + pumpNumber;
    document.getElementById('addNozzleModal').classList.add('show');
}

function closeAddNozzleModal() {
    document.getElementById('addNozzleModal').classList.remove('show');
}

function openEditNozzleModal(nozzleId, pumpId, nozzleNumber, fuelTypeId, status, notes) {
    document.getElementById('editNozzleId').value = nozzleId;
    document.getElementById('editNozzleNumber').value = nozzleNumber;
    document.getElementById('editNozzleFuelTypeId').value = fuelTypeId;
    document.getElementById('editNozzleStatus').value = status;
    document.getElementById('editNozzleNotes').value = notes;
    document.getElementById('editNozzleModal').classList.add('show');
}

function closeEditNozzleModal() {
    document.getElementById('editNozzleModal').classList.remove('show');
}

function toggleNozzleStatus(nozzleId, pumpId, currentStatus) {
    // For now, we'll submit an edit form with just the status toggled
    // A more elegant solution would be AJAX
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    alert('Status toggle submitted. Page will refresh. (This would be better with AJAX)');
    // This would require a separate handler or AJAX implementation
}

// Close modals when clicking outside
window.onclick = function(event) {
    let modals = ['addPumpModal', 'editPumpModal', 'deletePumpModal', 'addNozzleModal', 'editNozzleModal'];
    modals.forEach(function(modalId) {
        let modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
