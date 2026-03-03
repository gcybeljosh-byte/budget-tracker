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
$action = $_POST['action'] ?? ''; // 'savings', 'allowance', or 'dismiss'
$amount = (float)($_POST['amount'] ?? 0);

$current_month = date('Y-m');
$prev_month_name = date('F', strtotime('-1 month'));
$date = date('Y-m-01'); // 1st of current month

if ($action === 'dismiss') {
    $stmt = $conn->prepare("UPDATE users SET last_forwarded_month = ? WHERE id = ?");
    $stmt->bind_param("si", $current_month, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Prompt dismissed for this month']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'No balance to forward']);
    exit;
}

$conn->begin_transaction();
try {
    $logMsg = "";
    if ($action === 'savings') {
        $bh = new BalanceHelper($conn);
        $sources = $bh->getBalancesByAllSources($user_id);

        foreach ($sources as $s) {
            if ($s['balance'] > 0) {
                $stmt = $conn->prepare("INSERT INTO savings (user_id, date, amount, description, source_type) VALUES (?, ?, ?, ?, ?)");
                $desc = "Forwarded from $prev_month_name";
                $stmt->bind_param("isdss", $user_id, $date, $s['balance'], $desc, $s['source']);
                $stmt->execute();
                $stmt->close();
            }
        }
        $logMsg = "Forwarded balance to Savings from $prev_month_name";
    } elseif ($action === 'allowance') {
        $bh = new BalanceHelper($conn);
        $sources = $bh->getBalancesByAllSources($user_id);
        foreach ($sources as $s) {
            if ($s['balance'] > 0) {
                // 1. "Consume" the old balance from previous month to maintain lifetime consistency
                $stmt = $conn->prepare("INSERT INTO expenses (user_id, date, amount, description, category, source_type, expense_source) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $prev_last_day = date('Y-m-t', strtotime('-1 month'));
                $desc = "Balance Carryover to $current_month";
                $cat = 'Adjustment';
                $exp_src = 'Allowance';
                $stmt->bind_param("isdssss", $user_id, $prev_last_day, $s['balance'], $desc, $cat, $s['source'], $exp_src);
                $stmt->execute();
                $stmt->close();

                // 2. Add as new allowance in current month
                $stmt = $conn->prepare("INSERT INTO allowances (user_id, date, amount, description, source_type) VALUES (?, ?, ?, ?, ?)");
                $new_desc = "Carried over from $prev_month_name";
                $stmt->bind_param("isdss", $user_id, $date, $s['balance'], $new_desc, $s['source']);
                $stmt->execute();
                $stmt->close();
            }
        }
        $logMsg = "Carried over balance to $current_month Allowance";
    }

    // Update user record
    $stmt = $conn->prepare("UPDATE users SET last_forwarded_month = ? WHERE id = ?");
    $stmt->bind_param("si", $current_month, $user_id);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $user_id, 'balance_forward', $logMsg);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Balance processed successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();
