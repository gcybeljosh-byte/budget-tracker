<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// Get all unique months from allowances and expenses
$sql = "
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month 
    FROM (
        SELECT date FROM allowances WHERE user_id = ?
        UNION
        SELECT date FROM expenses WHERE user_id = ?
    ) as all_dates
    ORDER BY month DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$months = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statements = [];

foreach ($months as $m) {
    $month = $m['month'];
    $startDate = "$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));

    // Monthly Income
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $user_id, $startDate, $endDate);
    $stmt->execute();
    $income = (float)$stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Monthly Expenses
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $user_id, $startDate, $endDate);
    $stmt->execute();
    $expenses = (float)$stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Monthly Savings
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $user_id, $startDate, $endDate);
    $stmt->execute();
    $savings = (float)$stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $statements[] = [
        'month' => $month,
        'month_name' => date('F Y', strtotime($startDate)),
        'income' => $income,
        'expenses' => $expenses,
        'savings' => $savings,
        'net' => $income - $expenses
    ];
}

echo json_encode(['success' => true, 'data' => $statements]);
$conn->close();
?>
