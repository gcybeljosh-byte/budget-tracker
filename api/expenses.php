<?php
session_start();
header("Content-Type: application/json");
require_once '../includes/db.php';
require_once '../includes/NotificationHelper.php';
require_once '../includes/BalanceHelper.php';
$notifications = new NotificationHelper($conn);
$balanceHelper = new BalanceHelper($conn);

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

            if ($date && $category && $description && $amount) {
                // Balance Validation
                $currentBalance = $balanceHelper->getBalanceBySource($user_id, $expense_source, $source_type);
                if ($amount > $currentBalance) {
                    $response = ['success' => false, 'message' => 'Insufficient balance in ' . ($expense_source === 'Savings' ? 'Savings' : ($source_type . ' Balance')) . '. Available: ' . $currentBalance];
                    echo json_encode($response);
                    exit;
                }

                // Auto-Add category if it doesn't exist
                $catStmt = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
                $catStmt->bind_param("is", $user_id, $category);
                $catStmt->execute();
                $catStmt->close();

                $stmt = $conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, source_type, expense_source) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssdss", $user_id, $date, $category, $description, $amount, $source_type, $expense_source);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense added successfully', 'id' => $stmt->insert_id];
                    $notifications->checkLowAllowance($user_id);
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

            if ($id && $date && $category && $description && $amount) {
                // Balance Validation (Adjustment check)
                // First, get the old amount and source to "revert" it temporarily for calculation
                $oldStmt = $conn->prepare("SELECT amount, source_type, expense_source FROM expenses WHERE id = ? AND user_id = ?");
                $oldStmt->bind_param("ii", $id, $user_id);
                $oldStmt->execute();
                $oldData = $oldStmt->get_result()->fetch_assoc();
                $oldStmt->close();

                if ($oldData) {
                    $currentBalance = $balanceHelper->getBalanceBySource($user_id, $expense_source, $source_type);
                    // If source is the same, we add back the old amount to the balance before checking
                    if ($oldData['expense_source'] === $expense_source && $oldData['source_type'] === $source_type) {
                        $currentBalance += $oldData['amount'];
                    }

                    if ($amount > $currentBalance) {
                        $response = ['success' => false, 'message' => 'Insufficient balance for update. Available: ' . $currentBalance];
                        echo json_encode($response);
                        exit;
                    }
                }

                // Auto-Add category if it doesn't exist
                $catStmt = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
                $catStmt->bind_param("is", $user_id, $category);
                $catStmt->execute();
                $catStmt->close();

                $stmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, description = ?, amount = ?, source_type = ?, expense_source = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sssdssii", $date, $category, $description, $amount, $source_type, $expense_source, $id, $user_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense updated successfully'];
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
                $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $id, $user_id);

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
    // Fetch all for the user
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC");
    $stmt->bind_param("i", $user_id);
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
