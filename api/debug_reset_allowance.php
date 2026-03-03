<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/BalanceHelper.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$month_start = date('Y-m-01');

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
    echo json_encode([
        'success' => true,
        'message' => "Successfully reset current allowance. Deleted $deleted_count records.",
        'new_monthly_budget_goal' => $new_goal
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
