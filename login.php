<?php
session_start();
include 'config/db.php';

$error = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = md5($_POST['password']);

    // Join users with clinics to check clinic status
    $stmt = $conn->prepare("
        SELECT u.*, c.status AS clinic_status 
        FROM users u 
        LEFT JOIN clinics c ON u.clinic_id = c.id 
        WHERE u.username = ? AND u.password = ?
    ");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Block login if user is not super_admin and clinic is inactive or missing
        if ($row['role'] !== 'super_admin' && 
            ($row['clinic_id'] === null || $row['clinic_status'] !== 'active')) {
            $error = "Access Denied: Your clinic is deactivated. Please contact admin.";
        } else {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['clinic_id'] = $row['clinic_id'];

            // Redirect based on role
            if ($row['role'] == 'doctor') {
                header("Location: drdashboard.php");
            } elseif ($row['role'] == 'super_admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    } else {
        $error = "Invalid username or password.";
    }
}
 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Pharmacy POS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body, html { height: 100%; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center bg-light" style="min-height: 100vh;">
  <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
    <h4 class="mb-4 text-center">🔐 Login to Pharmacy POS</h4>

    <?php if ($error): ?>
      <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
    
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">Login</button>
        <a href="index.html" class="btn btn-secondary">Back</a>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
