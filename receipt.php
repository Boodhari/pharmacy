<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

// ✅ Sanitize and check sale_id BEFORE any output
if (!isset($_GET['sale_id']) || intval($_GET['sale_id']) < 1) {
    die("<div style='color:red; padding:15px;'>Invalid Sale ID.</div>");
}

$sale_id = intval($_GET['sale_id']);

// ✅ Use prepared statement for safety
$stmt = $conn->prepare("SELECT s.*, p.name AS product_name, p.price AS unit_price 
                        FROM sales s 
                        JOIN products p ON s.product_id = p.id 
                        WHERE s.id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div style='color:red; padding:15px;'>Sale not found for ID $sale_id.</div>");
}

$sale = $result->fetch_assoc();
?>

<?php include('includes/header.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Receipt - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print {
      button, a { display: none; }
    }
    .receipt-card {
      max-width: 500px;
      margin: 50px auto;
      padding: 30px;
      border: 1px solid #ccc;
      background: #fff;
    }
  </style>
</head>
<body class="bg-light">
<div class="receipt-card">
  <h4 class="text-center mb-4">🧾 Pharmacy Receipt</h4>
  <p><strong>Product:</strong> <?= htmlspecialchars($sale['product_name']) ?></p>
  <p><strong>Unit Price:</strong> <?= number_format($sale['unit_price'], 2) ?> SLSH</p>
  <p><strong>Quantity:</strong> <?= $sale['quantity_sold'] ?></p>
  <p><strong>Total:</strong> <?= number_format($sale['total'], 2) ?> SLSH</p>
  <p><strong>Date:</strong> <?= date('d M Y - H:i', strtotime($sale['sale_date'])) ?></p>

  <div class="text-center mt-4">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
  </div>
</div>

<div class="text-center mt-3">
  <a href="dashboard.php" class="btn btn-secondary">⬅️ Back to Dashboard</a>
</div>
</body>
</html>
