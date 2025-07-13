<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

$products = $conn->query("SELECT * FROM products");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $payment_type = $_POST['payment_type'] ;

    $transaction_id = uniqid("txn_");

    foreach ($product_ids as $index => $product_id) {
        $product_id = intval($product_id);
        $quantity = intval($quantities[$index]);

        if ($quantity < 1) continue;

        $res = $conn->query("SELECT * FROM products WHERE id = $product_id");
        $product = $res->fetch_assoc();

        if (!$product || $product['quantity'] < $quantity) {
            continue; // Skip invalid or insufficient stock
        }

        $total = $quantity * $product['price'];

        $stmt = $conn->prepare("INSERT INTO sales (transaction_id, product_id, quantity_sold, total,payment_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siids", $transaction_id, $product_id, $quantity, $total, $payment_type);
        $stmt->execute();

        $conn->query("UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id");
    }

    header("Location: receipt.php?txn_id=$transaction_id");
    exit;
}
?>
<?php include('includes/header.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Sell Multiple Products - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    function addRow() {
      const container = document.getElementById('product-container');
      const row = container.firstElementChild.cloneNode(true);
      row.querySelectorAll('input').forEach(i => i.value = '');
      container.appendChild(row);
    }
  </script>
</head>
<body class="bg-light">
<div class="container py-5">
  <h2>🛒 Sell Multiple Products</h2>
  <form method="POST">
    <div id="product-container">
      <div class="row g-2 mb-3">
        <div class="col-md-8">
          <select name="product_id[]" class="form-select" required>
            <option value="">-- Select Product --</option>
            <?php
            $products->data_seek(0); // Reset pointer
            while ($p = $products->fetch_assoc()): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['name']) ?> (<?= $p['quantity'] ?> in stock)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-4">
          <input type="number" name="quantity[]" class="form-control" placeholder="Quantity" min="1" required>
        </div>
         <div class="col-md-4">
          <label>Payment Type</label>
  <select name="payment_type" class="form-select" required>
    <option value="Zaad-SLSH">Zaad - SLSH</option>
    <option value="Zaad-USD">Zaad - USD</option>
    <option value="Edahab-SLSH">Edahab - SLSH</option>
    <option value="Edahab-USD">Edahab - USD</option>
    <option value="EVC">EVC</option>
    <option value="Cash-SLSH">Cash - SLSH</option>
    <option value="Cash-USD">Cash - USD</option>
  </select>
        </div>
      </div>
    </div>
    <button type="button" onclick="addRow()" class="btn btn-sm btn-secondary mb-3">➕ Add Another Product</button>
    <br>
    <button class="btn btn-success">✅ Sell & Print Receipt</button>
    <a href="dashboard.php" class="btn btn-secondary">⬅️ Back</a>
  </form>
</div>
</body>
</html>
