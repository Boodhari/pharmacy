<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/db.php';
include __DIR__ .'/../auth_check.php';
$clinic_name = 'Pharmacy POS'; // Default name
$clinic_logo = null;
if (isset($_SESSION['clinic_id'])) {
    $stmt = $conn->prepare("SELECT name , logo FROM clinics WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['clinic_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $clinic_name = $row['name'];
        $clinic_logo = $row['logo'] ?? null;
    }
    $stmt->close();
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    $clinic_name = "Super Admin Panel";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($clinic_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary px-3">
  <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
  <?php if ($clinic_logo): ?>
    <img src="uploads/logos/<?= htmlspecialchars($clinic_logo) ?>" alt="Logo" height="40" class="me-2">
  <?php endif; ?>
  <?= htmlspecialchars($clinic_name) ?>
</a>
  <div class="ms-auto">
    <a class="btn btn-light" href="logout.php">Logout</a>
  </div>
</nav>
<div class="container py-4">
