<?php
include 'config/db.php';
include('includes/header.php');
$result = $conn->query("SELECT * FROM history_taking ORDER BY date_taken DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Patient History Records</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h2 class="mb-4">📄 Patient History Records</h2>
  <div class="table-responsive">
    <table class="table table-bordered table-sm">
      <thead>
        <tr>
          <th>Date</th>
          <th>Patient</th>
          <th>Doctor</th>
          <th>Symptoms</th>
          <th>Services</th>
          <th>Total Price (SLSH)</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= date('d-m-Y H:i', strtotime($row['date_taken'])) ?></td>
          <td><?= htmlspecialchars($row['patient_name']) ?></td>
          <td><?= htmlspecialchars($row['doctor_name']) ?></td>
          <td><?= nl2br(htmlspecialchars($row['symptoms'])) ?></td>
          <td><?= nl2br(htmlspecialchars($row['services'])) ?></td>
          <td><?= number_format($row['total_price'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
