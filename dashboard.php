<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header.php');
include 'check_clinic_status.php';

$today = date('Y-m-d');
$clinic_id = $_SESSION['clinic_id'];

// Today's Sales Total
$sales_stmt = $conn->prepare("SELECT SUM(total) AS total_today FROM sales WHERE DATE(sale_date) = ? AND clinic_id = ?");
$sales_stmt->bind_param("si", $today, $clinic_id);
$sales_stmt->execute();
$sales_query = $sales_stmt->get_result();
$sales_today = $sales_query->fetch_assoc()['total_today'] ?? 0;
$sales_stmt->close();

// Total Stock
$stock_stmt = $conn->prepare("SELECT SUM(quantity) AS total_stock FROM products WHERE clinic_id = ?");
$stock_stmt->bind_param("i", $clinic_id);
$stock_stmt->execute();
$stock_query = $stock_stmt->get_result();
$total_stock = $stock_query->fetch_assoc()['total_stock'] ?? 0;
$stock_stmt->close();

// Low Stock Count
$low_stock_stmt = $conn->prepare("SELECT COUNT(*) AS low_count FROM products WHERE quantity < 10 AND clinic_id = ?");
$low_stock_stmt->bind_param("i", $clinic_id);
$low_stock_stmt->execute();
$low_stock_query = $low_stock_stmt->get_result();
$low_stock_count = $low_stock_query->fetch_assoc()['low_count'] ?? 0;
$low_stock_stmt->close();

// Total Prescriptions
$prescriptions_stmt = $conn->prepare("SELECT COUNT(*) AS total_prescriptions FROM prescriptions WHERE clinic_id = ?");
$prescriptions_stmt->bind_param("i", $clinic_id);
$prescriptions_stmt->execute();
$prescriptions_query = $prescriptions_stmt->get_result();
$total_prescriptions = $prescriptions_query->fetch_assoc()['total_prescriptions'] ?? 0;
$prescriptions_stmt->close();

// Weekly Sales Data for Chart
$weekly_sales = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT SUM(total) AS total FROM sales WHERE DATE(sale_date) = ? AND clinic_id = ?");
    $stmt->bind_param("si", $day, $clinic_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $amount = $res->fetch_assoc()['total'] ?? 0;
    $labels[] = $day;
    $weekly_sales[] = $amount;
    $stmt->close();
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
          <h5 class="card-title"><i class="bi bi-clipboard-check"></i> Prescriptions</h5>
          <p class="card-text fs-4"><?= $total_prescriptions ?> issued</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation Cards -->
  <div class="row g-4">
    <?php
    $cards = [
      ['Manage Products', 'bi-box', 'products.php', 'primary', 'Add, update and manage inventory.'],
      ['Sell Products', 'bi-cart-check', 'sell.php', 'success', 'Process sales and print receipts.'],
      ['Sales Report', 'bi-graph-up-arrow', 'sales_report.php', 'warning', 'View and search daily sales.'],
      ['Prescriptions', 'bi-clipboard-data', 'view_prescriptions.php', 'secondary', 'View and print prescriptions.'],
      ['Register Visitor', 'bi-person-plus', 'register_visitor.php', 'info', 'Register daily visitors to the pharmacy.'],
      ['Visitor Status', 'bi-person-lines-fill', 'visitor_status.php', 'dark', 'Check and manage visitor status.'],
      ['Visitor View', 'bi-person-lines-fill', 'view_visitors.php', 'dark', 'View all registered visitors.'],
      ['History View', 'bi-person-lines-fill', 'view_history.php', 'dark', 'View medical history records.'],
      ['Create Voucher', 'bi-person-lines-fill', 'generate_voucher.php', 'dark', 'Generate new service vouchers.'],
      ['Print Vouchers', 'bi-person-lines-fill', 'view_vouchers.php', 'dark', 'View and print service vouchers.']
    ];

    foreach ($cards as $card) {
      [$title, $icon, $link, $color, $text] = $card;
      echo <<<HTML
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <i class="bi {$icon} display-4 text-{$color} mb-3"></i>
            <h5 class="card-title">{$title}</h5>
            <p class="card-text">{$text}</p>
            <a href="{$link}" class="btn btn-outline-{$color} w-100">{$title}</a>
          </div>
        </div>
      </div>
      HTML;
    }
    ?>
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
        label: 'Daily Sales (SLSH)',
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
<?php include 'includes/footer.php'; ?>
</body>
</html>
