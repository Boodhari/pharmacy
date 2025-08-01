<?php
session_start();
include 'config/db.php';
include('includes/header1.php');
$success = false;
$clinic_id = $_SESSION['clinic_id'];

$visitors_result= $conn->query("SELECT id, full_name FROM visitors WHERE clinic_id=" . intval($_SESSION['clinic_id']) ." ORDER BY visit_date DESC");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient = $_POST['patient_name'];
    $doctor = $_POST['doctor_name'];
    $symptoms = $_POST['symptoms'];
    $services = $_POST['services'];
    $price = $_POST['total_price'];

    $stmt = $conn->prepare("INSERT INTO history_taking (clinic_id,patient_name, doctor_name, symptoms, services, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssd",$clinic_id, $patient, $doctor, $symptoms, $services, $price);
    $stmt->execute();
    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Take Patient History</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h2 class="mb-3">🩺 Take Patient History</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">✅ Patient history saved successfully.</div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Patient Name</label>
      <select name="patient_name" class="form-select" required>
        <option value="">-- Select Patient --</option>
        <?php while ($v = $visitors_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($v['full_name']) ?>"><?= htmlspecialchars($v['full_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Doctor Name</label>
      <input type="text" name="doctor_name" class="form-control" required>
    </div>
    <div class="col-12">
      <label class="form-label">Symptoms / Complaints</label>
      <textarea name="symptoms" class="form-control" rows="3" required></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Services to Provide</label>
      <textarea name="services" class="form-control" rows="2" required></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Total Price (USD)</label>
      <input type="number" step="0.01" name="total_price" class="form-control" required>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">💾 Save History</button>
      <a href="drdashboard.php" class="btn btn-secondary">⬅️ Back</a>
    </div>
  </form>
  <?php include 'includes/footer.php'; ?>
</body>
</html>