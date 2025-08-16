<?php
include 'config/db.php';
include('includes/header.php');
$success = false;

// Fetch visitors for dropdown
$visitors = $conn->query("SELECT id, full_name, purpose FROM visitors WHERE clinic_id = " . intval($_SESSION['clinic_id']) . " ORDER BY visit_date DESC");

// Fetch services from history_taking with their ID and total_price
$services = $conn->query("SELECT id, services, total_price FROM history_taking ORDER BY date_taken DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_id = $_POST['visitor_id'];
    $history_id = $_POST['history_id'];
    $amount_paid = floatval($_POST['amount_paid']);

    // Get visitor name
    $stmt = $conn->prepare("SELECT full_name FROM visitors WHERE id = ?");
    $stmt->bind_param("i", $visitor_id);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();
    $patient_name = $visitor['full_name'];

    // Get service total price and name from history_taking
    $stmt2 = $conn->prepare("SELECT total_price, services FROM history_taking WHERE id = ?");
    $stmt2->bind_param("i", $history_id);
    $stmt2->execute();
    $service = $stmt2->get_result()->fetch_assoc();
    $service_name = $service['services'];
    $service_total = floatval($service['total_price']);

    // Get previous balance (sum of all remaining balances)
    $stmt3 = $conn->prepare("SELECT SUM(balance) as total_previous_balance FROM vouchers WHERE visitor_id = ?");
    $stmt3->bind_param("i", $visitor_id);
    $stmt3->execute();
    $row = $stmt3->get_result()->fetch_assoc();
    $previous_balance = floatval($row['total_previous_balance'] ?? 0);

    // Calculate new balance
    $new_balance = max($previous_balance + $service_total - $amount_paid, 0);

    // Insert voucher
    $clinic_id = $_SESSION['clinic_id'];
    $insert = $conn->prepare("INSERT INTO vouchers (clinic_id, visitor_id, patient_name, history_id, service, amount_paid, service_total, previous_balance, balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("iisssdddd", $clinic_id, $visitor_id, $patient_name, $history_id, $service_name, $amount_paid, $service_total, $previous_balance, $new_balance);
    $insert->execute();
    $voucher_id = $insert->insert_id;
    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Generate Payment Voucher</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">🧾 Generate Voucher</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">
      Voucher created! <a href="print_voucher.php?id=<?= $voucher_id ?>" class="btn btn-sm btn-outline-primary">🖨️ Print Voucher</a>
    </div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label>Select Visitor</label>
      <select name="visitor_id" class="form-select" required>
        <option value="">-- Choose Patient --</option>
        <?php while ($v = $visitors->fetch_assoc()): ?>
          <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['full_name']) ?> - <?= htmlspecialchars($v['purpose']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label>Select Service</label>
      <select name="history_id" class="form-select" required>
        <option value="">-- Choose Service --</option>
        <?php while ($s = $services->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>">
            <?= htmlspecialchars($s['services']) ?> - <?= number_format($s['total_price'], 2) ?> SLSH
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label>Amount Paid (SLSH)</label>
      <input type="number" step="0.01" name="amount_paid" class="form-control" required>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-success">💾 Save Voucher</button>
      <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
  <?php include 'includes/footer.php'; ?>
</body>
</html>
