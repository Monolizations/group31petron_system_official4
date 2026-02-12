<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'manager_job_analytics';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();
$me = current_user();

$role = role_key($me['role'] ?? 'staff');
if (!in_array($role, ['manager','admin','superadmin'])) { header("Location: dashboard.php"); exit; }

include __DIR__ . '/../partials/header.php';
?>
<div class="page-head">
  <div>
    <h1 class="h1">Job Order Analytics</h1>
    <div class="sub">This page is part of the Job Order Analytics module.</div>
  </div>
</div>
<?php
$view = $_GET['view'] ?? 'service_breakdown';
$views = [
  'service_breakdown' => 'Service Type Breakdown',
  'staff_performance' => 'Staff Performance on Jobs',
  'completion_time'   => 'Completion Time Reports'
];
$label = $views[$view] ?? 'Job Order Analytics';
?>
<section class="card">
  <div class="card-head">
    <div class="card-title"><i class="fas fa-chart-pie"></i> <?php echo htmlspecialchars($label); ?></div>
    <div class="muted">Basic analytics (can be expanded). Use filters above your tables in Job Orders.</div>
  </div>
  <div style="padding:16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
      <a class="btn ghost" href="manager_job_analytics.php?view=service_breakdown">Service Breakdown</a>
      <a class="btn ghost" href="manager_job_analytics.php?view=staff_performance">Staff Performance</a>
      <a class="btn ghost" href="manager_job_analytics.php?view=completion_time">Completion Time</a>
      <a class="btn" href="joborder_stats.php"><i class="fas fa-arrow-right"></i> Open Detailed Analytics</a>
    </div>

    <div class="muted">If your database tables differ, connect this to your real reports page (joborder_stats.php).</div>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
