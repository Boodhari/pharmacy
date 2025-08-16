<?php
include 'config/db.php';
include 'auth_check.php';
include('includes/header.php');
$id = $_GET['id'] ?? 0;
$clinic_name = 'Clinic Management System'; // Default name
$clinic_logo = null;
if (isset($_SESSION['clinic_id'])) {
    $stmt = $conn->prepare("SELECT name , Address ,edahab_phone,zaad_phone, logo FROM clinics WHERE id = ?");
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


$stmt = $conn->prepare("SELECT * FROM vouchers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$voucher = $result->fetch_assoc();

if (!$voucher) {
    die("Voucher not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Print Voucher</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .voucher {
      width: 600px;
      margin: 40px auto;
      padding: 30px;
      border: 2px dashed #000;
      background: #fff;
    }
    @media print {
      .no-print { display: none; }
      body { background: #fff; }
    }
  </style>
</head>
<body>
  <div class="voucher">
   <h4 class="text-center text-primary mb-4"> <?= htmlspecialchars($clinic_name) ?> Dental Clinic</h4>
   <h5 class="text-center">Location : <?= htmlspecialchars($clinic_address) ?> ||
   Edahab :<?= htmlspecialchars($clinic_edahab) ?> ||
   Zaad :<?= htmlspecialchars($clinic_zaad) ?> </h5>
    <hr>
    <p><strong>Voucher ID:</strong> #<?= $voucher['id'] ?></p>
    <p><strong>Patient Name:</strong> <?= htmlspecialchars($voucher['patient_name']) ?></p>
    <p><strong>Service:</strong> <?= htmlspecialchars($voucher['service']) ?></p>
    <p><strong>Amount Paid:</strong> USD/SLSH <?= number_format($voucher['amount_paid'], 2) ?></p>
    <p><strong>Date:</strong> <?= date('d M Y - H:i', strtotime($voucher['date_paid'])) ?></p>
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
