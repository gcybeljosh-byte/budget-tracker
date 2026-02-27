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
    $stmt = $conn->prepare("SELECT DATE_FORMAT(date, '%Y-%m') as month, category, SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') IN ($placeholders) GROUP BY month, category ORDER BY month ASC, total DESC");
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
    $stmt = $conn->prepare("SELECT date, SUM(amount) as total FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ? GROUP BY date");
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
    $currentBalance = $balanceHelper->getCashBalance($user_id) + $balanceHelper->getDigitalBalance($user_id) + $balanceHelper->getTotalSavings($user_id);

    $day = (int)date('j');
    $spent = 0;
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ? AND expense_source = 'Allowance'");
    if ($stmt) {
        $start = date('Y-m-01');
        $today = date('Y-m-d');
        $stmt->bind_param("iss", $user_id, $start, $today);
        $stmt->execute();
        $spent = (float)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
    }

    $lastMonth = 0;
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ?");
    if ($stmt) {
        $lStart = date('Y-m-01', strtotime('-1 month'));
        $lEnd = date('Y-m-t', strtotime('-1 month'));
        $stmt->bind_param("iss", $user_id, $lStart, $lEnd);
        $stmt->execute();
        $lastMonth = (float)$stmt->get_result()->fetch_row()[0];
        $stmt->close();
    }

    $dailyAvg = $day > 0 ? ($spent / $day) : 0;
    $daysLeft = (int)date('t') - $day;
    $pSpend = $dailyAvg * $daysLeft;
    echo json_encode([
        'success'           => true,
        'current_balance'   => $currentBalance,
        'daily_avg_spend'   => round($dailyAvg, 2),
        'days_left'         => $daysLeft,
        'projected_spend'   => round($pSpend, 2),
        'projected_balance' => round(max(0, $currentBalance - $pSpend), 2),
        'runway_days'       => $dailyAvg > 0 ? (int)($currentBalance / $dailyAvg) : null,
        'last_month_total'  => $lastMonth,
        'trend_pct'         => $lastMonth > 0 ? round((($dailyAvg * (int)date('t') - $lastMonth) / $lastMonth) * 100, 1) : 0
    ]);
}
$conn->close();
