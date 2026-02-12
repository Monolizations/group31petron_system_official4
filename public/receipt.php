<?php
require_once __DIR__ . '/../backend/lib.php';
require_login();
$id = $_GET['id'] ?? '';
$sales = read_json('sales.json', []);
$sale = null;
foreach($sales as $s){ if(($s['id'] ?? '') === $id){ $sale = $s; break; } }
if(!$sale){
  http_response_code(404);
  echo "Receipt not found.";
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Receipt <?php echo htmlspecialchars($id); ?></title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body class="print">
  <div class="receipt-paper print-only">
    <?php include __DIR__ . '/templates/receipt_template.php'; ?>
  </div>

  <script>
    window.print();
  </script>
</body>
</html>
