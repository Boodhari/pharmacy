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

    // Get doctor user info
    $doctor_username = trim($_POST['doctor_username']);
    $doctor_password = md5($_POST['doctor_password']); // For better security use password_hash()
    $doctor_role = $_POST['doctor_role'];

    // Get pharmacy user info
    $pharmacy_username = trim($_POST['pharmacy_username']);
    $pharmacy_password = md5($_POST['pharmacy_password']); // For better security use password_hash()
    $pharmacy_role = $_POST['pharmacy_role'];

    // Insert into clinics table
    $stmt = $conn->prepare("INSERT INTO clinics (name, email, phone, address, subscription_start, subscription_end, status, logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $phone, $address, $start, $end, $status , $logo_filename);

    if ($stmt->execute()) {
        $clinic_id = $stmt->insert_id;

        // Insert doctor user
        $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("sssi", $doctor_username, $doctor_password, $doctor_role, $clinic_id);
        $doctor_result = $stmt2->execute();

        // Insert pharmacy user
        $stmt3 = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("sssi", $pharmacy_username, $pharmacy_password, $pharmacy_role, $clinic_id);
        $pharmacy_result = $stmt3->execute();

        if ($doctor_result && $pharmacy_result) {
            $success = "✅ Clinic and both users created successfully!";
        } else {
            $error = "❌ Failed to create users: " . $stmt2->error . " " . $stmt3->error;
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
                         <a class="nav-link" href="logout.php">Logout</a>
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
    <h5>🔐 Clinic Doctor user</h5>
    <div class="col-md-6">
      <label>User Role</label>
      <select name="doctor_role" class="form-control" required>
        <option value="">-- Choose Doctor Role --</option>
        <option value="doctor">Doctor</option>
      </select>
    </div>

    <div class="col-md-6">
      <label>Username</label>
      <input type="text" name="doctor_username" class="form-control" required>
    </div>

    <div class="col-md-6">
      <label>Password</label>
      <input type="password" name="doctor_password" class="form-control" required>
    </div>
    <hr>
    <h5 class="mt-4">🔐 Clinic Pharmacy user</h5>
     
    <div class="col-md-6">
      <label>User Role</label>
      <select name="pharmacy_role" class="form-control" required>
        <option value="">-- Choose Pharmacy Role --</option>
        <option value="pharmacy">Pharmacy</option>
      </select>
    </div>

    <div class="col-md-6">
      <label>Username</label>
      <input type="text" name="pharmacy_username" class="form-control" required>
    </div>

    <div class="col-md-6">
      <label>Password</label>
      <input type="password" name="pharmacy_password" class="form-control" required>
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
