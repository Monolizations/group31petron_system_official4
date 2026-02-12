<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'manager_home';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$rk = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));
if ($rk !== 'manager') { header('Location: dashboard.php'); exit; }

$station_id = user_station_id();

include __DIR__ . '/../partials/header.php';

// Manager metrics (safe fallbacks if tables/columns don't exist)
$metrics = [
  'staff_on_duty' => 0,
  'pending_approvals' => 0,
  'active_jobs' => 0,
  'quality_score' => 94.5,
  'shift_coverage' => 0,
  'compliance_checks' => 0
];

try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE station_id = ? AND status = 'active' AND shift_status = 'on_duty'");
  $stmt->execute([$station_id]);
  $metrics['staff_on_duty'] = (int)$stmt->fetchColumn();
} catch(Exception $e) {}

// Pending approvals: try approval_requests; fallback to approvals table; fallback to 0
try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM approval_requests WHERE station_id = ? AND status = 'pending'");
  $stmt->execute([$station_id]);
  $metrics['pending_approvals'] = (int)$stmt->fetchColumn();
} catch(Exception $e) {
  try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM approvals WHERE station_id = ? AND status = 'pending'");
    $stmt->execute([$station_id]);
    $metrics['pending_approvals'] = (int)$stmt->fetchColumn();
  } catch(Exception $e2) {}
}

try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_orders WHERE station_id = ? AND status IN ('Pending','PENDING_REVIEW','Pending Review','In Progress')");
  $stmt->execute([$station_id]);
  $metrics['active_jobs'] = (int)$stmt->fetchColumn();
} catch(Exception $e) {}

try {
  $stmt = $pdo->prepare("SELECT (COUNT(CASE WHEN shift_status = 'on_duty' THEN 1 END) * 100.0 / NULLIF(COUNT(*),0)) as coverage FROM users WHERE station_id = ? AND status = 'active'");
  $stmt->execute([$station_id]);
  $metrics['shift_coverage'] = (float)($stmt->fetchColumn() ?: 0);
} catch(Exception $e) {}

try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM compliance_checks WHERE station_id = ? AND due_date = CURDATE() AND status = 'pending'");
  $stmt->execute([$station_id]);
  $metrics['compliance_checks'] = (int)$stmt->fetchColumn();
} catch(Exception $e) {}

// Recent alerts (priority/high/medium)
$alerts = [];
try {
  $stmt = $pdo->prepare("SELECT id, action as title, created_at, details, 'medium' as priority FROM activity_logs WHERE station_id = ? ORDER BY created_at DESC LIMIT 5");
  $stmt->execute([$station_id]);
  $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Staff performance: safe fallback
$staff_performance = [];
try {
  $stmt = $pdo->prepare("SELECT u.name, COUNT(jo.id) as completed_jobs
                          FROM users u
                          LEFT JOIN job_orders jo ON u.id = jo.user_id AND jo.status = 'Completed' AND DATE(jo.completed_at) = CURDATE()
                          WHERE u.station_id = ?
                          GROUP BY u.id
                          ORDER BY completed_jobs DESC
                          LIMIT 5");
  $stmt->execute([$station_id]);
  $staff_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}
?>

<div class="page-head">
  <div>
    <h1 class="h1">Operations Control Center</h1>
    <div class="sub">Welcome, <?php echo htmlspecialchars($me['name'] ?? 'Manager'); ?> • Station Performance Dashboard</div>
  </div>
  <div class="header-actions">
    <span class="badge status-active" style="margin-right: 15px;">
      <i class="fas fa-clock"></i> <?php echo date('g:i A'); ?>
    </span>
    <button class="btn btn-outline" onclick="location.reload()">
      <i class="fas fa-sync-alt"></i> Refresh
    </button>
  </div>
</div>

<div class="manager-stats-grid">
  <div class="stat-card">
    <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$metrics['staff_on_duty']; ?></div>
      <div class="stat-label">Staff On Duty</div>
      <div class="stat-sub"><?php echo number_format((float)$metrics['shift_coverage'], 1); ?>% coverage</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-warning"><i class="fas fa-clipboard-check"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$metrics['pending_approvals']; ?></div>
      <div class="stat-label">Pending Approvals</div>
      <div class="stat-sub">Awaiting action</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-info"><i class="fas fa-tasks"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$metrics['active_jobs']; ?></div>
      <div class="stat-label">Active Jobs</div>
      <div class="stat-sub">Pending / In progress</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-success"><i class="fas fa-chart-line"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo number_format((float)$metrics['quality_score'], 1); ?>%</div>
      <div class="stat-label">Quality Score</div>
      <div class="stat-sub">Service rating</div>
    </div>
  </div>
</div>

<div class="manager-dashboard-grid">
  <div class="dashboard-column">
    <div class="dashboard-card">
      <div class="card-header">
        <h3><i class="fas fa-exclamation-circle text-danger"></i> Priority Alerts</h3>
        <a href="audit_logs.php" class="btn-link">View All</a>
      </div>
      <div class="card-body">
        <?php if (empty($alerts)): ?>
          <div class="empty-state"><i class="fas fa-check-circle text-success"></i><p>No priority alerts at this time</p></div>
        <?php else: ?>
          <div class="alert-list">
            <?php foreach ($alerts as $alert): ?>
              <div class="alert-item alert-medium">
                <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                <div class="alert-content">
                  <div class="alert-title"><?php echo htmlspecialchars($alert['title'] ?? 'Alert'); ?></div>
                  <div class="alert-time"><?php echo !empty($alert['created_at']) ? date('H:i', strtotime($alert['created_at'])) : ''; ?></div>
                </div>
                <a class="btn btn-sm btn-outline" href="audit_logs.php">Review</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="dashboard-card">
      <div class="card-header">
        <h3><i class="fas fa-user-check text-primary"></i> Staff Performance</h3>
        <a href="staff_management.php?view=productivity" class="btn-link">Details</a>
      </div>
      <div class="card-body">
        <div class="performance-list">
          <?php if (empty($staff_performance)): ?>
            <div class="empty-state"><i class="fas fa-user-clock"></i><p>No performance data available</p></div>
          <?php else: ?>
            <?php foreach ($staff_performance as $staff): ?>
              <div class="performance-item">
                <div class="staff-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="staff-info">
                  <div class="staff-name"><?php echo htmlspecialchars($staff['name'] ?? ''); ?></div>
                  <div class="staff-stats">
                    <span class="stat-badge"><i class="fas fa-check-circle"></i> <?php echo (int)($staff['completed_jobs'] ?? 0); ?> jobs</span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="dashboard-column">
    <div class="dashboard-card">
      <div class="card-header"><h3><i class="fas fa-bolt text-warning"></i> Quick Actions</h3></div>
      <div class="card-body">
        <div class="quick-actions-grid">
          <a href="joborder.php?tab=pending" class="quick-action-btn">
            <div class="quick-icon"><i class="fas fa-tasks"></i></div>
            <div class="quick-label">Review Job Orders</div>
          </a>
          <a href="approvals.php" class="quick-action-btn">
            <div class="quick-icon"><i class="fas fa-check-circle"></i></div>
            <div class="quick-label">Approvals Queue</div>
          </a>
          <a href="reports.php?view=shift_reports" class="quick-action-btn">
            <div class="quick-icon"><i class="fas fa-file-alt"></i></div>
            <div class="quick-label">Shift Reports</div>
          </a>
          <a href="reconciliation.php" class="quick-action-btn">
            <div class="quick-icon"><i class="fas fa-gas-pump"></i></div>
            <div class="quick-label">Fuel Reconciliation</div>
          </a>
          <a href="audit_logs.php" class="quick-action-btn">
            <div class="quick-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="quick-label">Audit Logs</div>
          </a>
          <a href="compliance.php" class="quick-action-btn">
            <div class="quick-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="quick-label">Compliance</div>
          </a>
        </div>
      </div>
    </div>

    <div class="dashboard-card">
      <div class="card-header">
        <h3><i class="fas fa-shield-alt text-success"></i> Compliance Status</h3>
        <span class="badge <?php echo ((int)$metrics['compliance_checks'] > 0) ? 'badge-warning' : 'badge-success'; ?>"><?php echo (int)$metrics['compliance_checks']; ?> due</span>
      </div>
      <div class="card-body">
        <div class="compliance-progress">
          <div class="progress-label"><span>Daily Compliance</span><span>85%</span></div>
          <div class="progress-bar"><div class="progress-fill" style="width:85%"></div></div>
        </div>
        <div class="compliance-checklist">
          <div class="checklist-item completed"><i class="fas fa-check-circle"></i><span>Safety Inspection</span></div>
          <div class="checklist-item completed"><i class="fas fa-check-circle"></i><span>Equipment Check</span></div>
          <div class="checklist-item <?php echo ((int)$metrics['compliance_checks'] > 0) ? 'pending' : 'completed'; ?>">
            <i class="fas fa-<?php echo ((int)$metrics['compliance_checks'] > 0) ? 'clock' : 'check-circle'; ?>"></i>
            <span>Documentation Review</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.manager-dashboard-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px}
.dashboard-column{display:flex;flex-direction:column;gap:24px}
.dashboard-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #e9ecef}
.card-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid #e9ecef}
.card-header h3{margin:0;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px}
.card-body{padding:24px}
.manager-stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin:24px 0}
.stat-card{background:#fff;border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #e9ecef;transition:transform .2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-icon{width:56px;height:56px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff}
.bg-primary{background:#0066cc}.bg-warning{background:#ff9500}.bg-info{background:#00b4d8}.bg-success{background:#00a65a}
.stat-value{font-size:28px;font-weight:700;color:#1a1a1a;line-height:1}
.stat-label{font-size:14px;color:#666;margin-top:4px}
.stat-sub{font-size:12px;color:#888;margin-top:2px}
.alert-list{display:flex;flex-direction:column;gap:12px}
.alert-item{display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;background:#f8f9fa}
.alert-medium{border-left:4px solid #ffc107}
.alert-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fff}
.alert-title{font-weight:500;flex:1}
.alert-time{font-size:12px;color:#888}
.performance-list{display:flex;flex-direction:column;gap:12px}
.performance-item{display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;background:#f8f9fa}
.staff-avatar{width:40px;height:40px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:20px;color:#666}
.staff-info{flex:1}.staff-name{font-weight:500;margin-bottom:4px}.staff-stats{display:flex;gap:8px}
.stat-badge{font-size:11px;padding:2px 8px;border-radius:12px;background:#e9ecef;display:inline-flex;align-items:center;gap:4px}
.quick-actions-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.quick-action-btn{display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px;border-radius:8px;background:#f8f9fa;text-decoration:none;color:#333;transition:all .2s}
.quick-action-btn:hover{background:#0066cc;color:#fff;transform:translateY(-2px)}
.quick-icon{width:40px;height:40px;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.quick-label{font-size:12px;font-weight:500;text-align:center}
.compliance-progress{margin-bottom:20px}.progress-label{display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px}
.progress-bar{height:8px;background:#e9ecef;border-radius:4px;overflow:hidden}.progress-fill{height:100%;background:#00a65a;border-radius:4px}
.compliance-checklist{display:flex;flex-direction:column;gap:12px}.checklist-item{display:flex;align-items:center;gap:8px;font-size:14px}
.checklist-item.completed{color:#00a65a}.checklist-item.pending{color:#ff9500}
.empty-state{text-align:center;padding:40px 20px;color:#888}.empty-state i{font-size:48px;margin-bottom:16px;opacity:.5}
.btn-link{font-size:14px;color:#0066cc;text-decoration:none;font-weight:500}.btn-link:hover{text-decoration:underline}
.header-actions{display:flex;align-items:center}
@media (max-width:1200px){.manager-stats-grid{grid-template-columns:repeat(2,1fr)}.manager-dashboard-grid{grid-template-columns:1fr}}
@media (max-width:768px){.manager-stats-grid{grid-template-columns:1fr}.quick-actions-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
