<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['clinic_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid sale ID.");
}

include 'config/db.php';
$id = intval($_GET['id']);
$clinic_id = $_SESSION['clinic_id'];
$stmt = $conn->prepare("DELETE FROM sales WHERE id = ? AND clinic_id = ?");
$stmt->bind_param("ii", $id, $clinic_id);
$stmt->execute();

header("Location: sales_report.php");
exit;