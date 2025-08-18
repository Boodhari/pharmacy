<?php
session_start();
include 'config/db.php';
include 'includes/auth_check.php';

// Initialize variables
$error = '';
$success = '';
$patientHistory = [];
$treatmentPlans = [];

// Check if we have a history_id parameter
$history_id = isset($_GET['history_id']) ? intval($_GET['history_id']) : 0;

if ($history_id > 0) {
    try {
        // Get patient history information (modified for your structure)
        $stmt = $conn->prepare("
            SELECT * FROM history_taking
            WHERE id = ? AND clinic_id = ?
        ");
        $stmt->bind_param("ii", $history_id, $_SESSION['clinic_id']);
        $stmt->execute();
        $patientHistory = $stmt->get_result()->fetch_assoc();

        if (!$patientHistory) {
            $error = "Patient history not found";
        } else {
            // Get all treatment plans for this history
            $stmt = $conn->prepare("
                SELECT tp.*, u.full_name as doctor_name
                FROM treatment_plans tp
                JOIN users u ON tp.doctor_id = u.id
                WHERE tp.history_id = ? AND tp.clinic_id = ?
                ORDER BY tp.created_at DESC
            ");
            $stmt->bind_param("ii", $history_id, $_SESSION['clinic_id']);
            $stmt->execute();
            $treatmentPlans = $stmt->get_result();
        }
    } catch (mysqli_sql_exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle form submission for new treatment plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_treatment'])) {
    $treatment_details = trim($_POST['treatment_details']);
    $followup_date = !empty($_POST['followup_date']) ? $_POST['followup_date'] : null;
    
    if (empty($treatment_details)) {
        $error = "Treatment details are required";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO treatment_plans 
                (history_id, clinic_id, doctor_id, treatment_details, followup_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiiss", 
                $history_id, 
                $_SESSION['clinic_id'], 
                $_SESSION['user_id'], 
                $treatment_details, 
                $followup_date
            );
            
            if ($stmt->execute()) {
                $success = "Treatment plan saved successfully";
                // Refresh the page to show the new plan
                header("Location: treatment_plan.php?history_id=$history_id&success=1");
                exit;
            } else {
                $error = "Failed to save treatment plan";
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treatment Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .treatment-card {
            border-left: 4px solid #0d6efd;
        }
        .followup-badge {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Treatment Plan Management</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="patients.php">Patients</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Treatment Plan</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Treatment plan saved successfully!</div>
        <?php endif; ?>

        <?php if ($history_id > 0 && $patientHistory): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Patient Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($patientHistory['patient_name']) ?></p>
                            <p><strong>Doctor:</strong> <?= htmlspecialchars($patientHistory['doctor_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Service:</strong> <?= htmlspecialchars($patientHistory['services']) ?></p>
                            <p><strong>Visit Date:</strong> <?= date('M d, Y', strtotime($patientHistory['date_taken'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Previous Treatment Plans</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($treatmentPlans && $treatmentPlans->num_rows > 0): ?>
                                <?php while ($plan = $treatmentPlans->fetch_assoc()): ?>
                                    <div class="card treatment-card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <h5>Plan by Dr. <?= htmlspecialchars($plan['doctor_name']) ?></h5>
                                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($plan['created_at'])) ?></small>
                                            </div>
                                            <p><?= nl2br(htmlspecialchars($plan['treatment_details'])) ?></p>
                                            <?php if ($plan['followup_date']): ?>
                                                <span class="badge followup-badge">
                                                    Follow-up: <?= date('M d, Y', strtotime($plan['followup_date'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="alert alert-info">No treatment plans found for this patient.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Add New Treatment Plan</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="treatment_details" class="form-label">Treatment Details</label>
                                    <textarea class="form-control" id="treatment_details" name="treatment_details" rows="6" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="followup_date" class="form-label">Follow-up Date (Optional)</label>
                                    <input type="date" class="form-control" id="followup_date" name="followup_date">
                                </div>
                                <button type="submit" name="submit_treatment" class="btn btn-primary w-100">Save Treatment Plan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                No patient history selected. Please select a patient history record first.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>