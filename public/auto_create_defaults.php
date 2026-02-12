<?php
$page_id = 'auto_create_defaults';
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../backend/rbac.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();
require_permission(CREATE_DEFAULT_ROLES_FOR_STATION);

$me = current_user();
$isSuper = ($me['role'] === 'superadmin');

$notice = '';

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- ACTION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $notice = 'Invalid request.';
    } elseif (!$isSuper) {
        $notice = 'Only Super Admin can create default accounts.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_defaults') {
            $station_id = $_POST['station_id'] ?? '';
            
            if (empty($station_id)) {
                $notice = 'Station is required.';
            } else {
                try {
                    // Get station info
                    $station = $pdo->prepare("SELECT name FROM stations WHERE id = ?");
                    $station->execute([$station_id]);
                    $station_name = $station->fetchColumn();
                    
                    if (!$station_name) {
                        $notice = 'Invalid station selected.';
                    } else {
                        $created_count = 0;
                        $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', $station_name);
                        
                        // Create Manager
                        $manager_username = strtolower("manager_" . substr($clean_name, 0, 8));
                        $manager_password = password_hash('Manager123!', PASSWORD_DEFAULT);
                        
                        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $chk->execute([$manager_username]);
                        if ($chk->rowCount() == 0) {
                            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, station_id, status, created_at) VALUES (?, ?, ?, 'manager', ?, 'active', NOW())");
                            $stmt->execute([$manager_username, $manager_password, "Manager - $station_name", $station_id]);
                            $created_count++;
                        }
                        
                        // Create 5 Staff
                        for ($i = 1; $i <= 5; $i++) {
                            $staff_username = strtolower("staff" . $i . "_" . substr($clean_name, 0, 6));
                            $staff_password = password_hash('Staff123!', PASSWORD_DEFAULT);
                            
                            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                            $chk->execute([$staff_username]);
                            if ($chk->rowCount() == 0) {
                                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, station_id, status, created_at) VALUES (?, ?, ?, 'staff', ?, 'active', NOW())");
                                $stmt->execute([$staff_username, $staff_password, "Staff $i - $station_name", $station_id]);
                                $created_count++;
                            }
                        }
                        
                        log_user_action('Create Default Roles', "Created $created_count default users for station '$station_name'");
                        $notice = "Default accounts created successfully";
                    }
                } catch (Exception $e) {
                    $notice = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// --- FETCH STATIONS ---
$stations = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM stations WHERE status = 'active' ORDER BY name");
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notice = "Database Error: " . $e->getMessage();
}

include __DIR__ . '/../partials/header.php';
?>

<style>
.page-container {
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    padding: 20px;
    overflow: hidden;
}

.page-header {
    margin-bottom: 30px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 8px 0;
}

.page-subtitle {
    color: var(--muted);
    font-size: 14px;
    margin: 0;
}

.content-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.stations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    width: 100%;
    max-width: 1200px;
    margin-bottom: 40px;
}

.station-card {
    background: var(--card);
    border-radius: 12px;
    padding: 24px;
    border: 2px solid #e0e0e0;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.station-card:hover {
    border-color: var(--blue);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 47, 108, 0.15);
}

.station-card.selected {
    border-color: var(--blue);
    background: rgba(0, 47, 108, 0.05);
}

.station-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 8px 0;
}

.station-id {
    font-size: 14px;
    color: var(--muted);
    margin: 0 0 16px 0;
}

.user-count {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--muted);
}

.user-count i {
    color: var(--blue);
}

.action-section {
    text-align: center;
    margin-top: 20px;
}

.info-panel {
    background: linear-gradient(135deg, #f8f9ff, #ffffff);
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-top: 16px;
    display: block;
}

.info-panel.show {
    display: block;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    font-weight: 600;
    color: var(--text);
}

.info-value {
    color: var(--blue);
    font-weight: 500;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--blue);
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 47, 108, 0.3);
}

.btn-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    transform: translateY(-1px);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--card);
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.modal-header {
    margin-bottom: 20px;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0;
}

.modal-body {
    margin-bottom: 20px;
    line-height: 1.6;
}

.modal-footer {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 20px;
    background: #28A745;
    color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 2000;
    display: none;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 14px;
}

.success-message {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 14px;
}

.account-summary {
    background: rgba(0, 47, 108, 0.05);
    border-radius: 8px;
    padding: 16px;
    margin: 16px 0;
}

.account-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.account-item:last-child {
    margin-bottom: 0;
}

.account-role {
    font-weight: 500;
    color: var(--text);
}

.account-count {
    background: var(--blue);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Auto-create Default Manager & Staff</h1>
        <p class="page-subtitle">Quickly create default user accounts for any station</p>
    </div>

    <div class="content-container">
        <?php if ($notice): ?>
            <div class="<?php echo strpos($notice, 'Error') !== false ? 'error-message' : 'success-message'; ?>">
                <?php echo htmlspecialchars($notice); ?>
            </div>
        <?php endif; ?>

        <div class="stations-grid">
            <?php foreach($stations as $station): 
                // Get current user count for this station
                $userCount = 0;
                try {
                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE station_id = ?");
                    $countStmt->execute([$station['id']]);
                    $userCount = $countStmt->fetchColumn();
                } catch(Exception $e) {}
            ?>
                <div class="station-card" onclick="selectStation(<?php echo $station['id']; ?>, '<?php echo htmlspecialchars($station['name']); ?>')">
                    <h3 class="station-name"><?php echo htmlspecialchars($station['name']); ?></h3>
                    <p class="station-id">Station ID: <?php echo str_pad($station['id'], 4, '0', STR_PAD_LEFT); ?></p>
                    <div class="user-count">
                        <i class="fas fa-users"></i>
                        <span><?php echo $userCount; ?> users assigned</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Auto-create default Manager & Staff accounts?</h3>
        </div>
        <div class="modal-body">
            <p>Auto-create default users for <strong id="modalStationName">-</strong>?</p>
            <div class="account-summary">
                <div class="account-item">
                    <span class="account-role">Manager</span>
                    <span class="account-count">1</span>
                </div>
                <div class="account-item">
                    <span class="account-role">Staff</span>
                    <span class="account-count">5</span>
                </div>
            </div>
            <div class="info-panel">
                <div class="info-row">
                    <span class="info-label">Station Name:</span>
                    <span class="info-value" id="modalStationNameInfo">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Number of accounts created:</span>
                    <span class="info-value" id="modalAccountsCreated">6</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="confirmCreate()">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Hidden Form -->
<form id="createForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="action" value="create_defaults">
    <input type="hidden" id="stationIdInput" name="station_id">
</form>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
let selectedStationId = null;
let selectedStationName = null;

function selectStation(stationId, stationName) {
    // Remove previous selection
    document.querySelectorAll('.station-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    event.currentTarget.classList.add('selected');
    
    // Store selection
    selectedStationId = stationId;
    selectedStationName = stationName;
    
    // Show confirmation modal directly
    showConfirmModal();
}

function showConfirmModal() {
    if (!selectedStationId) {
        showToast('Please select a station first', 'error');
        return;
    }
    
    document.getElementById('modalStationName').textContent = selectedStationName;
    document.getElementById('modalStationNameInfo').textContent = selectedStationName;
    
    console.log('Showing confirmation modal for station:', selectedStationName);
    
    document.getElementById('confirmModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function confirmCreate() {
    if (!selectedStationId) {
        showToast('Please select a station', 'error');
        return;
    }
    
    console.log('Creating default accounts for station:', selectedStationId, selectedStationName);
    
    // Set form values and submit
    document.getElementById('stationIdInput').value = selectedStationId;
    document.getElementById('createForm').submit();
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.background = type === 'success' ? '#28A745' : '#DC3545';
    toast.style.display = 'block';
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

<?php if ($notice && strpos($notice, 'successfully') !== false): ?>
showToast('<?php echo htmlspecialchars($notice); ?>', 'success');
<?php endif; ?>
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
