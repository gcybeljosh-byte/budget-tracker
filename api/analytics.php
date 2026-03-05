<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/BalanceHelper.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$action  = $_GET['action'] ?? 'trends';

if ($action === 'trends') {
    $months = [];
    $labels = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $months[] = $m;
        $labels[] = date('M Y', strtotime("-$i months"));
    }

    $placeholders = implode(',', array_fill(0, count($months), '?'));
    $stmt = $conn->prepare("SELECT DATE_FORMAT(date, '%Y-%m') as month, category, SUM(amount) as total FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND DATE_FORMAT(date, '%Y-%m') IN ($placeholders) GROUP BY month, category ORDER BY month ASC, total DESC");
    if ($stmt) {
        $stmt->bind_param("i" . str_repeat('s', count($months)), $user_id, ...$months);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $rows = [];
    }

    $categories = array_unique(array_column($rows, 'category'));
    $datasets = [];
    $colorPalette = ['#4e73df', '#e74a3b', '#1cc88a', '#f6c23e', '#36b9cc', '#858796', '#fd7e14', '#6f42c1', '#20c9a6', '#e83e8c'];
    $ci = 0;
    foreach ($categories as $cat) {
        $catData = [];
        foreach ($months as $m) {
            $found = 0;
            foreach ($rows as $r) {
                if ($r['month'] === $m && $r['category'] === $cat) {
                    $found = (float)$r['total'];
                    break;
                }
            }
            $catData[] = $found;
        }
        $color = $colorPalette[$ci % count($colorPalette)];
        $datasets[] = ['label' => $cat, 'data' => $catData, 'backgroundColor' => $color . '99', 'borderColor' => $color, 'borderWidth' => 2];
        $ci++;
    }
    echo json_encode(['success' => true, 'labels' => $labels, 'datasets' => $datasets]);
} elseif ($action === 'heatmap') {
    $stmt = $conn->prepare("SELECT date, SUM(amount) as total FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ? GROUP BY date");
    $start = date('Y-m-01');
    $end = date('Y-m-t');
    $data = [];
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $start, $end);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $data[$r['date']] = (float)$r['total'];
        $stmt->close();
    }
    echo json_encode(['success' => true, 'data' => $data, 'month' => date('Y-m'), 'days_in_month' => (int)date('t')]);
} elseif ($action === 'forecast') {
    $balanceHelper = new BalanceHelper($conn);
    $currentBalance = $balanceHelper->getTotalBalance($user_id, true);

    // Fetch User Preferences (Currency and Budget Goal)
    $stmtPref = $conn->prepare("SELECT preferred_currency, monthly_budget_goal FROM users WHERE id = ?");
    $stmtPref->bind_param("i", $user_id);
    $stmtPref->execute();
    $pref = $stmtPref->get_result()->fetch_assoc();
    $stmtPref->close();

    $currency = $pref['preferred_currency'] ?? 'PHP';
    $budgetGoal = (float)($pref['monthly_budget_goal'] ?? 0);

    // 1. Get Monthly Allowance
    $monthlyAllowance = 0;
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $monthlyAllowance = (float)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
    }

    $day = (int)date('j');
    $spent = 0;
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND expense_source = 'Allowance'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $spent = (float)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
    }

    $lastMonthTotal = 0;
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND date >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND date < DATE_FORMAT(NOW(), '%Y-%m-01') AND expense_source = 'Allowance'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $lastMonthTotal = (float)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
    }

    $dailyAvg = $day > 0 ? ($spent / $day) : 0;
    $daysInMonth = (int)date('t');
    $daysLeft = $daysInMonth - $day;
    $pSpendRemaining = $dailyAvg * $daysLeft;
    $totalProjectedSpend = $spent + $pSpendRemaining;

    // Logic: Is on track if total projected spend <= monthly budget goal (or allowance if goal is 0)
    $targetBudget = ($budgetGoal > 0) ? $budgetGoal : $monthlyAllowance;
    $isOnTrack = ($targetBudget > 0) ? ($totalProjectedSpend <= $targetBudget) : true;

    // Balanced Projection: current wallet balance minus what we PREDICT will be spent from now to end of month
    $projectedEndOfMonthBalance = $currentBalance - $pSpendRemaining;

    // Budget-centric projection: How much of the LIMIT will be left or exceeded?
    $budgetVariance = ($targetBudget > 0) ? ($targetBudget - $totalProjectedSpend) : 0;

    echo json_encode([
        'success'           => true,
        'currency'          => $currency,
        'current_balance'   => $currentBalance,
        'monthly_allowance' => $monthlyAllowance,
        'monthly_budget_goal' => $budgetGoal,
        'target_budget'     => $targetBudget,
        'daily_avg_spend'   => round($dailyAvg, 2),
        'days_left'         => $daysLeft,
        'spent_so_far'      => $spent,
        'projected_spend_remaining' => round($pSpendRemaining, 2),
        'total_projected_spend' => round($totalProjectedSpend, 2),
        'total_savings'     => $balanceHelper->getTotalSavings($user_id),
        'budget_variance'   => round($budgetVariance, 2),
        'runway_days'       => $dailyAvg > 0 ? (int)($currentBalance / $dailyAvg) : null,
        'last_month_total'  => $lastMonthTotal,
        'is_on_track'       => $isOnTrack,
        'basis'             => 'Based on your ' . ($budgetGoal > 0 ? 'Monthly Limit' : 'Monthly Allowance') . ' of ' . number_format($targetBudget, 2)
    ]);
}
$conn->close();
