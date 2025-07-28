<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
$success = '';
$error = '';

// Fetch clinics for the dropdown
$clinics = $conn->query("SELECT id, name FROM clinics ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_id = $_POST['clinic_id'];
    $username = trim($_POST['username']);
    $password = md5($_POST['password']); // For better security use password_hash()
    $role = $_POST['role'];

    // Check if user already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "❌ Username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, clinic_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $password, $role, $clinic_id);

        if ($stmt->execute()) {
            $success = "✅ User registered successfully!";
        } else {
            $error = "❌ Failed to register user: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Register User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">👤 Register New User</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label>Select Clinic</label>
      <select name="clinic_id" class="form-control" required>
        <option value="">-- Choose Clinic --</option>
        <?php while($clinic = $clinics->fetch_assoc()): ?>
          <option value="<?= $clinic['id'] ?>"><?= htmlspecialchars($clinic['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

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
      <button type="submit" class="btn btn-primary">Register User</button>
      <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
  <?php include 'includes/footer.php'; ?>
</body>
</html>
