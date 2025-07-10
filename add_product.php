<?php
include 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $qty = $_POST['quantity'];
    $conn->query("INSERT INTO products (name, price, quantity) VALUES ('$name', $price, $qty)");
    header("Location: products.php");
}
include('includes/header.php');
?>
<form method="POST" class="card p-4 mx-auto" style="max-width: 500px;">
  <h4>Add Product</h4>
  <input class="form-control mb-2" type="text" name="name" placeholder="Product Name" required>
  <input class="form-control mb-2" type="number" step="0.01" name="price" placeholder="Price" required>
  <input class="form-control mb-2" type="number" name="quantity" placeholder="Quantity" required>
  <button class="btn btn-primary" type="submit">Add</button>
</form>
<?php include('includes/footer.php'); ?>