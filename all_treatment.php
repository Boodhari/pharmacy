<?php
session_start();
include 'config/db.php';
include('includes/header1.php');

$history_id = intval($_GET['history_id'] ?? 0);

// Get patient info
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

// Get all treatment plans
$stmt = $conn->prepare("
    SELECT tp.*, u.full_name as doctor_name
    FROM treatment_plans tp
    JOIN users u ON tp.doctor_id = u.id
    WHERE tp.history_id = ? AND tp.clinic_id = ?
    ORDER BY tp.created_at DESC
");
$stmt->bind_param("ii", $history_id, $_SESSION['clinic_id']);
$stmt->execute();
$plans = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Treatment Plans - <?= htmlspecialchars($history['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Treatment Plans for <?= htmlspecialchars($history['full_name']) ?></h2>
            <a href="create.php?history_id=<?= $history_id ?>" class="btn btn-primary">
                ➕ Add New Plan
            </a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Treatment plan saved successfully!</div>
        <?php endif; ?>
        
        <div class="list-group">
            <?php while ($plan = $plans->fetch_assoc()): ?>
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">Plan by Dr. <?= htmlspecialchars($plan['doctor_name']) ?></h5>
                        <small><?= date('M d, Y', strtotime($plan['created_at'])) ?></small>
                    </div>
                    <p class="mb-1"><?= nl2br(htmlspecialchars(substr($plan['treatment_details'], 0, 100))) ?>...</p>
                    <?php if ($plan['followup_date']): ?>
                        <small>Follow-up: <?= date('M d, Y', strtotime($plan['followup_date'])) ?></small>
                    <?php endif; ?>
                    <div class="mt-2">
                        <a href="details.php?id=<?= $plan['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <?php if ($plans->num_rows === 0): ?>
                <div class="alert alert-info">No treatment plans found for this patient.</div>
            <?php endif; ?>
        </div>
        
        <div class="mt-3">
            <a href="../patients/history.php?id=<?= $history['visitor_id'] ?>" class="btn btn-secondary">
                ← Back to Patient History
            </a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>