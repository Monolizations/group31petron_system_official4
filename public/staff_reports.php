<?php
/**
 * STAFF REPORTS MODULE
 * 
 * Manager View: Performance, Attendance, Quality reports for station staff
 * Staff View: Individual shift summaries, job orders, and personal metrics
 * Station-specific: Reports filtered by user's assigned station_id
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$page_id = 'staff_reports';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();
$me = current_user();

$role = role_key($me['role'] ?? 'staff');
if (!in_array($role, ['staff','manager','admin','superadmin'])) { header("Location: dashboard.php"); exit; }

$station_id = user_station_id();
$view = $_GET['view'] ?? 'performance';

// Fetch station info
$station_name = 'Station';
try {
    $stmt = $pdo->prepare("SELECT name FROM stations WHERE id = ? LIMIT 1");
    $stmt->execute([$station_id]);
    $station = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($station) $station_name = $station['name'];
} catch(Exception $e) {}

// Fetch staff data for station
$staff_data = [];
try {
    $stmt = $pdo->prepare("SELECT id, username, role, email, created_at, last_login FROM users WHERE station_id = ? AND status = 'active' ORDER BY username");
    $stmt->execute([$station_id]);
    $staff_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Fetch performance metrics
$performance_data = [];
if ($view === 'performance') {
    try {
        $sql = "SELECT 
                u.id, u.username, u.role,
                COUNT(DISTINCT jo.id) as jobs_completed,
                COUNT(DISTINCT s.id) as sales_processed,
                COALESCE(SUM(jo.total_cost), 0) as total_revenue
            FROM users u
            LEFT JOIN job_orders jo ON u.id = jo.user_id AND jo.station_id = ? AND jo.status = 'completed'
            LEFT JOIN sales s ON u.id = s.user_id AND s.station_id = ?
            WHERE u.station_id = ? AND u.status = 'active'
                AND u.role NOT IN ('admin', 'superadmin', 'manager', 'Admin', 'Manager', 'Super Admin')
            GROUP BY u.id, u.username, u.role
            ORDER BY total_revenue DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id, $station_id, $station_id]);
        $performance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}
}

// Fetch attendance data
$attendance_data = [];
if ($view === 'attendance') {
    try {
        $sql = "SELECT 
                u.id, u.username, u.role,
                COUNT(DISTINCT DATE(ls.start_time)) as days_present,
                COALESCE(SUM(ls.hours_worked), 0) as total_hours,
                MAX(u.last_login) as last_active
            FROM users u
            LEFT JOIN labor_sessions ls ON u.id = ls.user_id AND ls.station_id = ?
            WHERE u.station_id = ? AND u.status = 'active'
                AND u.role NOT IN ('admin', 'superadmin', 'manager', 'Admin', 'Manager', 'Super Admin')
            GROUP BY u.id, u.username, u.role
            ORDER BY days_present DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id, $station_id]);
        $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}
}

// Fetch quality metrics
$quality_data = [];
if ($view === 'quality') {
    try {
        $sql = "SELECT 
                u.id, u.username, u.role,
                COUNT(CASE WHEN jo.status = 'completed' THEN 1 END) as completed_jobs,
                COUNT(CASE WHEN jo.status = 'cancelled' THEN 1 END) as cancelled_jobs,
                ROUND(AVG(CASE WHEN jo.status = 'completed' THEN 100 ELSE 0 END), 1) as success_rate
            FROM users u
            LEFT JOIN job_orders jo ON u.id = jo.user_id AND jo.station_id = ?
            WHERE u.station_id = ? AND u.status = 'active'
                AND u.role NOT IN ('admin', 'superadmin', 'manager', 'Admin', 'Manager', 'Super Admin')
            GROUP BY u.id, u.username, u.role
            ORDER BY success_rate DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$station_id, $station_id]);
        $quality_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}
}

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
  <div>
    <h1 class="h1"><i class="fas fa-chart-line"></i> Staff Reports</h1>
    <div class="sub">Performance analytics and reports for <?php echo htmlspecialchars($station_name); ?></div>
  </div>
  <div style="display: flex; gap: 10px; align-items: center;">
    <span class="badge" style="background: #3b82f6; color: white; padding: 6px 12px; border-radius: 4px;">
      <i class="fas fa-building"></i> <?php echo htmlspecialchars($station_name); ?>
    </span>
    <span class="muted" style="font-size: 12px;"><?php echo count($staff_data); ?> Active Staff</span>
  </div>
</div>

<!-- Tab Navigation -->
<div class="card" style="margin-bottom: 20px;">
  <div style="display: flex; gap: 5px; padding: 16px; flex-wrap: wrap;">
    <?php if(in_array($role, ['manager','admin','superadmin'])): ?>
      <a class="btn <?php echo $view === 'performance' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=performance">
        <i class="fas fa-trophy"></i> Performance
      </a>
      <a class="btn <?php echo $view === 'attendance' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=attendance">
        <i class="fas fa-calendar-check"></i> Attendance
      </a>
      <a class="btn <?php echo $view === 'quality' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=quality">
        <i class="fas fa-star"></i> Quality
      </a>
    <?php else: ?>
      <a class="btn ghost" href="staff_reports.php?view=shift_summary"><i class="fas fa-clock"></i> My Shifts</a>
      <a class="btn ghost" href="staff_reports.php?view=my_metrics"><i class="fas fa-chart-bar"></i> My Metrics</a>
    <?php endif; ?>
  </div>
</div>

<!-- Report Content -->
<?php if(in_array($role, ['manager','admin','superadmin'])): ?>
  
  <?php if($view === 'performance'): ?>
    <div class="card">
      <div class="card-head">
        <div class="card-title"><i class="fas fa-trophy"></i> Performance Overview</div>
        <div class="muted">Staff performance metrics and productivity</div>
      </div>
      <div style="padding: 20px;">
        <?php if(empty($performance_data)): ?>
          <p class="muted" style="text-align: center; padding: 40px;">No performance data available for this station.</p>
        <?php else: ?>
          <table class="data-table" style="width: 100%;">
            <thead>
              <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px; text-align: left;">Staff Member</th>
                <th style="padding: 12px; text-align: left;">Role</th>
                <th style="padding: 12px; text-align: center;">Jobs Completed</th>
                <th style="padding: 12px; text-align: center;">Sales Processed</th>
                <th style="padding: 12px; text-align: right;">Total Revenue</th>
                <th style="padding: 12px; text-align: center;">Rating</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($performance_data as $staff): 
                $score = ($staff['jobs_completed'] * 10) + ($staff['sales_processed'] * 5);
                $rating = $score > 100 ? 'Excellent' : ($score > 50 ? 'Good' : 'Average');
                $rating_color = $score > 100 ? '#10b981' : ($score > 50 ? '#3b82f6' : '#f59e0b');
              ?>
              <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;">
                  <strong><?php echo htmlspecialchars($staff['username']); ?></strong>
                </td>
                <td style="padding: 12px;">
                  <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                    <?php echo htmlspecialchars(normalize_role($staff['role'])); ?>
                  </span>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <strong><?php echo number_format($staff['jobs_completed']); ?></strong>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <strong><?php echo number_format($staff['sales_processed']); ?></strong>
                </td>
                <td style="padding: 12px; text-align: right;">
                  <strong style="color: #059669;">₱<?php echo number_format($staff['total_revenue'], 2); ?></strong>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <span style="color: <?php echo $rating_color; ?>; font-weight: 600;">
                    <?php echo $rating; ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif($view === 'attendance'): ?>
    <div class="card">
      <div class="card-head">
        <div class="card-title"><i class="fas fa-calendar-check"></i> Attendance Records</div>
        <div class="muted">Staff attendance and hours worked</div>
      </div>
      <div style="padding: 20px;">
        <?php if(empty($attendance_data)): ?>
          <p class="muted" style="text-align: center; padding: 40px;">No attendance data available for this station.</p>
        <?php else: ?>
          <table class="data-table" style="width: 100%;">
            <thead>
              <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px; text-align: left;">Staff Member</th>
                <th style="padding: 12px; text-align: left;">Role</th>
                <th style="padding: 12px; text-align: center;">Days Present</th>
                <th style="padding: 12px; text-align: center;">Total Hours</th>
                <th style="padding: 12px; text-align: left;">Last Active</th>
                <th style="padding: 12px; text-align: center;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($attendance_data as $staff): 
                $status = $staff['days_present'] > 20 ? 'Regular' : ($staff['days_present'] > 10 ? 'Active' : 'Irregular');
                $status_color = $staff['days_present'] > 20 ? '#10b981' : ($staff['days_present'] > 10 ? '#3b82f6' : '#f59e0b');
              ?>
              <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;">
                  <strong><?php echo htmlspecialchars($staff['username']); ?></strong>
                </td>
                <td style="padding: 12px;">
                  <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                    <?php echo htmlspecialchars(normalize_role($staff['role'])); ?>
                  </span>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <strong><?php echo number_format($staff['days_present']); ?></strong> days
                </td>
                <td style="padding: 12px; text-align: center;">
                  <strong><?php echo number_format($staff['total_hours'], 1); ?></strong> hrs
                </td>
                <td style="padding: 12px;">
                  <?php echo $staff['last_active'] ? date('M d, Y h:i A', strtotime($staff['last_active'])) : 'Never'; ?>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <span style="color: <?php echo $status_color; ?>; font-weight: 600;">
                    <?php echo $status; ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif($view === 'quality'): ?>
    <div class="card">
      <div class="card-head">
        <div class="card-title"><i class="fas fa-star"></i> Quality Metrics</div>
        <div class="muted">Job completion rates and quality indicators</div>
      </div>
      <div style="padding: 20px;">
        <?php if(empty($quality_data)): ?>
          <p class="muted" style="text-align: center; padding: 40px;">No quality data available for this station.</p>
        <?php else: ?>
          <table class="data-table" style="width: 100%;">
            <thead>
              <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px; text-align: left;">Staff Member</th>
                <th style="padding: 12px; text-align: left;">Role</th>
                <th style="padding: 12px; text-align: center;">Completed</th>
                <th style="padding: 12px; text-align: center;">Cancelled</th>
                <th style="padding: 12px; text-align: center;">Success Rate</th>
                <th style="padding: 12px; text-align: center;">Grade</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($quality_data as $staff): 
                $rate = floatval($staff['success_rate']);
                $grade = $rate >= 90 ? 'A+' : ($rate >= 80 ? 'A' : ($rate >= 70 ? 'B' : ($rate >= 60 ? 'C' : 'D')));
                $grade_color = $rate >= 90 ? '#10b981' : ($rate >= 70 ? '#3b82f6' : '#f59e0b');
              ?>
              <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;">
                  <strong><?php echo htmlspecialchars($staff['username']); ?></strong>
                </td>
                <td style="padding: 12px;">
                  <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                    <?php echo htmlspecialchars(normalize_role($staff['role'])); ?>
                  </span>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <strong style="color: #059669;"><?php echo number_format($staff['completed_jobs']); ?></strong>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <strong style="color: #dc2626;"><?php echo number_format($staff['cancelled_jobs']); ?></strong>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <strong><?php echo number_format($rate, 1); ?>%</strong>
                </td>
                <td style="padding: 12px; text-align: center;">
                  <span style="background: <?php echo $grade_color; ?>; color: white; padding: 4px 12px; border-radius: 4px; font-weight: 700;">
                    <?php echo $grade; ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php else: ?>
  <!-- Staff View -->
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fas fa-user"></i> My Reports</div>
      <div class="muted">Your personal performance and activity</div>
    </div>
    
    <!-- Staff Report Tabs -->
    <div style="display: flex; gap: 10px; padding: 16px; flex-wrap: wrap;">
      <a class="btn <?php echo $view === 'shift_summary' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=shift_summary">
        <i class="fas fa-clock"></i> Shift Summary
      </a>
      <a class="btn <?php echo $view === 'job_summary' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=job_summary">
        <i class="fas fa-wrench"></i> Job Summary
      </a>
      <a class="btn <?php echo $view === 'fuel_summary' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=fuel_summary">
        <i class="fas fa-gas-pump"></i> Fuel Summary
      </a>
      <a class="btn <?php echo $view === 'my_metrics' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=my_metrics">
        <i class="fas fa-chart-bar"></i> My Metrics
      </a>
      <a class="btn <?php echo $view === 'feedback' ? 'primary' : 'ghost'; ?>" href="staff_reports.php?view=feedback">
        <i class="fas fa-comment"></i> Feedback & Ratings
      </a>
    </div>

    <!-- Staff Report Content -->
    <?php 
    // Fetch real data for staff metrics
    $today_sales = 0.0;
    $txn_today = 0;
    $active_jobs_count = 0;
    $total_hours_week = 0.0;
    
    try {
        // Today's sales by this staff member
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total_sales, COUNT(*) as txn_count 
                               FROM sales 
                               WHERE user_id = ? AND station_id = ? AND DATE(sale_date) = CURDATE()");
        $stmt->execute([$me['id'], $station_id]);
        $sales_today = $stmt->fetch(PDO::FETCH_ASSOC);
        $today_sales = floatval($sales_today['total_sales'] ?? 0);
        $txn_today = intval($sales_today['txn_count'] ?? 0);
        
        // Active job orders assigned to this staff
        $stmt = $pdo->prepare("SELECT COUNT(*) as active_count 
                               FROM job_orders 
                               WHERE (user_id = ? OR assigned_mechanic_id = ?) 
                               AND station_id = ? 
                               AND status NOT IN ('Completed', 'Cancelled', 'finalized')");
        $stmt->execute([$me['id'], $me['id'], $station_id]);
        $active_jobs_count = intval($stmt->fetchColumn() ?? 0);
        
        // Total hours worked this week
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(hours_worked), 0) as total_hours 
                               FROM labor_sessions 
                               WHERE user_id = ? AND station_id = ? 
                               AND start_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute([$me['id'], $station_id]);
        $total_hours_week = floatval($stmt->fetchColumn() ?? 0);
    } catch (Exception $e) {
        // Keep defaults if queries fail
    }
    ?>
    
    <?php if($view === 'shift_summary'): ?>
      <div class="card">
        <div class="card-head">
          <div class="card-title"><i class="fas fa-clock"></i> Shift Encoding Summary</div>
          <div class="muted">Your shift activity and encoding performance</div>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Shift</th>
                <th>Transactions Processed</th>
                <th>Fuel Readings</th>
                <th>Items Received</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fetch real shift summary data from database
              try {
                  // Get data grouped by date and shift for the last 14 days
                  $sql = "SELECT 
                            DATE(s.sale_date) as shift_date,
                            CASE 
                                WHEN TIME(s.created_at) BETWEEN '06:00:00' AND '14:00:00' THEN 'Morning'
                                WHEN TIME(s.created_at) BETWEEN '14:00:01' AND '22:00:00' THEN 'Afternoon'
                                ELSE 'Evening'
                            END as shift,
                            COUNT(DISTINCT s.id) as transactions
                          FROM sales s
                          WHERE s.user_id = ? AND s.station_id = ? 
                          AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                          GROUP BY DATE(s.sale_date), shift
                          ORDER BY shift_date DESC";
                  $stmt = $pdo->prepare($sql);
                  $stmt->execute([$me['id'], $station_id]);
                  $sales_by_shift = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  
                  // Get fuel readings by date
                  $sql_fuel = "SELECT 
                                reading_date, shift, COUNT(*) as fuel_count
                               FROM fuel_daily_readings
                               WHERE user_id = ? AND station_id = ?
                               AND reading_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                               GROUP BY reading_date, shift";
                  $stmt_fuel = $pdo->prepare($sql_fuel);
                  $stmt_fuel->execute([$me['id'], $station_id]);
                  $fuel_readings = [];
                  while ($row = $stmt_fuel->fetch(PDO::FETCH_ASSOC)) {
                      $key = $row['reading_date'] . '_' . $row['shift'];
                      $fuel_readings[$key] = $row['fuel_count'];
                  }
                  
                  // Get received items by date (from receiving_batches or inventory_transactions)
                  $sql_received = "SELECT 
                                    DATE(created_at) as receive_date, COUNT(*) as items_count
                                   FROM receiving_batches
                                   WHERE received_by = ? AND station_id = ?
                                   AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                                   GROUP BY DATE(created_at)";
                  $stmt_received = $pdo->prepare($sql_received);
                  $stmt_received->execute([$me['id'], $station_id]);
                  $received_items = [];
                  while ($row = $stmt_received->fetch(PDO::FETCH_ASSOC)) {
                      $received_items[$row['receive_date']] = $row['items_count'];
                  }
                  
                  if (empty($sales_by_shift)) {
                      echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: #666;'>No shift data available for the last 14 days.</td></tr>";
                  } else {
                      foreach ($sales_by_shift as $shift_data) {
                          $fuel_key = $shift_data['shift_date'] . '_' . $shift_data['shift'];
                          $fuel_count = $fuel_readings[$fuel_key] ?? 0;
                          $items_count = $received_items[$shift_data['shift_date']] ?? 0;
                          
                          echo "<tr>
                            <td>" . date('M d, Y', strtotime($shift_data['shift_date'])) . "</td>
                            <td><span class='badge' style='background: " . ($shift_data['shift'] == 'Morning' ? '#ffc107' : ($shift_data['shift'] == 'Afternoon' ? '#007bff' : '#6c757d')) . "; color: " . ($shift_data['shift'] == 'Morning' ? '#333' : 'white') . "; padding: 4px 8px; border-radius: 4px;'>" . htmlspecialchars($shift_data['shift']) . "</span></td>
                            <td>" . number_format($shift_data['transactions']) . "</td>
                            <td>" . number_format($fuel_count) . "</td>
                            <td>" . number_format($items_count) . "</td>
                          </tr>";
                      }
                  }
              } catch (Exception $e) {
                  echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: #dc3545;'>Error loading shift data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif($view === 'job_summary'): ?>
      <div class="card">
        <div class="card-head">
          <div class="card-title"><i class="fas fa-wrench"></i> Job Order Summary</div>
          <div class="muted">Your completed and ongoing job orders</div>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Job ID</th>
                <th>Vehicle</th>
                <th>Service Type</th>
                <th>Status</th>
                <th>Created</th>
                <th>Completed</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fetch staff's job orders
              try {
                  $stmt = $pdo->prepare("SELECT jo.*, c.name as customer_name FROM job_orders jo LEFT JOIN customers c ON jo.customer_id = c.id WHERE jo.user_id = ? AND jo.station_id = ? ORDER BY jo.created_at DESC LIMIT 20");
                  $stmt->execute([$me['id'], $station_id]);
                  $job_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
              } catch (Exception $e) {
                  $job_orders = [];
              }
              
              foreach ($job_orders as $job) {
                  echo "<tr>
                    <td>" . htmlspecialchars($job['id']) . "</td>
                    <td>" . htmlspecialchars($job['vehicle_plate'] ?? '-') . "</td>
                    <td>" . htmlspecialchars($job['service_type'] ?? '-') . "</td>
                    <td>" . htmlspecialchars($job['status']) . "</td>
                    <td>" . date('M d, Y', strtotime($job['created_at'])) . "</td>
                    <td>" . ($job['completed_at'] ? date('M d, Y', strtotime($job['completed_at'])) : '-') . "</td>
                  </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif($view === 'fuel_summary'): ?>
      <div class="card">
        <div class="card-head">
          <div class="card-title"><i class="fas fa-gas-pump"></i> Fuel Reading Summary</div>
          <div class="muted">Your fuel reading activity and accuracy</div>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Pump</th>
                <th>Shift</th>
                <th>Previous</th>
                <th>Current</th>
                <th>Sales (L)</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fetch staff's fuel readings with pump info
              try {
                  $stmt = $pdo->prepare("SELECT fr.*, fp.pump_number, ft.name as fuel_type 
                                         FROM fuel_daily_readings fr 
                                         LEFT JOIN fuel_pumps fp ON fr.pump_id = fp.id 
                                         LEFT JOIN fuel_types ft ON fp.fuel_type_id = ft.id
                                         WHERE fr.user_id = ? AND fr.station_id = ? 
                                         ORDER BY fr.reading_date DESC, fr.shift DESC 
                                         LIMIT 20");
                  $stmt->execute([$me['id'], $station_id]);
                  $fuel_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  
                  if (empty($fuel_readings)) {
                      echo "<tr><td colspan='7' style='text-align: center; padding: 20px; color: #666;'>No fuel readings found.</td></tr>";
                  } else {
                      foreach ($fuel_readings as $reading) {
                          $status_color = $reading['status'] === 'finalized' ? '#28a745' : ($reading['status'] === 'Verified' ? '#007bff' : '#ffc107');
                          echo "<tr>
                            <td>" . date('M d, Y', strtotime($reading['reading_date'])) . "</td>
                            <td>" . htmlspecialchars($reading['pump_number'] ?? 'Pump ' . $reading['pump_id']) . " <small style='color: #666;'>(" . htmlspecialchars($reading['fuel_type'] ?? 'N/A') . ")</small></td>
                            <td><span class='badge' style='background: " . ($reading['shift'] == 'Morning' ? '#ffc107' : ($reading['shift'] == 'Afternoon' ? '#007bff' : '#6c757d')) . "; color: " . ($reading['shift'] == 'Morning' ? '#333' : 'white') . "; padding: 4px 8px; border-radius: 4px;'>" . htmlspecialchars($reading['shift']) . "</span></td>
                            <td>" . number_format($reading['previous_reading'], 2) . "</td>
                            <td>" . number_format($reading['current_reading'], 2) . "</td>
                            <td><strong>" . number_format($reading['sales_liters'], 2) . "</strong></td>
                            <td><span style='color: " . $status_color . "; font-weight: 600;'>" . htmlspecialchars($reading['status'] ?? 'Pending') . "</span></td>
                          </tr>";
                      }
                  }
              } catch (Exception $e) {
                  echo "<tr><td colspan='7' style='text-align: center; padding: 20px; color: #dc3545;'>Error loading fuel readings.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif($view === 'my_metrics'): ?>
      <div class="card">
        <div class="card-head">
          <div class="card-title"><i class="fas fa-chart-bar"></i> My Metrics</div>
          <div class="muted">Your personal performance metrics and statistics</div>
        </div>
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
          <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
            <div style="font-size: 24px; font-weight: bold; color: #0066cc;"><?php echo number_format($today_sales, 2); ?></div>
            <div style="color: #666; font-size: 14px;">Today's Sales</div>
          </div>
          
          <div class="stat-card" style="background: #e3f2fd; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
            <div style="font-size: 24px; font-weight: bold; color: #0066cc;"><?php echo $txn_today; ?></div>
            <div style="color: #666; font-size: 14px;">Transactions Today</div>
          </div>
          
          <div class="stat-card" style="background: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
            <div style="font-size: 24px; font-weight: bold; color: #0066cc;"><?php echo $active_jobs_count; ?></div>
            <div style="color: #666; font-size: 14px;">Active Job Orders</div>
          </div>
          
          <div class="stat-card" style="background: #d1ecf1; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
            <div style="font-size: 24px; font-weight: bold; color: #0066cc;"><?php echo number_format($total_hours_week, 1); ?></div>
            <div style="color: #666; font-size: 14px;">Hours This Week</div>
          </div>
        </div>
      </div>

    <?php elseif($view === 'feedback'): ?>
      <div class="card">
        <div class="card-head">
          <div class="card-title"><i class="fas fa-comment"></i> Feedback & Ratings</div>
          <div class="muted">Customer feedback and your performance ratings</div>
        </div>
        <div style="padding: 40px; text-align: center;">
          <i class="fas fa-comment-slash" style="font-size: 48px; color: #ccc; margin-bottom: 16px;"></i>
          <p style="color: #666; margin-top: 16px;">Customer feedback feature is not yet available.</p>
          <p style="color: #999; font-size: 12px;">This feature will be implemented in a future update.</p>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head">
          <div class="card-title"><i class="fas fa-user"></i> My Reports</div>
          <div class="muted">Select a report type from above</div>
        </div>
        <div style="padding: 40px; text-align: center;">
          <i class="fas fa-chart-line" style="font-size: 48px; color: #ccc;"></i>
          <p style="color: #666; margin-top: 16px;">Choose a report type to view your detailed analytics</p>
        </div>
      </div>
    <?php endif; ?>

<?php include __DIR__ . '/../partials/footer.php'; ?>
