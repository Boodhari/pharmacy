<?php
include 'config/db.php';
include('includes/header1.php');
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient = $_POST['patient_name'];
    $doctor = $_POST['doctor_name'];
    $symptoms = $_POST['symptoms'];
    $services = $_POST['services'];
    $price = $_POST['total_price'];

    $stmt = $conn->prepare("INSERT INTO history_taking (patient_name, doctor_name, symptoms, services, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $patient, $doctor, $symptoms, $services, $price);
    $stmt->execute();
    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Take Patient History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h2 class="mb-3">🩺 Take Patient History</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">Patient history saved successfully.</div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label>Patient Name</label>
      <input type="text" name="patient_name" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Doctor Name</label>
      <input type="text" name="doctor_name" class="form-control" required>
    </div>
    <div class="col-12">
      <label>Symptoms / Complaints</label>
      <textarea name="symptoms" class="form-control" rows="3" required></textarea>
    </div>
    <div class="col-12">
      <label>Services to Provide</label>
      <textarea name="services" class="form-control" rows="2" required></textarea>
    </div>
    <div class="col-md-4">
      <label>Total Price (SLSH)</label>
      <input type="number" step="0.01" name="total_price" class="form-control" required>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Save History</button>
      <a href="drdashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</body>
</html>
