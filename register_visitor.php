<?php
include 'config/db.php';
include('includes/header.php');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $purpose = trim($_POST['purpose']);
    $paid = trim($_POST['paid']);

    $stmt = $conn->prepare("INSERT INTO visitors (full_name, phone, purpose,paid) VALUES (?, ?, ?,?)");
    $stmt->bind_param("ssss", $full_name, $phone, $purpose,$paid);

    if ($stmt->execute()) {
        $message = "✅ Visitor registered successfully.";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Visitor - Pharmacy POS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h2 class="mb-4">📝 Register Daily Visitor</h2>

    <?php if ($message): ?>
      <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3">
      <div class="col-md-6">
        <label for="full_name" class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" id="full_name" required>
      </div>
      <div class="col-md-6">
        <label for="phone" class="form-label">Phone Number (optional)</label>
        <input type="text" name="phone" class="form-control" id="phone">
      </div>
      <div class="col-8">
        <label for="purpose" class="form-label">Purpose of Visit</label>
        <input type="text" name="purpose" class="form-control" id="purpose" required>
      </div>
       <div class="col-4">
        <label for="paid" class="form-label">Paid</label>
        <input type="text" name="paid" class="form-control" id="paid" required>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary">Register Visitor</button>
        <a href="visitor_status.php" class="btn btn-secondary">Check Status</a>
      </div>
    </form>
  </div>
</body>
</html>
