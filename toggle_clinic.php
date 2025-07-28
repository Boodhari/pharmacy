<?php
include 'config/db.php';

// Get the clinic ID from the URL
$id = intval($_GET['id']);

// Get current status and email
$stmt = $conn->prepare("SELECT status, email, name FROM clinics WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$clinic = $result->fetch_assoc();

if (!$clinic) {
    die("Clinic not found.");
}

$current_status = $clinic['status'];
$email = $clinic['email'];
$clinic_name = $clinic['name'];

// Toggle the status
$new_status = $current_status === 'active' ? 'inactive' : 'active';

$update = $conn->prepare("UPDATE clinics SET status = ? WHERE id = ?");
$update->bind_param("si", $new_status, $id);
$update->execute();

// Send email if status changed to active
if ($new_status === 'active') {
    $subject = "Your Clinic Account Has Been Activated";
    $message = "Dear $clinic_name,\n\nYour clinic account has been activated. You can now log in and start using the system.\n\nThank you,\nAdmin Team";
    $headers = "From: AbadirHassan10@gmail.com"; // Update with your admin email

    // Optional: Check if mail function is enabled and works
    if (!mail($email, $subject, $message, $headers)) {
        error_log("Failed to send activation email to $email");
    }
} else if($new_status === 'inactive') {
    $subject = "Your Clinic Account Has Been Deactivated";
    $message = "Dear $clinic_name,\n\nYour clinic account has been deactivated. Please contact support if you believe this is an error.\n\nThank you,\nAdmin Team";
    $headers = "From:AbadirHassan10@gmail.com";

header("Location: admin_dashboard.php");
exit;