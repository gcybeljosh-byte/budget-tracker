<?php
require_once 'includes/db.php';
require_once 'includes/BalanceHelper.php';

$user_id = 1; // User ID for cybel
$delta = 352;
$date = date('Y-m-d');

// 1. Reduce Cash by 352 (Expense)
$stmt1 = $conn->prepare("INSERT INTO expenses (user_id, date, amount, description, source_type, category, expense_source) VALUES (?, ?, ?, 'Balance Adjustment (Cash to Digital)', 'Cash', 'Adjustment', 'Allowance')");
$stmt1->bind_param("isd", $user_id, $date, $delta);
$stmt1->execute();
$stmt1->close();

// 2. Increase Digital by 352 (Allowance)
$stmt2 = $conn->prepare("INSERT INTO allowances (user_id, amount, date, description, source_type) VALUES (?, ?, ?, 'Balance Adjustment (Cash to Digital)', 'GCash')");
$stmt2->bind_param("ids", $user_id, $delta, $date);
$stmt2->execute();
$stmt2->close();

echo "Adjustments applied successfully.\n";

$bh = new BalanceHelper($conn);
echo "New Cash: " . $bh->getCashBalance($user_id) . "\n";
echo "New Digital: " . $bh->getDigitalBalance($user_id) . "\n";
