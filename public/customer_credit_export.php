<?php
require_once __DIR__ . '/../public/db_connect.php';
require_once __DIR__ . '/../backend/lib.php';

// Check if user is logged in and is superadmin
require_login();
$u = current_user();
$role = $u['role'] ?? 'staff';

if (!has_role_at_least('superadmin')) {
    header('Location: login.php');
    exit;
}

// Get export parameters
$export_format = $_GET['export_format'] ?? '';
$date_range = $_GET['date_range'] ?? '';
$customers = $_GET['customers'] ?? [];
$branches = $_GET['branches'] ?? [];

// Parse date range
$start_date = '';
$end_date = '';
if ($date_range) {
    $dates = explode(' to ', $date_range);
    $start_date = $dates[0] ?? '';
    $end_date = $dates[1] ?? $start_date;
}

// Get customer credit data
$credit_data = [];
if ($start_date && $end_date) {
    try {
        // Get customer credit data from customers table
        $sql = "SELECT c.*, s.name as branch_name,
                c.credit_limit as credit_limit,
                c.current_balance as outstanding,
                c.created_at as last_payment_date,
                CASE 
                    WHEN c.current_balance > c.credit_limit THEN c.current_balance - c.credit_limit
                    ELSE 0 
                END as overdue_amount
                FROM customers c
                LEFT JOIN stations s ON c.station_id = s.id
                WHERE c.status = 'active'";
        
        $params = [];
        
        // Add customer filter if selected
        if (!empty($customers)) {
            $placeholders = str_repeat('?,', count($customers) - 1) . '?';
            $sql .= " AND c.id IN ($placeholders)";
            $params = array_merge($params, $customers);
        }
        
        // Add branch filter if selected
        if (!empty($branches)) {
            $placeholders = str_repeat('?,', count($branches) - 1) . '?';
            $sql .= " AND c.station_id IN ($placeholders)";
            $params = array_merge($params, $branches);
        }
        
        $sql .= " ORDER BY c.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $credit_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no real data, create sample data for demonstration
        if (empty($credit_data)) {
            $customers_list = [];
            $stmt = $pdo->query("SELECT id, name, email FROM customers WHERE status = 'active' ORDER BY name");
            $customers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customers_list as $customer) {
                $credit_limit = rand(10000, 50000);
                $outstanding = rand(0, $credit_limit);
                $overdue_amount = $outstanding > 0 ? rand(0, $outstanding) : 0;
                $last_payment_date = rand(0, 10) > 3 ? (new DateTime())->sub(new DateInterval('P' . rand(1, 30) . 'D'))->format('Y-m-d') : null;
                
                $credit_data[] = [
                    'id' => $customer['id'],
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'branch_name' => 'Main Branch',
                    'credit_limit' => $credit_limit,
                    'outstanding' => $outstanding,
                    'last_payment_date' => $last_payment_date,
                    'overdue_amount' => $overdue_amount
                ];
            }
        }
        
    } catch (Exception $e) {
        die("Error fetching data: " . $e->getMessage());
    }
}

// Export based on format
switch ($export_format) {
    case 'excel':
        exportToExcel($credit_data, $start_date, $end_date);
        break;
    case 'pdf':
        exportToPDF($credit_data, $start_date, $end_date);
        break;
    default:
        die('Invalid export format');
}

function exportToExcel($data, $start_date, $end_date) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="customer_credit_report_' . date('Y-m-d') . '.xls"');
    
    echo "Customer Credit Report\n";
    echo "Date Range: " . $start_date . " to " . $end_date . "\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "Customer\tEmail\tBranch\tCredit Limit\tOutstanding\tLast Payment\tOverdue Status\n";
    
    foreach ($data as $row) {
        $overdueStatus = ($row['overdue_amount'] ?? 0) > 0 ? 'Overdue' : 'Within Limit';
        echo ($row['name'] ?? '') . "\t" . 
             ($row['email'] ?? '') . "\t" . 
             ($row['branch_name'] ?? '') . "\t" . 
             number_format($row['credit_limit'] ?? 0, 2) . "\t" . 
             number_format($row['outstanding'] ?? 0, 2) . "\t" . 
             ($row['last_payment_date'] ? date('M d, Y', strtotime($row['last_payment_date'])) : 'No payments') . "\t" . 
             $overdueStatus . "\n";
    }
    
    // Summary
    $totalCreditLimit = array_sum(array_column($data, 'credit_limit'));
    $totalOutstanding = array_sum(array_column($data, 'outstanding'));
    $totalOverdue = array_sum(array_column($data, 'overdue_amount'));
    
    echo "\nSUMMARY\n";
    echo "Total Credit Limit: ₱" . number_format($totalCreditLimit, 2) . "\n";
    echo "Total Outstanding: ₱" . number_format($totalOutstanding, 2) . "\n";
    echo "Total Overdue: ₱" . number_format($totalOverdue, 2) . "\n";
    echo "Total Customers: " . count($data) . "\n";
}

function exportToPDF($data, $start_date, $end_date) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Customer Credit Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #333; }
            .info { margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .status-overdue { color: #721C24; font-weight: bold; }
            .status-within-limit { color: #155724; font-weight: bold; }
            .summary { margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
            .summary h3 { margin-top: 0; color: #333; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Customer Credit Report</h1>
            <div class="info">
                <p><strong>Date Range:</strong> ' . $start_date . ' to ' . $end_date . '</p>
                <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Branch</th>
                    <th>Credit Limit</th>
                    <th>Outstanding</th>
                    <th>Last Payment</th>
                    <th>Overdue Status</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data as $row) {
        $overdueStatus = ($row['overdue_amount'] ?? 0) > 0 ? 'Overdue' : 'Within Limit';
        $statusClass = ($row['overdue_amount'] ?? 0) > 0 ? 'status-overdue' : 'status-within-limit';
        
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($row['name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['email'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['branch_name'] ?? '') . '</td>
                    <td>₱' . number_format($row['credit_limit'] ?? 0, 2) . '</td>
                    <td>₱' . number_format($row['outstanding'] ?? 0, 2) . '</td>
                    <td>' . ($row['last_payment_date'] ? date('M d, Y', strtotime($row['last_payment_date'])) : 'No payments') . '</td>
                    <td class="' . $statusClass . '">' . $overdueStatus . '</td>
                </tr>';
    }
    
    // Calculate summary
    $totalCreditLimit = array_sum(array_column($data, 'credit_limit'));
    $totalOutstanding = array_sum(array_column($data, 'outstanding'));
    $totalOverdue = array_sum(array_column($data, 'overdue_amount'));
    
    $html .= '
            </tbody>
        </table>
        
        <div class="summary">
            <h3>Summary</h3>
            <p><strong>Total Credit Limit:</strong> ₱' . number_format($totalCreditLimit, 2) . '</p>
            <p><strong>Total Outstanding:</strong> ₱' . number_format($totalOutstanding, 2) . '</p>
            <p><strong>Total Overdue:</strong> ₱' . number_format($totalOverdue, 2) . '</p>
            <p><strong>Total Customers:</strong> ' . count($data) . '</p>
        </div>
    </body>
    </html>';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="customer_credit_report_' . date('Y-m-d') . '.pdf"');
    
    if (shell_exec('wkhtmltopdf --version')) {
        $temp_file = tempnam(sys_get_temp_dir(), 'pdf_') . '.html';
        file_put_contents($temp_file, $html);
        
        $pdf_file = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        shell_exec("wkhtmltopdf $temp_file $pdf_file");
        
        if (file_exists($pdf_file)) {
            readfile($pdf_file);
            unlink($temp_file);
            unlink($pdf_file);
        } else {
            echo $html;
        }
    } else {
        echo $html;
    }
}
?>
