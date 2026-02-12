<?php
$page_id = 'customer_profile';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) { echo "Customer not found."; exit; }

include __DIR__ . '/../partials/header.php';
?>
<style>
    .page-layout { height: 100%; display: flex; flex-direction: column; gap: 20px; }
    
    /* Header Card */
    .profile-header {
        background: white; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .prof-name { font-size: 24px; font-weight: bold; color: var(--petron-blue); }
    .prof-meta { color: #666; font-size: 14px; margin-top: 5px; }
    .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; background: #eee; }
    .status-active { background: #d4edda; color: #155724; }
    
    /* Summary Cards */
    .profile-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .p-card { background: white; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; }
    .p-val { font-size: 20px; font-weight: bold; }
    .p-lbl { font-size: 12px; color: #888; text-transform: uppercase; }
    
    /* Tabs */
    .profile-tabs { display: flex; gap: 10px; border-bottom: 1px solid #ddd; margin-bottom: 15px; }
    .p-tab { padding: 10px 20px; cursor: pointer; border-bottom: 3px solid transparent; }
    .p-tab.active { border-bottom-color: var(--petron-blue); font-weight: bold; color: var(--petron-blue); }
    
    .tab-content { flex: 1; background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; overflow-y: auto; }
</style>

<div class="page-layout">
    <!-- 1. HEADER CARD -->
    <div class="profile-header">
        <div>
            <div style="display:flex; align-items:center; gap:10px;">
                <div class="prof-name"><?php echo htmlspecialchars($c['name']); ?></div>
                <span class="status-badge status-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span>
            </div>
            <div class="prof-meta">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($c['company'] ?? 'Personal'); ?> | 
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($c['phone']); ?> | 
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($c['email']); ?>
            </div>
        </div>
        <div>
            <button class="btn ghost">Edit Profile</button>
        </div>
    </div>

    <!-- 2. SUMMARY CARDS -->
    <div class="profile-summary">
        <div class="p-card">
            <div class="p-val">₱<?php echo number_format($c['credit_limit'], 2); ?></div>
            <div class="p-lbl">Credit Limit</div>
        </div>
        <div class="p-card">
            <div class="p-val" style="color:<?php echo $c['current_balance']>0?'#dc3545':'#28a745'; ?>">
                ₱<?php echo number_format($c['current_balance'], 2); ?>
            </div>
            <div class="p-lbl">Current Balance</div>
        </div>
        <div class="p-card">
            <div class="p-val">₱0.00</div>
            <div class="p-lbl">Overdue Amount</div>
        </div>
        <div class="p-card">
            <div class="p-val">-</div>
            <div class="p-lbl">Last Payment</div>
        </div>
    </div>

    <!-- 3. TAB SECTION -->
    <div style="flex:1; display:flex; flex-direction:column;">
        <div class="profile-tabs">
            <div class="p-tab active" onclick="switchTab('overview')">Overview</div>
            <div class="p-tab" onclick="switchTab('ledger')">Credit Ledger</div>
            <div class="p-tab" onclick="switchTab('statements')">Statements</div>
        </div>

        <div class="tab-content" id="tab-overview">
            <h3>Account Overview</h3>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($c['address']); ?></p>
                    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($c['contact_person']); ?></p>
                    <p><strong>Account Type:</strong> <?php echo ucfirst($c['type']); ?></p>
                </div>
                <div>
                    <p><strong>Joined:</strong> <?php echo $c['created_at']; ?></p>
                    <p><strong>Notes:</strong> No notes available.</p>
                </div>
            </div>
        </div>
        
        <div class="tab-content" id="tab-ledger" style="display:none;">
            <iframe src="customer_credit.php?id=<?php echo $c['id']; ?>&embed=1" style="width:100%; height:100%; border:none;"></iframe>
        </div>
        
        <div class="tab-content" id="tab-statements" style="display:none;">
            <iframe src="customer_statements.php?id=<?php echo $c['id']; ?>&embed=1" style="width:100%; height:100%; border:none;"></iframe>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    // Deactivate tabs
    document.querySelectorAll('.p-tab').forEach(el => el.classList.remove('active'));
    
    // Show target
    document.getElementById('tab-' + tabName).style.display = 'block';
    // Activate tab
    event.target.classList.add('active');
    
    // If iframe, reload to ensure size/content
    if(tabName === 'ledger' || tabName === 'statements') {
        // Optional: reload iframe logic
    }
}
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
