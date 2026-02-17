<?php
$page_id = 'customers';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$me = current_user();
$role = $me['role'] ?? 'staff';
$isAdminOrSuper = in_array($role, ['admin', 'superadmin']);
$canEdit = in_array($role, ['admin', 'superadmin', 'manager']);
$station_id = user_station_id();

// Get current view
$view = $_GET['view'] ?? 'list';

$msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($action === 'save') {
        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact_person'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $type = $_POST['type'] ?? 'cash';
        $limit = $_POST['credit_limit'] ?? 0;
        $status = $_POST['status'] ?? 'active';
        $merchandise_type = $_POST['merchandise_type'] ?? null;

        if ($name) {
            try {
                if ($id) {
                    // Update
                    if (!$canEdit) {
                        $msg = "❌ Error: Only Manager, Admin, or Super Admin can edit customers.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE customers SET name=?, contact_person=?, phone=?, email=?, address=?, type=?, credit_limit=?, merchandise_type=?, status=? WHERE id=?");
                        $stmt->execute([$name, $contact, $phone, $email, $address, $type, $limit, $merchandise_type, $status, $id]);
                        $msg = "✅ Customer updated successfully.";
                        log_activity($pdo, $me['id'], 'Update Customer', "Updated customer ID $id");
                    }
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO customers (name, contact_person, phone, email, address, type, credit_limit, merchandise_type, status, station_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $contact, $phone, $email, $address, $type, $limit, $merchandise_type, $status, $station_id]);
                    $msg = "✅ Customer added successfully.";
                    log_activity($pdo, $me['id'], 'Create Customer', "Created customer $name");
                }
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        if (!$isAdminOrSuper) {
            $msg = "❌ Error: Only Admin or Super Admin can delete customers.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
                $stmt->execute([$id]);
                $msg = "✅ Customer deleted successfully.";
                log_activity($pdo, $me['id'], 'Delete Customer', "Deleted customer ID $id");
            } catch (PDOException $e) {
                $msg = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch Customers based on view
$customers = [];
try {
    if ($view === 'my_customers' && !$isAdminOrSuper) {
        // Staff can only see customers they've interacted with
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.* FROM customers c
            LEFT JOIN sales s ON c.id = s.customer_id AND s.user_id = ?
            LEFT JOIN job_orders jo ON c.id = jo.customer_id AND jo.user_id = ?
            WHERE (s.id IS NOT NULL OR jo.id IS NOT NULL) AND (c.station_id = ? OR c.station_id IS NULL)
            ORDER BY c.name ASC
        ");
        $stmt->execute([$me['id'], $me['id'], $station_id]);
    } else {
        // Admin/Manager see all customers, or list view
        if ($isAdminOrSuper || $view === 'list') {
            $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
        } else {
            // Staff fallback - show station customers
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE station_id = ? OR station_id IS NULL ORDER BY name ASC");
            $stmt->execute([$station_id]);
        }
    }
    $customers = $stmt->fetchAll();
} catch (Exception $e) { /* Table might not exist yet */ }

// Metrics
$total_cust = count($customers);
$credit_cust = 0;
$total_outstanding = 0;
foreach ($customers as $c) {
    if ($c['type'] === 'credit') $credit_cust++;
    $total_outstanding += $c['current_balance'];
}

require_once __DIR__ . '/../partials/header.php';
?>
<div class="page">
  <div class="page-head">
    <div>
      <?php if ($view === 'create'): ?>
        <h1>Create Customer</h1>
        <div class="muted">Add a new customer account</div>
      <?php elseif ($view === 'my_customers'): ?>
        <h1>My Customers</h1>
        <div class="muted">Customers you've interacted with</div>
      <?php else: ?>
        <h1>Customers</h1>
        <div class="muted">Manage customer accounts and credit</div>
      <?php endif; ?>
    </div>
    <div class="actions" style="display:flex; align-items:center; gap:1rem;">
      <?php if ($canEdit): ?>
        <button class="btn dark" onclick="openCustomerModal()"><i class="fas fa-plus"></i> Add Customer</button>
      <?php endif; ?>
      <?php if (!$isAdminOrSuper): ?>
        <a href="customers.php?view=my_customers" class="btn <?php echo $view === 'my_customers' ? 'primary' : 'ghost'; ?>">
          <i class="fas fa-users"></i> My Customers
        </a>
        <?php if ($view !== 'create'): ?>
          <a href="customers.php?view=create" class="btn ghost">
            <i class="fas fa-plus"></i> Create Customer
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if($msg): ?><div class="card" style="padding:10px; margin-top:10px; background:#e6f4ea; color:green;"><?php echo $msg; ?></div><?php endif; ?>

  <?php if ($view === 'create'): ?>
    <!-- Create Customer View -->
    <div class="card" style="margin-top:16px;">
      <div class="card-head">
        <div class="card-title">Create New Customer</div>
        <div class="muted">Fill in the customer details below</div>
      </div>
      <form method="POST" style="padding:20px;">
        <input type="hidden" name="action" value="save">
        <div class="grid two" style="gap:20px;">
          <div>
            <label class="lbl">Customer Name *</label>
            <input type="text" name="name" class="inp full" required>
          </div>
          <div>
            <label class="lbl">Contact Person</label>
            <input type="text" name="contact_person" class="inp full">
          </div>
          <div>
            <label class="lbl">Phone *</label>
            <input type="text" name="phone" class="inp full" required>
          </div>
          <div>
            <label class="lbl">Email</label>
            <input type="email" name="email" class="inp full">
          </div>
          <div>
            <label class="lbl">Account Type</label>
            <select name="type" class="inp full">
              <option value="cash">Cash Customer</option>
              <option value="credit">Credit Customer</option>
            </select>
          </div>
          <div>
            <label class="lbl">Credit Limit</label>
            <input type="number" name="credit_limit" class="inp full" value="0" step="0.01">
          </div>
        </div>
        <div style="margin-top:20px;">
          <label class="lbl">Merchandise Type</label>
          <select name="merchandise_type" class="inp full">
            <option value="">-- Select Type --</option>
            <option value="oil_lube_grease">A. Oil/Lube/Grease</option>
            <option value="car_accessories">B. Car Accessories</option>
            <option value="oil_fuel_filter">C. Oil/Fuel Filter</option>
            <option value="others">D. Others</option>
            <option value="multiple">Multiple Types</option>
          </select>
        </div>
        <div style="margin-top:20px;">
          <label class="lbl">Address</label>
          <textarea name="address" class="inp full" rows="3"></textarea>
        </div>
        <div style="margin-top:20px;">
          <label class="lbl">Status</label>
          <select name="status" class="inp full">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div style="margin-top:20px;">
          <button type="submit" class="btn primary">
            <i class="fas fa-save"></i> Create Customer
          </button>
          <a href="customers.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>

  <?php else: ?>
    <!-- List Views (Default, My Customers) -->
    <div class="cards three" style="margin-top:18px">
      <div class="card metric">
        <div class="metric-meta">
          <div class="metric-label">Total Customers</div>
          <div class="metric-value" id="cTotal"><?php echo $total_cust; ?></div>
        </div>
      </div>
      <div class="card metric">
        <div class="metric-meta">
          <div class="metric-label">Credit Accounts</div>
          <div class="metric-value blue" id="cCredit"><?php echo $credit_cust; ?></div>
        </div>
      </div>
      <div class="card metric">
        <div class="metric-meta">
          <div class="metric-label">Total Outstanding</div>
          <div class="metric-value amber" id="cOutstanding">₱<?php echo number_format($total_outstanding, 2); ?></div>
        </div>
      </div>
    </div>

    <section class="panel" style="margin-top:16px">
      <div class="panel-head">
        <div class="panel-title">
          <?php echo $view === 'my_customers' ? 'My Customers' : 'Customer Directory'; ?>
        </div>
         <div style="display:flex; gap:10px; align-items:center;">
           <select id="filterType" class="inp" style="width:auto;">
             <option value="">All Types</option>
             <option value="cash">Cash</option>
             <option value="credit">Credit</option>
           </select>
           <select id="filterMerchType" class="inp" style="width:auto;">
             <option value="">All Merchandise</option>
             <option value="oil_lube_grease">A. Oil/Lube/Grease</option>
             <option value="car_accessories">B. Car Accessories</option>
             <option value="oil_fuel_filter">C. Oil/Fuel Filter</option>
             <option value="others">D. Others</option>
             <option value="multiple">Multiple Types</option>
           </select>
           <select id="filterStatus" class="inp" style="width:auto;">
             <option value="">All Status</option>
             <option value="active">Active</option>
             <option value="suspended">Suspended</option>
             <option value="inactive">Inactive</option>
           </select>
           <div class="search">
             <span class="ico"><i class="fas fa-search"></i></span>
             <input id="custSearch" placeholder="Search customers..." />
           </div>
         </div>
      </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Contact</th>
            <th>Account Type</th>
            <th>Merchandise Type</th>
            <th>Credit Limit</th>
            <th>Balance</th>
            <th>Status</th>
            <th style="width:120px">Actions</th>
          </tr>
        </thead>
        <tbody id="custTbody">
            <?php foreach($customers as $c): ?>
            <?php 
            $merchLabel = '';
            if (!empty($c['merchandise_type'])) {
                $merchLabels = [
                    'oil_lube_grease' => 'A. Oil/Lube/Grease',
                    'car_accessories' => 'B. Car Accessories',
                    'oil_fuel_filter' => 'C. Oil/Fuel Filter',
                    'others' => 'D. Others',
                    'multiple' => 'Multiple Types'
                ];
                $merchLabel = $merchLabels[$c['merchandise_type']] ?? $c['merchandise_type'];
            }
            ?>
            <tr data-name="<?php echo htmlspecialchars(strtolower($c['name'] ?? '')); ?>" data-type="<?php echo htmlspecialchars($c['type']); ?>" data-status="<?php echo htmlspecialchars($c['status']); ?>" data-merch="<?php echo htmlspecialchars($c['merchandise_type'] ?? ''); ?>" class="cust-row">
                <td><b><?php echo !empty($c['name']) ? htmlspecialchars($c['name']) : '<span class="muted" style="color:#999;">— No Name —</span>'; ?></b></td>
                <td>
                    <?php
                    $contact_parts = [];
                    if (!empty($c['contact_person'])) {
                        $contact_parts[] = htmlspecialchars($c['contact_person']);
                    }
                    if (!empty($c['phone'])) {
                        $contact_parts[] = htmlspecialchars($c['phone']);
                    }
                    if (!empty($c['email'])) {
                        $contact_parts[] = htmlspecialchars($c['email']);
                    }
                    echo !empty($contact_parts) ? implode('<br>', $contact_parts) : '<span class="muted">—</span>';
                    ?>
                </td>
                <td><span style="text-transform:capitalize;"><?php echo htmlspecialchars($c['type']); ?></span></td>
                <td><?php echo $merchLabel ? htmlspecialchars($merchLabel) : '<span class="muted">—</span>'; ?></td>
                <td>₱<?php echo number_format($c['credit_limit'], 2); ?></td>
                <td style="color:<?php echo $c['current_balance']>0?'red':'green'; ?>">₱<?php echo number_format($c['current_balance'], 2); ?></td>
                <td><?php echo htmlspecialchars($c['status']); ?></td>
                <td>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <?php if ($isAdminOrSuper): ?>
                            <button class="btn ghost small" onclick="editCustomer(<?php echo $c['id']; ?>)" title="Edit Customer">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn ghost small red" onclick="deleteCustomer(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['name'])); ?>')" title="Delete Customer">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        <?php elseif ($canEdit): ?>
                            <button class="btn ghost small" onclick="editCustomer(<?php echo $c['id']; ?>)" title="Edit Customer">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        <?php else: ?>
                            <button class="btn ghost small" onclick="viewCustomer(<?php echo $c['id']; ?>)" title="View Customer">
                                <i class="fas fa-eye"></i> View
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal" id="modalCustomer" aria-hidden="true">
  <div class="modal-card modal-card-customer">
    <div class="modal-head">
      <div class="modal-title" id="custModalTitle">Add New Customer</div>
      <button class="icon-btn" onclick="document.getElementById('modalCustomer').classList.remove('show')" title="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="">
    <div class="modal-body modal-body-customer">

      <!-- Basic Information -->
      <div class="field-section">
        <div class="field-section-title">
          <i class="fas fa-info-circle"></i> Basic Information
        </div>

        <div class="field">
          <label>Customer/Company Name <span class="required">*</span></label>
          <div class="field-with-icon">
            <i class="fas fa-building icon"></i>
            <input name="name" required class="inp" placeholder="Enter customer or company name" />
          </div>
        </div>

        <div class="form-grid two">
          <div class="field">
            <label>Contact Person</label>
            <div class="field-with-icon">
              <i class="fas fa-user icon"></i>
              <input name="contact_person" class="inp" placeholder="Primary contact" />
            </div>
          </div>
          <div class="field">
            <label>Phone <span class="required">*</span></label>
            <div class="field-with-icon">
              <i class="fas fa-phone icon"></i>
              <input name="phone" required class="inp" placeholder="09XX XXX XXXX" />
            </div>
          </div>
        </div>

        <div class="field">
          <label>Email Address</label>
          <div class="field-with-icon">
            <i class="fas fa-envelope icon"></i>
            <input name="email" type="email" class="inp" placeholder="email@example.com" />
          </div>
        </div>

        <div class="field">
          <label>Address</label>
          <div class="field-with-icon">
            <i class="fas fa-map-marker-alt icon"></i>
            <input name="address" class="inp" placeholder="Street address, City, Province" />
          </div>
        </div>
      </div>

      <!-- Account Settings -->
      <div class="field-section">
        <div class="field-section-title">
          <i class="fas fa-cog"></i> Account Settings
        </div>

        <div class="form-grid two">
          <div class="field">
            <label>Account Type</label>
            <div class="field-with-icon">
              <i class="fas fa-credit-card icon"></i>
              <select name="type" class="select">
                <option value="cash">Cash Customer</option>
                <option value="credit">Credit Customer</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>Credit Limit (₱)</label>
            <div class="field-with-icon">
              <i class="fas fa-coins icon"></i>
              <input name="credit_limit" type="number" min="0" step="0.01" placeholder="0.00" class="inp" />
            </div>
          </div>
        </div>

        <div class="form-grid two">
          <div class="field">
            <label>Merchandise Type</label>
            <div class="field-with-icon">
              <i class="fas fa-boxes icon"></i>
              <select name="merchandise_type" class="select">
                <option value="">-- Select Type --</option>
                <option value="oil_lube_grease">Oil/Lube/Grease</option>
                <option value="car_accessories">Car Accessories</option>
                <option value="oil_fuel_filter">Oil/Fuel Filter</option>
                <option value="others">Others</option>
                <option value="multiple">Multiple Types</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>Account Status</label>
            <div class="field-with-icon">
              <i class="fas fa-signal icon"></i>
              <select name="status" class="select">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Info Box -->
      <div class="info-box" id="modalInfoBox" style="display:none;">
        <i class="fas fa-info-circle icon"></i>
        <div class="text" id="modalInfoText"></div>
      </div>

    </div>
    <div class="modal-foot modal-foot-customer">
      <button type="button" class="btn ghost" id="custCancelBtn" onclick="document.getElementById('modalCustomer').classList.remove('show')">
        <i class="fas fa-times"></i> Cancel
      </button>
      <button type="submit" class="btn primary" id="custSaveBtn">
        <i class="fas fa-save"></i> Add Customer
      </button>
    </div>
    </form>
  </div>
</div>

<script>
function openCustomerModal() {
    document.getElementById('custModalTitle').textContent = 'Add New Customer';
    document.getElementById('custSaveBtn').innerHTML = '<i class="fas fa-plus"></i> Add Customer';
    document.getElementById('custSaveBtn').style.display = '';
    document.getElementById('custSaveBtn').className = 'btn primary';
    document.getElementById('custCancelBtn').innerHTML = '<i class="fas fa-times"></i> Cancel';
    document.getElementById('modalInfoBox').style.display = 'none';
    document.getElementById('modalCustomer').classList.add('show');
    // Reset form
    document.querySelector('#modalCustomer form').reset();
    document.querySelector('#modalCustomer input[name="id"]').value = '';
    // Enable all fields for adding
    document.querySelectorAll('#modalCustomer input, #modalCustomer select').forEach(el => {
        el.disabled = false;
    });
}

function viewCustomer(id) {
    // Fetch customer data and populate modal (read-only)
    fetch('../backend/customers.php?action=get&id=' + id)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const c = data.customer;
                document.getElementById('custModalTitle').textContent = 'View Customer';
                document.getElementById('custSaveBtn').style.display = 'none';
                document.getElementById('custCancelBtn').innerHTML = '<i class="fas fa-check"></i> Close';
                document.querySelector('#modalCustomer input[name="action"]').value = 'view';
                document.querySelector('#modalCustomer input[name="id"]').value = c.id;
                document.querySelector('#modalCustomer input[name="name"]').value = c.name || '';
                document.querySelector('#modalCustomer input[name="contact_person"]').value = c.contact_person || '';
                document.querySelector('#modalCustomer input[name="phone"]').value = c.phone || '';
                document.querySelector('#modalCustomer input[name="email"]').value = c.email || '';
                document.querySelector('#modalCustomer input[name="address"]').value = c.address || '';
                document.querySelector('#modalCustomer select[name="type"]').value = c.type;
                document.querySelector('#modalCustomer input[name="credit_limit"]').value = c.credit_limit;
                document.querySelector('#modalCustomer select[name="merchandise_type"]').value = c.merchandise_type || '';
                document.querySelector('#modalCustomer select[name="status"]').value = c.status;
                // Disable all fields for view mode
                document.querySelectorAll('#modalCustomer input, #modalCustomer select').forEach(el => {
                    el.disabled = true;
                });
                // Show info box
                const infoBox = document.getElementById('modalInfoBox');
                const infoText = document.getElementById('modalInfoText');
                infoBox.style.display = 'flex';
                infoText.innerHTML = 'You are viewing customer details in read-only mode. To make changes, please contact a Manager or Administrator.';
                document.getElementById('modalCustomer').classList.add('show');
            } else {
                alert('Error loading customer data: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading customer data: ' + error.message);
        });
}

function editCustomer(id) {
    // Fetch customer data and populate modal
    fetch('../backend/customers.php?action=get&id=' + id)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const c = data.customer;
                document.getElementById('custModalTitle').textContent = 'Edit Customer';
                document.getElementById('custSaveBtn').innerHTML = '<i class="fas fa-save"></i> Update Customer';
                document.getElementById('custSaveBtn').style.display = '';
                document.getElementById('custSaveBtn').className = 'btn primary';
                document.getElementById('custCancelBtn').innerHTML = '<i class="fas fa-times"></i> Cancel';
                document.querySelector('#modalCustomer input[name="action"]').value = 'save';
                document.querySelector('#modalCustomer input[name="id"]').value = c.id;
                document.querySelector('#modalCustomer input[name="name"]').value = c.name || '';
                document.querySelector('#modalCustomer input[name="contact_person"]').value = c.contact_person || '';
                document.querySelector('#modalCustomer input[name="phone"]').value = c.phone || '';
                document.querySelector('#modalCustomer input[name="email"]').value = c.email || '';
                document.querySelector('#modalCustomer input[name="address"]').value = c.address || '';
                document.querySelector('#modalCustomer select[name="type"]').value = c.type;
                document.querySelector('#modalCustomer input[name="credit_limit"]').value = c.credit_limit;
                document.querySelector('#modalCustomer select[name="merchandise_type"]').value = c.merchandise_type || '';
                document.querySelector('#modalCustomer select[name="status"]').value = c.status;
                // Enable all fields for editing
                document.querySelectorAll('#modalCustomer input, #modalCustomer select').forEach(el => {
                    el.disabled = false;
                });
                // Hide info box
                document.getElementById('modalInfoBox').style.display = 'none';
                document.getElementById('modalCustomer').classList.add('show');
            } else {
                alert('Error loading customer data: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading customer data: ' + error.message);
        });
}

function deleteCustomer(id, name) {
    if (confirm('Are you sure you want to delete customer "' + name + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input name="action" value="delete"><input name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function filterCustomers() {
    const type = document.getElementById('filterType').value.toLowerCase();
    const merchType = document.getElementById('filterMerchType').value.toLowerCase();
    const status = document.getElementById('filterStatus').value.toLowerCase();
    const search = document.getElementById('custSearch').value.toLowerCase();

    document.querySelectorAll('.cust-row').forEach(row => {
        const rType = row.dataset.type.toLowerCase();
        const rMerch = (row.dataset.merch || '').toLowerCase();
        const rStatus = row.dataset.status.toLowerCase();
        const rName = row.dataset.name;

        const show = (!type || rType === type) && (!merchType || rMerch === merchType) && (!status || rStatus === status) && (!search || rName.includes(search));
        row.style.display = show ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('custSearch').addEventListener('input', filterCustomers);
    document.getElementById('filterType').addEventListener('change', filterCustomers);
    document.getElementById('filterMerchType').addEventListener('change', filterCustomers);
    document.getElementById('filterStatus').addEventListener('change', filterCustomers);

    // Close modal when clicking outside
    document.getElementById('modalCustomer').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalCustomer');
            if (modal.classList.contains('show')) {
                modal.classList.remove('show');
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
