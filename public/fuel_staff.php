<?php
$page_id = 'fuel_staff';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

// Check if user is staff
$me = current_user();
$role = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));
if (!in_array($role, ['staff'])) {
    header('Location: fuel_management.php');
    exit();
}

$station_id = user_station_id();
$msg = '';

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
        status VARCHAR(20) DEFAULT 'active'
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

// Handle Staff Actions (Input Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ACTION 1: Record Daily Pump Reading
    if ($action === 'record_pump_reading') {
        $pump_id = $_POST['pump_id'];
        $reading_date = $_POST['reading_date'];
        $shift = $_POST['shift'];
        $previous_reading = (float)$_POST['previous_reading'];
        $current_reading = (float)$_POST['current_reading'];
        $calibration = (float)($_POST['calibration'] ?? 0);
        $notes = $_POST['notes'] ?? '';
        
        // Calculate sales liters
        $sales_liters = $current_reading - $previous_reading - $calibration;
        
        if ($pump_id && $reading_date && $shift) {
            try {
                $stmt = $pdo->prepare("INSERT INTO fuel_daily_readings (station_id, pump_id, reading_date, shift, previous_reading, current_reading, calibration, sales_liters, user_id, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$station_id, $pump_id, $reading_date, $shift, $previous_reading, $current_reading, $calibration, $sales_liters, $me['id'], $notes]);
                
                // Log activity
                log_activity($pdo, $me['id'], 'Record Pump Reading', "Recorded pump #$pump_id: $previous_reading → $current_reading = $sales_liters L ($shift)", 'fuel');
                
                $msg = "✅ Pump reading recorded! Sales: " . number_format($sales_liters, 2) . " liters. Awaiting admin verification.";
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $msg = "❌ Reading already exists for this pump, date, and shift.";
                } else {
                    $msg = "❌ Error: " . $e->getMessage();
                }
            }
        } else {
            $msg = "❌ Please fill all required fields.";
        }
    }
    
    // ACTION 2: Log Fuel Delivery
    elseif ($action === 'record_delivery') {
        $delivery_date = $_POST['delivery_date'];
        $fuel_type = $_POST['fuel_type'];
        $supplier = $_POST['supplier'];
        $invoice_no = $_POST['invoice_no'] ?? '';
        $delivery_liters = (float)$_POST['delivery_liters'];
        $tanker_number = $_POST['tanker_number'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if ($delivery_date && $fuel_type && $supplier && $delivery_liters > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO fuel_deliveries (station_id, delivery_date, fuel_type, supplier, invoice_no, delivery_liters, tanker_number, received_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$station_id, $delivery_date, $fuel_type, $supplier, $invoice_no, $delivery_liters, $tanker_number, $me['id'], $notes]);
                
                log_activity($pdo, $me['id'], 'Record Delivery', "Delivery from $supplier: " . number_format($delivery_liters, 2) . " L of $fuel_type", 'fuel');
                
                $msg = "✅ Delivery logged successfully! Awaiting admin verification.";
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        } else {
            $msg = "❌ Please fill all required fields.";
        }
    }
    
    // ACTION 3: Update Adjustment (Losses, Transfers, Consumption)
    elseif ($action === 'record_adjustment') {
        $adjustment_date = $_POST['adjustment_date'];
        $fuel_type = $_POST['fuel_type'];
        $adjustment_type = $_POST['adjustment_type'];
        $liters = (float)$_POST['liters'];
        $reason = $_POST['reason'];
        $notes = $_POST['notes'] ?? '';
        
        if ($adjustment_date && $fuel_type && $adjustment_type && $liters != 0 && $reason) {
            try {
                $stmt = $pdo->prepare("INSERT INTO fuel_adjustments (station_id, adjustment_date, fuel_type, adjustment_type, liters, reason, user_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$station_id, $adjustment_date, $fuel_type, $adjustment_type, $liters, $reason, $me['id'], $notes]);
                
                $type_text = ucfirst($adjustment_type);
                log_activity($pdo, $me['id'], 'Record Adjustment', "$type_text: " . number_format($liters, 2) . " L of $fuel_type - $reason", 'fuel');
                
                $msg = "✅ Adjustment recorded! Awaiting admin approval.";
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        } else {
            $msg = "❌ Please fill all required fields.";
        }
    }
}

// Fetch staff data
$fuel_stations = [];
$my_readings = [];
$my_deliveries = [];
$my_adjustments = [];

try {
    // Get pumps at this station
    $stmt = $pdo->prepare("SELECT fp.*, ft.name as fuel_type FROM fuel_pumps fp LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id WHERE fp.station_id = ? ORDER BY fp.pump_number");
    $stmt->execute([$station_id]);
    $fuel_stations = $stmt->fetchAll();
    
    // Get my recent readings
    $stmt = $pdo->prepare("SELECT dr.*, fp.pump_number, ft.name as fuel_type FROM fuel_daily_readings dr LEFT JOIN fuel_pumps fp ON dr.pump_id = fp.id LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id WHERE dr.station_id = ? AND dr.user_id = ? ORDER BY dr.reading_date DESC LIMIT 20");
    $stmt->execute([$station_id, $me['id']]);
    $my_readings = $stmt->fetchAll();
    
    // Get my recent deliveries
    $stmt = $pdo->prepare("SELECT * FROM fuel_deliveries WHERE station_id = ? AND received_by = ? ORDER BY delivery_date DESC LIMIT 20");
    $stmt->execute([$station_id, $me['id']]);
    $my_deliveries = $stmt->fetchAll();
    
    // Get my recent adjustments
    $stmt = $pdo->prepare("SELECT * FROM fuel_adjustments WHERE station_id = ? AND user_id = ? ORDER BY adjustment_date DESC LIMIT 20");
    $stmt->execute([$station_id, $me['id']]);
    $my_adjustments = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Staff Fuel Error: " . $e->getMessage());
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
      <h1 style="color: white; margin: 0 0 10px 0; font-size: 32px;"><i class="fas fa-tint"></i> Fuel Management - Staff Input</h1>
      <div style="color: rgba(255,255,255,0.9); font-size: 16px;">Encode daily readings, deliveries, and adjustments</div>
    </div>
  </div>

  <?php if($msg): ?>
    <div class="alert <?php echo strpos($msg, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>" style="margin-top:15px;">
      <?php echo $msg; ?>
    </div>
  <?php endif; ?>

  <!-- QUICK STATS -->
  <div class="cards three" style="margin-top:18px">
    <div class="card metric">
      <div class="metric-ico blue"><i class="fas fa-gas-pump"></i></div>
      <div class="metric-meta">
        <div class="metric-label">Today's Readings</div>
        <div class="metric-value">
          <?php 
          $today = date('Y-m-d');
          $today_count = 0;
          foreach($my_readings as $r) {
              if ($r['reading_date'] == $today) $today_count++;
          }
          echo $today_count;
          ?>
        </div>
      </div>
    </div>
    <div class="card metric">
      <div class="metric-ico green"><i class="fas fa-truck"></i></div>
      <div class="metric-meta">
        <div class="metric-label">Pending Deliveries</div>
        <div class="metric-value">
          <?php 
          $pending_del = 0;
          foreach($my_deliveries as $d) {
              if ($d['status'] == 'Pending') $pending_del++;
          }
          echo $pending_del;
          ?>
        </div>
      </div>
    </div>
    <div class="card metric">
      <div class="metric-ico amber"><i class="fas fa-exchange-alt"></i></div>
      <div class="metric-meta">
        <div class="metric-label">My Adjustments</div>
        <div class="metric-value"><?php echo count($my_adjustments); ?></div>
      </div>
    </div>
  </div>

  <!-- TABS FOR STAFF INPUT -->
  <div class="tabs" style="margin-top:16px">
    <button class="tab <?php echo $active_tab === 'pump' ? 'active' : ''; ?>" data-tab="pump">Pump Readings</button>
    <button class="tab <?php echo $active_tab === 'delivery' ? 'active' : ''; ?>" data-tab="delivery">Fuel Deliveries</button>
    <button class="tab <?php echo $active_tab === 'adjustment' ? 'active' : ''; ?>" data-tab="adjustment">Adjustments</button>
    <button class="tab <?php echo $active_tab === 'myentries' ? 'active' : ''; ?>" data-tab="myentries">My Entries</button>
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

</div>

<script src="../assets/js/data_helper.js"></script>

<script>
// Tab switching - more robust version
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing tabs...');

    // Load fuel types dynamically from backend
    DataHelper.populateFuelTypes('fuel_type_delivery', '-- Select Fuel Type --')
        .then(() => DataHelper.populateFuelTypes('fuel_type_adjustment', '-- Select Fuel --'))
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