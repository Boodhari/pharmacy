<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['clinic_id'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
$clinic_id = $_SESSION['clinic_id'];

$id = $_GET['id'] ?? 0;

// Fetch the product for this clinic only
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND clinic_id = ?");
$stmt->bind_param("ii", $id, $clinic_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found or access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);

    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, quantity = ? WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param("sdiii", $name, $price, $quantity, $id, $clinic_id);
    $stmt->execute();

    header("Location: products.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Product</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-5">
  <h2>Edit Product</h2>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Product Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Price</label>
      <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Quantity</label>
      <input type="number" name="quantity" value="<?= $product['quantity'] ?>" class="form-control" required>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Update Product</button>
      <a href="products.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
  <?php include 'includes/footer.php'; ?>
</body>
</html>
