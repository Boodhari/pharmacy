<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}
include 'config/db.php';

$id = intval($_GET['id']);
$conn->query("DELETE FROM clinics WHERE id = $id");
header("Location: admin_dashboard.php");
exit;