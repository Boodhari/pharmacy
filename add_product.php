<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['clinic_id'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
$clinic_id = $_SESSION['clinic_id'];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);

    $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, clinic_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdii", $name, $price, $quantity, $clinic_id);
    $stmt->execute();

    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Product - Pharmacy POS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-5">
  <h2 class="mb-4">➕ Add New Product</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">Product added successfully.</div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Product Name</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Price (SLSH)</label>
      <input type="number" name="price" step="0.01" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Quantity</label>
      <input type="number" name="quantity" class="form-control" required>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Add Product</button>
      <a href="products.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
  <?php include 'includes/footer.php'; ?>
</body>
</html>
