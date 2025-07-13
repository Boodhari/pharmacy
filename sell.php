<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];

    $sale_ids = [];

    foreach ($product_ids as $index => $product_id) {
        $product_id = intval($product_id);
        $quantity = intval($quantities[$index]);

        $res = $conn->query("SELECT * FROM products WHERE id = $product_id");
        $product = $res->fetch_assoc();

        if (!$product) {
            die("❌ Product not found (ID: $product_id).");
        }

        if ($product['quantity'] < $quantity) {
            die("❌ Not enough stock for {$product['name']}.");
        }

        $total = $quantity * $product['price'];

        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity_sold, total) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $product_id, $quantity, $total);

        if (!$stmt->execute()) {
            die("❌ Failed to insert sale: " . $stmt->error);
        }

        $sale_ids[] = $stmt->insert_id;

        $conn->query("UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id");
    }

    $last_sale_id = end($sale_ids);
    header("Location: receipt.php?sale_id=$last_sale_id");
    exit;
}

$products = $conn->query("SELECT * FROM products");
?>
<?php include('includes/header.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Sell Product - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    function addProductRow() {
      const row = document.querySelector('.product-row').cloneNode(true);
      document.getElementById('products-list').appendChild(row);
    }
  </script>
</head>
<body class="bg-light">
<div class="container py-5">
  <h2>🛒 Sell Products</h2>
  <form method="POST" class="mt-4" style="max-width: 600px;">
    <div id="products-list">
      <div class="product-row mb-3 row">
        <div class="col-md-8">
          <label>Product</label>
          <select name="product_id[]" class="form-select" required>
            <?php $products->data_seek(0); while ($p = $products->fetch_assoc()): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['name']) ?> (<?= $p['quantity'] ?> in stock)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Quantity</label>
          <input type="number" name="quantity[]" class="form-control" min="1" required>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addProductRow()">➕ Add Another Product</button><br>
    <button class="btn btn-success">✅ Sell & Print Receipt</button>
  </form>
  <a href="dashboard.php" class="btn btn-secondary mt-3">⬅️ Back to Dashboard</a>
</div>
</body>
</html>
