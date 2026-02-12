<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'staff_management';
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
    <h1 class="h1">Staff Management</h1>
    <div class="sub">This page is part of the Staff Management module.</div>
  </div>
</div>
<?php
$view = $_GET['view'] ?? 'active';
$station_id = user_station_id();
?>
<section class="card">
  <div class="card-head">
    <div class="card-title"><i class="fas fa-users"></i> Staff Management</div>
    <div class="muted">Station staff overview</div>
  </div>
  <div style="padding:16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
      <a class="btn ghost" href="staff_management.php?view=active">Active Staff</a>
      <a class="btn ghost" href="staff_management.php?view=schedule">Shift Schedule</a>
      <a class="btn ghost" href="staff_management.php?view=tasks">Task Assignments</a>
      <a class="btn ghost" href="staff_management.php?view=productivity">Productivity</a>
      <a class="btn ghost" href="staff_management.php?view=qc">Quality Control</a>
      <a class="btn ghost" href="staff_management.php?view=compliance">Compliance</a>
    </div>

    <?php if($view === 'active'): ?>
      <?php
        $rows = [];
        try {
          $stmt = $pdo->prepare("SELECT id, name, username, role, status FROM users WHERE station_id = ? AND role = 'staff' ORDER BY name");
          $stmt->execute([$station_id]);
          $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {}
      ?>
      <div style="overflow:auto;">
      <table class="table" style="width:100%; border-collapse:collapse;">
        <thead><tr>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Name</th>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Username</th>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Status</th>
        </tr></thead>
        <tbody>
          <?php if(empty($rows)): ?>
            <tr><td colspan="3" style="padding:12px;" class="muted">No staff found (or users table not available).</td></tr>
          <?php else: foreach($rows as $r): ?>
            <tr>
              <td style="padding:8px; border-bottom:1px solid #f3f3f3;"><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
              <td style="padding:8px; border-bottom:1px solid #f3f3f3;"><?php echo htmlspecialchars($r['username'] ?? ''); ?></td>
              <td style="padding:8px; border-bottom:1px solid #f3f3f3;"><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    <?php else: ?>
      <div class="muted">This view is a placeholder: <?php echo htmlspecialchars($view); ?>.</div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
