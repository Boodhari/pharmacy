<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require 'config/db.php';

$clinic_id = $_SESSION['clinic_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_id   = intval($_POST['visitor_id']);
    $history_id   = intval($_POST['history_id']);
    $patient_name = trim($_POST['patient_name']);
    $amount_paid  = floatval($_POST['amount_paid']);

    // 1) Get service total & name from history_taking
    $stmt2 = $conn->prepare("SELECT total_price, services FROM history_taking WHERE id = ?");
    $stmt2->bind_param("i", $history_id);
    $stmt2->execute();
    $service = $stmt2->get_result()->fetch_assoc();
    $service_name  = $service['services'];
    $service_total = (float)$service['total_price'];

    // 2) Get the LATEST remaining balance
    $prev = $conn->prepare("
      SELECT balance 
      FROM vouchers 
      WHERE clinic_id = ? AND visitor_id = ?
      ORDER BY date_paid DESC, id DESC
      LIMIT 1
    ");
    $prev->bind_param("ii", $clinic_id, $visitor_id);
    $prev->execute();
    $prev_res = $prev->get_result()->fetch_assoc();
    $previous_balance = $prev_res ? (float)$prev_res['balance'] : 0.0;

    // 3) Compute the new remaining balance
    $new_balance = max($previous_balance + $service_total - $amount_paid, 0);

    // 4) Insert voucher (store previous_balance too)
    $ins = $conn->prepare("
      INSERT INTO vouchers
        (clinic_id, visitor_id, patient_name, history_id, service, amount_paid, service_total, previous_balance, balance, date_paid)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $ins->bind_param(
        "iisssdddd",
        $clinic_id, $visitor_id, $patient_name, $history_id, $service_name,
        $amount_paid, $service_total, $previous_balance, $new_balance
    );
    if ($ins->execute()) {
        $voucher_id = $ins->insert_id;
        $success = "Voucher generated successfully. ID: " . $voucher_id;
    } else {
        $error = "Error creating voucher: " . $conn->error;
    }
}

// Fetch visitors for dropdown
$visitor_result = $conn->prepare("SELECT id, full_name FROM visitors WHERE clinic_id = ?");
$visitor_result->bind_param("i", $clinic_id);
$visitor_result->execute();
$visitor_data = $visitor_result->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Voucher</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">

    <h2 class="mb-4">Generate Voucher</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Select Patient</label>
            <select name="visitor_id" class="form-control" required>
                <option value="">-- Choose --</option>
                <?php while ($row = $visitor_data->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">History ID</label>
            <input type="number" name="history_id" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Patient Name</label>
            <input type="text" name="patient_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Amount Paid</label>
            <input type="number" step="0.01" name="amount_paid" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Generate Voucher</button>
    </form>
</body>
</html>
