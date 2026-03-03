<?php
// Set manual user ID since we're in CLI
$user_id = 1; // Defaulting to 1, usually the first user in solo systems

require_once 'includes/db.php';
require_once 'includes/BalanceHelper.php';

$month_start = date('Y-m-01');

echo "Resetting allowance for user ID $user_id for month starting $month_start...\n";

$conn->begin_transaction();
try {
    // Delete allowances for the current month
    $stmt = $conn->prepare("DELETE FROM allowances WHERE user_id = ? AND date >= ?");
    $stmt->bind_param("is", $user_id, $month_start);
    $stmt->execute();
    $deleted_count = $stmt->affected_rows;
    $stmt->close();

    // Sync budget limits to reflect the 0 allowance
    $balanceHelper = new BalanceHelper($conn);
    $new_goal = $balanceHelper->syncBudgetLimits($user_id);

    $conn->commit();
    echo "Success! Deleted $deleted_count allowance records.\n";
    echo "New Monthly Budget Goal: $new_goal\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
$conn->close();
