<?php
include 'config/db.php';
include('includes/header.php');
$search_name = $_GET['name'] ?? '';

$query = "SELECT * FROM vouchers WHERE 1";
$params = [];
$types = "";

if (!empty($search_name)) {
    $query .= " AND patient_name LIKE ?";
    $params[] = '%' . $search_name . '%';
    $types .= "s";
}

$query .= " ORDER BY date_paid DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>All Vouchers</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">💵 Generated Vouchers</h2>

  <!-- Search Form -->
  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-6">
      <input type="text" name="name" class="form-control" placeholder="Search by patient name..." value="<?= htmlspecialchars($search_name) ?>">
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-primary w-100">Search</button>
    </div>
    <div class="col-md-3">
      <a href="view_vouchers.php" class="btn btn-secondary w-100">Reset</a>
    </div>
  </form>

  <!-- Voucher Table -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Patient Name</th>
          <th>Service</th>
          <th>Amount Paid (SLSH)</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td><?= htmlspecialchars($row['service']) ?></td>
              <td><?= number_format($row['amount_paid'], 2) ?></td>
              <td><?= date('d M Y - H:i', strtotime($row['date_paid'])) ?></td>
              <td>
                <a href="print_voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">🖨️ Print</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center text-danger">No vouchers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
