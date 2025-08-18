<?php
session_start();
include 'config/db.php';
include('includes/header.php');

$success = false;
$voucher_id = 0;

// Fetch visitors for dropdown
$visitors = $conn->query("SELECT id, full_name, purpose FROM visitors WHERE clinic_id = " . intval($_SESSION['clinic_id']) . " ORDER BY visit_date DESC");

// Fetch distinct services (if you have history_taking or services table)
$services = $conn->query("SELECT DISTINCT services FROM history_taking ORDER BY date_taken DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_id = $_POST['visitor_id'];
    $service = $_POST['service'];
    $amount_paid = $_POST['amount_paid'];

    // ✅ Get visitor name
    $stmt = $conn->prepare("SELECT full_name FROM visitors WHERE id = ?");
    $stmt->bind_param("i", $visitor_id);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();
    $patient_name = $visitor['full_name'];

    // ✅ Get previous remaining balance
    $prev_stmt = $conn->prepare("SELECT balance FROM vouchers WHERE visitor_id = ? ORDER BY id DESC LIMIT 1");
    $prev_stmt->bind_param("i", $visitor_id);
    $prev_stmt->execute();
    $prev_result = $prev_stmt->get_result();
    $previous_balance = $prev_result->num_rows > 0 ? $prev_result->fetch_assoc()['balance'] : 0;

    // ✅ Get service total (assume from services table)
    $service_total = 0;
    $srv_stmt = $conn->prepare("SELECT price FROM services WHERE name = ? LIMIT 1");
    $srv_stmt->bind_param("s", $service);
    $srv_stmt->execute();
    $srv_res = $srv_stmt->get_result();
    if ($srv_res->num_rows > 0) {
        $service_total = $srv_res->fetch_assoc()['price'];
    }

    // ✅ Calculate remaining balance
    $total_due = $previous_balance + $service_total;
    $remaining_balance = $total_due - $amount_paid;

    // ✅ Insert voucher
    $clinic_id = $_SESSION['clinic_id'];
    $insert = $conn->prepare("
        INSERT INTO vouchers 
        (clinic_id, visitor_id, patient_name, service, amount_paid, date_paid, balance, service_total, previous_balance) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)
    ");
    $insert->bind_param("iissdddi",
        $clinic_id,
        $visitor_id,
        $patient_name,
        $service,
        $amount_paid,
        $remaining_balance,   // balance
        $service_total,
        $previous_balance
    );
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
      Voucher created! 
      <a href="print_voucher.php?id=<?= $voucher_id ?>" class="btn btn-sm btn-outline-primary">🖨️ Print Voucher</a>
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
      <label>Service Description</label>
      <input list="services" name="service" class="form-control" required placeholder="Choose or type service">
      <datalist id="services">
        <?php while ($s = $services->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($s['services']) ?>">
        <?php endwhile; ?>
      </datalist>
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
