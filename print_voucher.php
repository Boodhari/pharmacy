<?php
session_start();
include 'config/db.php';

$clinic_id = $_SESSION['clinic_id'] ?? 0;
$voucher_id = intval($_GET['id'] ?? 0);

if ($voucher_id <= 0) {
    die("Invalid voucher ID.");
}

// Fetch current voucher
$stmt = $conn->prepare("
    SELECT v.*, c.name AS clinic_name, c.address, c.phone, c.logo
    FROM vouchers v
    LEFT JOIN clinics c ON v.clinic_id = c.id
    WHERE v.id = ? AND v.clinic_id = ?
");
$stmt->bind_param("ii", $voucher_id, $clinic_id);
$stmt->execute();
$voucher = $stmt->get_result()->fetch_assoc();

if (!$voucher) {
    die("Voucher not found.");
}

// Fetch all previous unpaid vouchers for this patient (excluding current voucher)
$prev_stmt = $conn->prepare("
    SELECT service, service_total, amount_paid, balance, date_paid
    FROM vouchers
    WHERE visitor_id = ? AND clinic_id = ? AND id <> ?
    ORDER BY date_paid ASC, id ASC
");
$prev_stmt->bind_param("iii", $voucher['visitor_id'], $clinic_id, $voucher_id);
$prev_stmt->execute();
$previous_vouchers = $prev_stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Voucher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 20px; }
        .voucher-box {
            border: 2px solid #000;
            padding: 20px;
            max-width: 800px;
            margin: auto;
            background: #fff;
        }
        .voucher-header {
            text-align: center;
            border-bottom: 2px solid #000;
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        .voucher-header img {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .voucher-footer {
            border-top: 2px solid #000;
            margin-top: 15px;
            padding-top: 10px;
            text-align: center;
            font-size: 12px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="voucher-box">
    <div class="voucher-header">
        <?php if (!empty($voucher['logo'])): ?>
            <img src="uploads/<?= htmlspecialchars($voucher['logo']) ?>" alt="Clinic Logo">
        <?php endif; ?>
        <h3><?= htmlspecialchars($voucher['clinic_name'] ?? 'Clinic') ?></h3>
        <p><?= htmlspecialchars($voucher['address'] ?? '') ?> | <?= htmlspecialchars($voucher['phone'] ?? '') ?></p>
        <h4 class="mt-2">🧾 Payment Voucher</h4>
    </div>

    <p><strong>Voucher ID:</strong> <?= $voucher['id'] ?></p>
    <p><strong>Patient Name:</strong> <?= htmlspecialchars($voucher['patient_name']) ?></p>

    <?php if ($previous_vouchers->num_rows > 0): ?>
        <h5>Previous Unpaid Services:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $previous_vouchers->fetch_assoc()): ?>
                    <?php if ($row['balance'] > 0): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['service']) ?></td>
                        <td><?= number_format($row['service_total'], 2) ?> SLSH</td>
                        <td><?= number_format($row['amount_paid'], 2) ?> SLSH</td>
                        <td><?= number_format($row['balance'], 2) ?> SLSH</td>
                        <td><?= date("d M Y", strtotime($row['date_paid'])) ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No previous unpaid services.</p>
    <?php endif; ?>

    <h5>Current Service Payment:</h5>
    <table class="table table-bordered">
        <tr>
            <th>Service</th>
            <td><?= htmlspecialchars($voucher['service']) ?> - <?= number_format($voucher['service_total'], 2) ?> SLSH</td>
        </tr>
        <tr>
            <th>Previous Balance</th>
            <td><?= number_format($voucher['previous_balance'], 2) ?> SLSH</td>
        </tr>
        <tr>
            <th>Amount Paid</th>
            <td><strong><?= number_format($voucher['amount_paid'], 2) ?> SLSH</strong></td>
        </tr>
        <tr>
            <th>Remaining Balance</th>
            <td><strong><?= number_format($voucher['balance'], 2) ?> SLSH</strong></td>
        </tr>
        <tr>
            <th>Date</th>
            <td><?= date("d M Y - H:i", strtotime($voucher['date_paid'])) ?></td>
        </tr>
    </table>

    <p><strong>Received By:</strong> __________________________</p>

    <div class="voucher-footer">
        <p>Thank you for visiting <?= htmlspecialchars($voucher['clinic_name'] ?? 'our clinic') ?>.</p>
    </div>
</div>

<div class="text-center mt-3 no-print">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
    <a href="generate_voucher.php" class="btn btn-secondary">Back</a>
</div>

</body>
</html>
