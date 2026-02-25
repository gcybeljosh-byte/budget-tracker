<?php
require 'includes/db.php';
require 'includes/BalanceHelper.php';

$user_id = 1; // Assuming demo user
$bh = new BalanceHelper($conn);

echo "Testing for User ID: $user_id\n";

$sources = ['Cash', 'GCash', 'Maya', 'Bank', 'Electronic'];
foreach ($sources as $s) {
    echo "\nSource: $s\n";
    $details = $bh->getBalanceDetails($user_id, 'Allowance', $s);
    echo "  Allowance Balance: " . $details['balance'] . " (A:{$details['allowance_sum']}, E:{$details['expense_sum']}, S:{$details['savings_sum']})\n";

    $sDetails = $bh->getBalanceDetails($user_id, 'Savings', $s);
    echo "  Savings Balance  : " . $sDetails['balance'] . " (S:{$sDetails['allowance_sum']}, E:{$sDetails['expense_sum']})\n";
}
