<?php
require_once 'includes/db.php';
require_once 'includes/BalanceHelper.php';

$user_id = 1; // Assuming user 1
$bh = new BalanceHelper($conn);

$balance = $bh->getTotalBalance($user_id, false);
echo "Total Balance: " . $balance . PHP_EOL;

$stmt = $conn->prepare("SELECT SUM(amount) FROM recurring_payments 
                       WHERE user_id = ? AND is_active = 1 
                       AND (last_paid_at IS NULL OR DATE_FORMAT(last_paid_at, '%Y-%m') < DATE_FORMAT(NOW(), '%Y-%m'))
                       AND (due_date <= LAST_DAY(NOW()))");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unpaid = (float)$stmt->get_result()->fetch_row()[0];
echo "Unpaid Bills: " . $unpaid . PHP_EOL;

$current_day = (int)date('j');
$total_days = (int)date('t');
$remaining_days = $total_days - $current_day + 1;
echo "Remaining Days: " . $remaining_days . PHP_EOL;

$safe = ($remaining_days > 0) ? ($balance - $unpaid) / $remaining_days : 0;
echo "Safe-to-Spend: " . max(0, $safe) . PHP_EOL;
