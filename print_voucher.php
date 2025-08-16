<?php
include 'config/db.php';
include 'auth_check.php';
include('includes/header.php');

$id = $_GET['id'] ?? 0;

// Default clinic info
$clinic_name = 'Clinic Management System';
$clinic_logo = null;

if (isset($_SESSION['clinic_id'])) {
    $stmt = $conn->prepare("SELECT name, Address, edahab_phone, zaad_phone, logo FROM clinics WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['clinic_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $clinic_name = $row['name'];
        $clinic_address = $row['Address'];
        $clinic_edahab = $row['edahab_phone'];
        $clinic_zaad = $row['zaad_phone'];
        $clinic_logo = $row['logo'] ?? null;
    }
    $stmt->close();
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    $clinic_name = "Super Admin Panel";
}

// Fetch current voucher
$stmt = $conn->prepare("SELECT v.*, h.total_price AS service_total_price 
                        FROM vouchers v 
                        LEFT JOIN history_taking h ON v.history_id = h.id 
                        WHERE v.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$voucher = $result->fetch_assoc();

if (!$voucher) {
    die("Voucher not found.");
}

// Fetch all previous vouchers for this patient (excluding current)
$stmt2 = $conn->prepare("SELECT * FROM vouchers WHERE visitor_id = ? AND id != ? ORDER BY date_paid ASC");
$stmt2->bind_param("ii", $voucher['visitor_id'], $id);
$stmt2->execute();
$previous_vouchers = $stmt2->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Print Voucher</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .voucher {
      width: 800px;
      margin: 40px auto;
      padding: 30px;
      border: 2px dashed #000;
      background: #fff;
    }
    .voucher h4, .voucher h5 {
      margin-bottom: 15px;
    }
    table {
      width: 100%;
      margin-top: 15px;
    }
    @media print {
      .no-print { display: none; }
      body { background: #fff; }
    }
  </style>
</head>
<body>
  <div class="voucher">
    <h4 class="text-center text-primary mb-2"><?= htmlspecialchars($clinic_name) ?> Dental Clinic</h4>
    <h5 class="text-center mb-4">
      Location: <?= htmlspecialchars($clinic_address) ?> ||
      Edahab: <?= htmlspecialchars($clinic_edahab) ?> ||
      Zaad: <?= htmlspecialchars($clinic_zaad) ?>
    </h5>
    <hr>

    <p><strong>Voucher ID:</strong> #<?= $voucher['id'] ?></p>
    <p><strong>Patient Name:</strong> <?= htmlspecialchars($voucher['patient_name']) ?></p>
    <p><strong>Service:</strong> <?= htmlspecialchars($voucher['service']) ?></p>
    <p><strong>Service Total:</strong> SLSH <?= number_format($voucher['service_total'], 2) ?></p>
    <p><strong>Previous Balance:</strong> SLSH <?= number_format($voucher['previous_balance'], 2) ?></p>
    <p><strong>Amount Paid:</strong> SLSH <?= number_format($voucher['amount_paid'], 2) ?></p>
    <p><strong>Remaining Balance:</strong> SLSH <?= number_format($voucher['balance'], 2) ?></p>
    <p><strong>Date:</strong> <?= date('d M Y - H:i', strtotime($voucher['date_paid'])) ?></p>

    <?php if ($previous_vouchers->num_rows > 0): ?>
      <hr>
      <h5>Previous Vouchers</h5>
      <table class="table table-bordered table-sm">
        <thead>
          <tr>
            <th>#ID</th>
            <th>Service</th>
            <th>Service Total</th>
            <th>Amount Paid</th>
            <th>Remaining Balance</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php while($prev = $previous_vouchers->fetch_assoc()): ?>
            <tr>
              <td>#<?= $prev['id'] ?></td>
              <td><?= htmlspecialchars($prev['service']) ?></td>
              <td><?= number_format($prev['service_total'], 2) ?></td>
              <td><?= number_format($prev['amount_paid'], 2) ?></td>
              <td><?= number_format($prev['balance'], 2) ?></td>
              <td><?= date('d M Y', strtotime($prev['date_paid'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <hr>
    <p>Signature: ____________________________</p>
  </div>

  <div class="text-center no-print mt-4">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
    <a href="generate_voucher.php" class="btn btn-secondary">Back</a>
  </div>
  <?php include 'includes/footer.php'; ?>
</body>
</html>
