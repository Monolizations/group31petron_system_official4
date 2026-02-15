<?php
/**
 * FUEL INVENTORY WORKFLOW - TESTING GUIDE
 * 
 * Interactive testing workflow guide for the fuel inventory system.
 * Shows role-based testing instructions with step-by-step guidance.
 * 
 * Accessible to: All roles (staff, manager, admin, superadmin)
 * Role-specific content shown based on user's role
 */

session_start();
require_once 'db_connect.php';
require_once '../backend/lib.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'staff';
$station_id = $_SESSION['station_id'] ?? null;

// Get user info
$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Page ID for sidebar highlighting
$page_id = 'fuel_testing_workflow';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Workflow Testing Guide - Petron POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.2em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .role-badge {
            display: inline-block;
            background: rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }

        .quick-start {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #ff0000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .quick-start h2 {
            color: #ff0000;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .credentials {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .credential-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .credential-card strong {
            color: #ff0000;
            display: block;
            margin-bottom: 5px;
        }

        .credential-card code {
            background: #fff;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            display: block;
            margin: 3px 0;
            font-family: 'Courier New', monospace;
        }

        .test-section {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .test-section h2 {
            color: #ff0000;
            border-bottom: 2px solid #ff0000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step {
            background: #f9f9f9;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #ff0000;
            border-radius: 4px;
        }

        .step-title {
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-content {
            margin: 10px 0;
            line-height: 1.6;
        }

        .step-content ol {
            margin-left: 20px;
            margin-top: 8px;
        }

        .step-content li {
            margin: 5px 0;
        }

        .role-restricted {
            background: #fff3cd;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }

        .role-restricted strong {
            display: block;
            color: #856404;
            margin-bottom: 5px;
        }

        .role-restricted p {
            color: #856404;
            margin: 5px 0;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin: 8px 0;
        }

        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 12px;
            border-radius: 4px;
            margin: 8px 0;
        }

        .warning-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin: 8px 0;
        }

        .checklist {
            list-style: none;
            margin: 15px 0;
        }

        .checklist li {
            padding: 10px;
            margin: 5px 0;
            background: #f9f9f9;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checklist li:before {
            content: "☐";
            font-size: 1.3em;
            color: #ff0000;
            font-weight: bold;
        }

        .action-button {
            display: inline-block;
            background: #ff0000;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin: 8px 8px 8px 0;
            transition: background 0.3s;
            font-weight: 500;
        }

        .action-button:hover {
            background: #cc0000;
            text-decoration: none;
            color: white;
        }

        .action-button.secondary {
            background: #666;
        }

        .action-button.secondary:hover {
            background: #555;
        }

        .test-urls {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            margin: 15px 0;
        }

        .test-urls strong {
            display: block;
            color: #ff0000;
            margin-bottom: 8px;
        }

        .test-urls code {
            display: block;
            background: white;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
            font-size: 0.9em;
            word-break: break-all;
        }

        .tab-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-button {
            padding: 12px 20px;
            background: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-button.active {
            color: #ff0000;
            border-bottom-color: #ff0000;
        }

        .tab-button:hover {
            color: #ff0000;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .permissions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .permissions-table th,
        .permissions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .permissions-table th {
            background: #f5f5f5;
            font-weight: bold;
            color: #ff0000;
        }

        .permissions-table tr:hover {
            background: #f9f9f9;
        }

        .permission-yes {
            color: #28a745;
            font-weight: bold;
        }

        .permission-no {
            color: #dc3545;
            font-weight: bold;
        }

        footer {
            text-align: center;
            color: #666;
            margin-top: 40px;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5em;
            }

            .credentials {
                grid-template-columns: 1fr;
            }

            .test-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'db_connect.php'; ?>
    <?php include '../partials/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-flask-vial"></i> Fuel Workflow Testing Guide</h1>
                <p>Complete manual testing instructions for the fuel inventory workflow system</p>
                <div class="role-badge">
                    <i class="fas fa-user-circle"></i> 
                    Your Role: <strong><?php echo ucfirst($user_role); ?></strong>
                </div>
            </div>

            <!-- Quick Start Section -->
            <div class="quick-start">
                <h2><i class="fas fa-rocket"></i> Quick Start</h2>
                <p>Get started with testing the fuel workflow system. Follow these instructions based on your role.</p>
                
                <h3 style="color: #333; margin-top: 20px;">Test Accounts</h3>
                <div class="credentials">
                    <div class="credential-card">
                        <strong><i class="fas fa-user-tie"></i> Staff</strong>
                        <code>Username: teststaff</code>
                        <code>Password: staff123</code>
                        <code>Role: operations_staff</code>
                    </div>
                    <div class="credential-card">
                        <strong><i class="fas fa-user-secret"></i> Manager</strong>
                        <code>Username: testmanager</code>
                        <code>Password: manager123</code>
                        <code>Role: manager</code>
                    </div>
                    <div class="credential-card">
                        <strong><i class="fas fa-user-shield"></i> Admin</strong>
                        <code>Username: testadmin</code>
                        <code>Password: test123</code>
                        <code>Role: superadmin</code>
                    </div>
                </div>

                <h3 style="color: #333; margin-top: 20px;">Key URLs</h3>
                <div class="test-urls">
                    <strong>Main Pages:</strong>
                    <code>http://localhost/public/fuel_management.php - Fuel Operations Hub</code>
                    <code>http://localhost/public/fuel_delivery_verify.php - Manager Verification</code>
                    <code>http://localhost/public/fuel_delivery_finalize.php - Admin Finalization</code>
                    <code>http://localhost/public/fuel_shift_processing.php - Shift Processing</code>
                </div>
            </div>

            <!-- Workflow Selection -->
            <div class="test-section">
                <h2><i class="fas fa-compass"></i> Select Your Testing Path</h2>
                <div class="tab-container">
                    <button class="tab-button active" onclick="switchTab('delivery')">
                        <i class="fas fa-truck"></i> Delivery Workflow
                    </button>
                    <button class="tab-button" onclick="switchTab('shift')">
                        <i class="fas fa-clock"></i> Shift Processing
                    </button>
                    <button class="tab-button" onclick="switchTab('adjustment')">
                        <i class="fas fa-sliders-h"></i> Adjustments
                    </button>
                    <button class="tab-button" onclick="switchTab('edge')">
                        <i class="fas fa-exclamation-triangle"></i> Edge Cases
                    </button>
                </div>

                <!-- Delivery Workflow Tab -->
                <div id="delivery" class="tab-content active">
                    <h3 style="color: #ff0000; margin: 20px 0;">Testing the 3-Step Delivery Workflow</h3>
                    <p>Test the complete delivery process: Record → Verify → Finalize</p>

                    <!-- Step 1: Record -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-pencil-alt"></i> Step 1.1: Record a Delivery (As Staff)</div>
                        <div class="step-content">
                            <ol>
                                <li>Login with: <code>teststaff / staff123</code></li>
                                <li>Go to: <strong>fuel_management.php</strong></li>
                                <li>Click "Record New Delivery" button</li>
                                <li>Fill in the form:
                                    <ul>
                                        <li>Supplier: Select "Petron Supplier"</li>
                                        <li>Fuel Type: Select "Unleaded 95"</li>
                                        <li>Liters: Enter <code>5000</code></li>
                                        <li>Invoice Number: Enter <code>INV-20260216-001</code></li>
                                        <li>Tanker Number: Enter <code>TKR-001</code></li>
                                        <li>Notes: Enter test notes</li>
                                    </ul>
                                </li>
                                <li>Click "Record Delivery"</li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Delivery appears in list with status <strong>Encoded</strong> (yellow color)
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Verify -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-check-circle"></i> Step 1.2: Verify the Delivery (As Manager)</div>
                        <div class="step-content">
                            <ol>
                                <li>Logout and login with: <code>testmanager / manager123</code></li>
                                <li>Go to: <strong>fuel_delivery_verify.php</strong></li>
                                <li>Find your delivery with status <strong>Encoded</strong></li>
                                <li>Click "Verify" button</li>
                                <li>Optionally add verification notes</li>
                                <li>Click "Approve Delivery"</li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Status changes to <strong>Verified</strong> (blue color), verified timestamp shows current time
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Finalize -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-lock"></i> Step 1.3: Finalize the Delivery (As Admin)</div>
                        <div class="step-content">
                            <ol>
                                <li>Logout and login with: <code>testadmin / test123</code></li>
                                <li>Go to: <strong>fuel_delivery_finalize.php</strong></li>
                                <li>Find your delivery with status <strong>Verified</strong></li>
                                <li>Review the "Stock Update Preview":
                                    <ul>
                                        <li>Before: Shows current stock level</li>
                                        <li>Change: Shows <code>+5000</code> liters</li>
                                        <li>After: Shows updated stock level</li>
                                    </ul>
                                </li>
                                <li>Click "Finalize & Update Stock"</li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Status changes to <strong>Finalized</strong> (green color), stock is updated, before/after values recorded
                            </div>
                        </div>
                    </div>

                    <!-- Verify Audit Trail -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-history"></i> Step 1.4: Verify Audit Trail</div>
                        <div class="step-content">
                            <ol>
                                <li>Still logged in as Admin</li>
                                <li>Go to: <strong>fuel_monitoring.php</strong></li>
                                <li>Look for "Inventory Audit Log" section</li>
                                <li>Find entries for your delivery (3 entries total):
                                    <ul>
                                        <li><code>delivery_recorded</code></li>
                                        <li><code>delivery_verified</code></li>
                                        <li><code>delivery_finalized</code></li>
                                    </ul>
                                </li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> All 3 actions logged with timestamps and user info, stock before/after recorded
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shift Processing Tab -->
                <div id="shift" class="tab-content">
                    <h3 style="color: #ff0000; margin: 20px 0;">Testing Shift-End Processing</h3>
                    <p>Test batch pump reading processing with automatic stock deduction</p>

                    <!-- Record Readings -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-tachometer-alt"></i> Step 2.1: Record Pump Readings (As Staff)</div>
                        <div class="step-content">
                            <ol>
                                <li>Login with: <code>teststaff / staff123</code></li>
                                <li>Go to: <strong>fuel_management.php</strong></li>
                                <li>Click "Record New Reading" in Pump Readings section</li>
                                <li>Create multiple readings:
                                    <ul>
                                        <li><strong>Reading 1:</strong>
                                            <ul>
                                                <li>Pump: Select "Pump 1"</li>
                                                <li>Shift: Select "Morning"</li>
                                                <li>Fuel Type: "Unleaded 95"</li>
                                                <li>Reading: <code>100</code> liters</li>
                                            </ul>
                                        </li>
                                        <li><strong>Reading 2:</strong>
                                            <ul>
                                                <li>Pump: Select "Pump 1"</li>
                                                <li>Shift: Select "Afternoon"</li>
                                                <li>Fuel Type: "Unleaded 95"</li>
                                                <li>Reading: <code>200</code> liters</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Readings appear in list with status <strong>Pending</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Process Shift -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-sync"></i> Step 2.2: Process Shift-End (As Manager)</div>
                        <div class="step-content">
                            <ol>
                                <li>Logout and login with: <code>testmanager / manager123</code></li>
                                <li>Go to: <strong>fuel_shift_processing.php</strong></li>
                                <li>Select the shift (Morning or Afternoon) from dropdown</li>
                                <li>Click "View Pending Readings"</li>
                                <li>Review all pending readings</li>
                                <li>Click "Process Shift-End" button</li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> All readings status changes to <strong>Approved</strong>, stock is deducted, summary shows total liters processed
                            </div>
                        </div>
                    </div>

                    <!-- Verify Stock Deduction -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-chart-line"></i> Step 2.3: Verify Stock Deduction</div>
                        <div class="step-content">
                            <ol>
                                <li>Still logged in as Manager</li>
                                <li>Go to: <strong>fuel_monitoring.php</strong></li>
                                <li>Check "Current Stock Levels" - should be reduced</li>
                                <li>Check "Inventory Audit Log" - find <code>reading_approved</code> entries with negative quantity_change</li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Stock levels reflect deductions, audit trail shows all approvals
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adjustments Tab -->
                <div id="adjustment" class="tab-content">
                    <h3 style="color: #ff0000; margin: 20px 0;">Testing Adjustment Workflow</h3>
                    <p>Test request and approval of fuel adjustments</p>

                    <!-- Request Adjustment -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-plus-circle"></i> Step 3.1: Request Adjustment (As Staff)</div>
                        <div class="step-content">
                            <ol>
                                <li>Login with: <code>teststaff / staff123</code></li>
                                <li>Go to: <strong>fuel_management.php</strong></li>
                                <li>Click "Request Adjustment" button</li>
                                <li>Fill in the form:
                                    <ul>
                                        <li>Fuel Type: Select "Unleaded 95"</li>
                                        <li>Adjustment Type: Select "Inventory Loss" (or "Inventory Gain")</li>
                                        <li>Liters: Enter <code>50</code></li>
                                        <li>Reason: Enter a reason (e.g., "Spillage during transfer")</li>
                                        <li>Notes: Enter additional notes</li>
                                    </ul>
                                </li>
                                <li>Click "Submit Adjustment Request"</li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Adjustment appears in list with status <strong>Pending</strong> (yellow color)
                            </div>
                        </div>
                    </div>

                    <!-- Approve Adjustment -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-thumbs-up"></i> Step 3.2: Approve Adjustment (As Manager)</div>
                        <div class="step-content">
                            <ol>
                                <li>Logout and login with: <code>testmanager / manager123</code></li>
                                <li>Go to: <strong>fuel_management.php</strong></li>
                                <li>Find your pending adjustment</li>
                                <li>Click "Review" or "Approve" button</li>
                                <li><strong>Option A - Approve:</strong>
                                    <ul>
                                        <li>View adjustment details</li>
                                        <li>Optionally add approval notes</li>
                                        <li>Click "Approve Adjustment"</li>
                                    </ul>
                                </li>
                                <li><strong>Option B - Reject:</strong>
                                    <ul>
                                        <li>Add rejection reason</li>
                                        <li>Click "Reject Adjustment"</li>
                                    </ul>
                                </li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Status changes to <strong>Approved</strong> or <strong>Rejected</strong>, stock updated if approved
                            </div>
                        </div>
                    </div>

                    <!-- Verify Adjustment -->
                    <div class="step">
                        <div class="step-title"><i class="fas fa-search"></i> Step 3.3: Verify in Audit Log</div>
                        <div class="step-content">
                            <ol>
                                <li>Go to: <strong>fuel_monitoring.php</strong></li>
                                <li>Check "Inventory Audit Log"</li>
                                <li>Find entries for your adjustment (<code>adjustment_requested</code>, <code>adjustment_approved</code>)</li>
                            </ol>
                            <div class="success-message">
                                <strong>Expected Result:</strong> Complete audit trail with quantities and user info
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edge Cases Tab -->
                <div id="edge" class="tab-content">
                    <h3 style="color: #ff0000; margin: 20px 0;">Testing Edge Cases & Error Handling</h3>

                    <table class="permissions-table">
                        <tr>
                            <th>Test Case</th>
                            <th>Steps</th>
                            <th>Expected Result</th>
                        </tr>
                        <tr>
                            <td><strong>Double Finalization Prevention</strong></td>
                            <td>Admin attempts to finalize already-finalized delivery</td>
                            <td><span class="permission-yes">Error shown: "Already finalized"</span></td>
                        </tr>
                        <tr>
                            <td><strong>Manager Can't Finalize</strong></td>
                            <td>Manager tries to access finalize page</td>
                            <td><span class="permission-no">Error or no deliveries shown</span></td>
                        </tr>
                        <tr>
                            <td><strong>Staff Can't Verify</strong></td>
                            <td>Staff tries to access verify page</td>
                            <td><span class="permission-no">Error or access denied</span></td>
                        </tr>
                        <tr>
                            <td><strong>Zero Liters Validation</strong></td>
                            <td>Try to adjust with 0 liters</td>
                            <td><span class="permission-no">Validation error shown</span></td>
                        </tr>
                        <tr>
                            <td><strong>Negative Stock Prevention</strong></td>
                            <td>Try to process reading greater than stock</td>
                            <td><span class="permission-no">Blocked or flagged in reports</span></td>
                        </tr>
                        <tr>
                            <td><strong>Status Transition Validation</strong></td>
                            <td>Try to verify already-verified delivery</td>
                            <td><span class="permission-no">Error: "Already verified"</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Role-Based Permissions -->
            <div class="test-section">
                <h2><i class="fas fa-lock"></i> Role-Based Permissions</h2>
                
                <table class="permissions-table">
                    <tr>
                        <th>Action</th>
                        <th>Staff</th>
                        <th>Manager</th>
                        <th>Admin</th>
                    </tr>
                    <tr>
                        <td><strong>Record Delivery</strong></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>Verify Delivery</strong></td>
                        <td><span class="permission-no">✗ No</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>Finalize Delivery (Stock Update)</strong></td>
                        <td><span class="permission-no">✗ No</span></td>
                        <td><span class="permission-no">✗ No</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>Record Pump Reading</strong></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>Process Shift-End</strong></td>
                        <td><span class="permission-no">✗ No</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>Request Adjustment</strong></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>Approve Adjustment</strong></td>
                        <td><span class="permission-no">✗ No</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>View Audit Logs</strong></td>
                        <td><span class="permission-no">✗ No</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                        <td><span class="permission-yes">✓ Yes</span></td>
                    </tr>
                </table>
            </div>

            <!-- Database Verification -->
            <div class="test-section">
                <h2><i class="fas fa-database"></i> Database Verification (Optional)</h2>
                <p>Verify your test data in the database with these SQL queries:</p>

                <h3 style="color: #333; margin: 15px 0;">Check Deliveries</h3>
                <div class="info-box">
                    <code>SELECT id, fuel_type, liters, status, recorded_by, verified_by, finalized_by FROM fuel_deliveries WHERE station_id = 226 ORDER BY recorded_at DESC LIMIT 5;</code>
                </div>
                <p><strong>Expected:</strong> status = Finalized, all users populated</p>

                <h3 style="color: #333; margin: 15px 0;">Check Readings</h3>
                <div class="info-box">
                    <code>SELECT id, pump_id, reading, status, approved_by FROM fuel_daily_readings WHERE station_id = 226 AND DATE(reading_date) = CURDATE() ORDER BY recorded_at DESC LIMIT 10;</code>
                </div>
                <p><strong>Expected:</strong> status = Approved, approved_by populated</p>

                <h3 style="color: #333; margin: 15px 0;">Check Adjustments</h3>
                <div class="info-box">
                    <code>SELECT id, liters, status, reviewed_by FROM fuel_adjustments WHERE station_id = 226 ORDER BY requested_at DESC LIMIT 5;</code>
                </div>
                <p><strong>Expected:</strong> status = Approved/Rejected, reviewed_by populated</p>

                <h3 style="color: #333; margin: 15px 0;">Check Audit Logs</h3>
                <div class="info-box">
                    <code>SELECT action, user_id, quantity_before, quantity_after, quantity_change FROM fuel_inventory_logs WHERE station_id = 226 ORDER BY recorded_at DESC LIMIT 20;</code>
                </div>
                <p><strong>Expected:</strong> Complete trail with before/after values</p>
            </div>

            <!-- Automated Testing -->
            <div class="test-section">
                <h2><i class="fas fa-robot"></i> Automated Test Suite (Optional)</h2>
                <p>Run the comprehensive automated test suite to validate the system:</p>
                
                <div class="info-box">
                    <strong>Run from command line:</strong><br>
                    <code>cd /opt/lampp/htdocs/group31petron_system_official4<br>php tests/fuel_workflow_tests.php</code>
                </div>

                <p style="margin-top: 15px;"><strong>Expected Output:</strong></p>
                <div class="success-message">
                    All tests PASS ✓
                </div>
            </div>

            <!-- Completion Checklist -->
            <div class="test-section">
                <h2><i class="fas fa-list-check"></i> Testing Completion Checklist</h2>
                
                <ul class="checklist">
                    <li>Delivery workflow (Record → Verify → Finalize)</li>
                    <li>Shift-end processing with batch deduction</li>
                    <li>Adjustment request & approval</li>
                    <li>Audit trail complete (3+ entries each)</li>
                    <li>Permission tests (staff can't verify, manager can't finalize)</li>
                    <li>Stock levels accurate after all operations</li>
                    <li>Database data matches UI displays</li>
                    <li>Automated test suite passes (optional)</li>
                </ul>
            </div>

            <!-- Support & Troubleshooting -->
            <div class="test-section">
                <h2><i class="fas fa-life-ring"></i> Troubleshooting</h2>
                
                <h3 style="color: #333; margin: 15px 0;">Login fails</h3>
                <p>Check username/password spelling, verify account exists and is active in database</p>

                <h3 style="color: #333; margin: 15px 0;">Permission denied on pages</h3>
                <p>Verify you're logged in with correct role. Check user role in database.</p>

                <h3 style="color: #333; margin: 15px 0;">Delivery doesn't appear in verify list</h3>
                <p>Check if status is "Encoded" and you're looking at correct station</p>

                <h3 style="color: #333; margin: 15px 0;">Stock levels don't change</h3>
                <p>Verify finalization actually happened and fuel_inventory row exists for that station/fuel type</p>

                <h3 style="color: #333; margin: 15px 0;">Shift processing shows no pending readings</h3>
                <p>Verify readings were recorded with status "Pending" and shift/date match</p>
            </div>

            <!-- Important Notes -->
            <div class="test-section" style="border-left-color: #ffc107;">
                <h2><i class="fas fa-exclamation-circle"></i> Important Notes</h2>
                
                <div class="warning-box">
                    <strong>Test Data:</strong> All test data is stored in the main database. This is NOT a separate test database. You can clean up test data manually through the UI or delete from database when done.
                </div>

                <div class="info-box" style="margin-top: 15px;">
                    <strong>Test Accounts:</strong> These are for development/testing only. Remove before production deployment.
                </div>

                <div class="info-box" style="margin-top: 15px;">
                    <strong>Station 226:</strong> Test account is configured for station 226. Admin (testadmin) can access all stations.
                </div>

                <div class="info-box" style="margin-top: 15px;">
                    <strong>Timestamps:</strong> All operations are timestamped. Use these to verify workflow progression.
                </div>
            </div>

            <footer>
                <p>Fuel Inventory Workflow Testing Guide | Last Updated: 2026-02-16</p>
                <p style="font-size: 0.9em; color: #999;">For questions or issues, contact system administrator</p>
            </footer>
        </div>
    </main>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });

            // Deactivate all tab buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab content
            const selectedContent = document.getElementById(tabName);
            if (selectedContent) {
                selectedContent.classList.add('active');
            }

            // Activate clicked button
            event.target.closest('.tab-button').classList.add('active');
        }
    </script>

    <?php include '../partials/footer.php'; ?>
</body>
</html>
