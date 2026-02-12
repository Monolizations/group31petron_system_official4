<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'manager_dashboard';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$rk = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? ''));
if ($rk !== 'manager') { header('Location: dashboard.php'); exit; }

$station_id = user_station_id();

// KPIs
$kpi = [
  'pending_job_orders' => 0,
  'pending_reports' => 0,
  'fuel_variances' => 0,
];

// Pending job orders for review
$pending_job_rows = [];
try {
  $stmt = $pdo->prepare("SELECT j.*, u.name AS staff_name FROM job_orders j LEFT JOIN users u ON u.id = j.user_id WHERE j.station_id = ? AND j.status IN ('Pending','PENDING_REVIEW','Pending Review','Pending_Review') ORDER BY j.created_at DESC LIMIT 10");
  $stmt->execute([$station_id]);
  $pending_job_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $kpi['pending_job_orders'] = count($pending_job_rows);
} catch(Exception $e) {}

// Pending reports approvals (try approvals table; fall back to activity_logs)
$pending_report_rows = [];
try {
  $stmt = $pdo->prepare("SELECT * FROM approvals WHERE status = 'pending' AND (station_id = ? OR station_id IS NULL) ORDER BY created_at DESC LIMIT 10");
  $stmt->execute([$station_id]);
  $pending_report_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $kpi['pending_reports'] = count($pending_report_rows);
} catch(Exception $e) {
  try {
    $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE station_id = ? AND action LIKE '%Report%' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$station_id]);
    $pending_report_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $kpi['pending_reports'] = count($pending_report_rows);
  } catch(Exception $e2) {}
}

// Fuel variance summary (JSON fallback)
$fuel_readings = read_json('fuel_readings.json', []);
foreach($fuel_readings as $fr){
  if(($fr['station_id'] ?? null) != $station_id) continue;
  $v = (float)($fr['variance_liters'] ?? 0);
  if($v != 0) $kpi['fuel_variances']++;
}

// Chart data: Job Orders per Service (top 6)
$service_counts = [];
try {
  $stmt = $pdo->prepare("SELECT service_type, COUNT(*) c FROM job_orders WHERE station_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY service_type ORDER BY c DESC LIMIT 6");
  $stmt->execute([$station_id]);
  $service_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Chart data: Fuel variance last 7 days (from JSON)
$variance_by_day = [];
for($i=6;$i>=0;$i--){
  $d = date('Y-m-d', strtotime("-$i day"));
  $variance_by_day[$d] = 0;
}
foreach($fuel_readings as $fr){
  if(($fr['station_id'] ?? null) != $station_id) continue;
  $d = $fr['date'] ?? $fr['reading_date'] ?? null;
  if(!$d) continue;
  $d = substr($d,0,10);
  if(!isset($variance_by_day[$d])) continue;
  $variance_by_day[$d] += abs((float)($fr['variance_liters'] ?? 0));
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
  <div>
    <h1 class="h1">Manager Dashboard</h1>
    <div class="sub">Review queue • Variance alerts • Approvals</div>
  </div>
  <div class="header-actions">
    <span class="badge status-active" style="margin-right: 15px;"><i class="fas fa-clock"></i> <?php echo date('g:i A'); ?></span>
    <button class="btn btn-outline" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
  </div>
</div>

<div class="manager-stats-grid">
  <div class="stat-card">
    <div class="stat-icon bg-warning"><i class="fas fa-clipboard-check"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$kpi['pending_job_orders']; ?></div>
      <div class="stat-label">Pending Job Orders</div>
      <div class="stat-sub">For review</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-info"><i class="fas fa-file-signature"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$kpi['pending_reports']; ?></div>
      <div class="stat-label">Pending Reports</div>
      <div class="stat-sub">Awaiting approval</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-danger"><i class="fas fa-triangle-exclamation"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?php echo (int)$kpi['fuel_variances']; ?></div>
      <div class="stat-label">Fuel Variance Alerts</div>
      <div class="stat-sub">Flagged readings</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon bg-primary"><i class="fas fa-building"></i></div>
    <div class="stat-content">
      <div class="stat-value">#<?php echo (int)$station_id; ?></div>
      <div class="stat-label">Station</div>
      <div class="stat-sub"><?php echo htmlspecialchars($me['name'] ?? ''); ?></div>
    </div>
  </div>
</div>

<div class="manager-dashboard-grid">
  <div class="dashboard-column">
    <div class="dashboard-card">
      <div class="card-header">
        <h3><i class="fas fa-chart-bar text-primary"></i> Job Orders per Service (30 days)</h3>
      </div>
      <div class="card-body">
        <?php if(empty($service_counts)): ?>
          <div class="empty-state"><i class="fas fa-chart-bar"></i><p>No data yet</p></div>
        <?php else: ?>
          <div class="mini-chart">
            <?php
              $max = 1;
              foreach($service_counts as $r){ $max = max($max, (int)$r['c']); }
            ?>
            <?php foreach($service_counts as $r):
              $pct = ((int)$r['c'] / $max) * 100;
            ?>
              <div class="bar-row">
                <div class="bar-label"><?php echo htmlspecialchars($r['service_type'] ?: 'Unknown'); ?></div>
                <div class="bar-track"><div class="bar-fill" style="width: <?php echo (float)$pct; ?>%"></div></div>
                <div class="bar-val"><?php echo (int)$r['c']; ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="dashboard-card">
      <div class="card-header">
        <h3><i class="fas fa-list-check text-warning"></i> Job Orders for Review</h3>
        <a href="joborder.php?tab=pending" class="btn-link">Open Review</a>
      </div>
      <div class="card-body">
        <?php if(empty($pending_job_rows)): ?>
          <div class="empty-state"><i class="fas fa-check-circle text-success"></i><p>No pending job orders</p></div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="table">
              <thead><tr><th>#</th><th>Vehicle</th><th>Service</th><th>Staff</th><th>Date</th><th></th></tr></thead>
              <tbody>
                <?php foreach($pending_job_rows as $j): ?>
                  <tr>
                    <td><?php echo (int)($j['id'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars(($j['vehicle_plate'] ?? $j['plate'] ?? '—')); ?></td>
                    <td><?php echo htmlspecialchars(($j['service_type'] ?? '—')); ?></td>
                    <td><?php echo htmlspecialchars(($j['staff_name'] ?? '—')); ?></td>
                    <td><?php echo !empty($j['created_at']) ? date('M j, H:i', strtotime($j['created_at'])) : '—'; ?></td>
                    <td><a class="btn btn-sm btn-outline" href="joborder.php?tab=pending&id=<?php echo (int)($j['id'] ?? 0); ?>">Review</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="dashboard-column">
    <div class="dashboard-card">
      <div class="card-header">
        <h3><i class="fas fa-gas-pump text-danger"></i> Fuel Variance Summary (7 days)</h3>
        <a href="variance_reports.php" class="btn-link">View Variances</a>
      </div>
      <div class="card-body">
        <div class="mini-chart">
          <?php
            $maxV = 1;
            foreach($variance_by_day as $v){ $maxV = max($maxV, (float)$v); }
          ?>
          <?php foreach($variance_by_day as $d=>$v):
            $pct = ((float)$v / $maxV) * 100;
          ?>
            <div class="bar-row">
              <div class="bar-label"><?php echo date('M j', strtotime($d)); ?></div>
              <div class="bar-track"><div class="bar-fill danger" style="width: <?php echo (float)$pct; ?>%"></div></div>
              <div class="bar-val"><?php echo number_format((float)$v, 2); ?>L</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="dashboard-card">
      <div class="card-header">
        <h3><i class="fas fa-file-signature text-info"></i> Reports Awaiting Approval</h3>
        <a href="approvals.php" class="btn-link">Open Approvals</a>
      </div>
      <div class="card-body">
        <?php if(empty($pending_report_rows)): ?>
          <div class="empty-state"><i class="fas fa-check-circle text-success"></i><p>No pending reports</p></div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="table">
              <thead><tr><th>#</th><th>Type</th><th>Status</th><th>Date</th><th></th></tr></thead>
              <tbody>
                <?php foreach($pending_report_rows as $r): ?>
                  <tr>
                    <td><?php echo (int)($r['id'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars(($r['type'] ?? $r['action'] ?? 'Report')); ?></td>
                    <td><span class="pill pending">Pending</span></td>
                    <td><?php echo !empty($r['created_at']) ? date('M j, H:i', strtotime($r['created_at'])) : '—'; ?></td>
                    <td><a class="btn btn-sm btn-outline" href="approvals.php">Review</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
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
.bg-primary{background:#0066cc}.bg-warning{background:#ff9500}.bg-info{background:#00b4d8}.bg-danger{background:#dc3545}
.stat-value{font-size:28px;font-weight:700;color:#1a1a1a;line-height:1}
.stat-label{font-size:14px;color:#666;margin-top:4px}
.stat-sub{font-size:12px;color:#888;margin-top:2px}
.btn-link{font-size:14px;color:#0066cc;text-decoration:none;font-weight:500}
.btn-link:hover{text-decoration:underline}
.header-actions{display:flex;align-items:center}
.empty-state{text-align:center;padding:24px 10px;color:#888}
.empty-state i{font-size:36px;margin-bottom:10px;opacity:.5}

.mini-chart{display:flex;flex-direction:column;gap:10px}
.bar-row{display:grid;grid-template-columns:140px 1fr 72px;gap:12px;align-items:center}
.bar-label{font-size:13px;color:#444;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-track{height:10px;background:#e9ecef;border-radius:999px;overflow:hidden}
.bar-fill{height:100%;background:#0066cc;border-radius:999px}
.bar-fill.danger{background:#dc3545}
.bar-val{font-size:12px;color:#555;text-align:right}

.table-wrap{overflow:auto}
.table{width:100%;border-collapse:collapse;font-size:14px}
.table th,.table td{padding:10px;border-bottom:1px solid #eef1f4;text-align:left;white-space:nowrap}
.table th{font-size:12px;letter-spacing:.02em;text-transform:uppercase;color:#667085}
.pill{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600}
.pill.pending{background:#fff3cd;color:#856404}

@media (max-width:1200px){.manager-stats-grid{grid-template-columns:repeat(2,1fr)}.manager-dashboard-grid{grid-template-columns:1fr}}
@media (max-width:768px){.manager-stats-grid{grid-template-columns:1fr}.bar-row{grid-template-columns:110px 1fr 60px}}
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
