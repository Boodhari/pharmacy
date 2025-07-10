<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header.php');

// Handle search
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$query = "
SELECT s.*, p.name, p.price 
FROM sales s 
JOIN products p ON s.product_id = p.id 
WHERE DATE(s.sale_date) = '$selected_date'
ORDER BY s.sale_date DESC";

$result = $conn->query($query);

// Calculate total sales
$total_query = $conn->query("SELECT SUM(total) AS total_sales FROM sales WHERE DATE(sale_date) = '$selected_date'");
$total_sales = $total_query->fetch_assoc()['total_sales'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
  <title>Sales Report - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2 class="mb-4">Sales Report for <?= htmlspecialchars($selected_date) ?></h2>

  <!-- Date Search -->
  <form method="GET" class="row g-3 mb-4">
    <div class="col-auto">
      <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" required>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Search by Date</button>
    </div>
    <div class="col-auto">
      <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>

  <!-- Sales Table -->
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['quantity_sold'] ?></td>
                <td>SLSH<?= number_format($row['price'], 2) ?></td>
                <td>SLSH<?= number_format($row['total'], 2) ?></td>
                <td><?= $row['sale_date'] ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center text-muted">No sales found for this date.</td></tr>
          <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="4" class="text-end">Total Sales:</th>
            <th colspan="2">$<?= number_format($total_sales, 2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
</body>
</html>
