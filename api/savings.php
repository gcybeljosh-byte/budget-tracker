<?php
session_start();
header("Content-Type: application/json");
require_once '../includes/db.php';
require_once '../includes/BalanceHelper.php';
require_once '../includes/AchievementHelper.php';

$achievementHelper = new AchievementHelper($conn);
$balanceHelper = new BalanceHelper($conn);

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $date = $_POST['date'] ?? date('Y-m-d');
            $description = $_POST['description'] ?? 'Savings';
            $amount = $_POST['amount'] ?? 0;
            $source_type = $_POST['source_type'] ?? 'Cash';

            if ($amount > 0) {
                $balanceDetails = $balanceHelper->getBalanceDetails($user_id, 'Allowance', $source_type);
                $currentBalance = $balanceDetails['balance'];

                if ($amount > $currentBalance) {
                    echo json_encode(['success' => false, 'message' => "Insufficient $source_type Allowance to move to Savings. Available: " . number_format($currentBalance, 2)]);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO savings (user_id, date, amount, description, source_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdss", $user_id, $date, $amount, $description, $source_type);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Savings added successfully'];
                    if ($balanceHelper->getTotalSavings($user_id) >= 1000) {
                        $achievementHelper->unlockBySlug($user_id, 'savings_starter');
                    }
                    logActivity($conn, $user_id, 'savings_add', "Added savings: $description - $amount");
                } else {
                    $response = ['success' => false, 'message' => 'Database error adding savings'];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Amount must be greater than 0'];
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM savings WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Savings deleted successfully'];
                logActivity($conn, $user_id, 'savings_delete', "Deleted savings ID $id");
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete record'];
            }
            $stmt->close();
            break;

        case 'edit':
            $id = $_POST['id'] ?? 0;
            $amount = $_POST['amount'] ?? 0;
            $date = $_POST['date'] ?? date('Y-m-d');
            $description = $_POST['description'] ?? 'Savings';
            $source_type = $_POST['source_type'] ?? 'Cash';

            if ($id > 0 && $amount > 0) {
                $oldStmt = $conn->prepare("SELECT amount, source_type FROM savings WHERE id = ? AND user_id = ?");
                $oldStmt->bind_param("ii", $id, $user_id);
                $oldStmt->execute();
                $oldData = $oldStmt->get_result()->fetch_assoc();
                $oldStmt->close();

                if ($oldData) {
                    $balanceDetails = $balanceHelper->getBalanceDetails($user_id, 'Allowance', $source_type);
                    $totalAvailable = $balanceDetails['balance'] + ($oldData['source_type'] === $source_type ? $oldData['amount'] : 0);

                    if ($amount > $totalAvailable) {
                        echo json_encode(['success' => false, 'message' => "Insufficient $source_type Allowance for update. Available: " . number_format($totalAvailable, 2)]);
                        exit;
                    }
                }

                $stmt = $conn->prepare("UPDATE savings SET amount = ?, date = ?, description = ?, source_type = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("dsssii", $amount, $date, $description, $source_type, $id, $user_id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Savings updated successfully'];
                    logActivity($conn, $user_id, 'savings_edit', "Edited savings ID $id: $description - $amount");
                } else {
                    $response = ['success' => false, 'message' => 'Database error during update'];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Invalid ID or amount for update'];
            }
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'fetch';

    if ($action === 'stats') {
        $thisMonth = date('Y-m');
        $thisYear = date('Y');
        $stats = ['total' => $balanceHelper->getTotalSavings($user_id)];

        // Monthly
        $stmt = $conn->prepare("SELECT (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?) - (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings' AND DATE_FORMAT(date, '%Y-%m') = ?) as total");
        $stmt->bind_param("isis", $user_id, $thisMonth, $user_id, $thisMonth);
        $stmt->execute();
        $stats['monthly'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Yearly
        $stmt = $conn->prepare("SELECT (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND YEAR(date) = ?) - (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings' AND YEAR(date) = ?) as total");
        $stmt->bind_param("iiii", $user_id, $thisYear, $user_id, $thisYear);
        $stmt->execute();
        $stats['yearly'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $response = ['success' => true, 'data' => $stats];
    } else {
        $sql = "(SELECT id, date, amount, description, source_type, 'deposit' as type FROM savings WHERE user_id = ?) UNION ALL (SELECT id, date, amount, description, source_type, 'withdrawal' as type FROM expenses WHERE user_id = ? AND expense_source = 'Savings') ORDER BY date DESC, id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $savings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $response = ['success' => true, 'data' => $savings];
    }
}

echo json_encode($response);
$conn->close();
