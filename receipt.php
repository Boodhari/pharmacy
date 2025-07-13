<?php
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
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
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
<body>
  <div class="receipt-card">
    <h3 class="text-center">🧾 Smart Dental Pharmacy</h3>
    <p><strong>Date:</strong> <?= date('d M Y - H:i', strtotime($sale['sale_date'])) ?></p>
    <table class="table table-bordered">
      <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
      <tbody>
        <!-- Loop for multiple products -->
        <?php foreach ($sale_items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td><?= $item['quantity_sold'] ?></td>
            <td><?= number_format($item['unit_price'], 2) ?></td>
            <td><?= number_format($item['total'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <h5>Total: <?= number_format($total_amount, 2) ?> SLSH</h5>
  </div>

  <div class="text-center mt-3 no-print">
    <button class="btn btn-primary" onclick="window.print()">🖨 Print</button>
    <a href="dashboard.php" class="btn btn-secondary">⬅️ Back</a>
  </div>
</body>
</html>