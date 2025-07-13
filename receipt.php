<?php
date_default_timezone_set('Africa/Mogadishu');
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

// Get transaction ID from URL
$txn_id = $_GET['txn_id'] ?? '';
if (empty($txn_id)) {
    die("<div style='color: red;'>Invalid Transaction ID.</div>");
}

// Fetch all sales under this transaction
$stmt = $conn->prepare("SELECT s.*, p.name AS product_name, p.price AS unit_price 
                        FROM sales s 
                        JOIN products p ON s.product_id = p.id 
                        WHERE s.transaction_id = ?");
$stmt->bind_param("s", $txn_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div style='color: red;'>No sales found for this transaction.</div>");
}

$sales = [];
$total_amount = 0;
$sale_date = "";

while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
    $total_amount += $row['total'];
    $sale_date = $row['sale_date']; // Assume same for all in transaction
}
?>
<?php include('includes/header.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Receipt - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    
    .receipt-card {
      max-width: 600px;
      margin: 40px auto;
      padding: 30px;
      border: 1px solid #ccc;
      background: white;
    }
    @media print {
      body * { visibility: hidden; }
      .receipt-card, .receipt-card * { visibility: visible; }
      .receipt-card {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        padding: 20px;
        background: #fff;
      }
    }
  </style>
</head>
<body class="bg-light">
<div class="receipt-card">
  <h4 class="text-center mb-4">🧾 Smart Dental Pharmacy Receipt</h4>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Product</th>
        <th>Unit Price</th>
        <th>Qty</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sales as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['product_name']) ?></td>
          <td><?= number_format($s['unit_price'], 2) ?> SLSH</td>
          <td><?= $s['quantity_sold'] ?></td>
          <td><?= number_format($s['total'], 2) ?> SLSH</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3" class="text-end">Total</th>
        <th><?= number_format($total_amount, 2) ?> SLSH</th>
      </tr>
      <tr>
        <td colspan="4" class="text-end">Date: <?= date('d M Y - H:i', strtotime($sale_date)) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
  </div>
</div>

  <div class="text-center mt-3 no-print">
    <button class="btn btn-primary" onclick="window.print()">🖨 Print</button>
    <a href="dashboard.php" class="btn btn-secondary">⬅️ Back</a>
  </div>
</body>
</html>
