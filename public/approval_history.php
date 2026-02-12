<?php
/**
 * APPROVAL HISTORY
 * 
 * Displays historical record of all approved/rejected transactions
 * Accessible to: Manager, Admin, Super Admin
 * Purpose: Audit trail and transparency of approval decisions
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'approval_history';
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
    <h1 class="h1">Approval History</h1>
    <div class="sub">This page is part of the Approval History module.</div>
  </div>
</div>
<?php
$view = $_GET['view'] ?? 'all';
?>
<section class="card">
  <div class="card-head">
    <div class="card-title"><i class="fas fa-history"></i> Approval History</div>
    <div class="muted">Approved / rejected items overview</div>
  </div>
  <div style="padding:16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
      <a class="btn ghost" href="approval_history.php">All</a>
      <a class="btn ghost" href="approval_history.php?view=recent">Recent</a>
    </div>
    <div class="muted">Connect this to your approvals table/logs when ready.</div>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
