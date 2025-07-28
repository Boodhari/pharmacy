<?php
session_start();
// if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
//     header("Location: login.php");
//     exit;
// }

include 'config/db.php';
$success = '';
$error = '';
$logo_filename = null;

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/logos/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $logo_filename = uniqid('clinic_', true) . '.' . $ext;
    move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_filename);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $start = $_POST['subscription_start'];
    $end = $_POST['subscription_end'];
    $status = $_POST['status'];

    //$admin_username = trim($_POST['admin_username']);
   // $admin_password = md5($_POST['admin_password']); // You can improve this with password_hash
    $username = trim($_POST['username']);
    $password = md5($_POST['password']); // For better security use password_hash()
    $role = $_POST['role'];
    // Insert into clinics table
    $stmt = $conn->prepare("INSERT INTO clinics (name, email, phone, address, subscription_start, subscription_end, status, logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $phone, $address, $start, $end, $status , $logo_filename);

    if ($stmt->execute()) {
        $clinic_id = $stmt->insert_id;

        // Create clinic_admin user
       // $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, 'clinic_admin', ?)");
       // $stmt2->bind_param("ssi", $admin_username, $admin_password, $clinic_id);
 // test 2
        $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("sssi", $username, $password, $role, $clinic_id);
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
  <title>Create New Clinic</title>
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
  <h2 class="mb-4">🏥 Register New Clinic</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="row g-3">
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
    <label>Clinic Logo</label>
    <input type="file" name="logo" class="form-control" accept="image/*">
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
      <label>User Role</label>
      <select name="role" class="form-control" required>
        <option value="">-- Choose Role --</option>
        <option value="pharmacy">Pharmacy</option>
        <option value="doctor">Doctor</option>
        <option value="clinic_admin">Clinic Admin</option>
      </select>
    </div>

    <div class="col-md-6">
      <label>Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>

    <div class="col-md-6">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Create Clinic</button>
      <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
  <?php include 'includes/footer.php'; ?>
</body>
</html>
