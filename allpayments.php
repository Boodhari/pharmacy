<?php
include 'config/db.php';
include('auth_check.php');
include('includes/header.php');

// Get clinic ID from session
$clinic_id = $_SESSION['clinic_id'] ?? 0;

// Handle search
$search = $_GET['search'] ?? '';

// Fetch vouchers with optional search and clinic filter
if ($search) {
    $stmt = $conn->prepare("SELECT v.*, p.full_name 
                            FROM vouchers v 
                            JOIN visitors p ON v.visitor_id = p.id 
                            WHERE v.clinic_id = ? AND p.full_name LIKE ? 
                            ORDER BY v.date_paid DESC");
    $like = "%$search%";
    $stmt->bind_param("is", $clinic_id, $like);
} else {
    $stmt = $conn->prepare("SELECT v.*, p.full_name 
                            FROM vouchers v 
                            JOIN visitors p ON v.visitor_id = p.id 
                            WHERE v.clinic_id = ? 
                            ORDER BY v.date_paid DESC");
    $stmt->bind_param("i", $clinic_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Previous Payments</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">💳 Previous Payments</h2>

  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-6">
      <input type="text" name="search" class="form-control" placeholder="Search by patient name" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary">🔍 Search</button>
    </div>
  </form>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>#ID</th>
        <th>Patient Name</th>
        <th>Service</th>
        <th>Service Total</th>
        <th>Amount Paid</th>
        <th>Remaining Balance</th>
        <th>Date</th>
        <th>Print</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <tr <?= $row['balance'] > 0 ? 'class="table-danger"' : '' ?>>
            <td>#<?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['service']) ?></td>
            <td><?= number_format($row['service_total'], 2) ?></td>
            <td><?= number_format($row['amount_paid'], 2) ?></td>
            <td><?= number_format($row['balance'], 2) ?></td>
            <td><?= date('d M Y', strtotime($row['date_paid'])) ?></td>
            <td><a href="print_voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">🖨️ Print</a></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" class="text-center">No payments found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php include 'includes/footer.php'; ?>
</body>
</html>
