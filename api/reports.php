<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$type = $_GET['type'] ?? 'monthly'; // monthly, yearly, specific
$dateInput = $_GET['date'] ?? date('Y-m-d');

$response = [
    'success' => true,
    'total_allowance' => 0,
    'total_expenses' => 0,
    'total_savings' => 0,
    'analytics' => [
        'savings_rate' => 0,
        'budget_utilization' => 0,
        'daily_average_expense' => 0
    ],
    'period' => ''
];

// Determine Date Range
$startDate = '';
$endDate = '';

if ($type === 'yearly') {
    $year = date('Y', strtotime($dateInput));
    $startDate = "$year-01-01";
    $endDate = "$year-12-31";
    $response['period'] = "Year $year";
} elseif ($type === 'weekly') {
    $time = strtotime($dateInput);
    $startDate = date('Y-m-d', strtotime('monday this week', $time));
    $endDate = date('Y-m-d', strtotime('sunday this week', $time));
    $response['period'] = "Week: " . date('M j', strtotime($startDate)) . " - " . date('M j, Y', strtotime($endDate));
} elseif ($type === 'specific') {
    $startDate = date('Y-m-d', strtotime($dateInput));
    $endDate = $startDate;
    $response['period'] = date('F j, Y', strtotime($startDate));
} else {
    // Monthly (default)
    $year = date('Y', strtotime($dateInput));
    $month = date('m', strtotime($dateInput));
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    $response['period'] = date('F Y', strtotime($startDate));
}

// Fetch Totals
function getSum($conn, $table, $user_id, $startDate, $endDate, $source = null) {
    if ($table === 'expenses' && $source !== null) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM $table WHERE user_id = ? AND expense_source = ? AND date BETWEEN ? AND ?");
        $stmt->bind_param("isss", $user_id, $source, $startDate, $endDate);
    } else {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM $table WHERE user_id = ? AND date BETWEEN ? AND ?");
        $stmt->bind_param("iss", $user_id, $startDate, $endDate);
    }
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)$res['total'];
}

function getDetails($conn, $table, $user_id, $startDate, $endDate) {
    $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC, id ASC");
    $stmt->bind_param("iss", $user_id, $startDate, $endDate);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $res;
}

$response['total_allowance'] = getSum($conn, 'allowances', $user_id, $startDate, $endDate);
$response['total_expenses'] = getSum($conn, 'expenses', $user_id, $startDate, $endDate);
$response['allowance_expenses'] = getSum($conn, 'expenses', $user_id, $startDate, $endDate, 'Allowance');
$response['savings_expenses'] = getSum($conn, 'expenses', $user_id, $startDate, $endDate, 'Savings');
$response['total_savings'] = getSum($conn, 'savings', $user_id, $startDate, $endDate);

$response['details'] = [
    'allowances' => getDetails($conn, 'allowances', $user_id, $startDate, $endDate),
    'expenses' => getDetails($conn, 'expenses', $user_id, $startDate, $endDate),
    'savings' => getDetails($conn, 'savings', $user_id, $startDate, $endDate)
];

// Calculate Analytics
if ($response['total_allowance'] > 0) {
    $response['analytics']['savings_rate'] = ($response['total_savings'] / $response['total_allowance']) * 100;
    // Budget utilization should only look at Allowance expenses
    $response['analytics']['budget_utilization'] = ($response['allowance_expenses'] / $response['total_allowance']) * 100;
}

// Daily Average
$diff = abs(strtotime($endDate) - strtotime($startDate));
$days = max(1, floor($diff / (60 * 60 * 24)) + 1);
$response['analytics']['daily_average_expense'] = $response['total_expenses'] / $days;

// User Info for Header
$uStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$userRow = $uStmt->get_result()->fetch_assoc();
$response['user_name'] = ($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '');
$uStmt->close();

echo json_encode($response);
$conn->close();
?>
