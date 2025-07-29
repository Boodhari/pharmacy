<?php
// PHPMailer integration
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';
require 'includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include 'config/db.php';

// Get the clinic ID from the URL
$id = intval($_GET['id']);

// Fetch current status and email
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
$new_status = ($current_status === 'active') ? 'inactive' : 'active';

// Update clinic status
$update = $conn->prepare("UPDATE clinics SET status = ? WHERE id = ?");
$update->bind_param("si", $new_status, $id);
$update->execute();

// Prepare PHPMailer
$mail = new PHPMailer(true);

try {
     $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'AbadirHassan10@gmail.com';      // ✅ Your Gmail
    $mail->Password   = 'tbdyqbvssyskbweh';              // ✅ App password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('AbadirHassan10@gmail.com', 'Clinic SaaS System');
    $mail->addAddress($email, $clinic_name);

    $mail->isHTML(true);

    if ($new_status === 'active') {
        $mail->Subject = '✅ Your Clinic Account Has Been Activated';
        $mail->Body    = "
            <h3>Hello $clinic_name,</h3>
            <p>Your clinic account has been <strong>activated</strong>. You may now log in and use the system.
            </p>
            <a href='https://dentals-gcve.onrender.com/login.php' class='btn btn-primary'>Login Now</a>
            <p>Regards,<br>Admin Team</p>
        ";
    } else {
        $mail->Subject = '⚠️ Your Clinic Account Has Been Deactivated';
        $mail->Body    = "
            <h3>Hello $clinic_name,</h3>
            <p>Your clinic account has been <strong>deactivated</strong>. Please contact support for assistance.</p>
             
              <a href='https://wa.me/252634024452' class='text-white text-decoration-underline' target='_blank'>WhatsApp Chat</a>
             
           
         
        
            <p>Regards,<br>Admin Team</p>
        ";
    }

    $mail->send();

} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
    // Optional: Store $mail->ErrorInfo in a log or notify admin
}

// Redirect back to dashboard
header("Location: admin_dashboard.php");
exit;