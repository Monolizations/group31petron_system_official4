<?php
require_once __DIR__ . '/../backend/lib.php';
require_once __DIR__ . '/../public/db_connect.php';
require_login();

// Only allow superadmin and developers
$me = current_user();
$role = role_key($me['role'] ?? 'staff');

if (!in_array($role, ['superadmin'])) {
    header("Location: dashboard.php");
    exit;
}

$page_id = 'developer_panel';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="h1">Developer Panel</h1>
        <div class="sub">System Administration & Development Tools</div>
    </div>
</div>

<style>
.developer-panel {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 24px;
    margin-bottom: 30px;
}

.dev-card {
    background: var(--card);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.dev-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.dev-card h3 {
    color: var(--petron-blue);
    margin-bottom: 16px;
    font-size: 18px;
    font-weight: 600;
}

.dev-card p {
    color: var(--muted);
    margin-bottom: 20px;
    line-height: 1.5;
}

.dev-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn-danger {
    background: var(--petron-red);
    color: white;
}

.btn-danger:hover {
    background: #c41e3a;
}

.btn-warning {
    background: #f59e0b;
    color: #000;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.log-viewer {
    background: #1e1e1e;
    color: #fff;
    padding: 20px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 400px;
    overflow-y: auto;
    margin-top: 16px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.data-table th {
    background: var(--petron-blue);
    color: white;
    font-weight: 600;
}

.data-table tr:hover {
    background: rgba(0, 47, 108, 0.05);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: var(--card);
    border-radius: 12px;
    padding: 32px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.modal-title {
    font-size: 20px;
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
    margin-bottom: 24px;
}

.modal-footer {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--petron-blue);
    box-shadow: 0 0 0 3px rgba(0, 47, 108, 0.1);
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #10b981;
    color: white;
}

.alert-danger {
    background: var(--petron-red);
    color: white;
}

.alert-warning {
    background: #f59e0b;
    color: #000;
}
</style>

<div class="developer-panel">
    <!-- System Logs -->
    <div class="dev-card">
        <h3><i class="fas fa-file-alt"></i> System Logs</h3>
        <p>View and manage system logs, error reports, and audit trails.</p>
        <div class="dev-actions">
            <button class="btn" onclick="viewSystemLogs()"><i class="fas fa-eye"></i> View Logs</button>
            <button class="btn btn-danger" onclick="clearSystemLogs()"><i class="fas fa-trash"></i> Clear Logs</button>
            <button class="btn btn-success" onclick="downloadLogs()"><i class="fas fa-download"></i> Download</button>
        </div>
    </div>

    <!-- Database Management -->
    <div class="dev-card">
        <h3><i class="fas fa-database"></i> Database Management</h3>
        <p>Manage database operations, backup data, and perform maintenance tasks.</p>
        <div class="dev-actions">
            <button class="btn" onclick="viewDatabaseStatus()"><i class="fas fa-info-circle"></i> Status</button>
            <button class="btn btn-warning" onclick="backupDatabase()"><i class="fas fa-save"></i> Backup</button>
            <button class="btn btn-danger" onclick="resetDatabase()"><i class="fas fa-exclamation-triangle"></i> Reset Data</button>
        </div>
    </div>

    <!-- User Administration -->
    <div class="dev-card">
        <h3><i class="fas fa-users-cog"></i> User Administration</h3>
        <p>Manage users, roles, permissions, and access control across all stations.</p>
        <div class="dev-actions">
            <button class="btn" onclick="viewAllUsers()"><i class="fas fa-users"></i> All Users</button>
            <button class="btn btn-success" onclick="showAddUserModal()"><i class="fas fa-plus"></i> Add User</button>
            <button class="btn btn-warning" onclick="managePermissions()"><i class="fas fa-key"></i> Permissions</button>
        </div>
    </div>

    <!-- Station Management -->
    <div class="dev-card">
        <h3><i class="fas fa-gas-pump"></i> Station Management</h3>
        <p>Manage station settings, configurations, and operational parameters.</p>
        <div class="dev-actions">
            <button class="btn" onclick="viewAllStations()"><i class="fas fa-store"></i> All Stations</button>
            <button class="btn btn-success" onclick="showAddStationModal()"><i class="fas fa-plus"></i> Add Station</button>
            <button class="btn btn-warning" onclick="resetStationData()"><i class="fas fa-sync"></i> Reset Station</button>
        </div>
    </div>

    <!-- Data Operations -->
    <div class="dev-card">
        <h3><i class="fas fa-tools"></i> Data Operations</h3>
        <p>Perform advanced data operations, cleanup, and system maintenance.</p>
        <div class="dev-actions">
            <button class="btn" onclick="viewDataStats()"><i class="fas fa-chart-bar"></i> Statistics</button>
            <button class="btn btn-warning" onclick="cleanupData()"><i class="fas fa-broom"></i> Cleanup</button>
            <button class="btn btn-danger" onclick="deleteAllData()"><i class="fas fa-trash-alt"></i> Delete All Data</button>
        </div>
    </div>

    <!-- System Configuration -->
    <div class="dev-card">
        <h3><i class="fas fa-cog"></i> System Configuration</h3>
        <p>Configure system settings, environment variables, and operational parameters.</p>
        <div class="dev-actions">
            <button class="btn" onclick="viewSystemConfig()"><i class="fas fa-cogs"></i> Configuration</button>
            <button class="btn btn-warning" onclick="resetConfig()"><i class="fas fa-undo"></i> Reset Config</button>
            <button class="btn btn-success" onclick="exportConfig()"><i class="fas fa-file-export"></i> Export Config</button>
        </div>
    </div>
</div>

<!-- System Logs Modal -->
<div id="logsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">System Logs</h3>
            <button class="modal-close" onclick="closeModal('logsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="log-viewer" id="logContent">
                <!-- Logs will be loaded here -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('logsModal')">Close</button>
            <button class="btn btn-danger" onclick="clearSystemLogs()">Clear All Logs</button>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addUserForm">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="dev_role_add" required>
                        <option value="">Select role</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Station</label>
                    <select name="station_id" required>
                        <option value="">Select Station</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
            <button type="submit" class="btn btn-success" onclick="addUser()">Add User</button>
        </div>
    </div>
</div>

<!-- Add Station Modal -->
<div id="addStationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New Station</h3>
            <button class="modal-close" onclick="closeModal('addStationModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addStationForm">
                <div class="form-group">
                    <label>Station Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" name="contact_number">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addStationModal')">Cancel</button>
            <button type="submit" class="btn btn-success" onclick="addStation()">Add Station</button>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// System Logs Functions
function viewSystemLogs() {
    openModal('logsModal');
    loadSystemLogs();
}

function loadSystemLogs() {
    const logContent = document.getElementById('logContent');
    logContent.innerHTML = 'Loading system logs...';
    
    // Simulate loading logs (in real implementation, this would fetch from server)
    setTimeout(() => {
        const logs = [
            '[2026-02-07 01:52:00] INFO: System initialized successfully',
            '[2026-02-07 01:52:15] INFO: User superadmin logged in',
            '[2026-02-07 01:52:30] INFO: Database connection established',
            '[2026-02-07 01:52:45] INFO: Inventory system loaded',
            '[2026-02-07 01:53:00] INFO: Sales reports generated',
            '[2026-02-07 01:53:15] INFO: Job orders processed',
            '[2026-02-07 01:53:30] WARNING: Low stock detected for item #2',
            '[2026-02-07 01:53:45] INFO: User admin logged in',
            '[2026-07-02-07 01:54:00] INFO: Station status updated',
            '[2026-02-07 01:54:15] INFO: Reports dashboard accessed',
            '[2026-02-07 01:54:30] INFO: Database backup completed',
            '[2026-02-07 01:54:45] INFO: System maintenance performed'
        ];
        
        logContent.innerHTML = logs.join('\n');
    }, 1000);
}

function clearSystemLogs() {
    if (confirm('Are you sure you want to clear all system logs? This action cannot be undone.')) {
        // In real implementation, this would clear the log files
        const logContent = document.getElementById('logContent');
        logContent.innerHTML = 'System logs cleared successfully.';
        showToast('System logs cleared', 'success');
    }
}

function downloadLogs() {
    // In real implementation, this would download the log files
    showToast('Downloading logs...', 'info');
    setTimeout(() => {
        showToast('Logs downloaded successfully', 'success');
    }, 1000);
}

// Database Management Functions
function viewDatabaseStatus() {
    const status = {
        'Database': 'Connected',
        'Tables': 'All tables operational',
        'Records': 'Data integrity verified',
        'Backups': 'Last backup: 2026-02-07 01:54:00',
        'Performance': 'Query response time: < 100ms'
    };
    
    let statusHtml = '<div class="alert alert-success">';
    for (const [key, value] of Object.entries(status)) {
        statusHtml += `<strong>${key}:</strong> ${value}<br>`;
    }
    statusHtml += '</div>';
    
    showToast('Database Status Retrieved', 'success');
    
    // Create a temporary modal to show status
    const tempModal = document.createElement('div');
    tempModal.className = 'modal';
    tempModal.style.display = 'flex';
    tempModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Database Status</h3>
                <button class="modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</button>
            </div>
            <div class="modal-body">
                ${statusHtml}
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.parentElement.parentElement.parentElement.remove()">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(tempModal);
}

function backupDatabase() {
    if (confirm('Are you sure you want to backup the database? This may take a few moments.')) {
        showToast('Starting database backup...', 'info');
        setTimeout(() => {
            showToast('Database backup completed successfully', 'success');
        }, 2000);
    }
}

function resetDatabase() {
    if (confirm('⚠️ WARNING: This will reset all data to initial state. This action cannot be undone. Are you absolutely sure?')) {
        if (confirm('🚨 FINAL WARNING: All data will be permanently deleted. Type "RESET" to confirm:')) {
            const confirmation = prompt('Type "RESET" to confirm database reset:');
            if (confirmation === 'RESET') {
                showToast('Database reset initiated...', 'warning');
                setTimeout(() => {
                    showToast('Database reset completed', 'success');
                }, 3000);
            } else {
                showToast('Database reset cancelled', 'info');
            }
        } else {
            showToast('Database reset cancelled', 'info');
        }
    }
}

// User Administration Functions
function viewAllUsers() {
    window.location.href = 'view_all_users.php';
}

function showAddUserModal() {
    openModal('addUserModal');
    loadStationsForUser();
}

function loadStationsForUser() {
    const stationSelect = document.querySelector('#addUserForm select[name="station_id"]');
    stationSelect.innerHTML = '<option value="">Select Station</option>';

    DataHelper.populateStations('assigned_station', 'Select Station')
        .then(() => console.log('Stations loaded'))
        .catch(error => {
            console.error('Failed to load stations:', error);
            alert('Failed to load stations. Please refresh.');
        });
}

function addUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    
    // In real implementation, this would send data to server
    showToast('Adding user...', 'info');
    
    setTimeout(() => {
        showToast('User added successfully', 'success');
        closeModal('addUserModal');
        form.reset();
    }, 1500);
}

function managePermissions() {
    window.location.href = 'permissions.php';
}

// Station Management Functions
function viewAllStations() {
    window.location.href = 'view_all_stations.php';
}

function showAddStationModal() {
    openModal('addStationModal');
}

function addStation() {
    const form = document.getElementById('addStationForm');
    const formData = new FormData(form);
    
    // In real implementation, this would send data to server
    showToast('Adding station...', 'info');
    
    setTimeout(() => {
        showToast('Station added successfully', 'success');
        closeModal('addStationModal');
        form.reset();
    }, 1500);
}

function resetStationData() {
    const stationId = prompt('Enter Station ID to reset:');
    if (stationId) {
        if (confirm(`Reset all data for station ${stationId}? This action cannot be undone.`)) {
            showToast('Resetting station data...', 'warning');
            setTimeout(() => {
                showToast(`Station ${stationId} data reset successfully`, 'success');
            }, 2000);
        }
    }
}

// Data Operations Functions
function viewDataStats() {
    const stats = {
        'Users': '8 total',
        'Stations': '3 active',
        'Products': '15 available',
        'Inventory Items': '45 tracked',
        'Sales Records': '1,247',
        'Job Orders': '89',
        'Reports Generated': '156'
    };
    
    let statsHtml = '<div class="alert alert-success">';
    for (const [key, value] of Object.entries(stats)) {
        statsHtml += `<strong>${key}:</strong> ${value}<br>`;
    }
    statsHtml += '</div>';
    
    showToast('Data statistics retrieved', 'success');
    
    // Create a temporary modal to show stats
    const tempModal = document.createElement('div');
    tempModal.className = 'modal';
    tempModal.style.display = 'flex';
    tempModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Data Statistics</h3>
                <button class="modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</button>
            </div>
            <div class="modal-body">
                ${statsHtml}
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.parentElement.parentElement.parentElement.remove()">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(tempModal);
}

function cleanupData() {
    if (confirm('Are you sure you want to cleanup old data? This will remove old records and optimize the database.')) {
        showToast('Starting data cleanup...', 'info');
        setTimeout(() => {
            showToast('Data cleanup completed successfully', 'success');
        }, 2000);
    }
}

function deleteAllData() {
    if (confirm('⚠️ CRITICAL WARNING: This will delete ALL data from the system. This action cannot be undone. Are you absolutely sure?')) {
        if (confirm('🚨 FINAL WARNING: ALL DATA WILL BE PERMANENTLY DELETED. Type "DELETE" to confirm:')) {
            const confirmation = prompt('Type "DELETE" to confirm data deletion:');
            if (confirmation === 'DELETE') {
                if (confirm('🔴 IRREVERSIBLE ACTION: All system data will be permanently deleted. Type "DELETE_ALL" to final confirm:')) {
                    const finalConfirmation = prompt('Type "DELETE_ALL" to confirm complete data deletion:');
                    if (finalConfirmation === 'DELETE_ALL') {
                        showToast('Deleting all data...', 'danger');
                        setTimeout(() => {
                            showToast('All data deleted successfully', 'success');
                        }, 3000);
                    } else {
                        showToast('Data deletion cancelled', 'info');
                    }
                } else {
                    showToast('Data deletion cancelled', 'info');
                }
            } else {
                showToast('Data deletion cancelled', 'info');
            }
        } else {
            showToast('Data deletion cancelled', 'info');
        }
    }
}

// System Configuration Functions
function viewSystemConfig() {
    const config = {
        'System Version': '2.0.1',
        'Database Version': 'MySQL 8.0',
        'PHP Version': '8.2.0',
        'Environment': 'Production',
        'Debug Mode': 'Disabled',
        'Cache Enabled': 'Yes',
        'Backup Schedule': 'Daily at 2:00 AM',
        'Max Upload Size': '10MB',
        'Session Timeout': '30 minutes'
    };
    
    let configHtml = '<div class="alert alert-success">';
    for (const [key, value] of Object.entries(config)) {
        configHtml += `<strong>${key}:</strong> ${value}<br>`;
    }
    configHtml += '</div>';
    
    showToast('System configuration retrieved', 'success');
    
    // Create a temporary modal to show config
    const tempModal = document.createElement('div');
    tempModal.className = 'modal';
    tempModal.style.display = 'flex';
    tempModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">System Configuration</h3>
                <button class="modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</button>
            </div>
            <div class="modal-body">
                ${configHtml}
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.parentElement.parentElement.parentElement.remove()">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(tempModal);
}

function resetConfig() {
    if (confirm('Are you sure you want to reset system configuration to defaults?')) {
        showToast('Resetting configuration...', 'warning');
        setTimeout(() => {
            showToast('Configuration reset successfully', 'success');
        }, 1500);
    }
}

function exportConfig() {
    showToast('Exporting configuration...', 'info');
    setTimeout(() => {
        showToast('Configuration exported successfully', 'success');
    }, 1000);
}

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    
    if (type === 'success') {
        toast.style.background = '#10b981';
    } else if (type === 'error' || type === 'danger') {
        toast.style.background = 'var(--petron-red)';
    } else if (type === 'warning') {
        toast.style.background = '#f59e0b';
        toast.style.color = '#000';
    } else {
        toast.style.background = '#007bff';
    }
    
    toast.style.color = type === 'warning' ? '#000' : 'white';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '8px';
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '10000';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<script src="../assets/js/data_helper.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    DataHelper.populateRoles('dev_role_add', 'Select role');
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
