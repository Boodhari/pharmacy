<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php'; // Only include header *after* processing

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Get product
    $res = $conn->query("SELECT * FROM products WHERE id = $product_id");
    $product = $res->fetch_assoc();

    if (!$product) {
        die("❌ Product not found.");
    }

    if ($product['quantity'] < $quantity) {
        die("❌ Not enough stock.");
    }

    $total = $quantity * $product['price'];

    // Insert sale
    $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity_sold, total) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $product_id, $quantity, $total);

    if (!$stmt->execute()) {
        die("❌ Failed to insert sale: " . $stmt->error);
    }

    $sale_id = $stmt->insert_id;

    // Update product quantity
    $conn->query("UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id");

    // Redirect to receipt
    header("Location: receipt.php?sale_id=$sale_id");
    exit;
}

// Fetch products for dropdown
$products = $conn->query("SELECT * FROM products");
?>
<?php include('includes/header.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Sell Product - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h2>🛒 Sell Product</h2>
  <form method="POST" class="mt-4" style="max-width: 500px;">
    <div class="mb-3">
      <label>Product</label>
      <select name="product_id" class="form-select" required>
        <?php while ($p = $products->fetch_assoc()): ?>
          <option value="<?= $p['id'] ?>">
            <?= htmlspecialchars($p['name']) ?> (<?= $p['quantity'] ?> in stock)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label>Quantity</label>
      <input type="number" name="quantity" class="form-control" min="1" required>
    </div>
    <button class="btn btn-success">✅ Sell & Print Receipt</button>
  </form>
  <a href="dashboard.php" class="btn btn-secondary mt-3">⬅️ Back to Dashboard</a>
</div>
</body>
</html>
