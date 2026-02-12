<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'staff_home';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$rk = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));
if ($rk !== 'staff') { header('Location: dashboard.php'); exit; }

$station_id = user_station_id();

// Quick metrics
$my_sales_today = 0.0;
$my_txn_today = 0;
$my_active_jobs = 0;
$shift_label = (date('H') < 12) ? 'AM' : 'PM';

try {
  $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM transactions WHERE user_id = ? AND DATE(created_at)=CURDATE()");
  $stmt->execute([$me['id']]);
  $my_sales_today = (float)$stmt->fetchColumn();
} catch(Exception $e) {}

try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND DATE(created_at)=CURDATE()");
  $stmt->execute([$me['id']]);
  $my_txn_today = (int)$stmt->fetchColumn();
} catch(Exception $e) {}

try {
  // Some dbs use assigned_to instead of user_id
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_orders WHERE station_id = ? AND (user_id = ? OR assigned_to = ?) AND status IN ('Pending','In Progress')");
  $stmt->execute([$station_id, $me['id'], $me['id']]);
  $my_active_jobs = (int)$stmt->fetchColumn();
} catch(Exception $e) {}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
  <div>
    <h1 class="h1">My Home</h1>
    <div class="sub">Welcome, <?php echo htmlspecialchars($me['name'] ?? $me['username'] ?? ''); ?> • Shift <?php echo htmlspecialchars($shift_label); ?></div>
  </div>
  <div class="header-actions">
    <span class="badge status-active" style="margin-right: 15px;"><i class="fas fa-clock"></i> <?php echo date('g:i A'); ?></span>
    <a class="btn btn-outline" href="staff_dashboard.php"><i class="fas fa-gauge"></i> Open Dashboard</a>
  </div>
</div>

<div class="staff-stats-grid">
  <div class="stat-card">
    <div class="stat-icon bg-primary"><i class="fas fa-money-bill-wave"></i></div>
    <div class="stat-content">
      <div class="stat-value">₱<?php echo number_format($my_sales_today, 2); ?></div>
      <div class="stat-label">My Sales Today</div>
      <div class="stat-sub"><?php echo (int)$my_txn_today; ?> transactions</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-info"><i class="fas fa-wrench"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$my_active_jobs; ?></div>
      <div class="stat-label">My Active Job Orders</div>
      <div class="stat-sub">Pending / In progress</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-success"><i class="fas fa-building"></i></div>
    <div class="stat-content">
      <div class="stat-value">#<?php echo (int)$station_id; ?></div>
      <div class="stat-label">Station</div>
      <div class="stat-sub">Assigned station</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-warning"><i class="fas fa-bolt"></i></div>
    <div class="stat-content">
      <div class="stat-value">Quick</div>
      <div class="stat-label">Actions</div>
      <div class="stat-sub">POS • Job Order • Fuel</div>
    </div>
  </div>
</div>

<div class="staff-home-grid">
  <div class="dashboard-card">
    <div class="card-header">
      <h3><i class="fas fa-rocket text-warning"></i> Quick Actions</h3>
    </div>
    <div class="card-body">
      <div class="quick-actions-grid">
        <a href="pos.php" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-shopping-cart"></i></div><div class="quick-label">New Transaction</div></a>
        <a href="joborder.php?tab=create" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-wrench"></i></div><div class="quick-label">Create Job Order</div></a>
        <a href="credit_transactions.php" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-hand-holding-usd"></i></div><div class="quick-label">Credit Sale</div></a>
        <a href="fuel_staff.php" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-gas-pump"></i></div><div class="quick-label">Encode Fuel Reading</div></a>
        <a href="transactions.php?view=my_shift" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-receipt"></i></div><div class="quick-label">Shift Sales</div></a>
        <a href="my_shift.php?view=clock" class="quick-action-btn"><div class="quick-icon"><i class="fas fa-clock"></i></div><div class="quick-label">Clock In/Out</div></a>
      </div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="card-header">
      <h3><i class="fas fa-bell text-primary"></i> Reminders</h3>
    </div>
    <div class="card-body">
      <div class="empty-state" style="padding: 22px 14px;">
        <i class="fas fa-info-circle"></i>
        <p>Use the sidebar to open POS, Job Orders, Fuel, and My Reports.</p>
        <div class="muted">If a page looks blank, it usually means your role isn't detected correctly or the database table isn't available.</div>
      </div>
    </div>
  </div>
</div>

<style>
.staff-stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin:24px 0}
.staff-home-grid{display:grid;grid-template-columns:1.4fr .6fr;gap:24px;margin-top:24px}
.dashboard-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #e9ecef}
.card-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid #e9ecef}
.card-header h3{margin:0;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px}
.card-body{padding:24px}
.stat-card{background:#fff;border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #e9ecef;transition:transform .2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-icon{width:56px;height:56px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff}
.bg-primary{background:#0066cc}.bg-warning{background:#ff9500}.bg-info{background:#00b4d8}.bg-success{background:#00a65a}
.stat-value{font-size:22px;font-weight:800;color:#1a1a1a;line-height:1}
.stat-label{font-size:14px;color:#666;margin-top:4px}
.stat-sub{font-size:12px;color:#888;margin-top:2px}
.quick-actions-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.quick-action-btn{display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px;border-radius:8px;background:#f8f9fa;text-decoration:none;color:#333;transition:all .2s}
.quick-action-btn:hover{background:#0066cc;color:#fff;transform:translateY(-2px)}
.quick-icon{width:40px;height:40px;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.quick-label{font-size:12px;font-weight:600;text-align:center}
.empty-state{text-align:center;padding:40px 20px;color:#888}
.empty-state i{font-size:42px;margin-bottom:12px;opacity:.5}
@media (max-width:1200px){.staff-stats-grid{grid-template-columns:repeat(2,1fr)}.staff-home-grid{grid-template-columns:1fr}}
@media (max-width:768px){.staff-stats-grid{grid-template-columns:1fr}.quick-actions-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
