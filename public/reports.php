<?php
$page_id = 'reports';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$role = function_exists('role_key') ? role_key($me['role'] ?? '') : strtolower(trim($me['role'] ?? 'staff'));
$station_id = user_station_id();

// Access Control: Redirect staff to dashboard if they try to access directly
if (!in_array($role, ['admin', 'superadmin', 'manager'])) {
    header("Location: dashboard.php");
    exit;
}

// Parameters
$view = $_GET['view'] ?? 'daily_sales';
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');

// --- DATA FETCHING LOGIC ---
$data = [];
$headers = [];
$title = "";
$subtitle = "";

try {
    // 1. Daily Sales
    if ($view === 'daily_sales') {
        $title = "Daily Sales Report";
        $subtitle = "Total sales per day grouped by category";
        $headers = ['Date', 'Fuel Sales', 'Merchandise Sales', 'Services', 'Total', 'Actions'];
        
        // Use LEFT JOIN with products to categorize items properly
        $sql = "SELECT 
                    DATE(s.sale_date) as date,
                    SUM(CASE WHEN COALESCE(pt.name, 'merch') = 'fuel' THEN si.total_amount ELSE 0 END) as fuel_sales,
                    SUM(CASE WHEN COALESCE(pt.name, 'merch') = 'merch' THEN si.total_amount ELSE 0 END) as merch_sales,
                    SUM(CASE WHEN COALESCE(pt.name, 'merch') = 'service' THEN si.total_amount ELSE 0 END) as service_sales,
                    SUM(s.total) as total
                FROM sales s
                LEFT JOIN sale_items si ON s.id = si.sale_id
                LEFT JOIN products p ON si.product_id = p.id
                LEFT JOIN product_types pt ON p.type_id = pt.id
                WHERE (s.station_id = ? OR s.station_id IS NULL) AND s.sale_date BETWEEN ? AND ?
                GROUP BY DATE(s.sale_date)
                ORDER BY date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id, $start, $end]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Shift Reports
    elseif ($view === 'shift_reports') {
        $title = "Shift Reports";
        $subtitle = "Sales performance per staff per shift";
        $headers = ['Shift', 'Staff', 'Fuel', 'Merchandise', 'Services', 'Total', 'Actions'];
        
        // Approximate shift based on time
        $sql = "SELECT 
                    CASE 
                        WHEN TIME(s.created_at) BETWEEN '06:00:00' AND '14:00:00' THEN 'AM' 
                        ELSE 'PM' 
                    END as shift,
                    u.name as staff_name,
                    SUM(CASE WHEN COALESCE(pt.name, 'merch') = 'fuel' THEN si.total_amount ELSE 0 END) as fuel_sales,
                    SUM(CASE WHEN COALESCE(pt.name, 'merch') = 'merch' THEN si.total_amount ELSE 0 END) as merch_sales,
                    SUM(CASE WHEN COALESCE(pt.name, 'merch') = 'service' THEN si.total_amount ELSE 0 END) as service_sales,
                    SUM(s.total) as total
                FROM sales s
                LEFT JOIN sale_items si ON s.id = si.sale_id
                LEFT JOIN products p ON si.product_id = p.id
                LEFT JOIN product_types pt ON p.type_id = pt.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE (s.station_id = ? OR s.station_id IS NULL) AND s.sale_date BETWEEN ? AND ?
                GROUP BY shift, s.user_id
                ORDER BY s.sale_date DESC, shift ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id, $start, $end]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Inventory Reports
    elseif ($view === 'inventory_reports') {
        $title = "Inventory Movement";
        $subtitle = "Stock tracking for Fuel, Merchandise, and Accessories";
        $headers = ['Category', 'Product', 'Beginning', 'In', 'Out', 'Ending', 'Actions'];
        
        // Fetch current inventory with product types
        $stmt = $pdo->prepare("SELECT i.id, p.name as product_name, pt.name as type, i.stock_level as ending 
                               FROM inventory i 
                               JOIN products p ON i.product_id = p.id 
                               JOIN product_types pt ON p.type_id = pt.id 
                               WHERE i.station_id = ?");
        $stmt->execute([$station_id]);
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($inventory as $item) {
            // Calculate Out (Sales)
            $stmtOut = $pdo->prepare("SELECT SUM(qty) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.name = ? AND (s.station_id = ? OR s.station_id IS NULL) AND s.sale_date BETWEEN ? AND ?");
            $stmtOut->execute([$item['product_name'], $station_id, $start, $end]);
            $out = $stmtOut->fetchColumn() ?: 0;
            
            // Calculate In (Deliveries)
            $in = 0;
            if ($item['type'] === 'fuel') {
                // Check if fuel_deliveries table exists
                try {
                    $stmtIn = $pdo->prepare("SELECT SUM(delivery_liters) FROM fuel_deliveries WHERE fuel_type = ? AND station_id = ? AND delivery_date BETWEEN ? AND ?");
                    $stmtIn->execute([$item['product_name'], $station_id, $start, $end]);
                    $in = $stmtIn->fetchColumn() ?: 0;
                } catch (Exception $e) {}
            } else {
                // Check if purchase_order_items table exists
                try {
                    $stmtIn = $pdo->prepare("SELECT SUM(poi.received_quantity) FROM purchase_order_items poi JOIN purchase_orders po ON poi.po_id = po.id WHERE poi.item_name = ? AND po.station_id = ? AND po.updated_at BETWEEN ? AND ?");
                    $stmtIn->execute([$item['product_name'], $station_id, $start . ' 00:00:00', $end . ' 23:59:59']);
                    $in = $stmtIn->fetchColumn() ?: 0;
                } catch (Exception $e) {}
            }
            
            // Simple calculation: Beginning = Ending - In + Out (Approximation)
            // Note: This assumes 'Ending' is current stock. For historical accuracy, we'd need a daily snapshot table.
            $beginning = $item['ending'] - $in + $out;
            
            $data[] = [
                'category' => ucfirst($item['type']),
                'product' => $item['product_name'],
                'beginning' => number_format($beginning, 2),
                'in' => number_format($in, 2),
                'out' => number_format($out, 2),
                'ending' => number_format($item['ending'], 2),
                'id' => $item['id']
            ];
        }
    }

    // 4. Job Order Reports
    elseif ($view === 'job_order_reports') {
        $title = "Job Order Reports";
        $subtitle = "Completed service jobs and maintenance";
        $headers = ['Job ID', 'Customer', 'Service', 'Staff', 'Parts Used', 'Cost', 'Time', 'Actions'];
        
        $sql = "SELECT 
                    j.id, 
                    j.job_order_number,
                    j.customer_id, 
                    c.name as customer_name,
                    sc.name as service_type,
                    u.name as staff_name,
                    j.service_description as parts_used,
                    j.estimated_duration,
                    j.created_at
                FROM job_orders j
                LEFT JOIN customers c ON j.customer_id = c.id
                LEFT JOIN users u ON j.assigned_mechanic_id = u.id
                LEFT JOIN service_categories sc ON j.service_category_id = sc.id
                WHERE j.station_id = ? AND j.status = 'Completed' AND DATE(j.created_at) BETWEEN ? AND ?
                ORDER BY j.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id, $start, $end]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Verification
    elseif ($view === 'verification') {
        $title = "System Verification";
        $subtitle = "Review system activity logs and transaction approvals";
        $headers = ['Action', 'Module', 'User', 'Timestamp', 'Status', 'Actions'];
        
        // Ensure activity_logs table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255),
            details TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $sql = "SELECT 
                    al.action,
                    'System' as module,
                    u.name as user_name,
                    al.created_at,
                    'Success' as status,
                    al.details
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE (u.station_id = ? OR al.user_id = ?) AND DATE(al.created_at) BETWEEN ? AND ?
                ORDER BY al.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id, $me['id'], $start, $end]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $data = [];
    $subtitle = "Error loading data: " . $e->getMessage();
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="h1"><?php echo htmlspecialchars($title); ?></h1>
        <div class="sub"><?php echo htmlspecialchars($subtitle); ?></div>
    </div>
    <div class="actions">
        <button class="btn ghost" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        <button class="btn primary" onclick="exportTableToCSV('report.csv')"><i class="fas fa-file-export"></i> Export</button>
    </div>
</div>

<!-- FILTERS -->
<div class="card" style="padding: 20px; margin-bottom: 20px;">
    <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
        
        <div style="flex: 1; min-width: 200px;">
            <label class="lbl">Date Range</label>
            <div style="display: flex; gap: 10px;">
                <input type="date" name="start" class="inp" value="<?php echo htmlspecialchars($start); ?>">
                <span style="align-self: center;">to</span>
                <input type="date" name="end" class="inp" value="<?php echo htmlspecialchars($end); ?>">
            </div>
        </div>

        <?php if ($view === 'shift_reports' || $view === 'job_order_reports'): ?>
        <div style="flex: 1; min-width: 200px;">
            <label class="lbl">Staff / Mechanic</label>
            <input type="text" name="staff" class="inp" placeholder="Search staff...">
        </div>
        <?php endif; ?>

        <button type="submit" class="btn dark"><i class="fas fa-search"></i> Filter</button>
    </form>
</div>

<!-- REPORT TABLE -->
<div class="card">
    <div class="table-wrap">
        <table class="table" id="reportTable">
            <thead>
                <tr>
                    <?php foreach ($headers as $h): ?>
                        <th><?php echo htmlspecialchars($h); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="<?php echo count($headers); ?>" style="text-align: center; padding: 20px;">No records found for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php if ($view === 'daily_sales'): ?>
                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td>₱<?php echo number_format($row['fuel_sales'], 2); ?></td>
                                <td>₱<?php echo number_format($row['merch_sales'], 2); ?></td>
                                <td>₱<?php echo number_format($row['service_sales'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($row['total'], 2); ?></strong></td>
                                <td>
                                    <button class="btn small ghost" title="View Breakdown"><i class="fas fa-eye"></i></button>
                                </td>

                            <?php elseif ($view === 'shift_reports'): ?>
                                <td><span class="badge bg-<?php echo $row['shift']=='AM'?'warning':'primary'; ?>"><?php echo $row['shift']; ?></span></td>
                                <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                <td>₱<?php echo number_format($row['fuel_sales'], 2); ?></td>
                                <td>₱<?php echo number_format($row['merch_sales'], 2); ?></td>
                                <td>₱<?php echo number_format($row['service_sales'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($row['total'], 2); ?></strong></td>
                                <td>
                                    <button class="btn small ghost" title="View Transactions"><i class="fas fa-eye"></i></button>
                                </td>

                            <?php elseif ($view === 'inventory_reports'): ?>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['product']); ?></td>
                                <td><?php echo $row['beginning']; ?></td>
                                <td style="color: green;">+<?php echo $row['in']; ?></td>
                                <td style="color: red;">-<?php echo $row['out']; ?></td>
                                <td><strong><?php echo $row['ending']; ?></strong></td>
                                <td>
                                    <button class="btn small ghost" title="View History"><i class="fas fa-history"></i></button>
                                </td>

                            <?php elseif ($view === 'job_order_reports'): ?>
                                <td>JO-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name'] ?? 'Walk-in'); ?></td>
                                <td><?php echo htmlspecialchars($row['service_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['parts_used'] ?? '-'); ?></td>
                                <td>₱<?php echo number_format($row['total_cost'], 2); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <button class="btn small ghost" title="View Job"><i class="fas fa-eye"></i></button>
                                </td>

                            <?php elseif ($view === 'verification'): ?>
                                <td><?php echo htmlspecialchars($row['action']); ?></td>
                                <td><?php echo htmlspecialchars($row['module']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td><?php echo date('M d, h:i A', strtotime($row['created_at'])); ?></td>
                                <td><span class="badge bg-success"><?php echo $row['status']; ?></span></td>
                                <td>
                                    <button class="btn small ghost" onclick="alert('<?php echo htmlspecialchars($row['details']); ?>')" title="View Log"><i class="fas fa-file-alt"></i></button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("table tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) 
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(","));        
    }

    var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>

<style>
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; color: white; }
    .bg-primary { background: #007bff; }
    .bg-warning { background: #ffc107; color: #333; }
    .bg-success { background: #28a745; }
    .btn.small { padding: 4px 8px; font-size: 0.85em; }
    .inp { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%; }
    .lbl { display: block; font-size: 0.9em; font-weight: bold; margin-bottom: 5px; }
    
    /* Enhanced Footer Styles - Override any conflicts */
    .fixed-footer {
        position: fixed !important;
        bottom: 0 !important;
        left: 250px !important;
        width: calc(100% - 250px) !important;
        height: 40px !important;
        background-color: #ffffff !important;
        border-top: 1px solid #e0e0e0 !important;
        z-index: 9999 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 20px !important;
        font-size: 0.85em !important;
        color: #666666 !important;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;
    }
    
    /* Mobile footer - full width */
    @media (max-width: 991px) {
        .fixed-footer {
            left: 0 !important;
            width: 100% !important;
        }
    }
    
    /* Ensure footer is always visible */
    .fixed-footer * {
        pointer-events: auto !important;
    }
    
    .footer-content {
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
    }
    
    /* Override any conflicting styles */
    body {
        padding-bottom: 40px !important; /* Account for fixed footer */
    }
    
    main {
        padding-bottom: 60px !important; /* Account for fixed footer */
    }
    
    /* Ensure content doesn't hide footer */
    .sales-reports-container {
        margin-bottom: 60px !important; /* Account for fixed footer */
    }
</style>

<!-- INLINE FOOTER - GUARANTEED TO DISPLAY -->
<footer id="reports-footer" style="position: fixed !important; bottom: 0 !important; left: 250px !important; width: calc(100% - 250px) !important; height: 40px !important; background-color: #ffffff !important; border-top: 1px solid #e0e0e0 !important; z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 20px !important; font-size: 0.85em !important; color: #666666 !important; box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important; margin: 0 !important; padding: 0 20px !important; font-family: inherit !important; line-height: 1 !important;">
    <div style="width: 100% !important; display: flex !important; justify-content: center !important; align-items: center !important; margin: 0 !important; padding: 0 !important;">
        <span style="margin: 0 !important; padding: 0 !important;">&copy; 2026 Petron Management System</span>
        <span id="reports-footer-clock" style="margin-left: 20px !important; padding: 0 !important;"></span>
    </div>
</footer>

<!-- SCRIPT TO ENSURE FOOTER IS VISIBLE -->
<script>
// Force footer visibility
document.addEventListener('DOMContentLoaded', function() {
    const footer = document.getElementById('reports-footer');
    if (footer) {
        console.log('Reports footer found:', footer);
        
        // Force styles
        footer.style.cssText += 'position: fixed !important; bottom: 0 !important; left: 250px !important; width: calc(100% - 250px) !important; height: 40px !important; background-color: #ffffff !important; border-top: 1px solid #e0e0e0 !important; z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important;';
        
        // Update clock
        function updateReportsFooterClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockElement = document.getElementById('reports-footer-clock');
            if (clockElement) {
                clockElement.innerHTML = '<i class="far fa-clock"></i> ' + now.toLocaleDateString('en-US', options);
            }
        }
        
        updateReportsFooterClock();
        setInterval(updateReportsFooterClock, 1000);
        
        // Ensure footer stays visible
        setInterval(function() {
            if (footer) {
                footer.style.display = 'flex !important';
                footer.style.visibility = 'visible !important';
                footer.style.opacity = '1 !important';
            }
        }, 1000);
        
        console.log('Reports footer initialized successfully');
    } else {
        console.error('Reports footer not found');
    }
    
    // Add body padding to account for fixed footer
    document.body.style.paddingBottom = '40px !important';
    
    // Add main padding
    const mainElement = document.querySelector('main');
    if (mainElement) {
        mainElement.style.paddingBottom = '60px !important';
    }
});

// Mobile responsive
if (window.innerWidth <= 991) {
    const footer = document.getElementById('reports-footer');
    if (footer) {
        footer.style.left = '0 !important';
        footer.style.width = '100% !important';
    }
}

window.addEventListener('resize', function() {
    const footer = document.getElementById('reports-footer');
    if (footer) {
        if (window.innerWidth <= 991) {
            footer.style.left = '0 !important';
            footer.style.width = '100% !important';
        } else {
            footer.style.left = '250px !important';
            footer.style.width = 'calc(100% - 250px) !important';
        }
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
