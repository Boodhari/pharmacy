<?php
include 'config/db.php';
include('includes/header.php');
$prescriptions = $conn->query("SELECT * FROM prescriptions ORDER BY date_prescribed DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>View Prescriptions</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script>
    function printPrescription(id) {
      var content = document.getElementById("prescription-" + id).innerHTML;
      var frame = window.open('', '', 'height=600,width=800');
      frame.document.write('<html><head><title>Print</title></head><body>');
      frame.document.write(content);
      frame.document.write('</body></html>');
      frame.document.close();
      frame.print();
    }
  </script>
</head>
<body class="container py-5">
  <h2 class="mb-4">💊 View Prescriptions</h2>

  <?php while ($row = $prescriptions->fetch_assoc()): ?>
    <div class="card mb-4">
      <div class="card-body">
        <div id="prescription-<?= $row['id'] ?>">
          <h5>Patient: <?= htmlspecialchars($row['patient_name']) ?></h5>
          <p><strong>Doctor:</strong> <?= htmlspecialchars($row['doctor_name']) ?></p>
          <p><strong>Date:</strong> <?= $row['date_prescribed'] ?></p>
          <hr>
          <pre><?= htmlspecialchars($row['medications']) ?></pre>
        </div>
        <button onclick="printPrescription(<?= $row['id'] ?>)" class="btn btn-sm btn-outline-primary mt-2">🖨️ Print</button>
      </div>
    </div>
  <?php endwhile; ?>
</body>
</html>
