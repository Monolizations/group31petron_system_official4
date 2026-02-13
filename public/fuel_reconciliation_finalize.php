<?php
$page_id = 'fuel_reconciliation_finalize';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/db_connect.php';
require_login();

$me = current_user();
$role = role_key($me['role'] ?? '');
$station_id = user_station_id();

// Manager/Admin/SuperAdmin can finalize
if (!in_array($role, ['manager', 'admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';

// Handle reconciliation finalization with password lock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'finalize_reconciliation') {
        $recon_id = (int)($_POST['recon_id'] ?? 0);
        $physical_stock = (float)($_POST['physical_stock'] ?? 0);
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        try {
            // Verify password
            if (empty($admin_password)) {
                $msg = "❌ Admin password required to finalize reconciliation.";
            } elseif (!password_verify($admin_password, $me['password'] ?? '')) {
                $msg = "❌ Incorrect password. Reconciliation not finalized.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM fuel_reconciliation WHERE id=?");
                $stmt->execute([$recon_id]);
                $recon = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$recon) {
                    $msg = "❌ Reconciliation record not found.";
                } elseif ($recon['status'] !== 'approved') {
                    $msg = "❌ Reconciliation must be approved by manager first.";
                } else {
                    // Calculate variance
                    $system_stock = $recon['present_reading'] - $recon['previous_reading'] - ($recon['calibration_adjustment'] ?? 0);
                    $variance = $physical_stock - $system_stock;
                    $variance_percent = $system_stock != 0 ? ($variance / $system_stock) * 100 : 0;
                    
                    // Finalize with password lock
                    $update_stmt = $pdo->prepare("
                        UPDATE fuel_reconciliation 
                        SET status='finalized', physical_stock=?, variance=?, variance_percent=?, 
                            admin_notes=?, finalized_by=?, finalized_at=NOW(), is_locked=1
                        WHERE id=?
                    ");
                    $update_stmt->execute([$physical_stock, $variance, $variance_percent, $admin_notes, (int)$me['id'], $recon_id]);
                    
                    log_activity($pdo, $me['id'], 'Finalize Fuel Reconciliation', 
                        "Reconciliation #{$recon_id} | Physical Stock: $physical_stock L | System Stock: $system_stock L | Variance: $variance L ($variance_percent%) | LOCKED");
                    
                    $msg = "✅ Reconciliation finalized and locked! Report ready for export.";
                }
            }
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
    }
}

// Fetch approved reconciliation records ready for finalization
$reconciliations = [];
try {
    if ($role === 'superadmin') {
        $stmt = $pdo->query("
            SELECT fr.*, s.name as station_name, p.name as product_name, p.price,
                   u1.name as created_by_name, u2.name as validated_by_name
            FROM fuel_reconciliation fr
            LEFT JOIN stations s ON fr.station_id = s.id
            LEFT JOIN products p ON fr.product_id = p.id
            LEFT JOIN users u1 ON fr.created_by = u1.id
            LEFT JOIN users u2 ON fr.validated_by = u2.id
            WHERE fr.status IN ('approved', 'finalized')
            ORDER BY fr.reconciliation_date DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT fr.*, s.name as station_name, p.name as product_name, p.price,
                   u1.name as created_by_name, u2.name as validated_by_name
            FROM fuel_reconciliation fr
            LEFT JOIN stations s ON fr.station_id = s.id
            LEFT JOIN products p ON fr.product_id = p.id
            LEFT JOIN users u1 ON fr.created_by = u1.id
            LEFT JOIN users u2 ON fr.validated_by = u2.id
            WHERE fr.station_id = ? AND fr.status IN ('approved', 'finalized')
            ORDER BY fr.reconciliation_date DESC
        ");
        $stmt->execute([$station_id]);
    }
    $reconciliations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reconciliations = [];
}

// Handle export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $recon_id = (int)($_GET['id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("
            SELECT fr.*, s.name as station_name, p.name as product_name, p.price,
                   u1.name as created_by_name, u2.name as validated_by_name, u3.name as finalized_by_name
            FROM fuel_reconciliation fr
            LEFT JOIN stations s ON fr.station_id = s.id
            LEFT JOIN products p ON fr.product_id = p.id
            LEFT JOIN users u1 ON fr.created_by = u1.id
            LEFT JOIN users u2 ON fr.validated_by = u2.id
            LEFT JOIN users u3 ON fr.finalized_by = u3.id
            WHERE fr.id=? AND fr.status='finalized'
        ");
        $stmt->execute([$recon_id]);
        $recon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($recon) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="Fuel_Reconciliation_' . $recon_id . '_' . date('Ymd') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            fputcsv($output, ['FUEL RECONCILIATION REPORT']);
            fputcsv($output, ['Station', 'Product', 'Date', 'Status']);
            fputcsv($output, [$recon['station_name'], $recon['product_name'], $recon['reconciliation_date'], strtoupper($recon['status'])]);
            fputcsv($output, []);
            
            fputcsv($output, ['READINGS & CALCULATIONS']);
            fputcsv($output, ['Present Reading (L)', number_format($recon['present_reading'], 2)]);
            fputcsv($output, ['Previous Reading (L)', number_format($recon['previous_reading'], 2)]);
            fputcsv($output, ['Raw Difference (L)', number_format($recon['present_reading'] - $recon['previous_reading'], 2)]);
            fputcsv($output, ['Calibration Adjustment (L)', number_format($recon['calibration_adjustment'], 2)]);
            fputcsv($output, ['System Stock (L)', number_format($recon['present_reading'] - $recon['previous_reading'] - $recon['calibration_adjustment'], 2)]);
            fputcsv($output, ['Physical Stock Observed (L)', number_format($recon['physical_stock'], 2)]);
            fputcsv($output, []);
            
            fputcsv($output, ['VARIANCE ANALYSIS']);
            fputcsv($output, ['Variance (L)', number_format($recon['variance'], 2)]);
            fputcsv($output, ['Variance (%)', number_format($recon['variance_percent'], 2) . '%']);
            fputcsv($output, ['Unit Price (₱/L)', number_format($recon['price'], 2)]);
            fputcsv($output, ['Variance Value (₱)', number_format($recon['variance'] * $recon['price'], 2)]);
            fputcsv($output, []);
            
            fputcsv($output, ['APPROVALS & SIGN-OFF']);
            fputcsv($output, ['Created By', $recon['created_by_name']]);
            fputcsv($output, ['Created Date', $recon['reconciliation_date']]);
            fputcsv($output, ['Validated By', $recon['validated_by_name']]);
            fputcsv($output, ['Validated Date', $recon['reconciliation_date']]);
            fputcsv($output, ['Finalized By', $recon['finalized_by_name']]);
            fputcsv($output, ['Finalized Date', $recon['finalized_at']]);
            fputcsv($output, ['Report Status', 'LOCKED - No Changes Allowed']);
            
            fclose($output);
            exit;
        }
    } catch (Exception $e) {
        // Fall through to show error
    }
}

include __DIR__ . '/../partials/header.php';
?>

<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #f8fafc; }
  
  .ff-wrapper { max-width: 1200px; margin: 0 auto; padding: 24px; }
  
  .ff-header { 
    background: linear-gradient(135deg, #c026d3 0%, #a21caf 100%); 
    color: white; padding: 40px 32px; border-radius: 16px; margin-bottom: 32px; 
    box-shadow: 0 8px 24px rgba(192,38,211,0.3); 
  }
  .ff-header-content { display: flex; align-items: center; gap: 16px; }
  .ff-header-icon { font-size: 42px; }
  .ff-header h1 { font-size: 32px; font-weight: 700; margin-bottom: 6px; }
  .ff-header p { font-size: 14px; opacity: 0.85; }
  
  .ff-alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; }
  .ff-alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
  .ff-alert-error { background: #fee2e2; color: #7f1d1d; border: 1px solid #fecaca; }
  
  .ff-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px; }
  
  .ff-card { 
    background: white; border-radius: 12px; padding: 24px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-top: 4px solid #c026d3; 
  }
  .ff-card.locked { border-top: 4px solid #10b981; }
  .ff-card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px; }
  .ff-card-title { font-size: 18px; font-weight: 700; color: #0f172a; }
  .ff-card-badge { 
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; 
  }
  .ff-card-badge-approved { background: #bfdbfe; color: #1e3a8a; }
  .ff-card-badge-finalized { background: #bbf7d0; color: #065f46; }
  
  .ff-card-body { display: grid; gap: 12px; }
  .ff-info { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
  .ff-info-label { color: #64748b; font-size: 13px; font-weight: 600; }
  .ff-info-value { color: #0f172a; font-weight: 500; }
  
  .ff-calc-box { background: #f3e8ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 12px; margin: 12px 0; font-size: 12px; }
  .ff-calc-label { color: #7c3aed; font-weight: 600; margin-bottom: 6px; }
  .ff-calc-line { color: #7c3aed; margin: 4px 0; }
  .ff-calc-result { color: #6d28d9; font-weight: 700; margin-top: 6px; border-top: 1px solid #e9d5ff; padding-top: 6px; }
  
  .ff-form { display: grid; gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 2px solid #f1f5f9; }
  .ff-form label { font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px; }
  .ff-form input, .ff-form textarea { 
    width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; 
    border-radius: 8px; font-size: 13px; font-family: inherit;
  }
  .ff-form textarea { resize: none; height: 70px; }
  .ff-form input:focus, .ff-form textarea:focus { outline: none; border-color: #c026d3; box-shadow: 0 0 0 3px rgba(192,38,211,0.1); }
  
  .ff-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
  .ff-btn { 
    padding: 10px 16px; border: 0; border-radius: 8px; cursor: pointer; 
    font-weight: 600; font-size: 13px;
  }
  .ff-btn-finalize { background: #c026d3; color: white; }
  .ff-btn-finalize:hover { background: #a21caf; }
  .ff-btn-export { background: #10b981; color: white; }
  .ff-btn-export:hover { background: #059669; }
  
  .ff-lock-notice { 
    background: #bbf7d0; border: 2px solid #10b981; border-radius: 8px; 
    padding: 12px; margin-top: 12px; font-size: 12px; color: #065f46; font-weight: 600;
  }
  
  .ff-empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
</style>

<div class="ff-wrapper">
  <div class="ff-header">
    <div class="ff-header-content">
      <div class="ff-header-icon">🔐</div>
      <div>
        <h1>Fuel Reconciliation - Finalize & Lock</h1>
        <p>Admin/Owner - Encode physical stock, finalize report with password lock</p>
      </div>
    </div>
  </div>
  
  <?php if($msg): ?>
    <div class="ff-alert <?php echo strpos($msg, '✅') !== false ? 'ff-alert-success' : 'ff-alert-error'; ?>">
      <i class="fas <?php echo strpos($msg, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
      <?php echo htmlspecialchars($msg); ?>
    </div>
  <?php endif; ?>
  
  <?php if(empty($reconciliations)): ?>
    <div class="ff-empty">
      <div style="font-size: 48px; margin-bottom: 12px;">✓</div>
      <div style="font-size: 16px; font-weight: 500;">No reconciliations to finalize</div>
      <div style="font-size: 13px; margin-top: 6px; opacity: 0.7;">Waiting for manager validation before finalization.</div>
    </div>
  <?php else: ?>
    <div class="ff-cards">
      <?php foreach($reconciliations as $rec): ?>
        <div class="ff-card <?php echo $rec['status'] === 'finalized' ? 'locked' : ''; ?>">
          <div class="ff-card-header">
            <div>
              <div class="ff-card-title"><?php echo htmlspecialchars($rec['product_name'] ?? 'Fuel'); ?></div>
              <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;"><?php echo htmlspecialchars($rec['station_name']); ?></div>
            </div>
            <div class="ff-card-badge ff-card-badge-<?php echo $rec['status']; ?>">
              <?php echo $rec['status'] === 'finalized' ? '🔒 LOCKED' : 'APPROVED'; ?>
            </div>
          </div>
          
          <div class="ff-card-body">
            <div class="ff-info">
              <div class="ff-info-label">Date</div>
              <div class="ff-info-value"><?php echo date('M d, Y', strtotime($rec['reconciliation_date'])); ?></div>
            </div>
            
            <!-- Variance Display -->
            <div class="ff-calc-box">
              <div class="ff-calc-label">📊 Calculated Variance:</div>
              <div class="ff-calc-line">System Stock: <?php echo number_format($rec['present_reading'] - $rec['previous_reading'] - $rec['calibration_adjustment'], 2); ?> L</div>
              <div class="ff-calc-line">Physical Stock: <?php echo $rec['physical_stock'] ? number_format($rec['physical_stock'], 2) : 'Not encoded'; ?> L</div>
              <?php if($rec['physical_stock']): ?>
              <div class="ff-calc-result">Variance: <?php echo number_format($rec['variance'], 2); ?> L (<?php echo number_format($rec['variance_percent'], 2); ?>%)</div>
              <div class="ff-calc-line">Variance Value: ₱<?php echo number_format($rec['variance'] * ($rec['price'] ?? 0), 2); ?></div>
              <?php endif; ?>
            </div>
            
            <div class="ff-info">
              <div class="ff-info-label">Manager Notes</div>
              <div class="ff-info-value" style="font-size: 12px;"><?php echo htmlspecialchars($rec['manager_notes'] ?? '-'); ?></div>
            </div>
            
            <?php if($rec['status'] === 'finalized'): ?>
              <!-- Finalized Display -->
              <div class="ff-lock-notice">
                ✓ FINALIZED & LOCKED on <?php echo date('M d, Y H:i', strtotime($rec['finalized_at'])); ?>
                by <?php echo htmlspecialchars($rec['finalized_by_name']); ?>
              </div>
              
              <div class="ff-info">
                <div class="ff-info-label">Admin Notes</div>
                <div class="ff-info-value" style="font-size: 12px;"><?php echo htmlspecialchars($rec['admin_notes'] ?? '-'); ?></div>
              </div>
              
              <div style="display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 12px;">
                <a href="?export=csv&id=<?php echo $rec['id']; ?>" class="ff-btn ff-btn-export" style="text-align: center; text-decoration: none;">
                  <i class="fas fa-download"></i> Export to CSV
                </a>
              </div>
            <?php else: ?>
              <!-- Finalization Form -->
              <form method="post" class="ff-form">
                <input type="hidden" name="action" value="finalize_reconciliation">
                <input type="hidden" name="recon_id" value="<?php echo $rec['id']; ?>">
                
                <div>
                  <label>Physical Stock from Gauge (liters) *</label>
                  <input type="number" name="physical_stock" placeholder="Enter physical reading from fuel gauge" step="0.01" required>
                  <small style="color: #94a3b8; margin-top: 4px; display: block;">This is what you measure physically at the tank</small>
                </div>
                
                <div>
                  <label>Admin Password (for report lock) *</label>
                  <input type="password" name="admin_password" placeholder="Enter your password to finalize" required>
                  <small style="color: #94a3b8; margin-top: 4px; display: block;">Required to lock report and prevent changes</small>
                </div>
                
                <div>
                  <label>Admin Sign-Off Notes</label>
                  <textarea name="admin_notes" placeholder="e.g., Reconciliation verified and approved. All readings validated."></textarea>
                </div>
                
                <div class="ff-actions">
                  <button type="reset" class="ff-btn" style="background: #f3f4f6; color: #374151;">
                    Clear
                  </button>
                  <button type="submit" class="ff-btn ff-btn-finalize">
                    <i class="fas fa-lock"></i> Finalize & Lock Report
                  </button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <div style="margin-top: 40px; padding: 20px; background: #fce7f3; border-left: 4px solid #c026d3; border-radius: 8px;">
    <strong style="color: #9d174d;">🔐 Admin Finalization Flow:</strong>
    <ul style="margin-top: 8px; margin-left: 20px; color: #9d174d; font-size: 13px; line-height: 1.8;">
      <li>✓ Wait for manager to validate and approve reconciliation</li>
      <li>🔍 Encode physical stock reading from fuel gauge</li>
      <li>📊 System automatically calculates variance (physical vs system)</li>
      <li>🔒 Enter admin password to lock report</li>
      <li>✅ Finalize reconciliation report</li>
      <li>📥 Export to CSV for records/archive</li>
      <li>🔐 Report is now LOCKED - no changes allowed</li>
    </ul>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
