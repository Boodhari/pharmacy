<?php
session_start();
include 'config/db.php';
include('includes/header.php');

$success = false;
$error = '';
$clinic_id = $_SESSION['clinic_id'] ?? 0;
$search_phone = $_GET['search_phone'] ?? '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch visitors with optional phone search
if ($search_phone) {
    $visitors = $conn->prepare("
        SELECT id, full_name, purpose, phone 
        FROM visitors 
        WHERE clinic_id = ? AND phone LIKE ?
        ORDER BY visit_date DESC
    ");
    $like_phone = "%" . $search_phone . "%";
    $visitors->bind_param("is", $clinic_id, $like_phone);
} else {
    $visitors = $conn->prepare("
        SELECT id, full_name, purpose, phone 
        FROM visitors 
        WHERE clinic_id = ? 
        ORDER BY visit_date DESC
    ");
    $visitors->bind_param("i", $clinic_id);
}
$visitors->execute();
$visitor_result = $visitors->get_result();

// Fetch services
$services = $conn->prepare("
    SELECT id, services, total_price 
    FROM history_taking 
    WHERE clinic_id = ? 
    ORDER BY date_taken DESC
");
$services->bind_param("i", $clinic_id);
$services->execute();
$service_result = $services->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $visitor_id = intval($_POST['visitor_id']);
        $history_id = intval($_POST['history_id']);
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
            $error = "Selected service not found.";
        } else {
            $service_name = $service['services'];
            $service_total = floatval($service['total_price']);

            // Check if this is the first voucher for this service
            $first_voucher_stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM vouchers 
                WHERE visitor_id = ? AND clinic_id = ? AND history_id = ?
            ");
            $first_voucher_stmt->bind_param("iii", $visitor_id, $clinic_id, $history_id);
            $first_voucher_stmt->execute();
            $is_first_voucher = $first_voucher_stmt->get_result()->fetch_assoc()['count'] == 0;

            // For first voucher, we start with the service total
            if ($is_first_voucher) {
                $previous_balance = 0;
                $total_due = $service_total;
            } 
            // For subsequent vouchers, we use the last remaining balance
            else {
                $balance_stmt = $conn->prepare("
                    SELECT balance 
                    FROM vouchers 
                    WHERE visitor_id = ? AND clinic_id = ? AND history_id = ?
                    ORDER BY id DESC 
                    LIMIT 1
                ");
                $balance_stmt->bind_param("iii", $visitor_id, $clinic_id, $history_id);
                $balance_stmt->execute();
                $balance_result = $balance_stmt->get_result()->fetch_assoc();
                $previous_balance = floatval($balance_result['balance'] ?? 0);
                $total_due = $previous_balance;
            }

            $new_balance = max($total_due - $amount_paid, 0);

            // Insert voucher
            $insert = $conn->prepare("
                INSERT INTO vouchers (
                    clinic_id, visitor_id, patient_name, history_id, 
                    service, amount_paid, service_total, 
                    previous_balance, balance, date_paid
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insert->bind_param(
                "iisisdddd", 
                $clinic_id, $visitor_id, $patient_name, $history_id, 
                $service_name, $amount_paid, $service_total, 
                $previous_balance, $new_balance
            );

            if ($insert->execute()) {
                $voucher_id = $insert->insert_id;
                $success = true;
            } else {
                $error = "Failed to save voucher.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Payment Voucher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
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

    <!-- Phone Search Form -->
    <div class="search-container mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label>Search Patient by Phone</label>
                <input type="text" name="search_phone" value="<?= htmlspecialchars($search_phone) ?>" 
                       class="form-control" placeholder="Enter phone number...">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">🔍 Search</button>
                <?php if ($search_phone): ?>
                    <a href="generate_voucher.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <form method="POST" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        
        <div class="col-md-6">
            <label>Select Patient</label>
            <select name="visitor_id" class="form-select" required>
                <option value="">-- Choose Patient --</option>
                <?php while ($v = $visitor_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($v['id']) ?>">
                        <?= htmlspecialchars($v['full_name']) ?> 
                        (<?= htmlspecialchars($v['phone']) ?> - <?= htmlspecialchars($v['purpose']) ?>)
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