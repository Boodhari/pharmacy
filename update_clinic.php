<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

$clinic_id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Fetch clinic data
$stmt = $conn->prepare("SELECT * FROM clinics WHERE id = ?");
$stmt->bind_param("i", $clinic_id);
$stmt->execute();
$clinic = $stmt->get_result()->fetch_assoc();

if (!$clinic) {
    die("Clinic not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $start = $_POST['subscription_start'];
    $end = $_POST['subscription_end'];
    $status = $_POST['status'];

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/logos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo_filename = uniqid('clinic_', true) . '.' . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_filename);
    } else {
        $logo_filename = $clinic['logo']; // Keep old logo if not changed
    }

    // Update clinic info
    $stmt = $conn->prepare("UPDATE clinics SET name=?, email=?, phone=?, address=?, subscription_start=?, subscription_end=?, status=?, logo=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $name, $email, $phone, $address, $start, $end, $status, $logo_filename, $clinic_id);

    if ($stmt->execute()) {
        $success = "✅ Clinic updated successfully!";
        // Refresh clinic data
        $clinic = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'subscription_start' => $start,
            'subscription_end' => $end,
            'status' => $status,
            'logo' => $logo_filename
        ];
    } else {
        $error = "❌ Failed to update clinic: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Update Clinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
         <div class="container-fluid">
             <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
             <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                 data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                 aria-label="Toggle navigation">
                 <span class="navbar-toggler-icon"></span>
             </button>
             <div class="collapse navbar-collapse" id="navbarSupportedContent">
                 <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                     <li class="nav-item">
                         <a class="nav-link active" aria-current="page" href="#">Home</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="register_user.php">Register Users</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="create_clinic.php">New Clinics</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" href="Logout.php">Logout</a>
                     </li>
                 </ul>
             </div>
         </div>
     </nav>
  <h2 class="mb-4">🛠️ Update Clinic</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="row g-3">
    <div class="col-md-6">
      <label>Clinic Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($clinic['name']) ?>" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($clinic['email']) ?>" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Phone</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($clinic['phone']) ?>" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Address</label>
      <input type="text" name="address" value="<?= htmlspecialchars($clinic['address']) ?>" class="form-control">
    </div>
    <div class="col-md-6">
      <label>Subscription Start</label>
      <input type="date" name="subscription_start" value="<?= $clinic['subscription_start'] ?>" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Subscription End</label>
      <input type="date" name="subscription_end" value="<?= $clinic['subscription_end'] ?>" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label>Status</label>
      <select name="status" class="form-control">
        <option value="active" <?= $clinic['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $clinic['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-6">
      <label>Clinic Logo</label>
      <input type="file" name="logo" class="form-control">
      <?php if ($clinic['logo']): ?>
        <img src="uploads/logos/<?= htmlspecialchars($clinic['logo']) ?>" alt="Logo" height="60" class="mt-2">
      <?php endif; ?>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Update Clinic</button>
      <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</body>
</html>
