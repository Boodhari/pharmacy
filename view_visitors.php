<?php
include 'config/db.php';
include('includes/header.php');

$search_date = $_GET['date'] ?? '';
$search_phone = $_GET['phone'] ?? '';

// Base query
$query = "SELECT * FROM visitors WHERE 1=1";
$params = [];
$types = "";

// Filter by date
if (!empty($search_date)) {
    $query .= " AND DATE(visit_date) = ?";
    $params[] = $search_date;
    $types .= "s";
}

// Filter by phone
if (!empty($search_phone)) {
    $query .= " AND phone LIKE ?";
    $params[] = "%" . $search_phone . "%";
    $types .= "s";
}

// Finalize query
$query .= " ORDER BY visit_date DESC";
$stmt = $conn->prepare($query);

// Bind parameters if needed
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>View Visitors</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2 class="mb-4">📋 Registered Visitors</h2>

  <form method="GET" class="row g-3 mb-4">
     <div class="col-md-4">
      <input type="text" placeholder="Search by Phone"
       name="phone" class="form-control" value="<?= htmlspecialchars($search_phone) ?>">
    </div>
    <div class="col-md-4">
      <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($search_date) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Search</button>
    </div>
    <div class="col-md-2">
      <a href="view_visitors.php" class="btn btn-secondary w-100">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Full Name</th>
          <th>Phone</th>
          <th>Purpose</th>
          <th>Paid</th>
          <th>Visit Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['full_name']) ?></td>
              <td><?= htmlspecialchars($row['phone']) ?></td>
              <td><?= htmlspecialchars($row['purpose']) ?></td>
              <td><?= htmlspecialchars($row['Paid']) ?></td>
              <td><?= date('d M Y - H:i', strtotime($row['visit_date'])) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center text-danger">No visitors found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
