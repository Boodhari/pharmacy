<?php
include 'config/db.php';
include('includes/header1.php');

$result = $conn->query("SELECT * FROM visitors ORDER BY visit_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Live Visitor Status Monitor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script>
    // Auto refresh page every 10 seconds
    setTimeout(() => {
      window.location.reload();
    }, 10000);
  </script>
</head>
<body class="container py-5">
  <h2 class="mb-4">🔴 Live Visitor Status Monitor</h2>
  <p><small>Page auto-refreshes every 10 seconds</small></p>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Full Name</th>
        <th>Purpose</th>
        <th>Paid</th>
        <th>Visit Time</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['purpose']) ?></td>
            <td><?= htmlspecialchars($row['Paid']) ?></td>
            <td><?= date('d M Y - H:i', strtotime($row['visit_date'])) ?></td>
            <td>
              <?php
                $statusColors = [
                  'waiting' => 'warning',
                  'in_doctor' => 'info',
                  'done' => 'success'
                ];
                $status = $row['status'];
                $color = $statusColors[$status] ?? 'secondary';
              ?>
              <span class="badge bg-<?= $color ?>"><?= $status ?></span>
              
            </td>
            
                
              
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" class="text-center">No visitors found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
