<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}
include 'config/db.php';

$id = intval($_GET['id']);
$success = '';
$error = '';

$user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
$clinics = $conn->query("SELECT id, name FROM clinics");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $role = $_POST['role'];
    $clinic_id = $_POST['clinic_id'];
    $password = !empty($_POST['password']) ? md5($_POST['password']) : $user['password'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, clinic_id = ? WHERE id = ?");
    $stmt->bind_param("sssii", $username, $password, $role, $clinic_id, $id);
    if ($stmt->execute()) {
        $success = "✅ User updated!";
        $user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc(); // refresh
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Edit User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container py-5">
  <h2>Edit User</h2>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-6">
      <label>Username</label>
      <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>
    <div class="col-md-6">
      <label>Change Password (leave blank to keep current)</label>
      <input type="password" name="password" class="form-control">
    </div>
    <div class="col-md-6">
      <label>Role</label>
      <select name="role" class="form-control">
        <option value="clinic_admin" <?= $user['role'] === 'clinic_admin' ? 'selected' : '' ?>>Clinic Admin</option>
        <option value="pharmacy" <?= $user['role'] === 'pharmacy' ? 'selected' : '' ?>>Pharmacy</option>
        <option value="doctor" <?= $user['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option>
      </select>
    </div>
    <div class="col-md-6">
      <label>Clinic</label>
      <select name="clinic_id" class="form-control">
        <?php while ($clinic = $clinics->fetch_assoc()): ?>
          <option value="<?= $clinic['id'] ?>" <?= $clinic['id'] == $user['clinic_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($clinic['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Update</button>
      <a href="manage_users.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</body>
</html>
