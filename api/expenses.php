<?php
session_start();
header("Content-Type: application/json");
require_once '../includes/db.php';
require_once '../includes/NotificationHelper.php';
require_once '../includes/BalanceHelper.php';
require_once '../includes/AchievementHelper.php';
$notifications = new NotificationHelper($conn);
$balanceHelper = new BalanceHelper($conn);
$achievementHelper = new AchievementHelper($conn);

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// --- Auto-Migration: Add source_type and expense_source column to expenses if it doesn't exist ---
$checkColumn = $conn->query("SHOW COLUMNS FROM expenses LIKE 'source_type'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE expenses ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash' AFTER amount");
}
$checkSource = $conn->query("SHOW COLUMNS FROM expenses LIKE 'expense_source'");
if ($checkSource->num_rows == 0) {
    $conn->query("ALTER TABLE expenses ADD COLUMN expense_source VARCHAR(50) DEFAULT 'Allowance' AFTER source_type");
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $date = $_POST['date'] ?? '';
            $category = $_POST['category'] ?? '';
            $description = $_POST['description'] ?? '';
            $amount = $_POST['amount'] ?? '';
            $source_type = $_POST['source_type'] ?? 'Cash';
            $expense_source = $_POST['expense_source'] ?? 'Allowance';
            $group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : null;

            if ($date && $category && $description && $amount) {
                // Balance Validation
                $balanceDetails = $balanceHelper->getBalanceDetails($user_id, $expense_source, $source_type, $group_id);
                $currentBalance = $balanceDetails['balance'];

                if ($amount > $currentBalance) {
                    $sourceName = ($expense_source === 'Savings' ? ($source_type . ' Savings') : ($source_type . ' Balance'));
                    $reason = "Insufficient balance in $sourceName. ";
                    $reason .= "Available: " . number_format($currentBalance, 2) . ". ";
                    $reason .= "(Total " . ($expense_source === 'Savings' ? 'Deposits' : 'Allowance') . ": " . number_format($balanceDetails['allowance_sum'], 2);
                    $reason .= ", Total Spent: " . number_format($balanceDetails['expense_sum'], 2);
                    if ($expense_source !== 'Savings') {
                        $reason .= ", In Savings: " . number_format($balanceDetails['savings_sum'], 2);
                    }
                    $reason .= ")";

                    $response = ['success' => false, 'message' => $reason];
                    echo json_encode($response);
                    exit;
                }

                // Auto-Add category if it doesn't exist
                $catStmt = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
                $catStmt->bind_param("is", $user_id, $category);
                $catStmt->execute();
                $catStmt->close();

                $stmt = $conn->prepare("INSERT INTO expenses (user_id, group_id, date, category, description, amount, source_type, expense_source) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssdss", $user_id, $group_id, $date, $category, $description, $amount, $source_type, $expense_source);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense added successfully', 'id' => $stmt->insert_id];
                    $notifications->checkLowAllowance($user_id);
                    $notifications->checkBudgetLimit($user_id);
                    $achievementHelper->unlockBySlug($user_id, 'first_expense');
                    logActivity($conn, $user_id, 'expense_add', "Added expense: $description ($category) - $amount");
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
            $category = $_POST['category'] ?? '';
            $description = $_POST['description'] ?? '';
            $amount = $_POST['amount'] ?? '';
            $source_type = $_POST['source_type'] ?? 'Cash';
            $expense_source = $_POST['expense_source'] ?? 'Allowance';
            $group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : null;

            if ($id && $date && $category && $description && $amount) {
                // Balance Validation (Adjustment check)
                // First, get the old amount and source to "revert" it temporarily for calculation
                $oldStmt = $conn->prepare("SELECT amount, source_type, expense_source, group_id FROM expenses WHERE id = ? AND user_id = ?");
                $oldStmt->bind_param("ii", $id, $user_id);
                $oldStmt->execute();
                $oldData = $oldStmt->get_result()->fetch_assoc();
                $oldStmt->close();

                if ($oldData) {
                    $balanceDetails = $balanceHelper->getBalanceDetails($user_id, $expense_source, $source_type, $group_id);
                    $currentBalance = $balanceDetails['balance'];

                    // If source and group is the same, we add back the old amount to the balance before checking
                    if ($oldData['expense_source'] === $expense_source && $oldData['source_type'] === $source_type && $oldData['group_id'] == $group_id) {
                        $currentBalance += $oldData['amount'];
                    }

                    if ($amount > $currentBalance) {
                        $response = ['success' => false, 'message' => 'Insufficient balance for update. Available: ' . number_format($currentBalance, 2)];
                        echo json_encode($response);
                        exit;
                    }
                }

                // Auto-Add category if it doesn't exist
                $catStmt = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
                $catStmt->bind_param("is", $user_id, $category);
                $catStmt->execute();
                $catStmt->close();

                $stmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, description = ?, amount = ?, source_type = ?, expense_source = ?, group_id = ? WHERE id = ? AND (user_id = ? OR group_id IN (SELECT group_id FROM shared_group_members WHERE user_id = ? AND status = 'active'))");
                $stmt->bind_param("sssdssiiii", $date, $category, $description, $amount, $source_type, $expense_source, $group_id, $id, $user_id, $user_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense updated successfully'];
                    $notifications->checkBudgetLimit($user_id);
                    logActivity($conn, $user_id, 'expense_edit', "Edited expense ID $id: $description - $amount");
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
                $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND (user_id = ? OR group_id IN (SELECT group_id FROM shared_group_members WHERE user_id = ? AND status = 'active' AND role = 'admin'))");
                $stmt->bind_param("iii", $id, $user_id, $user_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense deleted successfully'];
                    logActivity($conn, $user_id, 'expense_delete', "Deleted expense ID $id");
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
    // Fetch all for the user or a group
    $group_id = !empty($_GET['group_id']) ? intval($_GET['group_id']) : null;

    if ($group_id) {
        // Verify membership
        $check = $conn->prepare("SELECT id FROM shared_group_members WHERE group_id = ? AND user_id = ? AND status = 'active'");
        $check->bind_param("ii", $group_id, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access to this group']);
            exit;
        }
        $check->close();

        $stmt = $conn->prepare("SELECT * FROM expenses WHERE group_id = ? ORDER BY date DESC");
        $stmt->bind_param("i", $group_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ? AND group_id IS NULL ORDER BY date DESC");
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    $stmt->close();

    $response = ['success' => true, 'data' => $expenses];
}

echo json_encode($response);
$conn->close();
