<?php
include 'config/db.php';
include('includes/header1.php');
$prescriptions = $conn->query("SELECT * FROM prescriptions ORDER BY date_prescribed DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>View Prescriptions</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .prescription-card {
      padding: 15px;
      border: 1px solid #0d6efd;
      margin-bottom: 30px;
      background: #fff;
      font-size: 14px;
      line-height: 1.4;
    }
    .clinic-header {
      border-bottom: 1px dashed #0d6efd;
      margin-bottom: 15px;
      padding-bottom: 5px;
    }
    .clinic-logo {
  width: 40px;
  max-width: 100%;
  height: auto;
  display: block;
  margin: 0 auto;
}

@media print {
  .clinic-logo {
    width: 40px !important;
    max-width: 40px;
    height: auto !important;
  }
}
    .clinic-info h2 {
      color: #0d6efd;
      margin: 0;
      font-weight: 700;
      font-size: 1.2rem;
    }
    .info-label {
      font-weight: bold;
    }
    
      body {
        background: #fff !important;
      }
    }
  </style>
  <script>
    function printPrescription(id) {
      const content = document.getElementById("prescription-" + id).innerHTML;
      const frame = window.open('', '', 'width=800,height=600');
      frame.document.write('<html><head><title>Print</title>');
      frame.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
      frame.document.write('<style>.no-print{display:none;} body{background:#fff;font-size:13px;}</style>');
      frame.document.write('</head><body onload="window.print(); window.close();">');
      frame.document.write(content);
      frame.document.write('</body></html>');
      frame.document.close();
    }
  </script>
</head>

<body class="container py-5 bg-light">
  <h2 class="mb-4">🧾 Prescriptions List</h2>

  <?php while ($row = $prescriptions->fetch_assoc()): ?>
    <div class="prescription-card" id="prescription-<?= $row['id'] ?>">

      <!-- Header: Logo Left, Info Right -->
     <div class="clinic-header text-center">
  <img src="./Logo.png" alt="Clinic Logo" class="clinic-logo mb-2"> <!-- Replace path -->
  <h2 class="mb-1 text-primary">SMART DENTAL CLINIC</h2>
  <p class="mb-0 fs-6 fw-bold">Tel: 063-4756610 /  063-4207474 /  518489</p>
  <p class="mb-0 fw-semibold fs-6">26 june , Indho-birta Mall ,Hargeisa, Somaliland</p>
  <h6 class="fw-bold fs-5 mt-2 text-success">Medical Prescription</h6>
</div>

      <!-- Responsive Patient Info Table -->
      <div class="table-responsive mb-3">
        <table class="table table-sm table-borderless">
          <tbody>
            <tr>
              <th scope="row" style="width: 100px;">Name</th>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <th scope="row" style="width: 100px;">Sex</th>
              <td><?= htmlspecialchars($row['patient_sex']) ?></td>
            </tr>
            <tr>
              <th scope="row">Weight</th>
              <td><?= htmlspecialchars($row['patient_weight']) ?> kg</td>
              <th scope="row">Date</th>
              <td><?= date('d-m-Y', strtotime($row['date_prescribed'])) ?></td>
            </tr>
            <tr>
              <th scope="row">Doctor</th>
              <td colspan="3">Dr. <?= htmlspecialchars($row['doctor_name']) ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Medication Instructions -->
      <div class="p-3 border bg-light mb-3">
        <pre class="mb-0"><?= htmlspecialchars($row['medications']) ?></pre>
      </div>
    </div>

    <!-- Print Button -->
    <div class="text-end no-print mb-4">
      <button onclick="printPrescription(<?= $row['id'] ?>)" class="btn btn-outline-primary btn-sm">🖨️ Print</button>
    </div>
  <?php endwhile; ?>

</body>
</html>
