<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'staff_dashboard';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$rk = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));
if ($rk !== 'staff') { header('Location: dashboard.php'); exit; }

$station_id = user_station_id();
if (!$station_id) { die('Error: You are not assigned to a station.'); }

$msg = '';
if (isset($_SESSION['success'])) { $msg = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { $msg = $_SESSION['error']; unset($_SESSION['error']); }

// Handle Clock In/Out (tables may not exist; handle gracefully)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'clock_in') {
    try {
      $check = $pdo->prepare("SELECT id FROM labor_sessions WHERE user_id = ? AND end_time IS NULL");
      $check->execute([$me['id']]);
      if ($check->fetch()) {
        $msg = "❌ Error: You are already clocked in.";
      } else {
        $stmt = $pdo->prepare("INSERT INTO labor_sessions (user_id, station_id, start_time) VALUES (?, ?, NOW())");
        $stmt->execute([$me['id'], $station_id]);
        if (function_exists('log_activity')) log_activity($pdo, $me['id'], 'Clock In', "Clocked in at station $station_id");
        $msg = "✅ Clocked in successfully.";
      }
    } catch (Exception $e) {
      $msg = "ℹ️ Time tracking is not configured yet (missing labor tables).";
    }
  }
  if ($action === 'clock_out') {
    try {
      $stmt = $pdo->prepare("UPDATE labor_sessions SET end_time = NOW(), hours_worked = TIMESTAMPDIFF(HOUR, start_time, NOW()) WHERE user_id = ? AND end_time IS NULL");
      $stmt->execute([$me['id']]);
      if ($stmt->rowCount() > 0) {
        if (function_exists('log_activity')) log_activity($pdo, $me['id'], 'Clock Out', "Clocked out");
        $msg = "✅ Clocked out successfully.";
      } else {
        $msg = "❌ Error: You are not clocked in.";
      }
    } catch (Exception $e) {
      $msg = "ℹ️ Time tracking is not configured yet (missing labor tables).";
    }
  }
}

// Current session
$current_session = null;
try {
  $stmt = $pdo->prepare("SELECT * FROM labor_sessions WHERE user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1");
  $stmt->execute([$me['id']]);
  $current_session = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// KPIs
$today_sales = 0.0;
$active_jobs_count = 0;
$txn_today = 0;
try {
  $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0), COUNT(*) FROM transactions WHERE user_id = ? AND DATE(created_at) = CURDATE()");
  $stmt->execute([$me['id']]);
  $row = $stmt->fetch(PDO::FETCH_NUM);
  if ($row) { $today_sales = (float)($row[0] ?? 0); $txn_today = (int)($row[1] ?? 0); }
} catch (Exception $e) {}

try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_orders WHERE station_id = ? AND status IN ('Pending','In Progress','Awaiting Parts')");
  $stmt->execute([$station_id]);
  $active_jobs_count = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Tables
$recent_txns = [];
try {
  $stmt = $pdo->prepare("SELECT id, total, payment_method, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
  $stmt->execute([$me['id']]);
  $recent_txns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$assigned_jobs = [];
try {
  // Some schemas use user_id, others use assigned_to; we try both.
  $stmt = $pdo->prepare("SELECT id, vehicle_plate, service_type, status, created_at FROM job_orders WHERE (user_id = ? OR assigned_to = ?) AND station_id = ? ORDER BY created_at DESC LIMIT 10");
  $stmt->execute([$me['id'], $me['id'], $station_id]);
  $assigned_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  try {
    $stmt = $pdo->prepare("SELECT id, vehicle_plate, service_type, status, created_at FROM job_orders WHERE station_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$station_id]);
    $assigned_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e2) {}
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
  <div>
    <h1 class="h1">My Dashboard</h1>
    <div class="sub">Welcome, <?php echo htmlspecialchars($me['name'] ?? $me['username'] ?? ''); ?> • Station #<?php echo (int)$station_id; ?></div>
  </div>
  <div class="header-actions">
    <span class="badge status-active" style="margin-right: 12px;"><i class="fas fa-clock"></i> <?php echo date('g:i A'); ?></span>
    <a class="btn btn-outline" href="pos.php"><i class="fas fa-shopping-cart"></i> New Transaction</a>
  </div>
</div>

<?php if($msg): ?>
  <div class="card" style="margin-bottom:16px;"><div style="padding:14px;" class="muted"><?php echo htmlspecialchars($msg); ?></div></div>
<?php endif; ?>

<div class="staff-stats-grid">
  <div class="stat-card">
    <div class="stat-icon bg-success"><i class="fas fa-money-bill-wave"></i></div>
    <div class="stat-content">
      <div class="stat-value">₱<?php echo number_format($today_sales, 2); ?></div>
      <div class="stat-label">Current Shift Sales</div>
      <div class="stat-sub"><?php echo (int)$txn_today; ?> transactions today</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-info"><i class="fas fa-wrench"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$active_jobs_count; ?></div>
      <div class="stat-label">Active Job Orders</div>
      <div class="stat-sub">Pending / In progress</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-primary"><i class="fas fa-user-clock"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo $current_session ? 'ON' : 'OFF'; ?></div>
      <div class="stat-label">Clock Status</div>
      <div class="stat-sub"><?php echo $current_session ? 'Since '.date('g:i A', strtotime($current_session['start_time'])) : 'Not clocked in'; ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-warning"><i class="fas fa-bolt"></i></div>
    <div class="stat-content">
      <div class="stat-value">Actions</div>
      <div class="stat-label">Quick Buttons</div>
      <div class="stat-sub"><a href="joborder.php?tab=create">Create Job</a> • <a href="fuel_staff.php">Fuel Reading</a></div>
    </div>
  </div>
</div>

<div class="staff-dashboard-grid">
  <div class="dashboard-card">
    <div class="card-header">
      <h3><i class="fas fa-clock text-primary"></i> Time Tracking</h3>
      <span class="muted">Optional (requires labor tables)</span>
    </div>
    <div class="card-body">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
          <?php if($current_session): ?>
            <div class="muted">You are clocked in.</div>
            <div style="font-weight:600;">Started: <?php echo date('M d, g:i A', strtotime($current_session['start_time'])); ?></div>
          <?php else: ?>
            <div class="muted">You are clocked out.</div>
            <div style="font-weight:600;">Clock in to start time tracking.</div>
          <?php endif; ?>
        </div>
        <div style="display:flex;gap:10px;">
          <?php if($current_session): ?>
            <form method="post"><input type="hidden" name="action" value="clock_out"><button class="btn btn-danger" type="submit"><i class="fas fa-sign-out-alt"></i> Clock Out</button></form>
          <?php else: ?>
            <form method="post"><input type="hidden" name="action" value="clock_in"><button class="btn btn-success" type="submit"><i class="fas fa-sign-in-alt"></i> Clock In</button></form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="card-header">
      <h3><i class="fas fa-receipt text-primary"></i> Current Shift Transactions</h3>
      <a class="btn-link" href="transactions.php?view=my_shift">View all</a>
    </div>
    <div class="card-body">
      <?php if(empty($recent_txns)): ?>
        <div class="empty-state"><i class="fas fa-inbox"></i><p>No transactions yet.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>ID</th><th>Total</th><th>Payment</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach($recent_txns as $t): ?>
                <tr>
                  <td><?php echo htmlspecialchars($t['id']); ?></td>
                  <td>₱<?php echo number_format((float)$t['total'], 2); ?></td>
                  <td><?php echo htmlspecialchars($t['payment_method'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars(date('M d, g:i A', strtotime($t['created_at']))); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="card-header">
      <h3><i class="fas fa-tools text-primary"></i> Assigned Job Orders</h3>
      <a class="btn-link" href="joborder.php?tab=ongoing">Open job orders</a>
    </div>
    <div class="card-body">
      <?php if(empty($assigned_jobs)): ?>
        <div class="empty-state"><i class="fas fa-check-circle"></i><p>No assigned job orders found.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th>Plate</th><th>Service</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
              <?php foreach($assigned_jobs as $j): ?>
                <tr>
                  <td><?php echo (int)$j['id']; ?></td>
                  <td><?php echo htmlspecialchars($j['vehicle_plate'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars($j['service_type'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars($j['status'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars(date('M d, g:i A', strtotime($j['created_at']))); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="card-header">
      <h3><i class="fas fa-bolt text-warning"></i> Quick Actions</h3>
    </div>
    <div class="card-body">
      <div class="quick-actions-grid">
        <a href="pos.php" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-shopping-cart"></i></div><div class="quick-label">New Transaction</div></a>
        <a href="credit_transactions.php" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-hand-holding-usd"></i></div><div class="quick-label">Credit Sale</div></a>
        <a href="joborder.php?tab=create" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-wrench"></i></div><div class="quick-label">Create Job Order</div></a>
        <a href="fuel_staff.php" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-gas-pump"></i></div><div class="quick-label">Fuel Reading</div></a>
        <a href="receiving_staff.php" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-box"></i></div><div class="quick-label">Receive Items</div></a>
        <a href="my_shift.php?view=clock" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-clock"></i></div><div class="quick-label">My Shift</div></a>
      </div>
    </div>
  </div>
</div>

<style>
.staff-stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin:24px 0}
.stat-card{background:#fff;border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #e9ecef;transition:transform .2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-icon{width:56px;height:56px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff}
.bg-primary{background:#0066cc}.bg-warning{background:#ff9500}.bg-info{background:#00b4d8}.bg-success{background:#00a65a}
.stat-value{font-size:20px;font-weight:800;color:#1a1a1a;line-height:1}
.stat-label{font-size:14px;color:#666;margin-top:4px}
.stat-sub{font-size:12px;color:#888;margin-top:2px}
.stat-sub a{color:#0066cc;text-decoration:none}
.staff-dashboard-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px}
.dashboard-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #e9ecef}
.card-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid #e9ecef}
.card-header h3{margin:0;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px}
.card-body{padding:24px}
.btn-link{font-size:14px;color:#0066cc;text-decoration:none;font-weight:500}
.btn-link:hover{text-decoration:underline}
.empty-state{text-align:center;padding:28px 16px;color:#888}
.empty-state i{font-size:42px;margin-bottom:12px;opacity:.5}
.table-wrap{overflow:auto;border:1px solid #eef1f4;border-radius:10px}
.table{width:100%;border-collapse:collapse;font-size:14px}
.table th,.table td{padding:10px 12px;border-bottom:1px solid #eef1f4;white-space:nowrap}
.table th{text-align:left;background:#f8f9fa;font-weight:700}
.header-actions{display:flex;align-items:center;gap:10px}
.quick-actions-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.quick-action-btn{display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px;border-radius:8px;background:#f8f9fa;text-decoration:none;color:#333;transition:all .2s}
.quick-action-btn:hover{background:#0066cc;color:#fff;transform:translateY(-2px)}
.quick-icon{width:40px;height:40px;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.quick-label{font-size:12px;font-weight:600;text-align:center}
@media (max-width:1200px){.staff-stats-grid{grid-template-columns:repeat(2,1fr)}.staff-dashboard-grid{grid-template-columns:1fr}}
@media (max-width:768px){.staff-stats-grid{grid-template-columns:1fr}.quick-actions-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
