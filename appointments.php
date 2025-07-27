<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header1.php');
$clinic_id = $_SESSION['clinic_id'];
$today = date('Y-m-d');
$visitors = $conn->query("SELECT id, full_name, phone FROM visitors where clinic_id= ". intval($_SESSION['clinic_id']) . " AND visit_date='$today' ORDER BY visit_date DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_id = $_POST['visitor_id'];
    $date = $_POST['appointment_date'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("SELECT full_name, phone FROM visitors WHERE id = ? AND clinic_id = ?");
    $stmt->bind_param("ii", $visitor_id , $clinic_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $visitor = $res->fetch_assoc();

    $insert = $conn->prepare("INSERT INTO appointments (clinit_id, visitor_id, patient_name, phone, appointment_date, reason) VALUES (?, ?, ?, ?, ? , ?)");
    $insert->bind_param("iissss",$clinic_id, $visitor_id, $visitor['full_name'], $visitor['phone'], $date, $reason);
    $insert->execute();

    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Schedule Appointment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">📅 Schedule Appointment</h2>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success">Appointment scheduled successfully!</div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label>Patient</label>
      <select name="visitor_id" class="form-select" required>
        <option value="">-- Select Patient --</option>
        <?php while ($v = $visitors->fetch_assoc()): ?>
          <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['full_name']) ?> (<?= $v['phone'] ?>)</option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label>Appointment Date</label>
      <input type="date" name="appointment_date" class="form-control" required>
    </div>
    <div class="col-12">
      <label>Reason</label>
      <textarea name="reason" class="form-control" rows="3"></textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-success">Save Appointment</button>
      <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</body>
</html>