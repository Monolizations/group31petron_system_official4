<?php
/**
 * Sales by Pump Report
 * 
 * Shows fuel sales breakdown by pump to help track which pumps are generating revenue
 * and identify any discrepancies in pump usage.
 */

$page_id = 'reports';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$station_id = user_station_id();
$role = role_key($me['role'] ?? '');

// Only managers and admins can view reports
if (!in_array($role, ['manager', 'admin', 'superadmin'])) {
    die('Access denied. Only managers and administrators can view reports.');
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$pump_id_filter = $_GET['pump_id'] ?? null;

// Fetch sales by pump
$sql = "SELECT 
            fp.id as pump_id,
            fp.pump_number,
            ft.name as fuel_type,
            COUNT(DISTINCT si.sale_id) as transaction_count,
            SUM(si.quantity) as total_liters,
            SUM(si.total_amount) as total_revenue,
            AVG(si.unit_price) as avg_price_per_liter,
            MIN(s.sale_date) as first_sale_date,
            MAX(s.sale_date) as last_sale_date
        FROM fuel_pumps fp
        LEFT JOIN nozzles n ON fp.id = n.pump_id
        LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id
        LEFT JOIN sale_items si ON n.id = si.nozzle_id
        LEFT JOIN sales s ON si.sale_id = s.id
        WHERE fp.station_id = ?
          AND (s.sale_date IS NULL OR (s.sale_date >= ? AND s.sale_date <= ?))";

$params = [$station_id, $start_date, $end_date];

if ($pump_id_filter) {
    $sql .= " AND fp.id = ?";
    $params[] = $pump_id_filter;
}

$sql .= " GROUP BY fp.id, fp.pump_number, ft.name
         ORDER BY fp.pump_number ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pump_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all pumps for filter dropdown
$stmt = $pdo->prepare("SELECT id, pump_number FROM fuel_pumps WHERE station_id = ? ORDER BY pump_number");
$stmt->execute([$station_id]);
$all_pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="h1">Sales by Pump Report</h1>
        <div class="sub">Fuel sales breakdown by pump for revenue tracking and analysis</div>
    </div>
</div>

<div class="card" style="padding: 20px; margin-bottom: 20px;">
    <form method="get" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: flex-end;">
        <div class="form-group mb-0">
            <label class="lbl">Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="inp full">
        </div>
        <div class="form-group mb-0">
            <label class="lbl">End Date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="inp full">
        </div>
        <div class="form-group mb-0">
            <label class="lbl">Pump (Optional)</label>
            <select name="pump_id" class="inp full">
                <option value="">All Pumps</option>
                <?php foreach ($all_pumps as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $pump_id_filter == $p['id'] ? 'selected' : ''; ?>>
                        Pump <?php echo htmlspecialchars($p['pump_number']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn primary">Filter</button>
        <button type="button" class="btn ghost" onclick="window.print()"><i class="fas fa-print"></i></button>
    </form>
</div>

<div class="card" style="padding: 20px; overflow-x: auto;">
    <h3 style="margin-top: 0;">Report Period: <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8f9fa; border-bottom: 2px solid #003d7a;">
            <tr>
                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #003d7a;">Pump #</th>
                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #003d7a;">Fuel Type</th>
                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #003d7a;">Transactions</th>
                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #003d7a;">Total Liters</th>
                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #003d7a;">Avg Price/Liter</th>
                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #003d7a;">Total Revenue</th>
                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #003d7a;">Last Sale</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_transactions = 0;
            $total_liters = 0;
            $total_revenue = 0;
            
            if (empty($pump_sales)): 
            ?>
                <tr>
                    <td colspan="7" style="padding: 20px; text-align: center; color: #666;">
                        No sales data found for the selected period.
                    </td>
                </tr>
            <?php else:
                foreach ($pump_sales as $row):
                    if ($row['transaction_count']): // Only show pumps with sales
                        $total_transactions += $row['transaction_count'];
                        $total_liters += $row['total_liters'] ?? 0;
                        $total_revenue += $row['total_revenue'] ?? 0;
            ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">Pump <?php echo htmlspecialchars($row['pump_number']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($row['fuel_type'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; text-align: right;"><?php echo (int)$row['transaction_count']; ?></td>
                    <td style="padding: 12px; text-align: right;"><?php echo number_format($row['total_liters'] ?? 0, 2); ?> L</td>
                    <td style="padding: 12px; text-align: right;">₱<?php echo number_format($row['avg_price_per_liter'] ?? 0, 2); ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: bold;">₱<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></td>
                    <td style="padding: 12px; text-align: center;"><?php echo $row['last_sale_date'] ? date('M d', strtotime($row['last_sale_date'])) : 'N/A'; ?></td>
                </tr>
            <?php
                    endif;
                endforeach;
                
                if ($total_revenue > 0):
            ?>
                <tr style="background: #f8f9fa; font-weight: bold; border-top: 2px solid #003d7a;">
                    <td colspan="2" style="padding: 12px;">TOTAL</td>
                    <td style="padding: 12px; text-align: right;"><?php echo (int)$total_transactions; ?></td>
                    <td style="padding: 12px; text-align: right;"><?php echo number_format($total_liters, 2); ?> L</td>
                    <td style="padding: 12px; text-align: right;">₱<?php echo number_format($total_liters > 0 ? $total_revenue / $total_liters : 0, 2); ?></td>
                    <td style="padding: 12px; text-align: right;">₱<?php echo number_format($total_revenue, 2); ?></td>
                    <td style="padding: 12px;"></td>
                </tr>
            <?php endif; endif; ?>
        </tbody>
    </table>
</div>

<style>
    .page-head { margin-bottom: 20px; }
    .h1 { color: #003d7a; margin: 0 0 5px 0; }
    .sub { color: #666; font-size: 14px; }
    .card { background: white; border-radius: 8px; border: 1px solid #ddd; }
    .form-group { margin-bottom: 0; }
    .lbl { display: block; font-weight: 500; margin-bottom: 5px; color: #333; font-size: 13px; }
    .inp { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
    .inp.full { width: 100%; }
    .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; }
    .btn.primary { background: #003d7a; color: white; }
    .btn.primary:hover { background: #002a56; }
    .btn.ghost { background: #f0f0f0; color: #333; }
    .btn.ghost:hover { background: #e0e0e0; }
    
    @media print {
        .card:first-child { display: none; } /* Hide filter form on print */
    }
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
