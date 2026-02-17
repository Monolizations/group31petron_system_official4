<?php
$page_id = 'inventory';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/db_connect.php';
require_login();

$me = current_user();
$role = role_key($me['role'] ?? 'staff');
$isAdminOrSuper = in_array($role, ['admin', 'superadmin']);
$canStock = ($role === 'staff');
$canReview = in_array($role, ['manager', 'admin'], true);
$canFinalize = ($role === 'admin');
$station_id = user_station_id();

// Load fuel types from database for validation and dropdowns
$fuel_type_names = [];
try {
    $stmtFuelTypes = $pdo->query("SELECT name FROM fuel_types ORDER BY id");
    $fuel_type_names = $stmtFuelTypes->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Fallback to Petron brand names if query fails
    $fuel_type_names = ['Diesel Max', 'XCS Plus', 'XCS Advance', 'Turbo Diesel', 'Kerosene'];
}

$msg = '';

// CSRF Token for Security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $msg = "❌ Error: Invalid request.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_fuel') {
          if (!$canStock) {
                $msg = "❌ Error: Not permitted by RBAC to manage fuel inventory.";
            } else {
                $fuel_type = trim($_POST['fuel_type'] ?? '');
                $liters = (float)($_POST['liters'] ?? 0);
                $station = $role === 'superadmin' ? trim($_POST['station_id'] ?? '') : $station_id;

                // Input validation
                if (empty($fuel_type)) {
                    $msg = "❌ Error: Fuel type is required.";
                } elseif (!in_array($fuel_type, $fuel_type_names)) {
                    $msg = "❌ Error: Invalid fuel type.";
                } elseif ($liters <= 0 || $liters > 100000) { // Reasonable max to prevent abuse
                    $msg = "❌ Error: Liters must be a positive number and less than 100,000.";
                } elseif ($role === 'superadmin' && empty($station)) {
                    $msg = "❌ Error: Station is required for Super Admin.";
                } else {
                    try {
                        // Check if fuel type exists in inventory
                        $stmt = $pdo->prepare("SELECT si.id FROM station_inventory si LEFT JOIN products p ON si.product_id = p.id WHERE si.station_id = ? AND p.name = ? AND p.type_id = (SELECT id FROM product_types WHERE name = 'fuel')");
                        $stmt->execute([$station, $fuel_type]);
                        if ($stmt->rowCount() > 0) {
                            // Update existing
                            $stmt = $pdo->prepare("UPDATE station_inventory si JOIN products p ON si.product_id = p.id SET si.stock_level = si.stock_level + ? WHERE si.station_id = ? AND p.name = ? AND p.type_id = (SELECT id FROM product_types WHERE name = 'fuel')");
                            $stmt->execute([$liters, $station, $fuel_type]);
                            $msg = "✅ Fuel stock updated successfully.";
                            log_activity($pdo, $me['id'], 'Update Fuel Inventory', "Added $liters liters of $fuel_type to station $station");
                        } else {
                            // Insert new - need to get product_id first
                            $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? AND type_id = (SELECT id FROM product_types WHERE name = 'fuel')");
                            $stmt->execute([$fuel_type]);
                            $product_id = $stmt->fetchColumn();
                            if ($product_id) {
                                $stmt = $pdo->prepare("INSERT INTO station_inventory (station_id, product_id, stock_level, unit) VALUES (?, ?, ?, 'liters')");
                                $stmt->execute([$station, $product_id, $liters]);
                                $msg = "✅ Fuel stock added successfully.";
                                log_activity($pdo, $me['id'], 'Create Fuel Inventory', "Created $liters liters of $fuel_type for station $station");
                            } else {
                                $msg = "❌ Error: Fuel type not found in products.";
                            }
                        }
                    } catch (PDOException $e) {
                        $msg = "❌ Error: " . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'save_merch') {
          if (!$canStock) {
                $msg = "❌ Error: Not permitted by RBAC to manage merchandise inventory.";
            } else {
                $id = trim($_POST['id'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $sku = trim($_POST['sku'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $stock = (int)($_POST['stock'] ?? 0);
                $cost = (float)($_POST['cost'] ?? 0);
                $price = (float)($_POST['price'] ?? 0);
                $station = $role === 'superadmin' ? trim($_POST['station_id'] ?? '') : $station_id;

                // Input validation
                if (empty($name) || strlen($name) > 255) {
                    $msg = "❌ Error: Product name is required and must be less than 255 characters.";
                } elseif (strlen($sku) > 100) {
                    $msg = "❌ Error: SKU must be less than 100 characters.";
                } elseif (strlen($category) > 100) {
                    $msg = "❌ Error: Category must be less than 100 characters.";
                } elseif ($stock < 0 || $stock > 1000000) { // Reasonable max
                    $msg = "❌ Error: Stock must be non-negative and less than 1,000,000.";
                } elseif ($cost < 0 || $cost > 100000) {
                    $msg = "❌ Error: Cost must be non-negative and less than 100,000.";
                } elseif ($price < 0 || $price > 100000) {
                    $msg = "❌ Error: Price must be non-negative and less than 100,000.";
                } elseif ($price < $cost) {
                    $msg = "❌ Error: Selling price must be at least equal to cost.";
                } elseif ($role === 'superadmin' && empty($station)) {
                    $msg = "❌ Error: Station is required for Super Admin.";
                } else {
                    try {
                        if ($id) {
                            // Update - get product_id from inventory
                            $stmt = $pdo->prepare("SELECT product_id FROM station_inventory WHERE id=? AND station_id=?");
                            $stmt->execute([$id, $station]);
                            $product_id = $stmt->fetchColumn();
                            if ($product_id) {
                                // Fetch old values for audit trail
                                $stmtOld = $pdo->prepare("SELECT name, cost, price FROM products WHERE id = ?");
                                $stmtOld->execute([$product_id]);
                                $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
                                
                                $stmt = $pdo->prepare("UPDATE products SET name=?, sku=?, category_id=?, cost=?, price=? WHERE id=?");
                                $stmt->execute([$name, $sku, $category, $cost, $price, $product_id]);
                                $stmt = $pdo->prepare("UPDATE station_inventory SET stock_level=? WHERE id=?");
                                $stmt->execute([$stock, $id]);
                                $msg = "Merchandise updated successfully.";
                                
                                $price_change = '';
                                if ($old && ($old['cost'] != $cost || $old['price'] != $price)) {
                                    $price_change = " | Cost: P{$old['cost']} -> P{$cost} | Price: P{$old['price']} -> P{$price}";
                                }
                                log_activity($pdo, $me['id'], 'Update Merchandise Inventory', "Updated $name (ID: $id){$price_change}");
                            } else {
                                $msg = "❌ Error: Item not found.";
                            }
                        } else {
                            // Insert - create product first
                            $stmt = $pdo->prepare("INSERT INTO products (name, sku, type_id, category_id, cost, price) VALUES (?, ?, (SELECT id FROM product_types WHERE name='merch'), ?, ?, ?)");
                            $stmt->execute([$name, $sku, $category, $cost, $price]);
                            $product_id = $pdo->lastInsertId();
                            
                            $stmt = $pdo->prepare("INSERT INTO station_inventory (station_id, product_id, stock_level, unit) VALUES (?, ?, ?, 'pieces')");
                            $stmt->execute([$station, $product_id, $stock]);
                            $msg = "✅ Merchandise added successfully.";
                            log_activity($pdo, $me['id'], 'Create Merchandise Inventory', "Created $name for station $station");
                        }
                    } catch (PDOException $e) {
                        $msg = "❌ Error: " . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete_merch') {
          if (!$canStock) {
                $msg = "❌ Error: Not permitted by RBAC to delete merchandise.";
            } else {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $msg = "❌ Error: Invalid item ID.";
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE station_inventory FROM station_inventory WHERE id=? AND product_id IN (SELECT id FROM products WHERE type_id = (SELECT id FROM product_types WHERE name='merch'))");
                        $stmt->execute([$id]);
                        $msg = "✅ Merchandise deleted successfully.";
                        log_activity($pdo, $me['id'], 'Delete Merchandise Inventory', "Deleted item ID: $id");
                    } catch (PDOException $e) {
                        $msg = "❌ Error: " . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'request_stock') {
          if ($role !== 'staff') {
            $msg = "❌ Error: Not permitted by RBAC to request stock.";
          } else {
            $req_type = ($_POST['req_type'] ?? '') === 'merch' ? 'merch' : 'fuel';
            $product = trim((string)($_POST['product_name'] ?? ''));
            $qty = (float)($_POST['qty'] ?? 0);
            $notes = trim((string)($_POST['notes'] ?? ''));

            $station = ($role === 'superadmin') ? (int)($_POST['station_id'] ?? 0) : (int)$station_id;

            if ($station <= 0) {
                $msg = "❌ Error: Station is required.";
            } elseif ($product === '') {
                $msg = "❌ Error: Product is required.";
            } elseif ($qty <= 0) {
                $msg = "❌ Error: Quantity must be greater than 0.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO stock_requests (station_id, requested_by, type, product_name, qty, notes, status, created_at)
                                          VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $stmt->execute([$station, (int)($me['id'] ?? 0), $req_type, $product, $qty, $notes]);
                    $msg = "✅ Stock request submitted. Waiting for admin approval.";
                    log_activity($pdo, $me['id'], 'Create Stock Request', "Requested $qty of $product ($req_type) for station $station");
                } catch (PDOException $e) {
                    // Most common cause: table doesn't exist yet
                    $msg = "❌ Error: Unable to save stock request. (Make sure stock_requests table exists.)";
                }
                  }
            }
        }

        // Admin/Super Admin: Approve/Reject a stock request
        elseif (in_array($action, ['approve_request','reject_request'], true)) {
          if (!in_array($role, ['admin','manager'], true)) {
                $msg = "❌ Error: Not permitted.";
            } else {
                $rid = (int)($_POST['request_id'] ?? 0);
                if ($rid <= 0) {
                    $msg = "❌ Error: Invalid request.";
                } else {
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM stock_requests WHERE id=?");
                        $stmt->execute([$rid]);
                        $req = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$req) {
                            $msg = "❌ Error: Request not found.";
                        } elseif (($req['status'] ?? '') !== 'pending') {
                            $msg = "❌ Error: Request already processed.";
                        } else {
                            // Station-level admins can only act on their station
                            if ($role !== 'superadmin' && (int)$req['station_id'] !== (int)$station_id) {
                                $msg = "❌ Error: You can only process requests from your station.";
                            } else {
                                if ($action === 'reject_request') {
                                    $stmt = $pdo->prepare("UPDATE stock_requests SET status='rejected', processed_by=?, processed_at=NOW() WHERE id=?");
                                    $stmt->execute([(int)($me['id'] ?? 0), $rid]);
                                    $msg = "✅ Request rejected.";
                                } else {
                                    // Approve: update inventory based on type
                                    $reqType = ($req['type'] ?? '') === 'merch' ? 'merch' : 'fuel';
                                    $product = (string)($req['product_name'] ?? '');
                                    $qty = (float)($req['qty'] ?? 0);
                                    $station = (int)($req['station_id'] ?? 0);

                                    if ($reqType === 'fuel') {
                                        // Add liters to fuel inventory
                                        $stmtI = $pdo->prepare("SELECT si.id FROM station_inventory si LEFT JOIN products p ON si.product_id = p.id WHERE si.station_id=? AND p.name=? AND p.type_id = (SELECT id FROM product_types WHERE name='fuel')");
                                        $stmtI->execute([$station, $product]);
                                        if ($stmtI->rowCount() > 0) {
                                            $stmtU = $pdo->prepare("UPDATE station_inventory si JOIN products p ON si.product_id = p.id SET si.stock_level = si.stock_level + ? WHERE si.station_id=? AND p.name=? AND p.type_id = (SELECT id FROM product_types WHERE name='fuel')");
                                            $stmtU->execute([$qty, $station, $product]);
                                        } else {
                                            // Get product_id first
                                            $stmtP = $pdo->prepare("SELECT id FROM products WHERE name=? AND type_id = (SELECT id FROM product_types WHERE name='fuel')");
                                            $stmtP->execute([$product]);
                                            $product_id = $stmtP->fetchColumn();
                                            if ($product_id) {
                                                $stmtIns = $pdo->prepare("INSERT INTO station_inventory (station_id, product_id, stock_level, unit) VALUES (?, ?, ?, 'liters')");
                                                $stmtIns->execute([$station, $product_id, $qty]);
                                            }
                                        }
                                    } else {
                                        // Add pieces to merchandise inventory
                                        $stmtI = $pdo->prepare("SELECT si.id FROM station_inventory si LEFT JOIN products p ON si.product_id = p.id WHERE si.station_id=? AND p.name=? AND p.type_id = (SELECT id FROM product_types WHERE name='merch')");
                                        $stmtI->execute([$station, $product]);
                                        if ($stmtI->rowCount() > 0) {
                                            $stmtU = $pdo->prepare("UPDATE station_inventory si JOIN products p ON si.product_id = p.id SET si.stock_level = si.stock_level + ? WHERE si.station_id=? AND p.name=? AND p.type_id = (SELECT id FROM product_types WHERE name='merch')");
                                            $stmtU->execute([(int)$qty, $station, $product]);
                                        } else {
                                            // Get product_id first
                                            $stmtP = $pdo->prepare("SELECT id FROM products WHERE name=? AND type_id = (SELECT id FROM product_types WHERE name='merch')");
                                            $stmtP->execute([$product]);
                                            $product_id = $stmtP->fetchColumn();
                                            if ($product_id) {
                                                $stmtIns = $pdo->prepare("INSERT INTO station_inventory (station_id, product_id, stock_level, unit) VALUES (?, ?, ?, 'pieces')");
                                                $stmtIns->execute([$station, $product_id, (int)$qty]);
                                            }
                                        }
                                    }

                                    $stmt = $pdo->prepare("UPDATE stock_requests SET status='approved', processed_by=?, processed_at=NOW() WHERE id=?");
                                    $stmt->execute([(int)($me['id'] ?? 0), $rid]);
                                    $msg = "✅ Request approved and stock updated.";
                                    log_activity($pdo, $me['id'], 'Approve Stock Request', "Approved request #$rid ($qty $product) station $station");
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        $msg = "❌ Error: " . $e->getMessage();
                    }
                }
            }
        }
    }

// Fetch Fuel Inventory
$fuel_inventory = [];
if ($role === 'superadmin') {
    $stmt = $pdo->prepare("
        SELECT si.*, s.name as station_name, p.name as product_name, p.sku, pt.name as type
        FROM station_inventory si 
        LEFT JOIN stations s ON si.station_id = s.id 
        LEFT JOIN products p ON si.product_id = p.id
        LEFT JOIN product_types pt ON p.type_id = pt.id
        WHERE pt.name = 'fuel' 
        ORDER BY s.name, p.name
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT si.*, s.name as station_name, p.name as product_name, p.sku, pt.name as type
        FROM station_inventory si 
        LEFT JOIN stations s ON si.station_id = s.id 
        LEFT JOIN products p ON si.product_id = p.id
        LEFT JOIN product_types pt ON p.type_id = pt.id
        WHERE pt.name = 'fuel' AND si.station_id = ? 
        ORDER BY p.name
    ");
    $stmt->execute([$station_id]);
}
$fuel_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Merchandise Inventory
$merch_inventory = [];
if ($role === 'superadmin') {
    $stmt = $pdo->prepare("
        SELECT si.*, s.name as station_name, p.name as product_name, p.sku, p.cost, p.price, pt.name as type, pc.name as category_name
        FROM station_inventory si 
        LEFT JOIN stations s ON si.station_id = s.id 
        LEFT JOIN products p ON si.product_id = p.id
        LEFT JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE pt.name = 'merch' 
        ORDER BY s.name, p.name
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT si.*, s.name as station_name, p.name as product_name, p.sku, p.cost, p.price, pt.name as type, pc.name as category_name
        FROM station_inventory si 
        LEFT JOIN stations s ON si.station_id = s.id 
        LEFT JOIN products p ON si.product_id = p.id
        LEFT JOIN product_types pt ON p.type_id = pt.id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE pt.name = 'merch' AND si.station_id = ? 
        ORDER BY p.name
    ");
    $stmt->execute([$station_id]);
}
$merch_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch stations for superadmin
$stations = [];
if ($role === 'superadmin') {
    $stations = $pdo->query("SELECT id, name FROM stations ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);
}

include __DIR__ . '/../partials/header.php';
?>
  <div class="page-head" data-rendering="php">
    <div>
      <h1 class="h1">Inventory Management</h1>
      <div class="sub">Track fuel levels and merchandise stock</div>
    </div>
  </div>

  <?php if($msg): ?><div class="card" style="padding:10px; margin-top:10px; background:#e6f4ea; color:green;"><?php echo $msg; ?></div><?php endif; ?>

  <div class="tabs pills">
    <button class="tab active" data-invtab="fuel"><i class="fas fa-gas-pump"></i> Fuel Inventory</button>
    <button class="tab" data-invtab="merch"><i class="fas fa-box"></i> Merchandise</button>
    <button class="tab" data-invtab="parts"><i class="fas fa-tools"></i> Available Parts</button>
    <button class="tab" data-invtab="low_stock"><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</button>
  </div>

  <!-- Fuel Inventory -->
  <section class="card" id="fuelInv">
    <div class="card-head">
      <div class="card-title">Fuel Inventory</div>
      <?php if ($role === 'staff'): ?>
        <a href="purchase_order.php" class="btn primary">+ Create PO</a>
      <?php elseif ($canStock): ?>
        <button class="btn primary" onclick="openFuelModal()">+ Stock In</button>
      <?php endif; ?>
    </div>
    <div class="table-wrap">
      <table class="table" id="fuelTable">
        <thead>
          <tr>
            <?php if ($role === 'superadmin'): ?><th>Station</th><?php endif; ?>
            <th>Fuel Type</th>
            <th>Current Level</th>
            <th>Capacity</th>
            <th>Status</th>
            <th>Price/L</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fuel_inventory as $item): ?>
            <tr>
              <?php if ($role === 'superadmin'): ?><td><?php echo htmlspecialchars($item['station_name'] ?? 'Unknown'); ?></td><?php endif; ?>
              <td><?php echo htmlspecialchars($item['product_name']); ?></td>
              <td><?php echo number_format($item['stock_level'], 2); ?> L</td>
              <td><?php echo number_format($item['capacity'] ?? 10000, 2); ?> L</td>
              <td>
                <?php
                $level = (float)($item['stock_level'] ?? 0);
                $capacity = (float)($item['capacity'] ?? 10000);
                $percentage = $capacity > 0 ? ($level / $capacity) * 100 : 0;
                $status = $percentage < 20 ? 'Low Stock' : ($percentage > 90 ? 'Near Full' : 'Normal');
                $status_color = $percentage < 20 ? 'red' : ($percentage > 90 ? 'orange' : 'green');
                echo "<span style='color: $status_color;'>$status</span>";
                ?>
              </td>
              <td>₱<?php echo number_format($item['price'] ?? 0, 2); ?></td>
              <td class="right">
                <?php if ($role === 'staff'): ?>
                  <a href="purchase_order.php?item=<?= urlencode($item['product_name']) ?>&qty=<?= max(1, ceil(($item['reorder_level'] ?? 1000) * 1.5 - $item['stock_level'])) ?>" class="btn btn-sm btn-primary" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                    <i class="fas fa-file-invoice"></i> Create PO
                  </a>
                <?php elseif ($canStock): ?>
                  <button class="btn ghost small" onclick="editFuel('<?php echo $item['product_name']; ?>', <?php echo $item['stock_level']; ?>)">Edit</button>
                <?php else: ?>
                  <span class="muted">View Only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($fuel_inventory)): ?>
            <tr><td colspan="<?php echo $role === 'superadmin' ? 7 : 6; ?>" style="text-align:center;">No fuel inventory data available.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Merchandise Inventory -->
  <section class="card hidden" id="merchInv">
    <div class="card-head">
      <div class="card-title">Merchandise Inventory</div>
      <?php if ($role === 'staff'): ?>
        <a href="purchase_order.php" class="btn primary">+ Create PO</a>
      <?php elseif ($canStock): ?>
        <button class="btn primary" onclick="openMerchModal()">+ Add Item</button>
      <?php endif; ?>
    </div>

    <div class="table-tools">
      <div class="searchbar small">
        <span class="ico"><i class="fas fa-search"></i></span>
        <input id="merchSearch" placeholder="Search items..." autocomplete="off" />
      </div>
    </div>

    <div class="table-wrap">
      <table class="table" id="merchTable">
        <thead>
          <tr>
            <?php if ($role === 'superadmin'): ?><th>Station</th><?php endif; ?>
            <th>Product</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Cost</th>
            <th>Price</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($merch_inventory as $item): ?>
            <tr>
              <?php if ($role === 'superadmin'): ?><td><?php echo htmlspecialchars($item['station_name'] ?? 'Unknown'); ?></td><?php endif; ?>
              <td><?php echo htmlspecialchars($item['product_name']); ?></td>
              <td><?php echo htmlspecialchars($item['sku'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></td>
              <td><?php echo number_format($item['stock_level'], 0); ?></td>
              <td>₱<?php echo number_format($item['cost'] ?? 0, 2); ?></td>
              <td>₱<?php echo number_format($item['price'] ?? 0, 2); ?></td>
              <td class="right">
                <?php if ($role === 'staff'): ?>
                  <a href="purchase_order.php?item=<?= urlencode($item['product_name']) ?>&qty=<?= max(1, ceil(($item['reorder_level'] ?? 10) * 1.5 - $item['stock_level'])) ?>" class="btn btn-sm btn-primary" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                    <i class="fas fa-file-invoice"></i> Create PO
                  </a>
                <?php elseif ($canStock): ?>
                  <button class="btn ghost small" onclick="editMerch(<?php echo $item['id']; ?>)">Edit</button>
                  <button class="btn ghost small red" onclick="deleteMerch(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['product_name']); ?>')">Delete</button>
                <?php else: ?>
                  <span class="muted">View Only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($merch_inventory)): ?>
            <tr><td colspan="<?php echo $role === 'superadmin' ? 8 : 7; ?>" style="text-align:center;">No merchandise inventory data available.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Stock Requests -->
  <section class="card hidden" id="reqInv">
    <div class="card-head">
      <div class="card-title">Stock Requests</div>
      <div class="muted">Staff submit requests here; admin approves and stock updates automatically.</div>
    </div>

    <?php
      // Fetch requests (pending first)
      $requests = [];
      try {
        if ($role === 'superadmin') {
          $stmt = $pdo->query("SELECT r.*, s.name AS station_name, u.name AS requester_name
                               FROM stock_requests r
                               LEFT JOIN stations s ON r.station_id = s.id
                               LEFT JOIN users u ON r.requested_by = u.id
                               ORDER BY (r.status='pending') DESC, r.created_at DESC");
          $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
          $stmt = $pdo->prepare("SELECT r.*, s.name AS station_name, u.name AS requester_name
                                 FROM stock_requests r
                                 LEFT JOIN stations s ON r.station_id = s.id
                                 LEFT JOIN users u ON r.requested_by = u.id
                                 WHERE r.station_id = ?
                                 ORDER BY (r.status='pending') DESC, r.created_at DESC");
          $stmt->execute([$station_id]);
          $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
      } catch (Exception $e) {
        $requests = [];
      }
    ?>

    <!-- Request form (any logged-in role) -->
    <div class="card" style="margin:14px 0; padding:14px;">
      <div style="font-weight:600; margin-bottom:10px;"><i class="fas fa-paper-plane"></i> Create a request</div>
      <form method="post" class="grid" style="display:grid; grid-template-columns: 180px 1fr 160px 1fr; gap:10px; align-items:end;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type="hidden" name="action" value="request_stock" />

        <div>
          <label class="pay-label">Type</label>
          <select class="select" name="req_type">
            <option value="fuel">Fuel (Liters)</option>
            <option value="merch">Merchandise (Pieces)</option>
          </select>
        </div>

        <div>
          <label class="pay-label">Product name</label>
          <input class="input" name="product_name" placeholder="e.g., Diesel Max / Engine Oil" required />
        </div>

        <div>
          <label class="pay-label">Qty</label>
          <input class="input" name="qty" type="number" min="0.01" step="0.01" placeholder="0" required />
        </div>

        <div>
          <label class="pay-label">Notes</label>
          <input class="input" name="notes" placeholder="Optional" />
        </div>

        <?php if ($role === 'superadmin'): ?>
          <div style="grid-column: 1 / -1;">
            <label class="pay-label">Station</label>
            <select class="select" name="station_id" required>
              <option value="">-- Select Station --</option>
              <?php foreach ($stations as $sid => $sname): ?>
                <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($sname); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div style="grid-column: 1 / -1; display:flex; gap:10px; justify-content:flex-end;">
          <button class="btn primary" type="submit">Submit Request</button>
        </div>
      </form>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <?php if ($role === 'superadmin'): ?><th>Station</th><?php endif; ?>
            <th>Date</th>
            <th>Requested By</th>
            <th>Type</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Status</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
            <tr>
              <?php if ($role === 'superadmin'): ?><td><?php echo htmlspecialchars($r['station_name'] ?? ''); ?></td><?php endif; ?>
              <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['requester_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars(($r['type'] ?? '') === 'merch' ? 'Merch' : 'Fuel'); ?></td>
              <td><?php echo htmlspecialchars($r['product_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['qty'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars(ucfirst($r['status'] ?? '')); ?></td>
              <td class="right">
                <?php if (($r['status'] ?? '') === 'pending'): ?>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
                    <input type="hidden" name="action" value="approve_request" />
                    <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>" />
                    <button class="btn ghost small" type="submit">Approve</button>
                  </form>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
                    <input type="hidden" name="action" value="reject_request" />
                    <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>" />
                    <button class="btn ghost small red" type="submit">Reject</button>
                  </form>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($requests)): ?>
            <tr><td colspan="<?php echo $role === 'superadmin' ? 8 : 7; ?>" style="text-align:center;">No requests yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Received Items -->
  <section class="card hidden" id="receivedInv">
    <div class="card-head">
      <div class="card-title">Received Items</div>
      <div class="muted">Items that have been delivered and received at the station</div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <?php if ($role === 'superadmin'): ?><th>Station</th><?php endif; ?>
            <th>Date Received</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Received By</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Fetch received items from database
          $received_items = [];
          try {
            if ($role === 'superadmin') {
              $stmt = $pdo->query("
                  SELECT ri.*, s.name as station_name, u.name as received_by_name, p.name as product_name
                  FROM received_items ri
                  LEFT JOIN stations s ON ri.station_id = s.id
                  LEFT JOIN users u ON ri.received_by = u.id
                  LEFT JOIN products p ON ri.product_id = p.id
                  ORDER BY ri.received_date DESC
              ");
              $received_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
              $stmt = $pdo->prepare("
                  SELECT ri.*, s.name as station_name, u.name as received_by_name, p.name as product_name
                  FROM received_items ri
                  LEFT JOIN stations s ON ri.station_id = s.id
                  LEFT JOIN users u ON ri.received_by = u.id
                  LEFT JOIN products p ON ri.product_id = p.id
                  WHERE ri.station_id = ?
                  ORDER BY ri.received_date DESC
              ");
              $stmt->execute([$station_id]);
              $received_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
          } catch (Exception $e) {
            $received_items = [];
          }
          ?>
          <?php foreach ($received_items as $item): ?>
            <tr>
              <?php if ($role === 'superadmin'): ?><td>Main Station</td><?php endif; ?>
              <td><?php echo date('M d, Y', strtotime($item['received_date'] ?? '')); ?></td>
              <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
              <td><?php echo number_format($item['quantity'] ?? 0, 0); ?></td>
              <td><?php echo htmlspecialchars($item['received_by_name'] ?? ''); ?></td>
              <td class="right">
                <button class="btn ghost small" onclick="viewReceivedItem(<?php echo $item['id']; ?>)">View</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Delivery History -->
  <section class="card hidden" id="deliveryInv">
    <div class="card-head">
      <div class="card-title">Delivery History</div>
      <div class="muted">History of all deliveries made to the station</div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <?php if ($role === 'superadmin'): ?><th>Station</th><?php endif; ?>
            <th>Date Received</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Received By</th>
            <th>Supplier</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Fetch delivery history from database (using received_items table)
          $delivery_history = [];
          try {
            if ($role === 'superadmin') {
              $stmt = $pdo->query("
                  SELECT ri.*, s.name as station_name, u.name as received_by_name, p.name as product_name
                  FROM received_items ri
                  LEFT JOIN stations s ON ri.station_id = s.id
                  LEFT JOIN users u ON ri.received_by = u.id
                  LEFT JOIN products p ON ri.product_id = p.id
                  ORDER BY ri.delivery_date DESC, ri.created_at DESC
              ");
              $delivery_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
              $stmt = $pdo->prepare("
                  SELECT ri.*, s.name as station_name, u.name as received_by_name, p.name as product_name
                  FROM received_items ri
                  LEFT JOIN stations s ON ri.station_id = s.id
                  LEFT JOIN users u ON ri.received_by = u.id
                  LEFT JOIN products p ON ri.product_id = p.id
                  WHERE ri.station_id = ?
                  ORDER BY ri.delivery_date DESC, ri.created_at DESC
              ");
              $stmt->execute([$station_id]);
              $delivery_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
          } catch (Exception $e) {
            $delivery_history = [];
          }
          ?>
          <?php foreach ($delivery_history as $delivery): ?>
            <tr>
              <?php if ($role === 'superadmin'): ?><td><?php echo htmlspecialchars($delivery['station_name'] ?? ''); ?></td><?php endif; ?>
              <td><?php echo date('M d, Y', strtotime($delivery['delivery_date'] ?? $delivery['created_at'])); ?></td>
              <td><?php echo htmlspecialchars($delivery['product_name'] ?? ''); ?></td>
              <td><?php echo number_format($delivery['quantity'] ?? 0, 2); ?></td>
              <td><?php echo htmlspecialchars($delivery['received_by_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($delivery['supplier'] ?? ''); ?></td>
              <td class="right">
                <button class="btn ghost small" onclick="viewReceivedItem(<?php echo $delivery['id']; ?>)">View</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Available Parts -->
  <section class="card hidden" id="partsInv">
    <div class="card-head">
      <div class="card-title">Available Parts</div>
      <div class="muted">Automotive parts and service items available for use</div>
    </div>

    <div class="table-tools">
      <div class="searchbar small">
        <span class="ico"><i class="fas fa-search"></i></span>
        <input id="partsSearch" placeholder="Search parts..." autocomplete="off" />
      </div>
    </div>

    <div class="table-wrap">
      <table class="table" id="partsTable">
        <thead>
          <tr>
            <?php if ($role === 'superadmin'): ?><th>Station</th><?php endif; ?>
            <th>Part Name</th>
            <th>Category</th>
            <th>Part Number</th>
            <th>Stock</th>
            <th>Price</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Fetch parts (merchandise/service items)
          $parts_inventory = [];
          try {
            if ($role === 'superadmin') {
              $stmt = $pdo->query("
                  SELECT si.*, s.name as station_name, p.name as product_name, p.sku, p.price, p.cost, pc.name as category_name
                  FROM station_inventory si 
                  LEFT JOIN stations s ON si.station_id = s.id 
                  LEFT JOIN products p ON si.product_id = p.id
                  LEFT JOIN product_types pt ON p.type_id = pt.id
                  LEFT JOIN product_categories pc ON p.category_id = pc.id
                  WHERE pt.name IN ('merch', 'service')
                  ORDER BY p.name
              ");
              $parts_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
              $stmt = $pdo->prepare("
                  SELECT si.*, s.name as station_name, p.name as product_name, p.sku, p.price, p.cost, pc.name as category_name
                  FROM station_inventory si 
                  LEFT JOIN stations s ON si.station_id = s.id 
                  LEFT JOIN products p ON si.product_id = p.id
                  LEFT JOIN product_types pt ON p.type_id = pt.id
                  LEFT JOIN product_categories pc ON p.category_id = pc.id
                  WHERE si.station_id = ? AND pt.name IN ('merch', 'service')
                  ORDER BY p.name
              ");
              $stmt->execute([$station_id]);
              $parts_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
          } catch (Exception $e) {
            $parts_inventory = [];
          }
          ?>
          <?php foreach ($parts_inventory as $part): ?>
            <tr>
              <?php if ($role === 'superadmin'): ?><td><?php echo htmlspecialchars($part['station_name'] ?? 'Unknown'); ?></td><?php endif; ?>
              <td><?php echo htmlspecialchars($part['product_name']); ?></td>
              <td><?php echo htmlspecialchars($part['category_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($part['sku'] ?? ''); ?></td>
              <td><?php echo number_format($part['stock_level'], 0); ?></td>
              <td>₱<?php echo number_format($part['price'] ?? 0, 2); ?></td>
              <td class="right">
                <?php if ($role === 'staff'): ?>
                  <a href="purchase_order.php?item=<?= urlencode($item['product_name']) ?>&qty=<?= max(1, ceil(($item['reorder_level'] ?? 10) * 1.5 - $item['stock_level'])) ?>" class="btn btn-sm btn-primary" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                    <i class="fas fa-file-invoice"></i> Create PO
                  </a>
                <?php elseif ($canStock): ?>
                  <button class="btn ghost small" onclick="editMerch(<?php echo $item['id']; ?>)" data-close="merchModal">✕</button>
                <?php else: ?>
                  <span class="muted">View Only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($parts_inventory)): ?>
            <tr><td colspan="<?php echo $role === 'superadmin' ? 7 : 6; ?>" style="text-align:center;">No parts available.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Low Stock Alerts -->
  <section class="card hidden" id="lowStockInv">
    <div class="card-head">
      <div class="card-title">Low Stock Alerts</div>
      <div class="muted">Items at 50% or below reorder level (applies to fuel and merchandise)</div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <?php if ($role === 'superadmin'): ?><th>Station</th><?php endif; ?>
            <th>Product</th>
            <th>Category</th>
            <th>Current Stock</th>
            <th>Reorder Level</th>
            <th>% of Reorder</th>
            <th>Status</th>
            <th>Last Updated</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Fetch low stock items (50% or less of reorder level)
          $low_stock_items = [];
          try {
            if ($role === 'superadmin') {
              $stmt = $pdo->query("
                  SELECT si.*, s.name as station_name, p.name as product_name, pc.name as category_name, pt.name as product_type
                  FROM station_inventory si 
                  LEFT JOIN stations s ON si.station_id = s.id 
                  LEFT JOIN products p ON si.product_id = p.id
                  LEFT JOIN product_types pt ON p.type_id = pt.id
                  LEFT JOIN product_categories pc ON p.category_id = pc.id
                  WHERE si.stock_level <= (COALESCE(si.reorder_level, 10) * 0.5) 
                     OR si.stock_level = 0
                  ORDER BY (si.stock_level / NULLIF(si.reorder_level, 0)) ASC
              ");
              $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
              $stmt = $pdo->prepare("
                  SELECT si.*, s.name as station_name, p.name as product_name, pc.name as category_name, pt.name as product_type
                  FROM station_inventory si 
                  LEFT JOIN stations s ON si.station_id = s.id 
                  LEFT JOIN products p ON si.product_id = p.id
                  LEFT JOIN product_types pt ON p.type_id = pt.id
                  LEFT JOIN product_categories pc ON p.category_id = pc.id
                  WHERE si.station_id = ? 
                    AND (si.stock_level <= (COALESCE(si.reorder_level, 10) * 0.5) 
                     OR si.stock_level = 0)
                  ORDER BY (si.stock_level / NULLIF(si.reorder_level, 0)) ASC
              ");
              $stmt->execute([$station_id]);
              $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
          } catch (Exception $e) {
            $low_stock_items = [];
          }
          ?>
          <?php foreach ($low_stock_items as $item): 
            $percentage = $item['reorder_level'] > 0 ? round(($item['stock_level'] / $item['reorder_level']) * 100, 1) : 0;
            $status_color = $percentage <= 25 ? '#dc3545' : ($percentage <= 50 ? '#fd7e14' : '#ffc107');
            $status_text = $percentage <= 25 ? 'CRITICAL' : ($percentage <= 50 ? 'LOW' : 'WARNING');
          ?>
            <tr style="background-color: <?php echo $percentage <= 25 ? '#fff2f2' : '#fff8e6'; ?>;">
              <?php if ($role === 'superadmin'): ?><td><?php echo htmlspecialchars($item['station_name'] ?? 'Unknown'); ?></td><?php endif; ?>
              <td><?php echo htmlspecialchars($item['product_name']); ?></td>
              <td><?php echo htmlspecialchars($item['category_name'] ?? $item['product_type'] ?? ''); ?></td>
              <td>
                <span style="color: #dc3545; font-weight: bold;"><?php echo number_format($item['stock_level'], 0); ?></span>
                <span style="color: #6c757d;"> / <?php echo number_format($item['reorder_level'], 0); ?></span>
              </td>
              <td><?php echo number_format($item['reorder_level'], 0); ?></td>
              <td>
                <span style="color: <?php echo $status_color; ?>; font-weight: bold;"><?php echo $percentage; ?>%</span>
              </td>
              <td>
                <span class="badge" style="background: <?php echo $status_color; ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                  <?php echo $status_text; ?>
                </span>
              </td>
              <td><?php echo date('M d, Y', strtotime($item['last_updated'] ?? $item['created_at'])); ?></td>
              <td class="right">
                <?php if ($canStock): ?>
                  <a href="purchase_order.php?item=<?= urlencode($item['product_name']) ?>&qty=<?= max(1, ceil($item['reorder_level'] * 1.5 - $item['stock_level'])) ?>" class="btn btn-sm btn-primary" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                    <i class="fas fa-file-invoice"></i> Create PO
                  </a>
                <?php else: ?>
                  <span class="muted">Contact Manager</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($low_stock_items)): ?>
            <tr><td colspan="<?php echo $role === 'superadmin' ? 9 : 8; ?>" style="text-align:center;">✅ No low stock items. All inventory levels are above 50% of reorder level!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Fuel Stock In Modal -->
  <div class="modal" id="fuelModal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-head">
        <div class="modal-title" id="fuelModalTitle">Stock In Fuel</div>
        <button class="icon-btn" onclick="document.getElementById('fuelModal').classList.remove('active')">✕</button>
      </div>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type="hidden" name="action" value="save_fuel">
        <?php if ($role === 'superadmin'): ?>
          <div class="pay-section">
            <label class="pay-label">Station</label>
            <select class="select" name="station_id" required>
              <option value="">-- Select Station --</option>
              <?php foreach ($stations as $sid => $sname): ?>
                <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($sname); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($station_id); ?>">
        <?php endif; ?>
        <div class="pay-section">
          <label class="pay-label">Fuel Type</label>
          <select class="select" name="fuel_type" id="fuelSelect" required>
            <option value="">-- Select Fuel Type --</option>
            <?php foreach ($fuel_type_names as $ft_name): ?>
              <option value="<?php echo htmlspecialchars($ft_name); ?>"><?php echo htmlspecialchars($ft_name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="pay-section">
          <label class="pay-label">Liters to add</label>
          <input class="input" name="liters" id="fuelLiters" type="number" min="0.01" step="0.01" placeholder="0.00" required />
        </div>
        <div class="modal-actions">
          <button type="button" class="btn" onclick="document.getElementById('fuelModal').classList.remove('active')">Cancel</button>
          <button type="submit" class="btn primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add/Edit Merchandise Modal -->
  <div class="modal" id="merchModal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-head">
        <div class="modal-title" id="merchModalTitle">Add Item</div>
        <button class="icon-btn" data-close="merchModal">✕</button>
      </div>

      <form method="post" id="merchForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type="hidden" name="action" value="save_merch" />
        <input type="hidden" name="id" id="mId" value="" />

        <?php if ($role === 'superadmin'): ?>
          <div class="pay-section" style="margin-bottom:10px;">
            <label class="pay-label">Station</label>
            <select class="select" name="station_id" id="mStation" required>
              <option value="">-- Select Station --</option>
              <?php foreach ($stations as $sid => $sname): ?>
                <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($sname); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($station_id); ?>">
        <?php endif; ?>

        <div class="form-grid">
          <div>
            <label>Product name</label>
            <input class="input" id="mName" name="name" placeholder="e.g., Coke Can" required />
          </div>
          <div>
            <label>SKU</label>
            <input class="input" id="mSku" name="sku" placeholder="e.g., BVG-001" />
          </div>
          <div>
            <label>Category</label>
            <input class="input" id="mCategory" name="category" placeholder="e.g., beverages" />
          </div>
          <div>
            <label>Stock</label>
            <input class="input" id="mStock" name="stock" type="number" min="0" step="1" value="0" />
          </div>
          <div>
            <label>Cost</label>
            <input class="input" id="mCost" name="cost" type="number" min="0" step="0.01" value="0" />
          </div>
          <div>
            <label>Price</label>
            <input class="input" id="mPrice" name="price" type="number" min="0" step="0.01" value="0" />
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn ghost" type="button" data-close="merchModal">Cancel</button>
          <button class="btn primary" id="merchSaveBtn" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

<script>
// Tab switching (fixed)
document.addEventListener('DOMContentLoaded', function() {
  const invTabs = document.querySelectorAll('.tab[data-invtab]');
  
  function showInvTab(key){
    console.log('Switching to tab:', key);
    
    // Update tab button active states
    invTabs.forEach(b => {
      if (b.dataset.invtab === key) {
        b.classList.add('active');
      } else {
        b.classList.remove('active');
      }
    });
    
    // Hide all sections first
    document.getElementById('fuelInv')?.classList.add('hidden');
    document.getElementById('merchInv')?.classList.add('hidden');
    document.getElementById('partsInv')?.classList.add('hidden');
    document.getElementById('lowStockInv')?.classList.add('hidden');
    document.getElementById('reqInv')?.classList.add('hidden');
    document.getElementById('receivedInv')?.classList.add('hidden');
    document.getElementById('deliveryInv')?.classList.add('hidden');
    
    // Show selected section
    const sectionMap = {
      'fuel': 'fuelInv',
      'merch': 'merchInv',
      'parts': 'partsInv',
      'low_stock': 'lowStockInv',
      'req': 'reqInv',
      'received': 'receivedInv',
      'delivery': 'deliveryInv'
    };
    
    const sectionId = sectionMap[key];
    if (sectionId) {
      const section = document.getElementById(sectionId);
      if (section) {
        section.classList.remove('hidden');
        console.log('Showing section:', sectionId);
      } else {
        console.error('Section not found:', sectionId);
      }
    }
  }
  
  invTabs.forEach(btn => {
    btn.addEventListener('click', function() {
      showInvTab(this.dataset.invtab);
    });
  });
  
  // Show fuel tab by default
  showInvTab('fuel');
});

// Fuel Modal Functions
function openFuelModal() {
    document.getElementById('fuelModalTitle').textContent = 'Stock In Fuel';
    document.getElementById('fuelModal').classList.add('active');
    // Reset form
    document.querySelector('#fuelModal form').reset();
}

function editFuel(fuelType, currentLevel) {
    document.getElementById('fuelModalTitle').textContent = 'Update Fuel Stock';
    document.getElementById('fuelSelect').value = fuelType;
    document.getElementById('fuelLiters').value = currentLevel;
    document.getElementById('fuelModal').classList.add('active');
}

// Merchandise Modal Functions
function openMerchModal() {
    document.getElementById('merchModalTitle').textContent = 'Add Item';
    document.getElementById('merchSaveBtn').textContent = 'Add Item';
    document.getElementById('merchModal').classList.add('active');
    // Reset form
    document.getElementById('merchForm').reset();
    document.getElementById('mId').value = '';
}

function editMerch(id) {
    // Find the item in the PHP-rendered merch_inventory array
    const item = <?php echo json_encode($merch_inventory); ?>.find(item => item.id == id);
    if (!item) {
        alert('Item not found');
        return;
    }

    // Populate modal with item data
    document.getElementById('merchModalTitle').textContent = 'Edit Item';
    document.getElementById('merchSaveBtn').textContent = 'Update Item';
    document.getElementById('mId').value = item.id;
    document.getElementById('mName').value = item.product_name;
    document.getElementById('mSku').value = item.sku || '';
    document.getElementById('mCategory').value = item.category_name || '';
    document.getElementById('mStock').value = item.stock_level;
    document.getElementById('mCost').value = item.cost || 0;
    document.getElementById('mPrice').value = item.price || 0;
    if (document.getElementById('mStation')) {
        document.getElementById('mStation').value = item.station_id;
    }
    document.getElementById('merchModal').classList.add('active');
}

function deleteMerch(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" /><input name="action" value="delete_merch"><input name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
