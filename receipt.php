<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header.php');
if (!isset($_GET['sale_id']) || intval($_GET['sale_id']) < 1) {
    die("Invalid Sale ID.");
}

$sale_id = intval($_GET['sale_id']);

$query = "SELECT s.*, p.name, p.price 
          FROM sales s 
          JOIN products p ON s.product_id = p.id 
          WHERE s.id = $sale_id";

$result = $conn->query($query);

if ($result->num_rows === 0) {
    die("Sale not found.");
}

$sale = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Receipt - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print {
      button { display: none; }
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card p-4">
    <h3 class="text-center mb-4">Pharmacy Receipt</h3>
    <p><strong>Product:</strong> <?= htmlspecialchars($sale['name']) ?></p>
    <p><strong>Unit Price:</strong> $<?= number_format($sale['price'], 2) ?></p>
    <p><strong>Quantity:</strong> <?= $sale['quantity_sold'] ?></p>
    <p><strong>Total:</strong> $<?= number_format($sale['total'], 2) ?></p>
    <p><strong>Date:</strong> <?= $sale['sale_date'] ?></p>
    <button onclick="window.print()" class="btn btn-primary mt-3">Print Receipt</button>
  </div>
  <a href="dashboard.php" class="mt-3 d-block">Back to Dashboard</a>
</div>
</body>
</html>
