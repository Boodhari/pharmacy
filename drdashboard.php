<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include('includes/header1.php');

// Error helper
function queryOrDie($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        die("SQL Error: " . $conn->error);
    }
    return $result;
}

// Total unique patients
$patients_result = queryOrDie($conn, "SELECT COUNT(DISTINCT patient_name) AS total_patients FROM prescriptions");
$total_patients = $patients_result->fetch_assoc()['total_patients'] ?? 0;

// Prescriptions today
$today = date('Y-m-d');
$today_prescriptions = queryOrDie($conn, "SELECT COUNT(*) AS today_total FROM prescriptions WHERE DATE(date_prescribed) = '$today'");
$prescriptions_today = $today_prescriptions->fetch_assoc()['today_total'] ?? 0;

// Filter logic
$filter_sql = "";
if (!empty($_GET['patient']) || !empty($_GET['date'])) {
    $patient = $conn->real_escape_string($_GET['patient'] ?? '');
    $date = $conn->real_escape_string($_GET['date'] ?? '');
    $filter_sql = "WHERE 1=1";
    if ($patient) {
        $filter_sql .= " AND patient_name LIKE '%$patient%'";
    }
    if ($date) {
        $filter_sql .= " AND DATE(date_prescribed) = '$date'";
    }
}

// Recent patients
$recent_result = queryOrDie($conn, "SELECT patient_name, doctor_name, date_prescribed FROM prescriptions $filter_sql ORDER BY date_prescribed DESC LIMIT 10");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Doctor Dashboard - Pharmacy POS</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h2 class="mb-4">Welcome Dr. <?= htmlspecialchars($_SESSION['username']) ?> 👨‍⚕️</h2>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm border-0 text-white bg-info">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-people"></i> Total Patients</h5>
          <p class="card-text fs-4"><?= $total_patients ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0 text-white bg-success">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-clipboard-check"></i> Today's Prescriptions</h5>
          <p class="card-text fs-4"><?= $prescriptions_today ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0 text-white bg-primary text-center">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-clock-history"></i> Date & Time</h5>
          <p class="card-text fs-5">
            <?= date('l, d M Y') ?><br>
            <span id="live-time"><?= date('H:i:s') ?></span>
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-journal-medical display-4 text-primary mb-3"></i>
          <h5 class="card-title">Write Prescription</h5>
          <p class="card-text">Create and save patient prescriptions.</p>
          <a href="write_prescription.php" class="btn btn-outline-primary w-100">Write Now</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-clipboard-data display-4 text-success mb-3"></i>
          <h5 class="card-title">View Prescriptions</h5>
          <p class="card-text">Review and print prescriptions given.</p>
          <a href="view_prescriptions1.php" class="btn btn-outline-success w-100">View Records</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-clipboard-data display-4 text-warning mb-3"></i>
          <h5 class="card-title">Visitor Status</h5>
          <p class="card-text">Track patient room visits.</p>
          <a href="live_visitor_status.php" class="btn btn-outline-warning w-100">View Visitors</a>
        </div>
      </div>
    </div>
     <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-person-vcard display-4 text-dark mb-3"></i>
          <h5 class="card-title">Take History</h5>
          <p class="card-text">Track patient History.</p>
          <a href="take_history.php" class="btn btn-outline-dark w-100">Take History</a>
        </div>
      </div>
    </div>
     <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi  bi-receipt display-4 text-secondary mb-3"></i>
          <h5 class="card-title">View Vouchersy</h5>
          <p class="card-text">Track Voucher History.</p>
          <a href="view_vouchers1.php" class="btn btn-outline-danger w-100">All Vouchers</a>
        </div>
      </div>
    </div>
     <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-receipt-cutoff display-4 text-danger mb-3"></i>
          <h5 class="card-title">Vouchers Report</h5>
          <p class="card-text">Track Voucher History.</p>
          <a href="voucher_report.php" class="btn btn-outline-warning w-100">All Vouchers</a>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-5">
    <h4 class="mb-3">🧾 Recent Prescriptions</h4>
    <form method="GET" class="row g-3 mb-3">
      <div class="col-md-4">
        <input type="text" name="patient" class="form-control" placeholder="Search by patient name" value="<?= htmlspecialchars($_GET['patient'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="drdashboard.php" class="btn btn-secondary">Reset</a>
      </div>
    </form>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Patient Name</th>
          <th>Doctor</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $recent_result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['patient_name']) ?></td>
          <td><?= htmlspecialchars($row['doctor_name']) ?></td>
          <td><?= date('d M Y H:i', strtotime($row['date_prescribed'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-5 text-end">
    <a href="logout.php" class="btn btn-outline-danger">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Live Clock
  setInterval(() => {
    const now = new Date();
    const time = now.toLocaleTimeString('en-GB');
    document.getElementById('live-time').textContent = time;
  }, 1000);
</script>
</body>
</html>
