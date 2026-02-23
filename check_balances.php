<?php
require_once 'includes/db.php';
require_once 'includes/BalanceHelper.php';

$res = $conn->query('SELECT id, username FROM users');
while ($row = $res->fetch_assoc()) {
    $user_id = $row['id'];
    $bh = new BalanceHelper($conn);
    $cash = $bh->getCashBalance($user_id);
    $digital = $bh->getDigitalBalance($user_id);
    $savings = $bh->getTotalSavings($user_id);

    echo "User [ID: $user_id, Name: " . $row['username'] . "]:\n";
    echo "  Cash: $cash\n";
    echo "  Digital: $digital\n";
    echo "  Savings: $savings\n";
    echo "  Total: " . ($cash + $digital + $savings) . "\n\n";
}
