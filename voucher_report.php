<?php
include 'config/db.php';
include('includes/header1.php');

// Filters
$search_name = $_GET['name'] ?? '';
$search_date = $_GET['date'] ?? '';

$query = "SELECT * FROM vouchers WHERE 1";
$total_query = "SELECT SUM(amount_paid) AS total_sales FROM vouchers WHERE 1";

$params = [];
$total_params = [];
$types = "";
$total_types = "";

if (!empty($search_name)) {
    $query .= " AND patient_name LIKE ?";
    $total_query .= " AND patient_name LIKE ?";
    $params[] = $total_params[] = "%$search_name%";
    $types .= $total_types .= "s";
}

if (!empty($search_date)) {
    $query .= " AND DATE(date_paid) = ?";
    $total_query .= " AND DATE(date_paid) = ?";
    $params[] = $total_params[] = $search_date;
    $types .= $total_types .= "s";
}

$query .= " ORDER BY date_paid DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$total_stmt = $conn->prepare($total_query);
if (!empty($total_params)) {
    $total_stmt->bind_param($total_types, ...$total_params);
}
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_sales = $total_result->fetch_assoc()['total_sales'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
  <title>Voucher Reports</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">📊 Voucher Reports</h2>

  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
      <input type="text" name="name" class="form-control" placeholder="Search by patient name..." value="<?= htmlspecialchars($search_name) ?>">
    </div>
    <div class="col-md-3">
      <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($search_date) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Search</button>
    </div>
    <div class="col-md-3">
      <a href="voucher_report.php" class="btn btn-secondary w-100">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Patient Name</th>
          <th>Service</th>
          <th>Amount Paid</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td><?= htmlspecialchars($row['service']) ?></td>
              <td><?= number_format($row['amount_paid'], 2) ?> SLSH</td>
              <td><?= date('d M Y - H:i', strtotime($row['date_paid'])) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center">No vouchers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    <h5>Total Collected: <span class="text-success">SLSH <?= number_format($total_sales, 2) ?></span></h5>
  </div>
</body>
</html>
