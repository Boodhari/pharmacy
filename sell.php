<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header.php');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Fetch product
    $res = $conn->query("SELECT * FROM products WHERE id = $product_id");
    $product = $res->fetch_assoc();

    if (!$product) {
        $error = "Product not found.";
    } elseif ($product['quantity'] < $quantity) {
        $error = "Insufficient stock.";
    } else {
        $total = $quantity * $product['price'];

        // Insert sale
        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity_sold, total) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $product_id, $quantity, $total);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Update product stock
            $conn->query("UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id");

            $sale_id = $stmt->insert_id;
            header("Location: receipt.php?sale_id=$sale_id");
            exit;
        } else {
            $error = "Failed to record sale.";
        }
    }
}

$products_result = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Sell Product - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2>Sell Product</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" style="max-width: 500px;">
    <div class="mb-3">
      <label>Product</label>
      <select name="product_id" class="form-select" required>
        <?php while ($prod = $products_result->fetch_assoc()): ?>
          <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?> (<?= $prod['quantity'] ?> in stock)</option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label>Quantity</label>
      <input type="number" name="quantity" min="1" class="form-control" required>
    </div>
    <button class="btn btn-primary" type="submit">Sell</button>
  </form>

  <a href="dashboard.php" class="mt-3 d-block">Back to Dashboard</a>
</div>
</body>
</html>
