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

$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : (isset($_POST['group_id']) ? (int)$_POST['group_id'] : null);
if ($group_id) {
    // Basic membership check
    $stmt = $conn->prepare("SELECT role FROM shared_group_members WHERE group_id = ? AND user_id = ? AND status = 'active'");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to this group']);
        exit;
    }
    $stmt->close();
}

// --- Auto-Migration: Add source_type column if it doesn't exist ---
$checkColumn = $conn->query("SHOW COLUMNS FROM allowances LIKE 'source_type'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE allowances ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash' AFTER amount");
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $date = $_POST['date'] ?? '';
            $description = $_POST['description'] ?? '';
            $amount = $_POST['amount'] ?? '';
            $source_type = $_POST['source_type'] ?? 'Cash';
            $group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : null;

            if ($date && $description && $amount) {
                $stmt = $conn->prepare("INSERT INTO allowances (user_id, group_id, date, description, amount, source_type) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissds", $user_id, $group_id, $date, $description, $amount, $source_type);

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
            $group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : null;

            if ($id && $date && $description && $amount) {
                $stmt = $conn->prepare("UPDATE allowances SET date = ?, description = ?, amount = ?, source_type = ?, group_id = ? WHERE id = ? AND (user_id = ? OR group_id IN (SELECT group_id FROM shared_group_members WHERE user_id = ? AND status = 'active'))");
                $stmt->bind_param("ssdsiiii", $date, $description, $amount, $source_type, $group_id, $id, $user_id, $user_id);

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
                $stmt = $conn->prepare("DELETE FROM allowances WHERE id = ? AND (user_id = ? OR group_id IN (SELECT group_id FROM shared_group_members WHERE user_id = ? AND status = 'active' AND role = 'admin'))");
                $stmt->bind_param("iii", $id, $user_id, $user_id);

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
        // Fetch aggregated balances for all sources
        $group_id = !empty($_GET['group_id']) ? intval($_GET['group_id']) : null;
        $sources = $balanceHelper->getBalancesByAllSources($user_id, $group_id);
        $response = ['success' => true, 'data' => $sources];
    } elseif ($mode === 'history') {
        $source = $_GET['source'] ?? '';
        $group_id = !empty($_GET['group_id']) ? intval($_GET['group_id']) : null;
        if (!$source) {
            echo json_encode(['success' => false, 'message' => 'Source is required']);
            exit;
        }

        // Fetch combined history of allowances and expenses for this source
        $history = [];

        // 1. Get Allowances
        $sql1 = "SELECT id, date, description, amount, 'Allowance' as type FROM allowances WHERE user_id = ? AND source_type = ? AND (group_id IS NULL OR group_id = 0)";
        $stmt = $conn->prepare($sql1);
        $stmt->bind_param("is", $user_id, $source);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();

        // 2. Get Expenses (from Allowance source)
        $sql2 = "SELECT id, date, description, amount, 'Expense' as type FROM expenses WHERE user_id = ? AND source_type = ? AND expense_source = 'Allowance' AND (group_id IS NULL OR group_id = 0)";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("is", $user_id, $source);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();

        // 3. Get Savings Transfers (Expenses that are technically deductions from Allowance)
        $sql3 = "SELECT id, date, description, amount, 'Savings' as type FROM savings WHERE user_id = ? AND source_type = ? AND (group_id IS NULL OR group_id = 0)";
        $stmt = $conn->prepare($sql3);
        $stmt->bind_param("is", $user_id, $source);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();

        // Sort by date DESC
        usort($history, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        $response = ['success' => true, 'data' => $history];
    } else {
        // Fetch all allowances for the user (legacy/backup)
        $stmt = $conn->prepare("SELECT id, date, description, amount, source_type FROM allowances WHERE user_id = ? ORDER BY date DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $allowances = [];
        while ($row = $result->fetch_assoc()) {
            $allowances[] = $row;
        }
        $stmt->close();

        $response = ['success' => true, 'data' => $allowances];
    }
}

echo json_encode($response);
$conn->close();
