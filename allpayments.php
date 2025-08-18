<?php
session_start();
include 'config/db.php';
include 'auth_check.php';
include('includes/header.php');

$clinic_id = $_SESSION['clinic_id'] ?? 0;
$search = $_GET['search'] ?? '';

// Get all vouchers with their service totals
if ($search) {
    $stmt = $conn->prepare("
        SELECT v.*, h.total_price AS history_total
        FROM vouchers v
        LEFT JOIN history_taking h ON v.history_id = h.id
        WHERE v.clinic_id = ? AND v.patient_name LIKE ?
        ORDER BY v.history_id, v.id ASC
    ");
    $like = "%" . $search . "%";
    $stmt->bind_param("is", $clinic_id, $like);
} else {
    $stmt = $conn->prepare("
        SELECT v.*, h.total_price AS history_total
        FROM vouchers v
        LEFT JOIN history_taking h ON v.history_id = h.id
        WHERE v.clinic_id = ?
        ORDER BY v.history_id, v.id ASC
    ");
    $stmt->bind_param("i", $clinic_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Process vouchers to calculate correct balances
$serviceBalances = [];
$processedVouchers = [];

while ($row = $result->fetch_assoc()) {
    $serviceKey = $row['history_id'];
    
    if (!isset($serviceBalances[$serviceKey])) {
        // First voucher for this service - initialize with service total
        $serviceTotal = $row['history_total'] ?? $row['service_total'];
        $serviceBalances[$serviceKey] = $serviceTotal;
    }
    
    // Calculate remaining balance
    $amountPaid = $row['amount_paid'];
    $remainingBalance = max($serviceBalances[$serviceKey] - $amountPaid, 0);
    
    // Store the calculated balance
    $row['corrected_balance'] = $remainingBalance;
    $row['corrected_previous'] = $serviceBalances[$serviceKey];
    
    // Update the balance for next voucher
    $serviceBalances[$serviceKey] = $remainingBalance;
    
    $processedVouchers[] = $row;
}

// Reverse to show newest first
$processedVouchers = array_reverse($processedVouchers);
?>

<div class="container mt-4">
    <h3 class="mb-4">📋 Previous Payments</h3>

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
                <th>Previous Balance</th>
                <th>Remaining Balance</th>
                <th>Date</th>
                <th>Print</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($processedVouchers)): ?>
                <?php foreach ($processedVouchers as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td><?= htmlspecialchars($row['service']) ?></td>
                        <td><?= number_format($row['history_total'] ?? $row['service_total'], 2) ?></td>
                        <td><?= number_format($row['amount_paid'], 2) ?></td>
                        <td><?= number_format($row['corrected_previous'], 2) ?></td>
                        <td><?= number_format($row['corrected_balance'], 2) ?></td>
                        <td><?= date('d M Y', strtotime($row['date_paid'])) ?></td>
                        <td>
                            <a href="print_voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">🖨️ Print</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include('includes/footer.php'); ?>