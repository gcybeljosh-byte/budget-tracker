<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/NotificationHelper.php';
require_once '../includes/BalanceHelper.php';
$notifications = new NotificationHelper($conn);
$balanceHelper = new BalanceHelper($conn);

header('Content-Type: application/json');

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
            $date = $_POST['date'] ?? '';
            $description = $_POST['description'] ?? '';
            $amount = $_POST['amount'] ?? '';
            $source_type = $_POST['source_type'] ?? 'Cash';

            if ($date && $description && $amount) {
                $stmt = $conn->prepare("INSERT INTO allowances (user_id, date, description, amount, source_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssd", $user_id, $date, $description, $amount, $source_type);

                if ($stmt->execute()) {
                    require_once '../includes/CurrencyHelper.php';
                    $symbol = CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP');
                    $response = ['success' => true, 'message' => 'Allowance added successfully', 'id' => $stmt->insert_id];
                    $notifications->addNotification($user_id, 'allowance_added', "New allowance of " . $symbol . number_format($amount, 2) . " added: $description");
                    $balanceHelper->syncBudgetLimits($user_id);
                    logActivity($conn, $user_id, 'allowance_add', "Added allowance: $description - $amount");
                } else {
                    $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'All fields are required'];
            }
            break;

        case 'edit':
            $id = $_POST['id'] ?? '';
            $date = $_POST['date'] ?? '';
            $description = $_POST['description'] ?? '';
            $amount = $_POST['amount'] ?? '';
            $source_type = $_POST['source_type'] ?? 'Cash';

            if ($id && $date && $description && $amount) {
                $stmt = $conn->prepare("UPDATE allowances SET date = ?, description = ?, amount = ?, source_type = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ssdsii", $date, $description, $amount, $source_type, $id, $user_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Allowance updated successfully'];
                    $balanceHelper->syncBudgetLimits($user_id);
                    logActivity($conn, $user_id, 'allowance_edit', "Edited allowance ID $id: $description - $amount");
                } else {
                    $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'All fields are required'];
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM allowances WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $id, $user_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Allowance deleted successfully'];
                    $balanceHelper->syncBudgetLimits($user_id);
                    logActivity($conn, $user_id, 'allowance_delete', "Deleted allowance ID $id");
                } else {
                    $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'ID is required'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['mode'] ?? 'list';

    if ($mode === 'sources') {
        $sources = $balanceHelper->getBalancesByAllSources($user_id);
        $response = ['success' => true, 'data' => $sources];
    } elseif ($mode === 'history') {
        $source = $_GET['source'] ?? '';
        if (!$source) {
            echo json_encode(['success' => false, 'message' => 'Source is required']);
            exit;
        }

        $history = [];
        // 1. Get Allowances
        $sql1 = "SELECT id, date, description, amount, 'Allowance' as type FROM allowances WHERE user_id = ? AND source_type = ?";
        $stmt = $conn->prepare($sql1);
        $stmt->bind_param("is", $user_id, $source);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $history[] = $row;
        $stmt->close();

        // 2. Get Expenses
        $sql2 = "SELECT id, date, description, amount, 'Expense' as type FROM expenses WHERE user_id = ? AND source_type = ? AND expense_source = 'Allowance'";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("is", $user_id, $source);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $history[] = $row;
        $stmt->close();

        // 3. Get Savings Transfers
        $sql3 = "SELECT id, date, description, amount, 'Savings' as type FROM savings WHERE user_id = ? AND source_type = ?";
        $stmt = $conn->prepare($sql3);
        $stmt->bind_param("is", $user_id, $source);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $history[] = $row;
        $stmt->close();

        usort($history, fn($a, $b) => strcmp($b['date'], $a['date']));
        $response = ['success' => true, 'data' => $history];
    } else {
        $stmt = $conn->prepare("SELECT id, date, description, amount, source_type FROM allowances WHERE user_id = ? ORDER BY date DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $allowances = [];
        while ($row = $result->fetch_assoc()) $allowances[] = $row;
        $stmt->close();
        $response = ['success' => true, 'data' => $allowances];
    }
}

echo json_encode($response);
$conn->close();
