<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header.php');

// Today's Sales Total
$today = date('Y-m-d');
$sales_query = $conn->query("SELECT SUM(total) AS total_today FROM sales WHERE DATE(sale_date) = '$today'");
$sales_today = $sales_query->fetch_assoc()['total_today'] ?? 0;

// Total Stock
$stock_query = $conn->query("SELECT SUM(quantity) AS total_stock FROM products");
$total_stock = $stock_query->fetch_assoc()['total_stock'] ?? 0;

// Low Stock Count
$low_stock_query = $conn->query("SELECT COUNT(*) AS low_count FROM products WHERE quantity < 10");
$low_stock_count = $low_stock_query->fetch_assoc()['low_count'] ?? 0;
// Total Prescriptions
$prescriptions_query = $conn->query("SELECT COUNT(*) AS total_prescriptions FROM prescriptions");
$total_prescriptions = $prescriptions_query->fetch_assoc()['total_prescriptions'] ?? 0;
// Weekly Sales Data for Chart
$weekly_sales = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $res = $conn->query("SELECT SUM(total) AS total FROM sales WHERE DATE(sale_date) = '$day'");
    $amount = $res->fetch_assoc()['total'] ?? 0;
    $labels[] = $day;
    $weekly_sales[] = $amount;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Dashboard - Pharmacy POS</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h2>

  <?php if ($low_stock_count > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <strong>Attention!</strong> <?= $low_stock_count ?> product(s) are low in stock.
      <a href="products.php" class="alert-link">Check inventory</a>.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
 
    <div class="col-md-3">
      <div class="card shadow-sm border-0 text-white bg-primary">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-box-seam"></i> Total Stock</h5>
          <p class="card-text fs-4"><?= $total_stock ?> units</p>
        </div>
      </div>
    </div>
       <div class="col-md-3">
      <div class="card shadow-sm border-0 text-white bg-success">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-cash-coin"></i> Today's Sales</h5>
          <p class="card-text fs-4">SLSH<?= number_format($sales_today, 2) ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0 text-white bg-danger">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-exclamation-triangle"></i> Low Stock</h5>
          <p class="card-text fs-4"><?= $low_stock_count ?> item(s)</p>
        </div>
      </div>
    </div>
        <div class="col-md-3">
      <div class="card shadow-sm border-0 text-white bg-info">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-clipboard-check"></i>Prescriptions</h5>
          <p class="card-text fs-4"><?= $total_prescriptions ?> issued</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-box display-4 text-primary mb-3"></i>
          <h5 class="card-title">Manage Products</h5>
          <p class="card-text">Add, update and manage inventory.</p>
          <a href="products.php" class="btn btn-outline-primary w-100">Go to Products</a>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-cart-check display-4 text-success mb-3"></i>
          <h5 class="card-title">Sell Products</h5>
          <p class="card-text">Process sales and print receipts.</p>
          <a href="sell.php" class="btn btn-outline-success w-100">Sell Now</a>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-graph-up-arrow display-4 text-warning mb-3"></i>
          <h5 class="card-title">Sales Report</h5>
          <p class="card-text">View and search daily sales.</p>
          <a href="sales_report.php" class="btn btn-outline-warning w-100">View Reports</a>
       
         
        </div>
      </div>
      
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-clipboard-data display-4 text-secondary mb-3"></i>
          <h5 class="card-title">Prescriptions</h5>
          <p class="card-text">View and print prescriptions.</p>
          <a href="view_prescriptions.php" class="btn btn-outline-secondary w-100">View Prescriptions</a>
        </div>
      </div>
    </div>
  </div>
  <div class="row py-2">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-plus display-4 text-info mb-3"></i>
          <h5 class="card-title">Register Visitor</h5>
          <p class="card-text">Register daily visitors to the pharmacy.</p>
          <a href="register_visitor.php" class="btn btn-outline-info w-100">Register Visitor</a>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-lines-fill display-4 text-dark mb-3"></i>
          <h5 class="card-title">Visitor Status</h5>
          <p class="card-text">Check and manage visitor status.</p>
          <a href="visitor_status.php" class="btn btn-outline-dark w-100">Check Status</a>
        </div>  
        </div>
      </div>
        <div class="col-md-3">  
          <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-lines-fill display-4 text-dark mb-3"></i>
          <h5 class="card-title">Visitor View</h5>
          <p class="card-text">visitor view</p>
          <a href="view_visitors.php" class="btn btn-outline-dark w-100">View Visitors</a>
        </div>
       </div>
       </div>
       <div class="col-md-3">  
          <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-lines-fill display-4 text-dark mb-3"></i>
          <h5 class="card-title">History View</h5>
          <p class="card-text">History view</p>
          <a href="view_history.php" class="btn btn-outline-dark w-100">View History</a>
        </div>
       </div>
       </div>
        
  </div>
  <div class="row py-2">
    <div class="col-md-6">  
          <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-lines-fill display-4 text-dark mb-3"></i>
          <h5 class="card-title">Create voucher</h5>
          <p class="card-text">Voucher view</p>
          <a href="generate_voucher.php" class="btn btn-outline-dark w-100">New Voucher</a>
        </div>
       </div>
       </div>
        <div class="col-md-6">  
          <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-lines-fill display-4 text-dark mb-3"></i>
          <h5 class="card-title">Print vouchers</h5>
          <p class="card-text">Voucher view</p>
          <a href="view_vouchers.php" class="btn btn-outline-dark w-100">View Voucher</a>
        </div>
       </div>
       </div>
  </div>

  <!-- Chart Section -->
  <div class="mt-5">
    <h4 class="mb-3">📊 Sales (Last 7 Days)</h4>
    <canvas id="weeklyChart" height="100"></canvas>
  </div>

  <div class="mt-5 text-end">
    <a href="logout.php" class="btn btn-outline-danger">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</div>

<!-- Bootstrap and Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('weeklyChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Daily Sales ($)',
        data: <?= json_encode($weekly_sales) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.7)'
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>
</body>
</html>
