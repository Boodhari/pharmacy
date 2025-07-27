<?php
session_start();
include 'config/db.php';
include('includes/header1.php');
include 'send_whatsapp.php';

$tomorrow = date('Y-m-d', strtotime('+1 day'));
$clinic_id = $_SESSION['clinic_id'];
$stmt = $conn->prepare("SELECT patient_name, phone, appointment_date FROM appointments WHERE clinic_id=". intval($_SESSION['clinic_id'])." AND appointment_date = ?");
$stmt->bind_param("s", $tomorrow);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $phone = preg_replace('/[^0-9]/', '', $row['phone']); // clean phone number
    if (strlen($phone) < 9) continue; // skip invalid numbers

    if (strpos($phone, "252") !== 0) {
        $phone = "252" . ltrim($phone, "0"); // add country code if missing
    }

    $message = "🦷 Xasuusin: Mudane / Marwo " . $row['patient_name'] . ",Ogow Beri waxad leedahay balantii dhakhtarka ee kaalay " . $row['appointment_date'] . ". - Modern Dental Clinic";

    sendWhatsApp($phone, $message);
}
?>