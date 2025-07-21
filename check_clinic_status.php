<?php
// check_clinic_status.php
if ($_SESSION['role'] !== 'super_admin') {
    $clinic_id = $_SESSION['clinic_id'];

    $stmt = $conn->prepare("SELECT status FROM clinics WHERE id = ?");
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $clinic = $result->fetch_assoc();

    if (!$clinic || $clinic['status'] !== 'active') {
        // Destroy session and redirect to login with message
        session_destroy();
        header("Location: login.php?deactivated=1");
        exit;
    }
}
