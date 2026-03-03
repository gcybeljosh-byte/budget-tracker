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

// --- Gamification: Update Streaks ---
$achievementHelper->updateNoSpendStreak($user_id);

// Fetch User Info (Name and Forwarding Status)
$user_name = 'User';
$last_forwarded_month = '';
try {
    $stmt = $conn->prepare("SELECT first_name, last_forwarded_month FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_name = $row['first_name'];
            $last_forwarded_month = $row['last_forwarded_month'] ?? '';
        }
    }
    $stmt->close();
} catch (Exception $e) {
    // If column missing, try to add it (Migration fallback)
    $conn->query("ALTER TABLE users ADD COLUMN last_forwarded_month VARCHAR(7) DEFAULT NULL");

    // Retry fetch
    $stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $row = $stmt->get_result()->fetch_assoc();
        $user_name = $row['first_name'] ?? 'User';
    }
    $stmt->close();
}

$lifetime_allowance = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $lifetime_allowance = (float)$stmt->get_result()->fetch_row()[0];
}
$stmt->close();

$lifetime_expenses = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?");
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
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $row = $stmt->get_result()->fetch_row();
        $response['total_allowance'] = (float)$row[0];
    }
    $stmt->close();
}

// 2. Monthly Expenses
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $response['total_expenses'] = (float)$row['total'];
    }
    $stmt->close();
}

// 3. Balances
$source_balances = $balanceHelper->getBalancesByAllSources($user_id);
$response['source_balances'] = $source_balances;
$response['cash_balance'] = $balanceHelper->getCashBalance($user_id);
$response['digital_balance'] = $balanceHelper->getDigitalBalance($user_id); // Kept for legacy if needed
$response['total_savings'] = $balanceHelper->getTotalSavings($user_id);

$response['balance'] = $balanceHelper->getTotalBalance($user_id, false);

// 4. Category Spending
$stmt = $conn->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY category");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response['category_spending'][$row['category']] = (float)$row['total'];
        }
    }
    $stmt->close();
}

// 5. Advanced Analytics
$expenses_this_month = $response['total_expenses'];
$expenses_last_month = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND date < DATE_FORMAT(NOW(), '%Y-%m-01')");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $expenses_last_month = (float)$stmt->get_result()->fetch_assoc()['total'];
    }
    $stmt->close();
}

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
$stmt = $conn->prepare("SELECT title, amount, due_date, category FROM recurring_payments WHERE user_id = ? AND due_date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND due_date <= LAST_DAY(NOW()) AND (last_paid_at IS NULL OR last_paid_at < DATE_FORMAT(NOW(), '%Y-%m-01')) ORDER BY due_date ASC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (count($upcoming_bills) < 5) $upcoming_bills[] = $row;
        }
    }
    $stmt->close();
}
$response['upcoming_bills'] = $upcoming_bills;

// 7. Safe-to-Spend Calculation (Refined)
// Include all active unpaid bills whose due date has passed or is within the current month
$stmt = $conn->prepare("SELECT SUM(amount) FROM recurring_payments 
                       WHERE user_id = ? AND is_active = 1 
                       AND (last_paid_at IS NULL OR DATE_FORMAT(last_paid_at, '%Y-%m') < DATE_FORMAT(NOW(), '%Y-%m'))
                       AND (due_date <= LAST_DAY(NOW()))");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_unpaid_bills = (float)$stmt->get_result()->fetch_row()[0];
$stmt->close();

// Current day of month and total days in month
$current_day = (int)date('j');
$total_days = (int)date('t');
$remaining_days = $total_days - $current_day + 1; // Inclusive of today

// Safe-to-Spend Calculation Strategy:
// We use the "Liquid" balance (Total Wallet Balances) minus "Unpaid Bills" for the month.
// If the user has a negative lifetime balance, it naturally reduces their safe-to-spend.
$available_balance = (float)$response['balance'];
$net_liquid = $available_balance - $total_unpaid_bills;
$safe_to_spend = ($remaining_days > 0) ? ($net_liquid / $remaining_days) : 0;

$response['analytics']['safe_to_spend'] = [
    'daily_limit' => max(0, $safe_to_spend),
    'remaining_days' => $remaining_days,
    'unpaid_bills_sum' => $total_unpaid_bills,
    'available_balance' => $available_balance,
    'is_overdrawn' => ($net_liquid < 0)
];

// 7. Recent Transactions
$sql = "
    (SELECT 'allowances' as type, id, date, description, amount FROM allowances WHERE user_id = ?)
    UNION ALL
    (SELECT 'expenses' as type, id, date, description, amount FROM expenses WHERE user_id = ?)
    UNION ALL
    (SELECT 'savings' as type, id, date, description, amount FROM savings WHERE user_id = ?)
    ORDER BY date DESC, id DESC
    LIMIT 10
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $response['recent_transactions'][] = $row;
        }
    }
    $stmt->close();
}

// 8. Financial Goals Summary
$goals_summary = ['active' => 0, 'total' => 0];
// Auto-update overdue goals (Sync logic from financial_goals.php)
$conn->query("UPDATE financial_goals SET status = 'overdue' 
              WHERE user_id = $user_id AND status = 'active' AND deadline < CURDATE() AND saved_amount < target_amount");
$conn->query("UPDATE financial_goals SET status = 'completed' 
              WHERE user_id = $user_id AND saved_amount >= target_amount");

$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM financial_goals WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $row = $stmt->get_result()->fetch_assoc();
        $goals_summary['total'] = (int)$row['total'];
        $goals_summary['active'] = (int)($row['active'] ?? 0);
    }
    $stmt->close();
}
$response['goals_summary'] = $goals_summary;

// 9. Journal Summary
$journal_summary = null;
$stmt = $conn->prepare("SELECT date FROM journals WHERE user_id = ? ORDER BY date DESC LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $journal_summary = ['date' => $row['date']];
        }
    }
    $stmt->close();
}
$response['journal_summary'] = $journal_summary;

// 10. Reports Count (This Month)
$reports_count = 0;
// Check if reports table exists before querying
$check = $conn->query("SHOW TABLES LIKE 'reports'");
if ($check && $check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $reports_count = (int)$stmt->get_result()->fetch_row()[0];
        }
        $stmt->close();
    }
}
$response['reports_count'] = $reports_count;

// 11. Top Category Insight
$top_category = 'None';
$stmt = $conn->prepare("SELECT category FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY category ORDER BY SUM(amount) DESC LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $top_category = $row['category'];
        }
    }
    $stmt->close();
}
$response['analytics']['top_category'] = $top_category;

// 12. Balance Forwarding Logic
$current_month = date('Y-m');
$is_first_day = (date('j') == 1);
$response['needs_forwarding'] = false;
$response['prev_month_name'] = date('F', strtotime('-1 month'));

if ($last_forwarded_month !== $current_month && $response['balance'] > 0) {
    if ($is_first_day || (date('j') <= 5)) {
        $response['needs_forwarding'] = true;
    }
}

echo json_encode($response);
$conn->close();
