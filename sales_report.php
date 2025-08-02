<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';


// Handle search
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$query = "
SELECT s.*, p.name, p.price 
FROM sales s 
JOIN products p ON s.product_id = p.id 
WHERE DATE(s.sale_date) = ? AND s.clinic_id = ?
ORDER BY s.sale_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('si', $selected_date, $_SESSION['clinic_id']);
$stmt->execute();
$stmt = $conn->prepare("SELECT SUM(total) AS total_sales FROM sales WHERE DATE(sale_date) = ?");
$stmt->bind_param("s", $selected_date);
// Calculate total sales
// If there are no sales for the selected date, SUM(total) will return null, so we default to 0
$total_query = $conn->query("SELECT SUM(total) AS total_sales FROM sales WHERE DATE(sale_date) = '$selected_date'");
$total_sales = $total_query->fetch_assoc()['total_sales'] ?? 0;
$stmt->close();
// Calculate total sales
$total_query = $conn->query("SELECT SUM(total) AS total_sales FROM sales WHERE DATE(sale_date) = '$selected_date'");
$total_sales = $total_query->fetch_assoc()['total_sales'] ?? 0;
?>
<?php include('includes/header.php'); ?>
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
      <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" required>
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
             <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // You need to fetch the result from the first prepared statement
          $result = $conn->prepare($query);
          $result->bind_param('si', $selected_date, $_SESSION['clinic_id']);
          $result->execute();
          $result = $result->get_result();
          ?>
          <?php if ($result->num_rows > 0): ?>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['quantity_sold'] ?></td>
                <td>SLSH<?= number_format($row['price'], 2) ?></td>
                <td>SLSH<?= number_format($row['total'], 2) ?></td>
                <td><?= $row['sale_date'] ?></td>
                <td>
                    <a href="receipt.php?sale_id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" target="_blank">🖨️ Print</a>
                    <a href="delete_sale.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this sale?');">🗑️ Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center">No sales found for this date.</td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="5" class="text-end">Total Sales:</th>
            <th colspan="2">SLSH<?= number_format($total_sales, 2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>