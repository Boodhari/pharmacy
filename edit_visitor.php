<?php
include 'config/db.php';
include('includes/header.php');
$id = $_GET['id'] ?? 0;
$success = false;

// Fetch existing visitor
$stmt = $conn->prepare("SELECT * FROM visitors WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$visitor = $result->fetch_assoc();

if (!$visitor) {
    die("Visitor not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $purpose = $_POST['purpose'];
    $paid = $_POST['paid'];
    $status = $_POST['status'];

    $update = $conn->prepare("UPDATE visitors SET full_name=?, purpose=?,paid=?, status=? WHERE id=?");
    $update->bind_param("ssssi", $full_name, $purpose,$paid, $status, $id);
    $update->execute();
    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Visitor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h2 class="mb-4">✏️ Edit Visitor Status</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">Visitor updated successfully. <a href="live_visitor_status.php">Back to list</a></div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label>Full Name</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($visitor['full_name']) ?>" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Purpose</label>
      <input type="text" name="purpose" value="<?= htmlspecialchars($visitor['purpose']) ?>" class="form-control" required>
    </div>
     <div class="col-md-6">
      <label>Paid</label>
      <input type="text" name="paid" value="<?= htmlspecialchars($visitor['Paid']) ?>" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label>Status</label>
      <select name="status" class="form-control" required>
        <option value="waiting" <?= $visitor['status'] === 'waiting' ? 'selected' : '' ?>>Waiting</option>
        <option value="in_doctor" <?= $visitor['status'] === 'in_doctor' ? 'selected' : '' ?>>In Doctor</option>
        <option value="done" <?= $visitor['status'] === 'done' ? 'selected' : '' ?>>Done</option>
      </select>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Update</button>
      <a href="live_visitor_status.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</body>
</html>
