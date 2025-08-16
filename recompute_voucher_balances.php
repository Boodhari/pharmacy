<?php
require 'config/db.php';
session_start();

$clinic_id = $_SESSION['clinic_id'] ?? 0;

$sqlPairs = $conn->prepare("
  SELECT DISTINCT clinic_id, visitor_id
  FROM vouchers
  WHERE (? = 0 OR clinic_id = ?)
");
$sqlPairs->bind_param("ii", $clinic_id, $clinic_id);
$sqlPairs->execute();
$pairs = $sqlPairs->get_result();

$updated = 0;

while ($pair = $pairs->fetch_assoc()) {
  $c = (int)$pair['clinic_id'];
  $v = (int)$pair['visitor_id'];

  $q = $conn->prepare("
    SELECT id, service_total, amount_paid, date_paid
    FROM vouchers
    WHERE clinic_id = ? AND visitor_id = ?
    ORDER BY date_paid ASC, id ASC
  ");
  $q->bind_param("ii", $c, $v);
  $q->execute();
  $rows = $q->get_result();

  $running = 0.0;
  $prev_balance = 0.0;

  while ($row = $rows->fetch_assoc()) {
    $running += (float)$row['service_total'] - (float)$row['amount_paid'];
    if ($running < 0) $running = 0.0;

    $upd = $conn->prepare("UPDATE vouchers SET previous_balance = ?, balance = ? WHERE id = ?");
    $upd->bind_param("ddi", $prev_balance, $running, $row['id']);
    $upd->execute();

    $prev_balance = $running;
    $updated++;
  }
}

echo "Recalculated $updated voucher rows.\n";
