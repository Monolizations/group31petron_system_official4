<?php
$page_id = 'view_po';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();
$role = strtolower(trim($me['role'] ?? 'staff'));
$isStaff = ($role === 'staff');
$isManager = in_array($role, ['manager', 'admin', 'superadmin']);

$msg = '';
$mode = $_GET['mode'] ?? 'view'; // 'view', 'my'

// Get PO ID if viewing specific PO
$po_id = $_GET['id'] ?? null;
$po = null;
$po_items = [];
$activity_logs = [];

if ($po_id) {
    // Fetch PO details
    $stmt = $pdo->prepare("
        SELECT po.*, s.name as supplier_name, s.contact_person, s.phone as supplier_phone, s.email as supplier_email,
               u.name as created_by_name, u.email as created_by_email,
               approver.name as approved_by_name,
               st.name as station_name
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        JOIN users u ON po.created_by = u.id
        JOIN stations st ON po.station_id = st.id
        LEFT JOIN users approver ON po.approved_by = approver.id
        WHERE po.id = ? AND (po.station_id = ? OR ? = 'superadmin')
    ");
    $stmt->execute([$po_id, $station_id, $role]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) {
        header("Location: view_po.php?mode=my");
        exit;
    }
    
    // Check permissions
    if ($isStaff && $po['created_by'] != $me['id']) {
        header("Location: view_po.php?mode=my");
        exit;
    }
    
    // Fetch items
    $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $stmt->execute([$po_id]);
    $po_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch activity logs
    $stmt = $pdo->prepare("SELECT pal.*, u.name as user_name FROM po_activity_log pal JOIN users u ON pal.performed_by = u.id WHERE pal.po_id = ? ORDER BY pal.created_at DESC");
    $stmt->execute([$po_id]);
    $activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get list of POs
$po_list = [];
if ($mode === 'my' && $isStaff) {
    // Staff sees only their own POs
    $stmt = $pdo->prepare("
        SELECT po.*, s.name as supplier_name,
               COUNT(poi.id) as item_count,
               SUM(poi.total_price) as total_amount
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN purchase_order_items poi ON po.id = poi.po_id
        WHERE po.created_by = ? AND po.station_id = ?
        GROUP BY po.id
        ORDER BY po.created_at DESC
    ");
    $stmt->execute([$me['id'], $station_id]);
    $po_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($isManager) {
    // Managers see all POs for their station
    $stmt = $pdo->prepare("
        SELECT po.*, s.name as supplier_name, u.name as created_by_name,
               COUNT(poi.id) as item_count,
               SUM(poi.total_price) as total_amount
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        JOIN users u ON po.created_by = u.id
        LEFT JOIN purchase_order_items poi ON po.id = poi.po_id
        WHERE po.station_id = ?
        GROUP BY po.id
        ORDER BY 
            CASE po.status 
                WHEN 'Pending Approval' THEN 1 
                ELSE 2 
            END,
            po.created_at DESC
    ");
    $stmt->execute([$station_id]);
    $po_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
.po-detail-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
}
.po-header-info {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid #003d7a;
}
.status-badge-large {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 14px;
}
.info-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.info-box-light {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
}
.items-table-view {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
.items-table-view th {
    background: #003d7a;
    color: white;
    padding: 12px;
    text-align: left;
}
.items-table-view td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}
.items-table-view tr:nth-child(even) {
    background: #f8f9fa;
}
.total-box {
    background: #003d7a;
    color: white;
    padding: 15px;
    border-radius: 6px;
    text-align: right;
    font-size: 18px;
    font-weight: bold;
}
.activity-log {
    max-height: 300px;
    overflow-y: auto;
}
.activity-item {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: start;
}
.activity-item:last-child {
    border-bottom: none;
}
.po-list-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: box-shadow 0.2s;
}
.po-list-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<div class="page-head">
    <div>
        <h1 class="h1">
            <?php if ($po): ?>
                Purchase Order Details
            <?php else: ?>
                <?php echo $mode === 'my' ? 'My Purchase Orders' : 'All Purchase Orders'; ?>
            <?php endif; ?>
        </h1>
        <div class="sub">
            <?php if ($po): ?>
                View and manage PO #<?php echo htmlspecialchars($po['po_number']); ?>
            <?php else: ?>
                <?php echo $mode === 'my' ? 'View all your purchase orders' : 'View all purchase orders for your station'; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($po && $po['status'] === 'Approved'): ?>
    <a href="print_po.php?id=<?php echo $po['id']; ?>" target="_blank" class="btn primary">
        <i class="fas fa-print"></i> Print PO
    </a>
    <?php endif; ?>
</div>

<?php if($msg): ?>
<div class="card" style="padding:15px; margin-bottom:20px; background:#e6f4ea; color:green;">
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<?php if ($po): ?>
<!-- Single PO View -->
<div class="po-detail-card">
    <div class="po-header-info">
        <div>
            <h2 style="margin: 0 0 10px 0; color: #003d7a;"><?php echo htmlspecialchars($po['po_number']); ?></h2>
            <span class="status-badge-large" style="background: <?php echo $status_badges[$po['status']]['bg']; ?>; color: <?php echo $status_badges[$po['status']]['color']; ?>">
                <?php echo $po['status']; ?>
            </span>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 24px; font-weight: bold; color: #198754;">
                ₱<?php echo number_format(array_sum(array_column($po_items, 'total_price')), 2); ?>
            </div>
            <div style="color: #6c757d; font-size: 12px;">
                <?php echo count($po_items); ?> item(s)
            </div>
        </div>
    </div>
    
    <div class="info-grid-2">
        <div class="info-box-light">
            <h4 style="margin: 0 0 10px 0; color: #003d7a;"><i class="fas fa-building"></i> Supplier Information</h4>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($po['supplier_name']); ?></p>
            <?php if ($po['contact_person']): ?>
            <p><strong>Contact:</strong> <?php echo htmlspecialchars($po['contact_person']); ?></p>
            <?php endif; ?>
            <?php if ($po['supplier_phone']): ?>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($po['supplier_phone']); ?></p>
            <?php endif; ?>
            <?php if ($po['supplier_email']): ?>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($po['supplier_email']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="info-box-light">
            <h4 style="margin: 0 0 10px 0; color: #003d7a;"><i class="fas fa-info-circle"></i> Order Information</h4>
            <p><strong>Station:</strong> <?php echo htmlspecialchars($po['station_name']); ?></p>
            <p><strong>Created:</strong> <?php echo date('M d, Y g:i A', strtotime($po['created_at'])); ?></p>
            <p><strong>By:</strong> <?php echo htmlspecialchars($po['created_by_name']); ?></p>
            <?php if ($po['expected_delivery_date']): ?>
            <p><strong>Expected Delivery:</strong> <?php echo date('M d, Y', strtotime($po['expected_delivery_date'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($po['approved_by_name']): ?>
    <div class="info-box-light" style="background: #d1fae5; border-left: 4px solid #198754;">
        <h4 style="margin: 0 0 10px 0; color: #065f46;"><i class="fas fa-check-circle"></i> Approval Information</h4>
        <p><strong>Approved By:</strong> <?php echo htmlspecialchars($po['approved_by_name']); ?></p>
        <p><strong>Approved At:</strong> <?php echo date('M d, Y g:i A', strtotime($po['approved_at'])); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($po['status'] === 'Rejected'): ?>
    <div class="info-box-light" style="background: #f8d7da; border-left: 4px solid #dc3545;">
        <h4 style="margin: 0 0 10px 0; color: #721c24;"><i class="fas fa-times-circle"></i> Rejection Information</h4>
        <p><strong>Rejected By:</strong> <?php echo htmlspecialchars($po['approved_by_name']); ?></p>
        <p><strong>Rejected At:</strong> <?php echo date('M d, Y g:i A', strtotime($po['approved_at'])); ?></p>
        <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($po['rejection_reason'])); ?></p>
    </div>
    <?php endif; ?>
    
    <h3 style="color: #003d7a; margin: 25px 0 15px 0;"><i class="fas fa-shopping-cart"></i> Items</h3>
    <table class="items-table-view">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th style="text-align: right;">Quantity</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($po_items as $index => $item): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td style="text-align: right;"><?php echo number_format($item['quantity'], 0); ?></td>
                <td style="text-align: right;">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                <td style="text-align: right;">₱<?php echo number_format($item['total_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="total-box">
        Total Amount: ₱<?php echo number_format(array_sum(array_column($po_items, 'total_price')), 2); ?>
    </div>
    
    <?php if ($po['remarks']): ?>
    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
        <h4 style="margin: 0 0 10px 0; color: #003d7a;"><i class="fas fa-comment"></i> Remarks</h4>
        <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($po['remarks'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 25px; display: flex; gap: 10px;">
        <a href="view_po.php?mode=my" class="btn ghost">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        <?php if ($po['status'] === 'Approved'): ?>
        <a href="print_po.php?id=<?php echo $po['id']; ?>" target="_blank" class="btn primary">
            <i class="fas fa-print"></i> Print PO
        </a>
        <?php endif; ?>
        <?php if ($isStaff && in_array($po['status'], ['Draft', 'Rejected'])): ?>
        <a href="purchase_order.php?id=<?php echo $po['id']; ?>" class="btn" style="background: #fd7e14; color: white;">
            <i class="fas fa-edit"></i> Edit PO
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Activity Log -->
<div class="po-detail-card">
    <h3 style="color: #003d7a; margin: 0 0 15px 0;"><i class="fas fa-history"></i> Activity Log</h3>
    <?php if (!empty($activity_logs)): ?>
    <div class="activity-log">
        <?php foreach ($activity_logs as $log): ?>
        <div class="activity-item">
            <div>
                <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                <?php if ($log['details']): ?>
                <div style="font-size: 12px; color: #6c757d; margin-top: 3px;">
                    <?php echo htmlspecialchars($log['details']); ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="text-align: right; font-size: 12px; color: #6c757d;">
                <div><?php echo htmlspecialchars($log['user_name']); ?></div>
                <div><?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color: #6c757d;">No activity recorded yet.</p>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- PO List View -->
<?php if (empty($po_list)): ?>
<div class="card" style="padding: 40px; text-align: center;">
    <i class="fas fa-inbox" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
    <h3>No Purchase Orders Found</h3>
    <p style="color: #6c757d;">
        <?php echo $mode === 'my' ? 'You haven\'t created any purchase orders yet.' : 'No purchase orders found for this station.'; ?>
    </p>
    <?php if ($isStaff): ?>
    <a href="purchase_order.php" class="btn primary" style="margin-top: 15px;">
        <i class="fas fa-plus"></i> Create Purchase Order
    </a>
    <?php endif; ?>
</div>
<?php else: ?>
<?php foreach ($po_list as $po_item): ?>
<div class="po-list-item">
    <div>
        <div style="font-weight: bold; font-size: 16px; margin-bottom: 5px;">
            <?php echo htmlspecialchars($po_item['po_number']); ?>
            <span class="status-badge-large" style="background: <?php echo $status_badges[$po_item['status']]['bg']; ?>; color: <?php echo $status_badges[$po_item['status']]['color']; ?>; font-size: 11px; padding: 3px 8px; margin-left: 10px;">
                <?php echo $po_item['status']; ?>
            </span>
        </div>
        <div style="color: #6c757d; font-size: 13px;">
            <i class="fas fa-building"></i> <?php echo htmlspecialchars($po_item['supplier_name']); ?> &nbsp;|&nbsp;
            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($po_item['created_at'])); ?> &nbsp;|&nbsp;
            <i class="fas fa-cube"></i> <?php echo $po_item['item_count']; ?> item(s)
            <?php if ($isManager && isset($po_item['created_by_name'])): ?>
            &nbsp;|&nbsp; <i class="fas fa-user"></i> <?php echo htmlspecialchars($po_item['created_by_name']); ?>
            <?php endif; ?>
        </div>
    </div>
    <div style="text-align: right;">
        <div style="font-size: 18px; font-weight: bold; color: #198754;">
            ₱<?php echo number_format($po_item['total_amount'] ?? 0, 2); ?>
        </div>
        <a href="view_po.php?id=<?php echo $po_item['id']; ?>" class="btn ghost small" style="margin-top: 5px;">
            <i class="fas fa-eye"></i> View
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../partials/footer.php'; ?>