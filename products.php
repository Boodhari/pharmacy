<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['clinic_id'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header.php');
include 'check_clinic_status.php';

// Fetch products only for the current clinic
$clinic_id = $_SESSION['clinic_id'];
$result = $conn->prepare("SELECT * FROM products WHERE quantity > 0 AND clinic_id = ?");
$result->bind_param("i", $clinic_id);
$result->execute();
$products = $result->get_result();
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
      <tr>
        <th>Name</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $products->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td>$<?= number_format($row['price'], 2) ?></td>
          <td><?= $row['quantity'] ?></td>
          <td>
            <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this product?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <a href="dashboard.php">Back to Dashboard</a>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
