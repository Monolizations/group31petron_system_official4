<?php
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$po_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$po_id) {
    die('Invalid PO ID');
}

$stmt = $pdo->prepare("
    SELECT po.*, 
           s.name as station_name, s.location as station_address,
           u.name as created_by_name,
           sr.product_name as request_product, sr.qty as request_qty, sr.type as request_type
    FROM purchase_orders po
    LEFT JOIN stations s ON po.station_id = s.id
    LEFT JOIN users u ON po.created_by = u.id
    LEFT JOIN stock_requests sr ON po.request_id = sr.id
    WHERE po.id = ?
");
$stmt->execute([$po_id]);
$po = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$po) {
    die('PO not found');
}

$product = $po['product_name'] ?: $po['request_product'] ?: 'N/A';
$qty = $po['quantity'] ?: $po['request_qty'] ?: 0;
$type = $po['type'] ?: $po['request_type'] ?: 'merch';
$station = $po['station_name'] ?: 'N/A';
$station_address = $po['station_address'] ?: '';
$created_by = $po['created_by_name'] ?: 'N/A';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - <?php echo htmlspecialchars($po['po_number']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            background: white;
            padding: 20px;
        }
        .po-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #333;
            padding: 0;
        }
        .po-header {
            background: #003d7a;
            color: white;
            padding: 20px 30px;
            text-align: center;
        }
        .po-header h1 {
            font-size: 28px;
            letter-spacing: 3px;
            margin-bottom: 5px;
        }
        .po-header p {
            font-size: 12px;
            opacity: 0.9;
        }
        .po-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 20px 30px;
            border-bottom: 1px solid #ccc;
            background: #f9f9f9;
        }
        .po-info div { margin-bottom: 8px; }
        .po-info strong { color: #003d7a; }
        .po-items {
            padding: 20px 30px;
        }
        .po-items h3 {
            font-size: 14px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #003d7a;
        }
        .po-table {
            width: 100%;
            border-collapse: collapse;
        }
        .po-table th, .po-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .po-table th {
            background: #f0f0f0;
            font-weight: bold;
            color: #333;
        }
        .po-table .right { text-align: right; }
        .po-footer {
            padding: 20px 30px;
            background: #f9f9f9;
            border-top: 1px solid #ccc;
        }
        .po-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .po-status.pending { background: #fef3c7; color: #92400e; }
        .po-status.confirmed { background: #dbeafe; color: #1e40af; }
        .po-status.received { background: #d1fae5; color: #065f46; }
        .po-status.cancelled { background: #fee2e2; color: #991b1b; }
        .po-signature {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding: 0 30px 30px;
        }
        .sign-box {
            border-top: 1px solid #333;
            padding-top: 10px;
            text-align: center;
        }
        .sign-box p { font-size: 12px; color: #666; }
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        .btn-print {
            background: #003d7a;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-print:hover { background: #002d5c; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .po-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <div class="po-container">
        <div class="po-header">
            <h1>PURCHASE ORDER</h1>
            <p>PETRON SERVICE STATION</p>
        </div>

        <div class="po-info">
            <div>
                <strong>PO Number:</strong> <?php echo htmlspecialchars($po['po_number']); ?><br>
                <strong>Date:</strong> <?php echo date('F d, Y', strtotime($po['created_at'])); ?><br>
                <strong>Time:</strong> <?php echo date('h:i A', strtotime($po['created_at'])); ?>
            </div>
            <div>
                <strong>Station:</strong> <?php echo htmlspecialchars($station); ?><br>
                <?php if ($station_address): ?>
                <strong>Address:</strong> <?php echo htmlspecialchars($station_address); ?><br>
                <?php endif; ?>
                <strong>Requested By:</strong> <?php echo htmlspecialchars($created_by); ?>
            </div>
        </div>

        <div class="po-items">
            <h3>ORDER DETAILS</h3>
            <table class="po-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Description</th>
                        <th>Type</th>
                        <th class="right">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><?php echo htmlspecialchars($product); ?></td>
                        <td><?php echo strtoupper($type); ?></td>
                        <td class="right"><?php echo number_format($qty, 2); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr style="background: #f9f9f9;">
                        <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL:</td>
                        <td class="right" style="font-weight: bold;"><?php echo number_format($qty, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="po-footer">
            <strong>Status:</strong> 
            <span class="po-status <?php echo strtolower($po['status']); ?>">
                <?php echo strtoupper($po['status']); ?>
            </span>
            <br><br>
            <?php if ($po['remarks']): ?>
            <strong>Remarks:</strong> <?php echo htmlspecialchars($po['remarks']); ?><br><br>
            <?php endif; ?>
            <strong>Expected Delivery:</strong> 
            <?php echo $po['expected_delivery_date'] ? date('F d, Y', strtotime($po['expected_delivery_date'])) : 'Not specified'; ?>
        </div>

        <div class="po-signature">
            <div class="sign-box">
                <p>Prepared By</p>
                <br><br>
                ________________________<br>
                <strong><?php echo htmlspecialchars($created_by); ?></strong>
            </div>
            <div class="sign-box">
                <p>Approved By</p>
                <br><br>
                ________________________<br>
                <strong>Station Manager</strong>
            </div>
        </div>
    </div>

    <script>
        // Auto-open print dialog
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
