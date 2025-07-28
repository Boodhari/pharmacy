<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}
include 'config/db.php';
$users = $conn->query("SELECT users.*, clinics.name AS clinic_name FROM users LEFT JOIN clinics ON users.clinic_id = clinics.id ORDER BY users.id DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Users</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-5">
  <h2 class="mb-4">👥 Manage Users</h2>
  <a href="register_user.php" class="btn btn-success mb-3">➕ Add New User</a>
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>ID</th><th>Username</th><th>Role</th><th>Clinic</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $users->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td><?= $row['role'] ?></td>
          <td><?= htmlspecialchars($row['clinic_name'] ?? '—') ?></td>
          <td>
            <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this user?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
