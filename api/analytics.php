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
$groupFilter = " AND (group_id IS NULL OR group_id = 0)";

if ($action === 'trends') {
    // Last 6 months — total spending per category per month
    $months = [];
    $labels = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $months[] = $m;
        $labels[] = date('M Y', strtotime("-$i months"));
    }

    $placeholders = implode(',', array_fill(0, count($months), '?'));
    $types = str_repeat('s', count($months));

    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(date, '%Y-%m') as month, category, SUM(amount) as total
        FROM expenses
        WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') IN ($placeholders) $groupFilter
        GROUP BY month, category
        ORDER BY month ASC, total DESC
    ");
    $params = array_merge([$user_id], $months);
    $stmt->bind_param("i" . $types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Pivot by category
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
    // Daily spending for current month
    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');

    $stmt = $conn->prepare("
        SELECT date, SUM(amount) as total
        FROM expenses
        WHERE user_id = ? AND date BETWEEN ? AND ? $groupFilter
        GROUP BY date
    ");
    $stmt->bind_param("iss", $user_id, $monthStart, $monthEnd);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $data = [];
    foreach ($rows as $r) {
        $data[$r['date']] = (float)$r['total'];
    }

    echo json_encode(['success' => true, 'data' => $data, 'month' => date('Y-m'), 'days_in_month' => (int)date('t')]);
} elseif ($action === 'forecast') {
    $balanceHelper = new BalanceHelper($conn);

    // Current balance (Consolidated: Cash + Digital + Savings)
    $cash    = $balanceHelper->getCashBalance($user_id);
    $digital = $balanceHelper->getDigitalBalance($user_id);
    $savings = $balanceHelper->getTotalSavings($user_id);
    $currentBalance = $cash + $digital + $savings;

    // Daily average spending this month
    $dayOfMonth = (int)date('j');
    $monthStart = date('Y-m-01');
    $today      = date('Y-m-d');

    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ? AND expense_source = 'Allowance' $groupFilter");
    $stmt->bind_param("iss", $user_id, $monthStart, $today);
    $stmt->execute();
    $monthlySpent = (float)$stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $dailyAvg    = $dayOfMonth > 0 ? ($monthlySpent / $dayOfMonth) : 0;
    $daysLeft    = (int)date('t') - $dayOfMonth;
    $projectedSpend  = $dailyAvg * $daysLeft;
    $projectedBalance = max(0, $currentBalance - $projectedSpend);
    $runwayDays  = $dailyAvg > 0 ? floor($currentBalance / $dailyAvg) : null;

    // Month-on-month comparison
    $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
    $lastMonthEnd   = date('Y-m-t',  strtotime('-1 month'));
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ? $groupFilter");
    $stmt->bind_param("iss", $user_id, $lastMonthStart, $lastMonthEnd);
    $stmt->execute();
    $lastMonthTotal = (float)$stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $currentMonthProjected = $dailyAvg * (int)date('t');
    $trendPct = $lastMonthTotal > 0 ? (($currentMonthProjected - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

    echo json_encode([
        'success'            => true,
        'current_balance'    => $currentBalance,
        'daily_avg_spend'    => round($dailyAvg, 2),
        'days_left'          => $daysLeft,
        'projected_spend'    => round($projectedSpend, 2),
        'projected_balance'  => round($projectedBalance, 2),
        'runway_days'        => $runwayDays,
        'last_month_total'   => $lastMonthTotal,
        'trend_pct'          => round($trendPct, 1),
        'is_on_track'        => ($projectedBalance > 0),
        'basis'              => "Current Balance (" . number_format($currentBalance, 2) . ") - (Daily Avg (" . number_format($dailyAvg, 2) . ") * Days Left (" . $daysLeft . "))"
    ]);
}

$conn->close();
