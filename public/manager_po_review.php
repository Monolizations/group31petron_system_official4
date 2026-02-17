<?php
$page_id = 'review_po';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();
$role = strtolower(trim($me['role'] ?? 'staff'));

// Only managers, admins, and superadmins can access
if (!in_array($role, ['manager', 'admin', 'superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';
$msg_type = 'success';

// Helper functions
function log_po_activity($pdo, $po_id, $action, $user_id, $details = '') {
    $stmt = $pdo->prepare("INSERT INTO po_activity_log (po_id, action, performed_by, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$po_id, $action, $user_id, $details]);
}

function create_notification($pdo, $user_id, $type, $title, $message, $link = '') {
    $stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $type, $title, $message, $link]);
}

// Handle actions
$action = $_GET['action'] ?? '';
$po_id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    $po_id = $_POST['po_id'] ?? null;
    
    if ($post_action === 'approve' && $po_id) {
        try {
            // Get PO details
            $stmt = $pdo->prepare("SELECT po.*, u.name as created_by_name FROM purchase_orders po JOIN users u ON po.created_by = u.id WHERE po.id = ? AND po.station_id = ?");
            $stmt->execute([$po_id, $station_id]);
            $po = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($po && $po['status'] === 'Pending Approval') {
                $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$me['id'], $po_id]);
                
                log_po_activity($pdo, $po_id, 'Approved', $me['id'], 'PO approved by manager');
                
                // Notify staff
                create_notification($pdo, $po['created_by'], 'po_approved', 'PO Approved', 
                    "Your Purchase Order {$po['po_number']} has been approved.", 
                    "view_po.php?id=$po_id");
                
                $msg = "✅ Purchase Order {$po['po_number']} approved successfully.";
                
                // Redirect to print page if requested
                if ($_POST['print_after'] == '1') {
                    header("Location: print_po.php?id=$po_id&approved=1");
                    exit;
                }
            }
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
    
    if ($post_action === 'reject' && $po_id) {
        $reason = trim($_POST['rejection_reason'] ?? '');
        
        if (empty($reason)) {
            $msg = "❌ Please provide a reason for rejection.";
            $msg_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT po.*, u.name as created_by_name FROM purchase_orders po JOIN users u ON po.created_by = u.id WHERE po.id = ? AND po.station_id = ?");
                $stmt->execute([$po_id, $station_id]);
                $po = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($po && $po['status'] === 'Pending Approval') {
                    $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->execute([$reason, $me['id'], $po_id]);
                    
                    log_po_activity($pdo, $po_id, 'Rejected', $me['id'], 'Reason: ' . $reason);
                    
                    // Notify staff
                    create_notification($pdo, $po['created_by'], 'po_rejected', 'PO Rejected', 
                        "Your Purchase Order {$po['po_number']} has been rejected. Reason: $reason", 
                        "purchase_order.php?id=$po_id");
                    
                    $msg = "✅ Purchase Order {$po['po_number']} rejected.";
                }
            } catch (Exception $e) {
                $msg = "❌ Error: " . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }
}

// Get pending POs count for badge
$pending_count = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE status = 'Pending Approval' AND station_id = ?");
$pending_count->execute([$station_id]);
$pending_badge = $pending_count->fetchColumn();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'Pending Approval';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT po.*, u.name as created_by_name, s.name as supplier_name,
           approver.name as approved_by_name,
           COUNT(poi.id) as item_count,
           SUM(poi.total_price) as total_amount
    FROM purchase_orders po
    JOIN users u ON po.created_by = u.id
    JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN users approver ON po.approved_by = approver.id
    LEFT JOIN purchase_order_items poi ON po.id = poi.po_id
    WHERE po.station_id = ?
";
$params = [$station_id];

if ($status_filter) {
    $query .= " AND po.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $query .= " AND DATE(po.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(po.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $query .= " AND (po.po_number LIKE ? OR u.name LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " GROUP BY po.id ORDER BY 
    CASE po.status 
        WHEN 'Pending Approval' THEN 1 
        WHEN 'Draft' THEN 2 
        WHEN 'Approved' THEN 3 
        ELSE 4 
    END, 
    po.submitted_at DESC, po.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status badges
$status_badges = [
    'Draft' => ['color' => '#6c757d', 'bg' => '#e9ecef'],
    'Pending Approval' => ['color' => '#fd7e14', 'bg' => '#fff3cd'],
    'Approved' => ['color' => '#198754', 'bg' => '#d1fae5'],
    'Rejected' => ['color' => '#dc3545', 'bg' => '#f8d7da'],
    'Pending' => ['color' => '#0dcaf0', 'bg' => '#cff4fc'],
    'Confirmed' => ['color' => '#6610f2', 'bg' => '#e0cffc'],
    'Received' => ['color' => '#198754', 'bg' => '#d1fae5'],
    'Cancelled' => ['color' => '#dc3545', 'bg' => '#f8d7da']
];

include __DIR__ . '/../partials/header.php';
?>

<style>
.filter-bar {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: end;
}
.po-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: box-shadow 0.2s;
}
.po-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.po-card.pending {
    border-left: 4px solid #fd7e14;
}
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
}
.action-btns {
    display: flex;
    gap: 8px;
}
.btn-approve {
    background: #198754;
    color: white;
}
.btn-reject {
    background: #dc3545;
    color: white;
}
.btn-view {
    background: #0dcaf0;
    color: #212529;
}
.btn-print {
    background: #6c757d;
    color: white;
}
</style>

<div class="page-head">
    <div>
        <h1 class="h1">Review Purchase Orders</h1>
        <div class="sub">Approve or reject staff purchase orders</div>
    </div>
    <?php if ($pending_badge > 0): ?>
    <div style="background: #dc3545; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold;">
        <i class="fas fa-bell"></i> <?php echo $pending_badge; ?> Pending Approval
    </div>
    <?php endif; ?>
</div>

<?php if($msg): ?>
<div class="card" style="padding:15px; margin-bottom:20px; background:<?php echo $msg_type === 'error' ? '#f8d7da' : '#e6f4ea'; ?>; color:<?php echo $msg_type === 'error' ? '#721c24' : '#155724'; ?>;">
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="filter-bar">
    <div>
        <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 12px;">Status</label>
        <select class="select" onchange="window.location.href='manager_po_review.php?status='+this.value+'&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>'">
            <option value="">All Statuses</option>
            <option value="Pending Approval" <?php echo $status_filter == 'Pending Approval' ? 'selected' : ''; ?>>Pending Approval</option>
            <option value="Draft" <?php echo $status_filter == 'Draft' ? 'selected' : ''; ?>>Draft</option>
            <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending with Supplier</option>
        </select>
    </div>
    
    <div>
        <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 12px;">From Date</label>
        <input type="date" class="input" value="<?php echo $date_from; ?>" onchange="window.location.href='manager_po_review.php?status=<?php echo $status_filter; ?>&date_from='+this.value+'&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>'">
    </div>
    
    <div>
        <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 12px;">To Date</label>
        <input type="date" class="input" value="<?php echo $date_to; ?>" onchange="window.location.href='manager_po_review.php?status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to='+this.value+'&search=<?php echo urlencode($search); ?>'">
    </div>
    
    <div style="flex: 1; min-width: 200px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 12px;">Search</label>
        <div style="display: flex; gap: 5px;">
            <input type="text" class="input" id="searchInput" placeholder="PO #, Staff, Supplier..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn primary" onclick="doSearch()">Search</button>
        </div>
    </div>
</div>

<!-- PO List -->
<?php if (empty($pos)): ?>
<div class="card" style="padding: 40px; text-align: center;">
    <i class="fas fa-inbox" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
    <h3>No Purchase Orders Found</h3>
    <p style="color: #6c757d;">There are no purchase orders matching your criteria.</p>
</div>
<?php else: ?>
    <?php foreach ($pos as $po): 
        $is_pending = $po['status'] === 'Pending Approval';
    ?>
    <div class="po-card <?php echo $is_pending ? 'pending' : ''; ?>">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
            <div>
                <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                    <?php echo htmlspecialchars($po['po_number']); ?>
                    <span class="status-badge" style="background: <?php echo $status_badges[$po['status']]['bg']; ?>; color: <?php echo $status_badges[$po['status']]['color']; ?>; margin-left: 10px;">
                        <?php echo $po['status']; ?>
                    </span>
                </div>
                <div style="color: #6c757d; font-size: 14px;">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($po['created_by_name']); ?> &nbsp;|&nbsp;
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($po['supplier_name']); ?> &nbsp;|&nbsp;
                    <i class="fas fa-calendar"></i> <?php echo date('M d, Y g:i A', strtotime($po['created_at'])); ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 24px; font-weight: bold; color: #198754;">
                    ₱<?php echo number_format($po['total_amount'] ?? 0, 2); ?>
                </div>
                <div style="color: #6c757d; font-size: 12px;">
                    <?php echo $po['item_count']; ?> item(s)
                </div>
            </div>
        </div>
        
        <?php if ($po['status'] === 'Rejected'): ?>
        <div style="background: #f8d7da; border-left: 3px solid #dc3545; padding: 10px; margin: 10px 0; border-radius: 4px;">
            <strong>Rejection Reason:</strong> <?php echo nl2br(htmlspecialchars($po['rejection_reason'])); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($po['status'] === 'Approved'): ?>
        <div style="background: #d1fae5; border-left: 3px solid #198754; padding: 10px; margin: 10px 0; border-radius: 4px;">
            <strong>Approved by:</strong> <?php echo htmlspecialchars($po['approved_by_name']); ?> on <?php echo date('M d, Y g:i A', strtotime($po['approved_at'])); ?>
        </div>
        <?php endif; ?>
        
        <div class="action-btns" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
            <a href="view_po.php?id=<?php echo $po['id']; ?>" class="btn btn-view small">
                <i class="fas fa-eye"></i> View Details
            </a>
            
            <?php if ($po['status'] === 'Approved'): ?>
            <a href="print_po.php?id=<?php echo $po['id']; ?>" target="_blank" class="btn btn-print small">
                <i class="fas fa-print"></i> Print PO
            </a>
            <?php endif; ?>
            
            <?php if ($is_pending): ?>
            <button type="button" class="btn btn-approve small" onclick="showApproveModal(<?php echo $po['id']; ?>, '<?php echo htmlspecialchars($po['po_number']); ?>')">
                <i class="fas fa-check"></i> Approve
            </button>
            <button type="button" class="btn btn-reject small" onclick="showRejectModal(<?php echo $po['id']; ?>, '<?php echo htmlspecialchars($po['po_number']); ?>')">
                <i class="fas fa-times"></i> Reject
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Approve Modal -->
<div id="approveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
        <h3 style="margin-top: 0;"><i class="fas fa-check-circle" style="color: #198754;"></i> Approve Purchase Order</h3>
        <p>Are you sure you want to approve <strong id="approvePoNumber"></strong>?</p>
        <p style="background: #fff3cd; padding: 10px; border-radius: 4px; font-size: 14px;">
            <i class="fas fa-info-circle"></i> After approval, you can print the PO immediately or later from the View PO page.
        </p>
        <form method="post" style="margin-top: 20px;">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="po_id" id="approvePoId">
            <label style="display: flex; align-items: center; margin-bottom: 15px; cursor: pointer;">
                <input type="checkbox" name="print_after" value="1" style="margin-right: 8px;">
                <span>Print PO immediately after approval</span>
            </label>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" class="btn btn-approve"><i class="fas fa-check"></i> Approve PO</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
        <h3 style="margin-top: 0; color: #dc3545;"><i class="fas fa-times-circle"></i> Reject Purchase Order</h3>
        <p>You are about to reject <strong id="rejectPoNumber"></strong>.</p>
        <form method="post">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="po_id" id="rejectPoId">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Reason for Rejection *</label>
                <textarea name="rejection_reason" id="rejectReason" rows="4" class="input" placeholder="Please explain why this PO is being rejected..." required style="width: 100%;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-reject"><i class="fas fa-times"></i> Reject PO</button>
            </div>
        </form>
    </div>
</div>

<script>
function doSearch() {
    const search = document.getElementById('searchInput').value;
    window.location.href = 'manager_po_review.php?status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=' + encodeURIComponent(search);
}

function showApproveModal(id, poNumber) {
    document.getElementById('approvePoId').value = id;
    document.getElementById('approvePoNumber').textContent = poNumber;
    document.getElementById('approveModal').style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
}

function showRejectModal(id, poNumber) {
    document.getElementById('rejectPoId').value = id;
    document.getElementById('rejectPoNumber').textContent = poNumber;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.id === 'approveModal') closeApproveModal();
    if (event.target.id === 'rejectModal') closeRejectModal();
}

// Enter key for search
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') doSearch();
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>