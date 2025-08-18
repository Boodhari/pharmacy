<?php
session_start();
include 'config/db.php';
include('includes/header1.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $history_id = intval($_POST['history_id']);
    $treatment_details = trim($_POST['treatment_details']);
    $followup_date = $_POST['followup_date'] ?: null;
   $doctor_Name=$_POST['doctor_Name'] ?? 'Unknown Doctor';
    $clinic_id = $_SESSION['clinic_id'];

    // Validate
    if (empty($treatment_details)) {
        $error = "Treatment details are required";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO treatment_plans 
            (history_id, clinic_id, doctor_Name, treatment_details, followup_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisss", $history_id, $clinic_id, $doctor_Name, $treatment_details, $followup_date);
        
        if ($stmt->execute()) {
            $success = "Treatment plan saved successfully";
            header("Location: view.php?history_id=$history_id&success=1");
            exit;
        } else {
            $error = "Failed to save treatment plan";
        }
    }
}

// Get patient history
$history_id = intval($_GET['history_id'] ?? 0);
$stmt = $conn->prepare("
    SELECT h.*, v.full_name 
    FROM history_taking h
    JOIN visitors v ON h.visitor_id = v.id
    WHERE h.id = ? AND h.clinic_id = ?
");
$stmt->bind_param("ii", $history_id, $_SESSION['clinic_id']);
$stmt->execute();
$history = $stmt->get_result()->fetch_assoc();

if (!$history) {
    die("Patient history not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Treatment Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Create Treatment Plan for <?= htmlspecialchars($history['full_name']) ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="history_id" value="<?= $history_id ?>">
            
            <div class="mb-3">
                <label class="form-label">Treatment Details</label>
                <textarea name="treatment_details" class="form-control" rows="6" required></textarea>
            </div>
              <div class="mb-3">
                <label class="form-label">Doctor Name</label>
                <textarea name="doctor_Name" class="form-control" rows="6" required></textarea>
            </div>
            
            <div class="mb-3 col-md-4">
                <label class="form-label">Follow-up Date (Optional)</label>
                <input type="date" name="followup_date" class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary">Save Treatment Plan</button>
            <a href="view.php?history_id=<?= $history_id ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>