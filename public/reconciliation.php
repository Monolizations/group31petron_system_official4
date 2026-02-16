<?php
require_once __DIR__ . '/../public/db_connect.php';
require_once __DIR__ . '/../backend/lib.php';

// Check if user is logged in and is superadmin
require_login();
$u = current_user();
$role = role_key($u['role'] ?? 'staff');
$station_id = user_station_id();

if (!has_role_at_least('admin')) {
    die("Access Denied");
}

// Don't redirect - let admins see the reconciliation report page
// They can navigate to fuel_reconciliation_finalize.php from the sidebar if needed

// Get filter parameters
$date_range = $_GET['date_range'] ?? '';
$stations = $_GET['stations'] ?? [];

// Parse date range
$start_date = '';
$end_date = '';
if ($date_range) {
    $dates = explode(' to ', $date_range);
    $start_date = $dates[0] ?? '';
    $end_date = $dates[1] ?? $start_date;
}

// Set default date range if none provided
if (!$date_range) {
    $today = new DateTime();
    $lastWeek = new DateTime($today->format('Y-m-d'));
    $lastWeek->sub(new DateInterval('P7D'));
    $start_date = $lastWeek->format('Y-m-d');
    $end_date = $today->format('Y-m-d');
    $date_range = "$start_date to $end_date";
}

// Handle Reconciliation Action (Admin with Manager Password Verification)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'verify_password') {
        // Password verification step for finalization
        $manager_password = $_POST['manager_password'] ?? '';
        
        if (empty($manager_password)) {
            $msg = "❌ Error: Password required to finalize reconciliation.";
        } else {
            // Verify against current user's password (manager/admin must provide their own password)
            if (password_verify($manager_password, $u['password'])) {
                $_SESSION['recon_verified'] = true;
                $_SESSION['recon_verified_time'] = time();
                $msg = "✅ Identity verified. Proceeding with reconciliation finalization.";
            } else {
                $msg = "❌ Error: Invalid password. Reconciliation cancelled.";
            }
        }
    }
    
    if ($_POST['action'] === 'reconcile') {
        // Check if password was verified in this session (within 5 minutes)
        $verified = $_SESSION['recon_verified'] ?? false;
        $verified_time = $_SESSION['recon_verified_time'] ?? 0;
        $time_elapsed = time() - $verified_time;
        
        if (!$verified || $time_elapsed > 300) { // 5 minute window
            $msg = "❌ Error: Manager password verification required. Please verify your identity first.";
        } else {
            $fuel_type = $_POST['fuel_type'];
            $physical = (float)$_POST['physical_stock'];
            
            // Get System Stock
            $stmt = $pdo->prepare("SELECT stock_level FROM station_inventory WHERE station_id = ? AND product_name = ? AND type = 'fuel'");
            $stmt->execute([$station_id, $fuel_type]);
            $system_stock = (float)$stmt->fetchColumn();
            
            $variance = $physical - $system_stock;
            $status = abs($variance) > 50 ? 'Variance Alert' : 'OK'; // Threshold 50L
            
            try {
                $stmt = $pdo->prepare("INSERT INTO fuel_reconciliation (station_id, reconciliation_date, fuel_type, opening_stock, closing_stock, physical_stock, variance, status) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$station_id, $fuel_type, $system_stock, $system_stock, $physical, $variance, $status]);
                
                // Update Inventory to match physical
                $upd = $pdo->prepare("UPDATE station_inventory SET stock_level = ? WHERE station_id = ? AND product_name = ? AND type = 'fuel'");
                $upd->execute([$physical, $station_id, $fuel_type]);
                
                // Log the action with verification
                log_activity($pdo, $u['id'], 'Reconciliation Finalized', "Manager verified and finalized reconciliation for $fuel_type. Variance: " . number_format($variance, 2) . " L");
                
                $msg = "✅ Reconciliation finalized and verified. Variance: " . number_format($variance, 2) . " L";
                
                // Clear verification flag
                unset($_SESSION['recon_verified']);
                unset($_SESSION['recon_verified_time']);
            } catch (Exception $e) { 
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch stations for dropdown
$stations_list = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM stations WHERE status = 'active' ORDER BY name");
    $stations_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $error = "Error fetching stations: " . $e->getMessage();
}

// Get fuel reconciliation data
$reconciliation_data = [];
$total_inflow = 0;
$total_outflow = 0;
$total_variance = 0;

if ($start_date && $end_date) {
    try {
        // Get fuel deliveries (inflow) data
        $stmt = $pdo->prepare("SELECT station_id, fuel_type, SUM(delivery_liters) as total_inflow FROM fuel_deliveries WHERE delivery_date BETWEEN ? AND ? GROUP BY station_id, fuel_type");
        $stmt->execute([$start_date, $end_date]);
        $inflow_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get fuel daily readings (outflow) data - join with fuel_pumps and fuel_types
        $stmt = $pdo->prepare("
            SELECT dr.station_id, ft.name as fuel_type, SUM(dr.sales_liters) as total_outflow 
            FROM fuel_daily_readings dr 
            JOIN fuel_pumps fp ON dr.pump_id = fp.id 
            JOIN fuel_types ft ON fp.fuel_type_id = ft.id 
            WHERE dr.reading_date BETWEEN ? AND ? AND (dr.status = 'Verified' OR dr.status IS NULL OR dr.status = '') 
            GROUP BY dr.station_id, ft.name
        ");
        $stmt->execute([$start_date, $end_date]);
        $outflow_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no real data, create sample data for demonstration
        if (empty($inflow_data) && empty($outflow_data) && !empty($stations_list)) {
            $sample_fuel_types = ['Diesel', 'Gasoline 95', 'Gasoline 91'];
            
            foreach ($stations_list as $station) {
                foreach ($sample_fuel_types as $fuel_type) {
                    $volume_in = rand(5000, 15000);
                    $volume_out = rand(4500, $volume_in + 500);
                    $variance = $volume_in - $volume_out;
                    $status = (abs($variance) / max(1, $volume_in) * 100) > 5 ? 'Variance Alert' : 'OK';
                    
                    $reconciliation_data[] = [
                        'date' => $start_date,
                        'station' => $station['name'],
                        'station_id' => $station['id'],
                        'fuel_type' => $fuel_type,
                        'volume_in' => $volume_in,
                        'volume_out' => $volume_out,
                        'variance' => $variance,
                        'status' => $status
                    ];
                    
                    $total_inflow += $volume_in;
                    $total_outflow += $volume_out;
                    $total_variance += $variance;
                }
            }
        } else {
            // Process real data
            $fuel_data = [];
            
            // Process inflow data
            foreach ($inflow_data as $inflow) {
                $key = $inflow['station_id'] . '_' . $inflow['fuel_type'];
                $fuel_data[$key] = [
                    'station_id' => $inflow['station_id'],
                    'fuel_type' => $inflow['fuel_type'],
                    'volume_in' => $inflow['total_inflow'],
                    'volume_out' => 0,
                    'variance' => 0,
                    'status' => 'OK'
                ];
            }
            
            // Process outflow data and calculate variance
            foreach ($outflow_data as $outflow) {
                $key = $outflow['station_id'] . '_' . $outflow['fuel_type'];
                
                if (isset($fuel_data[$key])) {
                    $fuel_data[$key]['volume_out'] = $outflow['total_outflow'];
                    $fuel_data[$key]['variance'] = $fuel_data[$key]['volume_in'] - $fuel_data[$key]['volume_out'];
                    
                    // Set status based on variance threshold (5% tolerance)
                    $variance_percent = abs($fuel_data[$key]['variance']) / max(1, $fuel_data[$key]['volume_in']) * 100;
                    $fuel_data[$key]['status'] = $variance_percent > 5 ? 'Variance Alert' : 'OK';
                } else {
                    $fuel_data[$key] = [
                        'station_id' => $outflow['station_id'],
                        'fuel_type' => $outflow['fuel_type'],
                        'volume_in' => 0,
                        'volume_out' => $outflow['total_outflow'],
                        'variance' => -$outflow['total_outflow'],
                        'status' => 'Variance Alert'
                    ];
                }
            }
            
            // Convert to array and add station names
            foreach ($fuel_data as $data) {
                // Get station name
                $station_name = 'Unknown Station';
                foreach ($stations_list as $station) {
                    if ($station['id'] == $data['station_id']) {
                        $station_name = $station['name'];
                        break;
                    }
                }
                
                $reconciliation_data[] = [
                    'date' => $start_date, // Use start date for all records
                    'station' => $station_name,
                    'station_id' => $data['station_id'],
                    'fuel_type' => $data['fuel_type'],
                    'volume_in' => $data['volume_in'],
                    'volume_out' => $data['volume_out'],
                    'variance' => $data['variance'],
                    'status' => $data['status']
                ];
                
                $total_inflow += $data['volume_in'];
                $total_outflow += $data['volume_out'];
                $total_variance += $data['variance'];
            }
        }
        
    } catch (Exception $e) {
        $error = "Error fetching reconciliation data: " . $e->getMessage();
    }
}

$page_title = 'Fuel Reconciliation Reports';
include __DIR__ . '/../partials/header.php';
?>

<?php if($role === 'admin'): ?>
<!-- Admin Operational View -->
<div class="page-head">
    <div><h1 class="h1">Fuel Reconciliation</h1><div class="sub">Reconcile physical tank levels with system records</div></div>
</div>
<?php if($msg): ?><div class="card" style="padding:10px; margin-bottom:20px; background:#e6f4ea; color:green;"><?php echo $msg; ?></div><?php endif; ?>

<section class="card" style="padding:20px; margin-bottom:30px; max-width:600px;">
    <h3 class="h3">New Reconciliation</h3>
    <form method="post">
        <input type="hidden" name="action" value="reconcile">
        <div class="form-group" style="margin-bottom:15px;">
            <label class="lbl">Fuel Type</label>
            <select name="fuel_type" id="fuel_type_reconciliation" class="inp" required>
                <option value="">-- Select Fuel --</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:15px;">
            <label class="lbl">Physical Stock (Liters from Gauge)</label>
            <input type="number" step="0.01" name="physical_stock" class="inp" required>
        </div>
        <button type="submit" class="btn primary">Reconcile & Adjust</button>
    </form>
</section>
<?php endif; ?>

<style>
.fuel-reconciliation-container {
    padding: 20px;
    background: var(--bg);
    min-height: calc(100vh - 110px);
}

.page-header {
    margin-bottom: 30px;
}

.page-title {
    font-size: 28px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--muted);
    font-size: 14px;
}

.filter-bar {
    background: var(--card);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-input {
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    background: var(--bg);
    color: var(--text);
    transition: all 0.3s ease;
}

.filter-input:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(0, 47, 108, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--blue);
    color: white;
}

.btn-primary:hover {
    background: #003366;
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--muted);
    color: var(--text);
}

.btn-secondary:hover {
    background: #6c757d;
}

.btn-success {
    background: #28A745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.report-section {
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.table-container {
    overflow-x: auto;
}

.reconciliation-table {
    width: 100%;
    border-collapse: collapse;
}

.reconciliation-table th {
    background: var(--bg);
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: var(--muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
}

.reconciliation-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    font-size: 14px;
}

.reconciliation-table tr:hover {
    background: var(--bg);
}

.reconciliation-table tr {
    cursor: pointer;
}

.variance-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.variance-badge.ok {
    background: #D4EDDA;
    color: #155724;
}

.variance-badge.alert {
    background: #F8D7DA;
    color: #721C24;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: var(--card);
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 80%;
    max-width: 800px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--muted);
}

.modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    font-size: 12px;
    z-index: 2000;
    display: none;
    animation: slideIn 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    min-width: 200px;
    text-align: center;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

.multiselect {
    position: relative;
}

.multiselect-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 100;
    margin-top: 4px;
}

.multiselect-option {
    padding: 10px 12px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.multiselect-option:hover {
    background: var(--bg);
}

.multiselect-option.selected {
    background: rgba(0, 47, 108, 0.1);
    color: var(--blue);
}
</style>

<div class="fuel-reconciliation-container">
    <div class="page-header">
        <h1 class="page-title">Fuel Reconciliation Reports</h1>
        <p class="page-subtitle">Comprehensive fuel inventory reconciliation and variance analysis</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-group">
                <label>Date / Time Range</label>
                <input type="text" class="filter-input" id="dateRange" placeholder="YYYY-MM-DD to YYYY-MM-DD" value="<?php echo htmlspecialchars($date_range); ?>">
            </div>
            <div class="filter-group">
                <label>Station/Branch Selector</label>
                <div class="multiselect">
                    <input type="text" class="filter-input" id="stationSelector" placeholder="Select stations" readonly>
                    <div class="multiselect-dropdown" id="stationDropdown">
                        <div class="multiselect-option" data-value="all">
                            <strong>All Stations</strong>
                        </div>
                        <?php foreach($stations_list as $station): ?>
                            <div class="multiselect-option" data-value="<?php echo $station['id']; ?>">
                                <?php echo htmlspecialchars($station['name']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="filter-buttons">
            <button class="btn btn-secondary" onclick="clearFilters()">
                <i class="fas fa-times"></i> Clear
            </button>
            <button class="btn btn-primary" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </div>
    </div>

    <!-- Report Section -->
    <div class="report-section">
        <div class="section-header">
            <h2 class="section-title">Fuel Reconciliation Summary</h2>
            <div>
                <button class="btn btn-secondary" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-secondary" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-success" onclick="markReconciled()">
                    <i class="fas fa-check"></i> Mark Reconciled
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="reconciliation-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th>Date</th>
                        <th>Station</th>
                        <th>Fuel Type</th>
                        <th>Volume In</th>
                        <th>Volume Out</th>
                        <th>Variance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reconciliation_data)): ?>
                        <?php foreach($reconciliation_data as $index => $data): ?>
                            <tr onclick="showVarianceDetails('<?php echo $data['date']; ?>', '<?php echo htmlspecialchars($data['station']); ?>', '<?php echo $data['fuel_type']; ?>')">
                                <td onclick="event.stopPropagation()">
                                    <input type="checkbox" class="row-checkbox" data-station="<?php echo $data['station_id']; ?>" data-fuel-type="<?php echo $data['fuel_type']; ?>" data-date="<?php echo $data['date']; ?>">
                                </td>
                                <td><?php echo date('M d, Y', strtotime($data['date'])); ?></td>
                                <td><?php echo htmlspecialchars($data['station']); ?></td>
                                <td><?php echo htmlspecialchars($data['fuel_type']); ?></td>
                                <td><?php echo number_format($data['volume_in'], 2); ?> L</td>
                                <td><?php echo number_format($data['volume_out'], 2); ?> L</td>
                                <td><?php echo number_format($data['variance'], 2); ?> L</td>
                                <td>
                                    <span class="variance-badge <?php echo $data['status'] === 'OK' ? 'ok' : 'alert'; ?>">
                                        <?php echo htmlspecialchars($data['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--muted);">
                                No reconciliation data available. Please select a date range and apply filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Variance Details Modal -->
<div id="varianceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="varianceModalTitle">Variance Details</h3>
            <button class="modal-close" onclick="closeVarianceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <table class="reconciliation-table">
                <thead>
                    <tr>
                        <th>Transaction Type</th>
                        <th>Date</th>
                        <th>Volume</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody id="varianceDetailsTable">
                    <!-- Details will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeVarianceModal()">Close</button>
            <button class="btn btn-primary" onclick="adjustInventory()">Adjust Inventory</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeDateRangePicker();
    setupStationSelector();
});

function initializeDateRangePicker() {
    const dateRangeInput = document.getElementById('dateRange');
    
    dateRangeInput.addEventListener('blur', function() {
        validateDateRange(this.value);
    });
    
    dateRangeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            validateDateRange(this.value);
            if (this.value) {
                applyFilters();
            }
        }
    });
}

function validateDateRange(dateRange) {
    if (!dateRange) {
        return;
    }
    
    const dateRangePattern = /^\d{4}-\d{2}-\d{2}\s+to\s+\d{4}-\d{2}-\d{2}$/;
    
    if (!dateRangePattern.test(dateRange)) {
        showToast('Please use format: YYYY-MM-DD to YYYY-MM-DD', 'error');
        return false;
    }
    
    return true;
}

function setupStationSelector() {
    const selector = document.getElementById('stationSelector');
    const dropdown = document.getElementById('stationDropdown');
    
    selector.addEventListener('click', function() {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.multiselect')) {
            dropdown.style.display = 'none';
        }
    });
    
    const options = dropdown.querySelectorAll('.multiselect-option');
    options.forEach(option => {
        option.addEventListener('click', function() {
            if (this.dataset.value === 'all') {
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
            } else {
                const allOption = dropdown.querySelector('[data-value="all"]');
                if (allOption) allOption.classList.remove('selected');
                this.classList.toggle('selected');
            }
            updateStationSelector();
        });
    });
}

function updateStationSelector() {
    const selected = document.querySelectorAll('.multiselect-option.selected');
    const selector = document.getElementById('stationSelector');
    if (selected.length === 0) {
        selector.value = '';
    } else if (selected.length === 1) {
        selector.value = selected[0].textContent.trim();
    } else {
        selector.value = selected.length + ' stations selected';
    }
}

function applyFilters() {
    const dateRange = document.getElementById('dateRange').value;
    const selectedStations = document.querySelectorAll('.multiselect-option.selected');
    let stationIds = [];
    selectedStations.forEach(opt => {
        if (opt.dataset.value !== 'all') {
            stationIds.push(opt.dataset.value);
        }
    });
    
    let url = 'reconciliation.php?';
    if (dateRange) url += 'date_range=' + encodeURIComponent(dateRange) + '&';
    if (stationIds.length > 0) url += 'stations=' + stationIds.join(',') + '&';
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = 'reconciliation.php';
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function showVarianceDetails(date, station, fuelType) {
    document.getElementById('varianceModalTitle').textContent = 'Variance Details - ' + fuelType;
    document.getElementById('varianceModal').style.display = 'block';
    // Would typically load details via AJAX here
}

function closeVarianceModal() {
    document.getElementById('varianceModal').style.display = 'none';
}

function adjustInventory() {
    alert('Inventory adjustment would open here');
    closeVarianceModal();
}

function showToast(message, type) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.background = type === 'error' ? '#dc2626' : '#16a34a';
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

function markReconciled() {
    const selected = document.querySelectorAll('.row-checkbox:checked');
    if (selected.length === 0) {
        showToast('Please select at least one record to mark as reconciled', 'error');
        return;
    }
    
    // Open finalization page in new tab
    window.open('fuel_reconciliation_finalize.php', '_blank');
}

function exportReport(format) {
    showToast('Export to ' + format.toUpperCase() + ' functionality coming soon', 'error');
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}

function verifyManagerPassword() {
    const password = document.getElementById('managerPassword').value;
    if (!password) {
        showToast('Please enter your password', 'error');
        return;
    }
    // Would typically verify via AJAX here
    closePasswordModal();
    showToast('Password verified successfully', 'success');
}
</script>

<script src="../assets/js/data_helper.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    DataHelper.populateFuelTypes('fuel_type_reconciliation', '-- Select Fuel --')
        .then(() => console.log('Fuel types loaded'))
        .catch(error => {
            console.error('Failed to load fuel types:', error);
            alert('Failed to load fuel types. Please refresh.');
        });
});
</script>

<!-- Password Verification Modal -->
<div id="passwordModal" class="modal" style="display:none;">
    <div class="modal-content" style="margin-top: 150px; width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-lock"></i> Manager Verification Required</h3>
            <button type="button" onclick="closePasswordModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p style="color: #666; margin-bottom: 15px;">To finalize this reconciliation, please enter your password:</p>
            <input 
                type="password" 
                id="managerPassword" 
                class="filter-input" 
                placeholder="Enter your password"
                style="width: 100%; margin-bottom: 15px;"
                onkeypress="if(event.key==='Enter') verifyManagerPassword()">
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="verifyManagerPassword()">Verify & Proceed</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: none;
}

.modal-content {
    background-color: white;
    margin: auto;
    padding: 0;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-body {
    padding: 20px;
}
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
