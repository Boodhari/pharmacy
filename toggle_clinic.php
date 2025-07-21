<?php
include 'config/db.php';
$id = intval($_GET['id']);
$conn->query("UPDATE clinics SET status = IF(status='active', 'inactive', 'active') WHERE id = $id");
header("Location: admin_dashboard.php");
exit;
