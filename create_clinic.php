<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $start = $_POST['subscription_start'];
    $end = $_POST['subscription_end'];
    $status = $_POST['status'];

    $admin_username = trim($_POST['admin_username']);
    $admin_password = md5($_POST['admin_password']); // You can improve this with password_hash

    // Insert into clinics table
    $stmt = $conn->prepare("INSERT INTO clinics (name, email, phone, address, subscription_start, subscription_end, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $email, $phone, $address, $start, $end, $status);

    if ($stmt->execute()) {
        $clinic_id = $stmt->insert_id;

        // Create clinic_admin user
        $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, 'clinic_admin', ?)");
        $stmt2->bind_param("ssi", $admin_username, $admin_password, $clinic_id);

        if ($stmt2->execute()) {
            $success = "✅ Clinic and admin created successfully!";
        } else {
            $error = "❌ Failed to create admin: " . $stmt2->error;
        }
    } else {
        $error = "❌ Failed to create clinic: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Create Clinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">🏥 Register New Clinic</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label>Clinic Name</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Phone</label>
      <input type="text" name="phone" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Address</label>
      <input type="text" name="address" class="form-control">
    </div>
    <div class="col-md-6">
      <label>Subscription Start</label>
      <input type="date" name="subscription_start" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Subscription End</label>
      <input type="date" name="subscription_end" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Status</label>
      <select name="status" class="form-control">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <hr class="my-3">

    <h5>🔐 Clinic Admin Info</h5>
    <div class="col-md-6">
      <label>Admin Username</label>
      <input type="text" name="admin_username" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Admin Password</label>
      <input type="password" name="admin_password" class="form-control" required>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Create Clinic</button>
      <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</body>
</html>
