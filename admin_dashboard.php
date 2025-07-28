<?php
session_start();
if ($_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
$result = $conn->query("SELECT * FROM clinics ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Super Admin - Clinic Management</title>
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
  <h2 class="mb-4">🏥 Super Admin Dashboard</h2>
  <a href="create_clinic.php" class="btn btn-success mb-3">+ Create New Clinic</a>
 
  <a href="register_user.php" class="btn btn-success mb-3">+ Create New User</a>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Clinic</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Subscription</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= $row['email'] ?></td>
          <td><?= $row['phone'] ?></td>
          <td><?= $row['subscription_start'] ?> → <?= $row['subscription_end'] ?></td>
          <td>
            <span class="badge bg-<?= $row['status'] == 'active' ? 'success' : 'danger' ?>">
              <?= ucfirst($row['status']) ?>
            </span>
          </td>
          <td>
            <a href="update_clinic.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            <a href="toggle_clinic.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
              <?= $row['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
