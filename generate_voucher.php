<?php
session_start();
include 'config/db.php';
include('includes/header.php');

$success = false;
$error = '';
$clinic_id = $_SESSION['clinic_id'] ?? 0;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch visitors
$visitors = $conn->prepare("SELECT id, full_name, purpose FROM visitors WHERE clinic_id = ? ORDER BY visit_date DESC");
$visitors->bind_param("i", $clinic_id);
$visitors->execute();
$visitor_result = $visitors->get_result();

// Fetch services
$services = $conn->prepare("SELECT id, services, total_price FROM history_taking WHERE clinic_id = ? ORDER BY date_taken DESC");
$services->bind_param("i", $clinic_id);
$services->execute();
$service_result = $services->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_id  = intval($_POST['visitor_id']);
    $history_id  = intval($_POST['history_id']);
    $amount_paid = max(0.0, floatval($_POST['amount_paid']));

    // Get patient name
    $stmt = $conn->prepare("SELECT full_name FROM visitors WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param("ii", $visitor_id, $clinic_id);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();
    $patient_name = $visitor['full_name'] ?? 'Unknown';

    // Get service details
    $stmt2 = $conn->prepare("SELECT services, total_price FROM history_taking WHERE id = ? AND clinic_id = ?");
    $stmt2->bind_param("ii", $history_id, $clinic_id);
    $stmt2->execute();
    $service = $stmt2->get_result()->fetch_assoc();
    if (!$service) {
        die("Selected service not found.");
    }
    $service_name  = $service['services'] ?? 'Unknown Service';
    $service_total = floatval($service['total_price'] ?? 0);

    // ✅ Step 1: calculate unpaid amount for this specific service
    $paid_stmt = $conn->prepare("
        SELECT SUM(amount_paid) AS total_paid 
        FROM vouchers 
        WHERE history_id = ? AND visitor_id = ? AND clinic_id = ?
    ");
    $paid_stmt->bind_param("iii", $history_id, $visitor_id, $clinic_id);
    $paid_stmt->execute();
    $paid_result = $paid_stmt->get_result()->fetch_assoc();
    $already_paid = floatval($paid_result['total_paid'] ?? 0);

    $remaining_for_service = max($service_total - $already_paid, 0);

    // ✅ Step 2: calculate unpaid balances from other services (FIFO)
    $prev_stmt = $conn->prepare("
        SELECT service_total, amount_paid 
        FROM vouchers 
        WHERE visitor_id = ? AND clinic_id = ? AND history_id != ?
        ORDER BY date_paid ASC, id ASC
    ");
    $prev_stmt->bind_param("iii", $visitor_id, $clinic_id, $history_id);
    $prev_stmt->execute();
    $prev_result = $prev_stmt->get_result();

    $previous_balance = 0;
    while ($row = $prev_result->fetch_assoc()) {
        $remaining = max($row['service_total'] - $row['amount_paid'], 0);
        $previous_balance += $remaining;
    }

    // ✅ Step 3: total due = unpaid from other services + remaining for this service
    $total_due = $previous_balance + $remaining_for_service;

    // ✅ Step 4: new balance after payment
    $new_balance = max($total_due - $amount_paid, 0);

    // Insert voucher
    $insert = $conn->prepare("
        INSERT INTO vouchers 
        (clinic_id, visitor_id, patient_name, history_id, service, amount_paid, service_total, previous_balance, balance, date_paid)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $insert->bind_param(
        "iisisdddd",
        $clinic_id,
        $visitor_id,
        $patient_name,
        $history_id,
        $service_name,
        $amount_paid,
        $service_total,
        $previous_balance,
        $new_balance
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
        <a href="print_voucher.php?id=<?= htmlspecialchars($voucher_id) ?>" class="btn btn-sm btn-outline-primary">🖨️ Print Voucher</a>
    </div>
<?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <div class="col-md-6">
        <label>Select Visitor</label>
        <select name="visitor_id" class="form-select" required>
            <option value="">-- Choose Patient --</option>
            <?php while ($v = $visitor_result->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($v['id']) ?>">
                    <?= htmlspecialchars($v['full_name']) ?> - <?= htmlspecialchars($v['purpose']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label>Select Service</label>
        <select name="history_id" class="form-select" required>
            <option value="">-- Choose Service --</option>
            <?php while ($s = $service_result->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($s['id']) ?>">
                    <?= htmlspecialchars($s['services']) ?> - <?= number_format($s['total_price'], 2) ?> SLSH
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label>Amount Paid (SLSH)</label>
        <input type="number" step="0.01" name="amount_paid" class="form-control" min="0" required>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-success">💾 Save Voucher</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
</body>
</html>
