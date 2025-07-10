<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header.php');
$result = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Products - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2>Products</h2>
  <a href="add_product.php" class="btn btn-success mb-3">Add New Product</a>
  <table class="table table-bordered">
    <thead>
      <tr><th>Name</th><th>Price</th><th>Quantity</th></tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td>$<?= number_format($row['price'], 2) ?></td>
          <td><?= $row['quantity'] ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <a href="dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>
