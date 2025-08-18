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

// Fetch visitors and services (same as before)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $visitor_id = intval($_POST['visitor_id']);
        $history_id = intval($_POST['history_id']);
        $amount_paid = max(0.0, floatval($_POST['amount_paid']));

        // Get patient and service details (same as before)

        if (!$service) {
            $error = "Selected service not found.";
        } else {
            $service_name = $service['services'];
            $service_total = floatval($service['total_price']);

            // NEW CALCULATION LOGIC:
            // 1. Get the LAST balance for this service
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
            
            // Previous balance is either the last balance or 0 if no previous vouchers
            $previous_balance = $balance_result ? floatval($balance_result['balance']) : 0;

            // 2. Calculate new balance
            // Total due is the service amount plus any previous balance
            $total_due = $service_total + $previous_balance;
            
            // New balance is total due minus amount paid (never negative)
            $new_balance = max($total_due - $amount_paid, 0);

            // Insert voucher (same as before)
        }
    }
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