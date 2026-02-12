<?php
$page_id = 'operations_staff';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$role = role_key($me['role'] ?? 'staff');

// Role check - only operations staff can access this page
if ($role !== 'staff') {
    echo "<div style='padding:20px;color:red;'>Access Denied. Operations Staff privileges required.</div>";
    exit;
}

$station_id = user_station_id();

include __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
  <div>
    <h1 class="h1">Operations Staff Dashboard</h1>
    <div class="sub">Inventory Management and Operations</div>
  </div>
</div>

<!-- Quick Stats -->
<section class="cards four">
  <div class="card metric">
    <div class="metric-label">Fuel Inventory</div>
    <div class="metric-value blue">
      <?php
      try {
          $stmt = $pdo->prepare("
              SELECT COUNT(*) FROM station_inventory si 
              LEFT JOIN products p ON si.product_id = p.id 
              LEFT JOIN product_types pt ON p.type_id = pt.id 
              WHERE si.station_id = ? AND pt.name = 'fuel'
          ");
          $stmt->execute([$station_id]);
          echo $stmt->fetchColumn();
      } catch (Exception $e) {
          echo "0";
      }
      ?>
    </div>
    <div class="metric-sub">Fuel Types</div>
  </div>
  
  <div class="card metric">
    <div class="metric-label">Merchandise Items</div>
    <div class="metric-value green">
      <?php
      try {
          $stmt = $pdo->prepare("
              SELECT COUNT(*) FROM station_inventory si 
              LEFT JOIN products p ON si.product_id = p.id 
              LEFT JOIN product_types pt ON p.type_id = pt.id 
              WHERE si.station_id = ? AND pt.name = 'merch'
          ");
          $stmt->execute([$station_id]);
          echo $stmt->fetchColumn();
      } catch (Exception $e) {
          echo "0";
      }
      ?>
    </div>
    <div class="metric-sub">Products in Stock</div>
  </div>
  
  <div class="card metric">
    <div class="metric-label">Low Stock Items</div>
    <div class="metric-value red">
      <?php
      try {
          $stmt = $pdo->prepare("
              SELECT COUNT(*) FROM station_inventory si 
              WHERE si.station_id = ? AND si.stock_level <= si.reorder_level
          ");
          $stmt->execute([$station_id]);
          echo $stmt->fetchColumn();
      } catch (Exception $e) {
          echo "0";
      }
      ?>
    </div>
    <div class="metric-sub">Need Reorder</div>
  </div>
  
  <div class="card metric">
    <div class="metric-label">My Requests</div>
    <div class="metric-value orange">
      <?php
      try {
          $stmt = $pdo->prepare("
              SELECT COUNT(*) FROM stock_requests 
              WHERE station_id = ? AND requested_by = ? AND status = 'pending'
          ");
          $stmt->execute([$station_id, $me['id']]);
          echo $stmt->fetchColumn();
      } catch (Exception $e) {
          echo "0";
      }
      ?>
    </div>
    <div class="metric-sub">Pending Approval</div>
  </div>
</section>

<!-- Quick Actions -->
<section class="card" style="margin-top:20px; padding:20px;">
  <h2 class="h2">Quick Actions</h2>
  <div class="grid-3" style="gap:15px;">
    <a href="inventory.php" class="card" style="text-decoration:none; padding:20px; text-align:center; border:1px solid #ddd;">
      <div style="font-size:2em; color:#007bff; margin-bottom:10px;">
        <i class="fas fa-boxes"></i>
      </div>
      <div style="font-weight:600; color:#333;">Inventory Management</div>
      <div style="font-size:0.9em; color:#666; margin-top:5px;">Manage fuel and merchandise stock</div>
    </a>
    
    <a href="stock_request.php" class="card" style="text-decoration:none; padding:20px; text-align:center; border:1px solid #ddd;">
      <div style="font-size:2em; color:#28a745; margin-bottom:10px;">
        <i class="fas fa-clipboard-list"></i>
      </div>
      <div style="font-weight:600; color:#333;">Stock Requests</div>
      <div style="font-size:0.9em; color:#666; margin-top:5px;">Request new inventory items</div>
    </a>
    
    <a href="fuel_management.php" class="card" style="text-decoration:none; padding:20px; text-align:center; border:1px solid #ddd;">
      <div style="font-size:2em; color:#ffc107; margin-bottom:10px;">
        <i class="fas fa-gas-pump"></i>
      </div>
      <div style="font-weight:600; color:#333;">Fuel Management</div>
      <div style="font-size:0.9em; color:#666; margin-top:5px;">Monitor fuel levels</div>
    </a>
  </div>
</section>

<!-- Recent Activity -->
<section class="card" style="margin-top:20px; padding:20px;">
  <h2 class="h2">Recent Activity</h2>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Action</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT al.*, u.name as user_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE al.user_id = ? OR (al.details LIKE ? AND al.details LIKE ?)
                ORDER BY al.created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$me['id'], "%station $station_id%", "%Operations%"]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($activities as $activity) {
                echo "<tr>";
                echo "<td>" . date('M d, Y H:i', strtotime($activity['created_at'])) . "</td>";
                echo "<td>" . htmlspecialchars($activity['action']) . "</td>";
                echo "<td>" . htmlspecialchars($activity['details']) . "</td>";
                echo "</tr>";
            }
        } catch (Exception $e) {
            echo "<tr><td colspan='3'>Unable to load activity data</td></tr>";
        }
        
        if (empty($activities ?? [])) {
            echo "<tr><td colspan='3' style='text-align:center; color:#666;'>No recent activity found</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</section>

<style>
  .grid-3 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
  .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
  }
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
