<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['clinic_id'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
$clinic_id = $_SESSION['clinic_id'];

$id = $_GET['id'] ?? 0;

// Ensure product belongs to this clinic
$stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND clinic_id = ?");
$stmt->bind_param("ii", $id, $clinic_id);
$stmt->execute();

header("Location: products.php");
exit;
