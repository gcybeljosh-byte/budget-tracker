<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/BalanceHelper.php';
require_once '../includes/AchievementHelper.php';
$balanceHelper = new BalanceHelper($conn);
$achievementHelper = new AchievementHelper($conn);

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$groupFilter = " AND (group_id IS NULL OR group_id = 0)";

// --- Gamification: Update Streaks ---
$achievementHelper->updateNoSpendStreak($user_id);

// --- Auto-Migration: Ensure columns exist (Standard Solo Sync) ---
$checkAllowance = $conn->query("SHOW COLUMNS FROM allowances LIKE 'source_type'");
if ($checkAllowance->num_rows == 0) {
    $conn->query("ALTER TABLE allowances ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash'");
}
$conn->query("UPDATE allowances SET source_type = 'Cash' WHERE source_type IS NULL OR source_type = ''");

$checkExpense = $conn->query("SHOW COLUMNS FROM expenses LIKE 'source_type'");
if ($checkExpense->num_rows == 0) {
    $conn->query("ALTER TABLE expenses ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash'");
}
$checkSource = $conn->query("SHOW COLUMNS FROM expenses LIKE 'expense_source'");
if ($checkSource->num_rows == 0) {
    $conn->query("ALTER TABLE expenses ADD COLUMN expense_source VARCHAR(50) DEFAULT 'Allowance' AFTER source_type");
}
$conn->query("UPDATE expenses SET source_type = 'Cash' WHERE source_type IS NULL OR source_type = ''");
$conn->query("UPDATE expenses SET expense_source = 'Allowance' WHERE expense_source IS NULL OR expense_source = ''");

$checkSavings = $conn->query("SHOW COLUMNS FROM savings LIKE 'source_type'");
if ($checkSavings->num_rows == 0) {
    $conn->query("ALTER TABLE savings ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash'");
}
$conn->query("UPDATE savings SET source_type = 'Cash' WHERE source_type IS NULL OR source_type = ''");

// Fetch User Name
$user_name = 'User';
$stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_name = $row['first_name'];
    }
}
$stmt->close();

$lifetime_allowance = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND (group_id IS NULL OR group_id = 0)");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $lifetime_allowance = (float)$stmt->get_result()->fetch_row()[0];
}
$stmt->close();

$lifetime_expenses = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND (group_id IS NULL OR group_id = 0)");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $lifetime_expenses = (float)$stmt->get_result()->fetch_row()[0];
}
$stmt->close();

$response = [
    'success' => true,
    'user_name' => $user_name,
    'lifetime_allowance' => $lifetime_allowance,
    'lifetime_expenses' => $lifetime_expenses,
    'total_allowance' => 0,
    'total_expenses' => 0,
    'balance' => 0,
    'cash_balance' => 0,
    'digital_balance' => 0,
    'total_savings' => 0,
    'category_spending' => [],
    'recent_transactions' => []
];

// 1. Monthly Allowance
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND (group_id IS NULL OR group_id = 0) AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $row = $stmt->get_result()->fetch_row();
    $response['total_allowance'] = (float)$row[0];
}
$stmt->close();

// 2. Monthly Expenses
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND (group_id IS NULL OR group_id = 0) AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['total_expenses'] = (float)$row['total'];
}
$stmt->close();

// 3. Balances
$response['cash_balance'] = $balanceHelper->getCashBalance($user_id);
$response['digital_balance'] = $balanceHelper->getDigitalBalance($user_id);
$response['total_savings'] = $balanceHelper->getTotalSavings($user_id);
$response['balance'] = $response['cash_balance'] + $response['digital_balance'];

// 4. Category Spending
$stmt = $conn->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND (group_id IS NULL OR group_id = 0) AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY category");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['category_spending'][$row['category']] = (float)$row['total'];
    }
}
$stmt->close();

// 5. Advanced Analytics
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND (group_id IS NULL OR group_id = 0) AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $expenses_this_month = (float)$stmt->get_result()->fetch_assoc()['total'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND (group_id IS NULL OR group_id = 0) AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND date < DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $expenses_last_month = (float)$stmt->get_result()->fetch_assoc()['total'];
}
$stmt->close();

$current_day = (int)date('j');
$days_in_month = (int)date('t');
$daily_average = ($current_day > 0) ? ($expenses_this_month / $current_day) : 0;

$response['analytics'] = [
    'expenses_this_month' => $expenses_this_month,
    'expenses_last_month' => $expenses_last_month,
    'spending_trend' => ($expenses_last_month > 0) ? (($expenses_this_month - $expenses_last_month) / $expenses_last_month) * 100 : 0,
    'daily_average' => $daily_average,
    'projected_spending' => $daily_average * $days_in_month,
    'runway' => ($daily_average > 0) ? ($response['balance'] / $daily_average) : 999,
    'savings_rate' => ($lifetime_allowance > 0) ? (($response['balance'] / $lifetime_allowance) * 100) : 0
];

// 6. Bills & Hub logic (Personal Only)
$upcoming_bills = [];
$total_unpaid_bills = 0;
$stmt = $conn->prepare("SELECT title, amount, due_date, category FROM recurring_payments WHERE user_id = ? AND (group_id IS NULL OR group_id = 0) AND due_date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND due_date <= LAST_DAY(NOW()) AND (last_paid_at IS NULL OR last_paid_at < DATE_FORMAT(NOW(), '%Y-%m-01')) ORDER BY due_date ASC");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $total_unpaid_bills += (float)$row['amount'];
        if (count($upcoming_bills) < 5) $upcoming_bills[] = $row;
    }
}
$stmt->close();
$response['upcoming_bills'] = $upcoming_bills;

$remaining_days = (int)date('t') - (int)date('j') + 1;
$safe_to_spend = ($response['balance'] - $total_unpaid_bills) / $remaining_days;
$response['analytics']['safe_to_spend'] = [
    'daily_limit' => max(0, $safe_to_spend),
    'remaining_days' => $remaining_days,
    'unpaid_bills_sum' => $total_unpaid_bills
];

// 7. Recent Transactions
$sql = "
    (SELECT 'allowances' as type, id, date, description, amount FROM allowances WHERE user_id = ? AND (group_id IS NULL OR group_id = 0))
    UNION ALL
    (SELECT 'expenses' as type, id, date, description, amount FROM expenses WHERE user_id = ? AND (group_id IS NULL OR group_id = 0))
    UNION ALL
    (SELECT 'savings' as type, id, date, description, amount FROM savings WHERE user_id = ? AND (group_id IS NULL OR group_id = 0))
    ORDER BY date DESC, id DESC
    LIMIT 10
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $response['recent_transactions'][] = $row;
    }
}
$stmt->close();

echo json_encode($response);
$conn->close();
