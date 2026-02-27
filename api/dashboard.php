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
$group_id = !empty($_GET['group_id']) ? intval($_GET['group_id']) : null;
$groupFilter = $group_id ? " AND group_id = ?" : " AND group_id IS NULL";

// --- Gamification: Update Streaks ---
$achievementHelper->updateNoSpendStreak($user_id);

// --- Auto-Migration: Ensure source_type columns exist ---
// Allowances table
$checkAllowance = $conn->query("SHOW COLUMNS FROM allowances LIKE 'source_type'");
if ($checkAllowance->num_rows == 0) {
    $conn->query("ALTER TABLE allowances ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash'");
}
$conn->query("UPDATE allowances SET source_type = 'Cash' WHERE source_type IS NULL OR source_type = ''");

// Expenses table
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

// Savings table (Ensuring source_type exists and is populated)
$checkSavings = $conn->query("SHOW COLUMNS FROM savings LIKE 'source_type'");
if ($checkSavings->num_rows == 0) {
    $conn->query("ALTER TABLE savings ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash'");
}
$conn->query("UPDATE savings SET source_type = 'Cash' WHERE source_type IS NULL OR source_type = ''");

// Users table (Security Questions)
$checkSQ = $conn->query("SHOW COLUMNS FROM users LIKE 'security_question'");
if ($checkSQ->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN security_question TEXT, ADD COLUMN security_answer TEXT");
}

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
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? $groupFilter");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $lifetime_allowance = (float)$stmt->get_result()->fetch_row()[0];
}
$stmt->close();

$lifetime_expenses = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? $groupFilter");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $lifetime_expenses = (float)$stmt->get_result()->fetch_row()[0];
}
$stmt->close();

$response = [
    'success' => true,
    'user_name' => $user_name,
    'lifetime_allowance' => $lifetime_allowance,
    'lifetime_expenses' => $lifetime_expenses,
    'total_allowance' => 0, // Monthly placeholder
    'total_expenses' => 0,  // Monthly placeholder
    'balance' => 0,
    'cash_balance' => 0,
    'digital_balance' => 0,
    'total_savings' => 0,
    'category_spending' => [],
    'recent_transactions' => []
];

// 1. Monthly Allowance (For dashboard card labeled as monthly)
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') $groupFilter");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $row = $stmt->get_result()->fetch_row();
    $response['total_allowance'] = (float)$row[0];
}
$stmt->close();

// 2. Monthly Expenses (For dashboard card labeled as monthly)
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') $groupFilter");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['total_expenses'] = (float)$row['total'];
}
$stmt->close();

// 3.1 Cash Balance (Lifetime Sync - Physical Wallet)
$response['cash_balance'] = $balanceHelper->getCashBalance($user_id, false, $group_id);

// 3.2 Digital (Bank/E-Wallet) Balance (Lifetime Sync - Digital Wallet)
$response['digital_balance'] = $balanceHelper->getDigitalBalance($user_id, false, $group_id);

// 3.3 Total Savings (Net of Savings Expenses - Lifetime Standing)
$response['total_savings'] = $balanceHelper->getTotalSavings($user_id, false, null, $group_id);

// 3. Balance (Consolidated available spendable funds: Cash + Digital)
$response['balance'] = $response['cash_balance'] + $response['digital_balance'];

// 4. Category Spending (Current month only, only from Allowance)
$stmt = $conn->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') $groupFilter GROUP BY category");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['category_spending'][$row['category']] = (float)$row['total'];
    }
}
$stmt->close();

// --- Advanced Analytics ---

// 4.1 This Month's Expenses
$expenses_this_month = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') $groupFilter");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $expenses_this_month = (float)$stmt->get_result()->fetch_assoc()['total'];
}
$stmt->close();

// 4.2 Last Month's Expenses
$expenses_last_month = 0;
// Use explicit dates to ensure correct range for last month
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND date < DATE_FORMAT(NOW(), '%Y-%m-01') $groupFilter");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $expenses_last_month = (float)$stmt->get_result()->fetch_assoc()['total'];
}
$stmt->close();

// 4.3 Calculations
$current_day = (int)date('j');
$days_in_month = (int)date('t');
$daily_average = ($current_day > 0) ? ($expenses_this_month / $current_day) : 0;

$response['analytics'] = [
    'expenses_this_month' => $expenses_this_month,
    'expenses_last_month' => $expenses_last_month,
    'spending_trend' => ($expenses_last_month > 0) ? (($expenses_this_month - $expenses_last_month) / $expenses_last_month) * 100 : 0,
    'daily_average' => $daily_average,
    'projected_spending' => $daily_average * $days_in_month,
    'runway' => ($daily_average > 0) ? ($response['balance'] / $daily_average) : 999, // Days left until funds run out
    'savings_rate' => ($lifetime_allowance > 0) ? (($response['balance'] / $lifetime_allowance) * 100) : 0
];

// 4.4 Peak Spending Day (New Feature)
$peak_data = null;
$stmt = $conn->prepare("SELECT date, SUM(amount) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' $groupFilter GROUP BY date ORDER BY total DESC LIMIT 1");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $peak_date = $row['date'];
        $peak_total = (float)$row['total'];

        // Get top 3 items for that day
        $top_items = [];
        $stmt_items = $conn->prepare("SELECT description, amount FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date = ? $groupFilter ORDER BY amount DESC LIMIT 3");
        if ($group_id) {
            $stmt_items->bind_param("isi", $user_id, $peak_date, $group_id);
        } else {
            $stmt_items->bind_param("is", $user_id, $peak_date);
        }
        if ($stmt_items->execute()) {
            $res_items = $stmt_items->get_result();
            while ($item = $res_items->fetch_assoc()) {
                $top_items[] = $item;
            }
        }
        $stmt_items->close();

        $peak_data = [
            'date' => $peak_date,
            'total' => $peak_total,
            'items' => $top_items
        ];
    }
}
$stmt->close();
$response['analytics']['peak_spending'] = $peak_data;

// --- Dashboard Hub Summary (Journal, Goals, Reports) ---

// 4.5 Latest Journal Entry
$journal_summary = null;
$stmt = $conn->prepare("SELECT title, date FROM journals WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $journal_summary = ['title' => $row['title'], 'date' => $row['date']];
    }
}
$stmt->close();
$response['journal_summary'] = $journal_summary;

// 4.6 Goals Summary
$goals_summary = ['active' => 0, 'total' => 0];
$stmt = $conn->prepare("SELECT COUNT(*) as count, status FROM financial_goals WHERE user_id = ? GROUP BY status");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $goals_summary['total'] += $row['count'];
        if ($row['status'] === 'Active') $goals_summary['active'] = $row['count'];
    }
}
$stmt->close();
$response['goals_summary'] = $goals_summary;

// 4.7 Reports Summary (Count this month)
$reports_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $row = $stmt->get_result()->fetch_row();
    $reports_count = (int)$row[0];
}
$stmt->close();
$response['reports_count'] = $reports_count;

// 4.8 Recurring Payments (Bills) Analysis
$total_unpaid_bills = 0;
$upcoming_bills = [];
// Unpaid bills are those due this month
$stmt = $conn->prepare("SELECT title, amount, due_date, category FROM recurring_payments WHERE user_id = ? AND due_date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND due_date <= LAST_DAY(NOW()) AND (last_paid_at IS NULL OR last_paid_at < DATE_FORMAT(NOW(), '%Y-%m-01')) $groupFilter ORDER BY due_date ASC");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $total_unpaid_bills += (float)$row['amount'];
        if (count($upcoming_bills) < 5) $upcoming_bills[] = $row;
    }
}
$stmt->close();
$response['upcoming_bills'] = $upcoming_bills;

// 4.9 Safe-to-Spend Calculation
$remaining_days = (int)date('t') - (int)date('j') + 1;
$safe_to_spend = ($response['balance'] - $total_unpaid_bills) / $remaining_days;

$response['analytics']['safe_to_spend'] = [
    'daily_limit' => max(0, $safe_to_spend),
    'remaining_days' => $remaining_days,
    'unpaid_bills_sum' => $total_unpaid_bills
];

// 4.8 Top Category Name
$top_category = 'None';
if (!empty($response['category_spending'])) {
    arsort($response['category_spending']);
    $top_category = key($response['category_spending']);
}
$response['analytics']['top_category'] = $top_category;

// 5. Recent Transactions (Union of allowances and expenses)
// We need to select common columns: type, id, date, description, amount
// We'll limit to 10 most recent
$sql = "
    (SELECT 'allowances' as type, id, date, description, amount FROM allowances WHERE user_id = ? $groupFilter)
    UNION ALL
    (SELECT 'expenses' as type, id, date, description, amount FROM expenses WHERE user_id = ? $groupFilter)
    UNION ALL
    (SELECT 'savings' as type, id, date, description, amount FROM savings WHERE user_id = ? $groupFilter)
    ORDER BY date DESC, id DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
if ($group_id) {
    $stmt->bind_param("iiiiii", $user_id, $group_id, $user_id, $group_id, $user_id, $group_id);
} else {
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
}
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['recent_transactions'][] = $row;
    }
}
$stmt->close();

// 6. Full Expense History (For AI Search/Analysis - Limit 100)
$response['expense_history'] = [];
$stmt = $conn->prepare("SELECT date, description, amount, category FROM expenses WHERE user_id = ? $groupFilter ORDER BY date DESC LIMIT 100");
if ($group_id) {
    $stmt->bind_param("ii", $user_id, $group_id);
} else {
    $stmt->bind_param("i", $user_id);
}
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['expense_history'][] = $row;
    }
}
$stmt->close();

echo json_encode($response);
$conn->close();
