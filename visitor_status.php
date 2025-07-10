<?php
include 'config/db.php';
include('includes/header.php');
// Update status if requested
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];

    $stmt = $conn->prepare("UPDATE visitors SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}

// Fetch visitors
$result = $conn->query("SELECT * FROM visitors ORDER BY visit_date DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Visitor Status - Pharmacy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">📍 Visitor Status Control</h2>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Full Name</th>
        <th>Purpose</th>
        <th>Paid</th>
        <th>Visit Time</th>
        <th>Status</th>
        <th>Action</th>
        <th>Edit</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($row['full_name']) ?></td>
          <td><?= htmlspecialchars($row['purpose']) ?></td>
          <td><?= htmlspecialchars($row['Paid']) ?></td>
          <td><?= date('d M Y - H:i', strtotime($row['visit_date'])) ?></td>
          <td>
            <?php
              $badge = [
                'waiting' => 'warning',
                'in_doctor' => 'info',
                'done' => 'success'
              ];
              $status = $row['status'];
              echo "<span class='badge bg-{$badge[$status]}'>{$status}</span>";
            ?>
          </td>
          <td>
            <?php if ($status != 'in_doctor'): ?>
              <a href="?id=<?= $row['id'] ?>&status=in_doctor" class="btn btn-sm btn-info">Send to Doctor</a>
            <?php endif; ?>
            <?php if ($status != 'done'): ?>
              <a href="?id=<?= $row['id'] ?>&status=done" class="btn btn-sm btn-success">Mark Done</a>
            <?php endif; ?>
          </td>
          <td>
             <a href="edit_visitor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
