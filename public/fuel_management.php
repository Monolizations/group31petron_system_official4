<?php
$page_id = 'fuel_management';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$isSuper = ($me['role'] ?? '') === 'superadmin';
$station_id = $isSuper ? ($_GET['station'] ?? '') : user_station_id();
$msg = '';

// Fetch stations for dropdown if superadmin
$stations = [];
if ($isSuper) {
    try {
        $stations = $pdo->query("SELECT id, name FROM stations ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}
}

// Handle Fuel Management Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // STAFF: Record Daily Pump Reading
    if ($action === 'record_pump_reading') {
        if (!in_array($me['role'], ['staff', 'admin', 'superadmin'])) {
            $msg = "❌ Error: Only authorized users can record pump readings.";
        } else {
            $fuel_station_id = $_POST['fuel_station_id'];
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
                    $stmt = $pdo->prepare("INSERT INTO fuel_daily_readings (station_id, fuel_station_id, reading_date, shift, previous_reading, current_reading, calibration, sales_liters, user_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
        if (!in_array($me['role'], ['staff', 'admin', 'superadmin'])) {
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
                    $stmt = $pdo->prepare("INSERT INTO fuel_deliveries (station_id, delivery_date, fuel_type, supplier, invoice_no, delivery_liters, tanker_number, received_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
        if (!in_array($me['role'], ['staff', 'admin', 'superadmin'])) {
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
                    $stmt = $pdo->prepare("INSERT INTO fuel_adjustments (station_id, adjustment_date, fuel_type, adjustment_type, liters, reason, user_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
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
    
    // MANAGER: Verify Pump Reading
    } elseif ($action === 'verify_reading') {
        if (!in_array($me['role'], ['manager', 'admin', 'superadmin'])) {
            $msg = "❌ Error: Only managers can verify readings.";
        } else {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE fuel_daily_readings SET status = ?, notes = CONCAT(COALESCE(notes,''), '\n[Admin Review] ', ?) WHERE id = ?");
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
        if (!in_array($me['role'], ['manager', 'admin', 'superadmin'])) {
            $msg = "❌ Error: Only managers can verify deliveries.";
        } else {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE fuel_deliveries SET status = ?, verified_by = ?, notes = CONCAT(COALESCE(notes,''), '\n[Admin Verification] ', ?) WHERE id = ?");
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
        if (!in_array($me['role'], ['manager', 'admin', 'superadmin'])) {
            $msg = "❌ Error: Only managers can approve adjustments.";
        } else {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE fuel_adjustments SET status = ?, approved_by = ?, notes = CONCAT(COALESCE(notes,''), '\n[Admin Approval] ', ?) WHERE id = ?");
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
        if (!in_array($me['role'], ['manager', 'admin', 'superadmin'])) {
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
                    $stmt = $pdo->prepare("SELECT SUM(delivery_liters) as total FROM fuel_deliveries WHERE station_id = ? AND fuel_type = ? AND delivery_date = ? AND status = 'Verified'");
                    $stmt->execute([$station_id, $fuel_type, $reconciliation_date]);
                    $deliveries_data = $stmt->fetch();
                    $deliveries = $deliveries_data['total'] ?? 0;
                    
                    // Get total sales for the day
                    $stmt = $pdo->prepare("SELECT SUM(sales_liters) as total FROM fuel_daily_readings WHERE station_id = ? AND EXISTS (SELECT 1 FROM fuel_stations WHERE id = fuel_station_id AND fuel_type = ?) AND reading_date = ? AND status = 'Verified'");
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
                    $stmt = $pdo->prepare("INSERT INTO fuel_reconciliation (station_id, reconciliation_date, fuel_type, opening_stock, deliveries, sales, adjustments, closing_stock, physical_stock, variance, variance_percent, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$station_id, $reconciliation_date, $fuel_type, $opening_stock, $deliveries, $sales, $adjustments, $closing_stock, $physical_stock, $variance, $variance_percent, $notes]);
                    
                    // If variance exceeds threshold, create variance report
                    $variance_threshold = 0.05; // 5%
                    if (abs($variance_percent) > $variance_threshold) {
                        $stmt = $pdo->prepare("INSERT INTO fuel_variance_reports (station_id, report_date, fuel_type, expected_stock, actual_stock, variance_liters, variance_percent, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
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
    }
}

// Fetch data based on user role
$fuel_stations = [];
$daily_readings = [];
$deliveries = [];
$adjustments = [];
$reconciliations = [];
$variance_reports = [];

if ($station_id) {
    try {
        // Fetch fuel stations/pumps
        $stmt = $pdo->prepare("SELECT * FROM fuel_stations WHERE station_id = ? ORDER BY pump_number");
        $stmt->execute([$station_id]);
        $fuel_stations = $stmt->fetchAll();
        
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
        
        // Fetch reconciliations
        $stmt = $pdo->prepare("SELECT r.*, v.name as verifier_name 
                              FROM fuel_reconciliation r 
                              LEFT JOIN users v ON r.verified_by = v.id 
                              WHERE r.station_id = ? 
                              ORDER BY r.reconciliation_date DESC 
                              LIMIT 30");
        $stmt->execute([$station_id]);
        $reconciliations = $stmt->fetchAll();
        
        // Fetch variance reports
        $stmt = $pdo->prepare("SELECT vr.*, i.name as investigator_name 
                              FROM fuel_variance_reports vr 
                              LEFT JOIN users i ON vr.investigated_by = i.id 
                              WHERE vr.station_id = ? 
                              ORDER BY vr.report_date DESC 
                              LIMIT 20");
        $stmt->execute([$station_id]);
        $variance_reports = $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Fuel Management Error: " . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
.fuel-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.fuel-metric {
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
}
.fuel-metric .value {
    font-size: 24px;
    font-weight: bold;
    margin: 10px 0;
}
.fuel-metric .label {
    font-size: 14px;
    color: #6c757d;
}
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-verified { background: #d4edda; color: #155724; }
.status-finalized { background: #155724; color: white; }
.status-rejected { background: #f8d7da; color: #721c24; }
.shift-badge { 
    padding: 4px 8px; 
    border-radius: 12px; 
    font-size: 11px;
    font-weight: 600;
}
.shift-morning { background: #e3f2fd; color: #0d47a1; }
.shift-afternoon { background: #fff3e0; color: #e65100; }
.shift-evening { background: #f3e5f5; color: #4a148c; }
.variance-positive { color: #28a745; font-weight: bold; }
.variance-negative { color: #dc3545; font-weight: bold; }
</style>

<div class="page">
  <div class="page-head">
    <div>
      <h1>Fuel Management System</h1>
      <div class="muted">Centralized fuel inventory monitoring and reconciliation</div>
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
              return $d['status'] == 'Pending';
          });
          echo count($pending_deliveries);
          ?>
        </div>
        <div class="metric-sub">Awaiting verification</div>
      </div>
    </div>
    <div class="card metric">
      <div class="metric-ico amber"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="metric-meta">
        <div class="metric-label">Open Variances</div>
        <div class="metric-value">
          <?php 
          $open_variances = array_filter($variance_reports, function($v) {
              return $v['status'] == 'Open' || $v['status'] == 'Under Investigation';
          });
          echo count($open_variances);
          ?>
        </div>
        <div class="metric-sub">Requires attention</div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs" style="margin-top:16px">
    <button class="tab active" data-tab="operations">Daily Operations</button>
    <button class="tab" data-tab="deliveries">Fuel Deliveries</button>
    <button class="tab" data-tab="adjustments">Adjustments</button>
    <button class="tab" data-tab="reconciliation">Reconciliation</button>
    <button class="tab" data-tab="variances">Variance Reports</button>
    <button class="tab" data-tab="history">Shift History</button>
  </div>

  <!-- TAB 1: DAILY OPERATIONS -->
  <section class="panel" id="tab-operations">
    <div class="fuel-card">
      <div class="row">
        <div class="col-md-8">
          <h4>Daily Pump Readings</h4>
          <div class="muted">Record daily pump meter readings per shift</div>
        </div>
        <div class="col-md-4 text-end">
          <button class="btn dark" data-bs-toggle="modal" data-bs-target="#modalRecordReading">
            <i class="fas fa-plus"></i> New Reading
          </button>
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
                <?php if($reading['status'] == 'Pending' && in_array($me['role'], ['admin', 'superadmin'])): ?>
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
    </div>
  </section>

  <!-- TAB 2: FUEL DELIVERIES -->
  <section class="panel hidden" id="tab-deliveries">
    <div class="fuel-card">
      <div class="row">
        <div class="col-md-8">
          <h4>Fuel Deliveries</h4>
          <div class="muted">Record and verify fuel deliveries from suppliers</div>
        </div>
        <div class="col-md-4 text-end">
          <button class="btn dark" data-bs-toggle="modal" data-bs-target="#modalRecordDelivery">
            <i class="fas fa-truck"></i> New Delivery
          </button>
        </div>
      </div>
      
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
                <?php if($delivery['status'] == 'Pending' && in_array($me['role'], ['admin', 'superadmin'])): ?>
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
    </div>
  </section>

  <!-- TAB 3: ADJUSTMENTS -->
  <section class="panel hidden" id="tab-adjustments">
    <div class="fuel-card">
      <div class="row">
        <div class="col-md-8">
          <h4>Fuel Adjustments</h4>
          <div class="muted">Record losses, transfers, and other adjustments</div>
        </div>
        <div class="col-md-4 text-end">
          <button class="btn dark" data-bs-toggle="modal" data-bs-target="#modalRecordAdjustment">
            <i class="fas fa-exchange-alt"></i> New Adjustment
          </button>
        </div>
      </div>
      
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
                <?php if($adj['status'] == 'Pending' && in_array($me['role'], ['admin', 'superadmin'])): ?>
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

  <!-- TAB 4: RECONCILIATION -->
  <section class="panel hidden" id="tab-reconciliation">
    <div class="fuel-card">
      <div class="row">
        <div class="col-md-8">
          <h4>Fuel Reconciliation</h4>
          <div class="muted">Daily reconciliation of fuel stock vs sales</div>
        </div>
        <div class="col-md-4 text-end">
          <?php if(in_array($me['role'], ['admin', 'superadmin'])): ?>
          <button class="btn dark" data-bs-toggle="modal" data-bs-target="#modalRunReconciliation">
            <i class="fas fa-calculator"></i> Run Reconciliation
          </button>
          <?php endif; ?>
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

  <!-- TAB 5: VARIANCE REPORTS -->
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
                <?php if(in_array($report['status'], ['Open', 'Under Investigation']) && in_array($me['role'], ['admin', 'superadmin'])): ?>
                  <button class="btn small green" onclick="openInvestigateVarianceModal(<?php echo $report['id']; ?>)">
                    <i class="fas fa-search"></i> Investigate
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- TAB 6: SHIFT HISTORY -->
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
                          WHERE al.action LIKE '%fuel%' 
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

</div> <!-- This closes the main div -->

<?php endif; ?>

<!-- MODALS -->
<!-- Modal: Record Pump Reading -->
<div class="modal fade" id="modalRecordReading" tabindex="-1">
  <!-- ... all your modals ... -->
</div>

<!-- Modal: Record Delivery -->
<div class="modal fade" id="modalRecordDelivery" tabindex="-1">
  <!-- ... all your modals ... -->
</div>

<!-- Modal: Record Adjustment -->
<div class="modal fade" id="modalRecordAdjustment" tabindex="-1">
  <!-- ... all your modals ... -->
</div>

<!-- Modal: Run Reconciliation -->
<div class="modal fade" id="modalRunReconciliation" tabindex="-1">
  <!-- ... all your modals ... -->
</div>

<!-- Verification/Approval Modals (will be populated by JavaScript) -->
<div class="modal fade" id="modalVerifyReading" tabindex="-1"></div>
<div class="modal fade" id="modalVerifyDelivery" tabindex="-1"></div>
<div class="modal fade" id="modalApproveAdjustment" tabindex="-1"></div>
<div class="modal fade" id="modalInvestigateVariance" tabindex="-1"></div>

<script>
// Tab Switching
document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.add('hidden'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
    });
});

// Auto-calculate sales liters
document.querySelectorAll('input[name="previous_reading"], input[name="current_reading"], input[name="calibration"]').forEach(input => {
    input.addEventListener('input', function() {
        const prev = parseFloat(document.querySelector('input[name="previous_reading"]').value) || 0;
        const curr = parseFloat(document.querySelector('input[name="current_reading"]').value) || 0;
        const calib = parseFloat(document.querySelector('input[name="calibration"]').value) || 0;
        const sales = curr - prev - calib;
        document.getElementById('salesLitersDisplay').value = sales.toFixed(2) + ' liters';
    });
});

// Filter Functions
function applyFilters() {
    const date = document.getElementById('filterDate').value;
    const shift = document.getElementById('filterShift').value;
    const status = document.getElementById('filterStatus').value;
    
    let url = 'fuel_management.php?';
    if (date) url += 'date=' + encodeURIComponent(date) + '&';
    if (shift) url += 'shift=' + encodeURIComponent(shift) + '&';
    if (status) url += 'status=' + encodeURIComponent(status) + '&';
    url += 'station=<?php echo $station_id; ?>';
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = 'fuel_management.php?station=<?php echo $station_id; ?>';
}

// Open Verification Modals (would be implemented with AJAX calls)
function openVerifyReadingModal(id) {
    // AJAX call to load verification form
    fetch(`backend/fuel_verify_reading.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalVerifyReading').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalVerifyReading')).show();
        });
}

function openVerifyDeliveryModal(id) {
    fetch(`backend/fuel_verify_delivery.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalVerifyDelivery').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalVerifyDelivery')).show();
        });
}

function openApproveAdjustmentModal(id) {
    fetch(`backend/fuel_approve_adjustment.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalApproveAdjustment').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalApproveAdjustment')).show();
        });
}

function openInvestigateVarianceModal(id) {
    fetch(`backend/fuel_investigate_variance.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalInvestigateVariance').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalInvestigateVariance')).show();
        });
}

// View Details Functions
function viewReadingDetails(id) {
    window.open(`fuel_reading_details.php?id=${id}`, '_blank');
}

function viewDeliveryDetails(id) {
    window.open(`fuel_delivery_details.php?id=${id}`, '_blank');
}

function viewAdjustmentDetails(id) {
    window.open(`fuel_adjustment_details.php?id=${id}`, '_blank');
}

function viewVarianceDetails(id) {
    window.open(`fuel_variance_details.php?id=${id}`, '_blank');
}
</script>

<script src="../assets/js/data_helper.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    DataHelper.populateShifts('filterShift', 'All Shifts')
        .then(() => console.log('Shifts loaded'))
        .catch(error => {
            console.error('Failed to load shifts:', error);
            alert('Failed to load shifts. Please refresh.');
        });
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
