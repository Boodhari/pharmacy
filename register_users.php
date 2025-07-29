<?php
session_start();
include 'config/db.php';

// PHPMailer integration
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';
require 'includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = '';
$error = '';
$logo_filename = null;

// Handle logo upload
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/logos/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $logo_filename = uniqid('clinic_', true) . '.' . $ext;
    move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_filename);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $start = $_POST['subscription_start'];
    $end = $_POST['subscription_end'];
    $status = $_POST['status'];

    $doctor_username = trim($_POST['doctor_username']);
    $doctor_password = md5($_POST['doctor_password']);
    $doctor_role = $_POST['doctor_role'];

    $pharmacy_username = trim($_POST['pharmacy_username']);
    $pharmacy_password = md5($_POST['pharmacy_password']);
    $pharmacy_role = $_POST['pharmacy_role'];

    $stmt = $conn->prepare("INSERT INTO clinics (name, email, phone, address, subscription_start, subscription_end, status, logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $phone, $address, $start, $end, $status, $logo_filename);

    if ($stmt->execute()) {
        $clinic_id = $stmt->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("sssi", $doctor_username, $doctor_password, $doctor_role, $clinic_id);
        $doctor_result = $stmt2->execute();

        $stmt3 = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("sssi", $pharmacy_username, $pharmacy_password, $pharmacy_role, $clinic_id);
        $pharmacy_result = $stmt3->execute();

        if ($doctor_result && $pharmacy_result) {
            // PHPMailer Notification
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'AbadirHassan10@gmail.com';        // ✅ Replace
                $mail->Password   = 'tbdyqbvssyskbweh';           // ✅ Replace
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('your-email@gmail.com', 'Clinic Registration');
                $mail->addAddress('AbadirHassan10@gmail.com', 'Admin');

                $mail->isHTML(true);
                $mail->Subject = "✅ New Clinic Registered: $name";
                $mail->Body    = "
                    <h3>New Clinic Registered</h3>
                    <b>Clinic:</b> $name <br>
                    <b>Email:</b> $email <br>
                    <b>Phone:</b> $phone <br>
                    <b>Doctor:</b> $doctor_username <br>
                    <b>Pharmacy:</b> $pharmacy_username <br>
                ";

                $mail->send();
                header("Location: login.php");
                exit;

            } catch (Exception $e) {
                $error = "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "❌ Failed to create users.";
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
        <option value="inactive">Inactive</option>
        <option value="active">Active</option>
      </select>
    </div>

    <hr>
    <h5>🔐 Clinic Doctor User</h5>
    <div class="col-md-6">
      <label>User Role</label>
      <select name="doctor_role" class="form-control" required>
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
    <h5 class="mt-4">🔐 Clinic Pharmacy User</h5>
    <div class="col-md-6">
      <label>User Role</label>
      <select name="pharmacy_role" class="form-control" required>
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

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Create Clinic</button>
      <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</body>
</html>
