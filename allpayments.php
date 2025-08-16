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
?>

<div class="container mt-4">
    <h3 class="mb-4">📋 Previous Payments</h3>

    <!-- Search Form -->
    <form method="get" class="mb-3 d-flex">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control me-2" placeholder="Search patient name...">
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
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        // Correct calculation
                        $service_total = $row['history_total'] ?? $row['service_total'];
                        $previous_balance = $row['previous_balance'];
                        $amount_paid = $row['amount_paid'];

                        $remaining_balance = ($service_total + $previous_balance) - $amount_paid;
                        if ($remaining_balance < 0) $remaining_balance = 0;
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td><?= htmlspecialchars($row['service']) ?></td>
                        <td><?= number_format($service_total, 2) ?></td>
                        <td><?= number_format($amount_paid, 2) ?></td>
                        <td><?= number_format($remaining_balance, 2) ?></td>
                        <td><?= date('d M Y', strtotime($row['date_paid'])) ?></td>
                        <td>
                            <a href="print_voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">🖨️ Print</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include('includes/footer.php'); ?>
