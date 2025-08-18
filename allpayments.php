<?php
session_start();
include 'config/db.php';
include 'auth_check.php';
include('includes/header.php');

// Get clinic_id from session
$clinic_id = $_SESSION['clinic_id'] ?? 0;

// Search
$search = $_GET['search'] ?? '';

// Fetch vouchers with optional search
if ($search) {
    $stmt = $conn->prepare("
        SELECT v.*, h.total_price AS history_total
        FROM vouchers v
        LEFT JOIN history_taking h ON v.history_id = h.id
        WHERE v.clinic_id = ? AND v.patient_name LIKE ?
        ORDER BY v.date_paid DESC
    ");
    $like = "%" . $search . "%";
    $stmt->bind_param("is", $clinic_id, $like);
} else {
    $stmt = $conn->prepare("
        SELECT v.*, h.total_price AS history_total
        FROM vouchers v
        LEFT JOIN history_taking h ON v.history_id = h.id
        WHERE v.clinic_id = ?
        ORDER BY v.date_paid DESC
    ");
    $stmt->bind_param("i", $clinic_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Pre-calculate correct balances for each service
$balances = [];
$vouchers = [];
while ($row = $result->fetch_assoc()) {
    $service_key = $row['visitor_id'] . '-' . $row['history_id'];
    
    if (!isset($balances[$service_key])) {
        $balances[$service_key] = 0;
    }
    
    // For the first voucher of a service, add the service total
    if ($balances[$service_key] == 0) {
        $service_total = $row['history_total'] ?? $row['service_total'];
        $balances[$service_key] = $service_total;
    }
    
    // Calculate remaining balance
    $amount_paid = $row['amount_paid'];
    $remaining_balance = max($balances[$service_key] - $amount_paid, 0);
    
    // Store voucher with corrected balance
    $row['corrected_balance'] = $remaining_balance;
    $vouchers[] = $row;
    
    // Update balance for next voucher
    $balances[$service_key] = $remaining_balance;
}
?>

<div class="container mt-4">
    <h3 class="mb-4">📋 Previous Payments</h3>

    <!-- Search Form -->
    <form method="get" class="mb-3 d-flex">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
               class="form-control me-2" placeholder="Search patient name...">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
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
            <?php if (count($vouchers) > 0): ?>
                <?php foreach ($vouchers as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td><?= htmlspecialchars($row['service']) ?></td>
                        <td><?= number_format($row['history_total'] ?? $row['service_total'], 2) ?></td>
                        <td><?= number_format($row['amount_paid'], 2) ?></td>
                        <td><?= number_format($row['corrected_balance'], 2) ?></td>
                        <td><?= date('d M Y', strtotime($row['date_paid'])) ?></td>
                        <td>
                            <a href="print_voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">🖨️ Print</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include('includes/footer.php'); ?>