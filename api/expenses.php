<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/BalanceHelper.php';
require_once '../includes/NotificationHelper.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $balanceHelper = new BalanceHelper($conn);

    switch ($action) {
        case 'add':
            $date = $_POST['date'] ?? date('Y-m-d');
            $category = $_POST['category'] ?? 'Uncategorized';
            $description = $_POST['description'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $source_type = $_POST['source_type'] ?? 'Cash';
            $expense_source = $_POST['expense_source'] ?? 'Allowance';

            if ($amount > 0) {
                // Balance Validation
                $currentBalance = $balanceHelper->getBalanceBySource($user_id, $expense_source, $source_type);

                if ($amount > $currentBalance) {
                    echo json_encode(['success' => false, 'message' => "Insufficient balance in $expense_source ($source_type). Available: " . number_format($currentBalance, 2)]);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, source_type, expense_source) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssdss", $user_id, $date, $category, $description, $amount, $source_type, $expense_source);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense added successfully'];
                    logActivity($conn, $user_id, 'expense_add', "Added $category expense: $amount");

                    // Trigger budget limit check logic (optional)
                } else {
                    $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Amount must be greater than 0'];
            }
            break;

        case 'edit':
            $id = $_POST['id'] ?? 0;
            $date = $_POST['date'] ?? date('Y-m-d');
            $category = $_POST['category'] ?? 'Uncategorized';
            $description = $_POST['description'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $source_type = $_POST['source_type'] ?? 'Cash';
            $expense_source = $_POST['expense_source'] ?? 'Allowance';

            if ($id > 0 && $amount > 0) {
                // Fetch old data for balance reversal check
                $oldStmt = $conn->prepare("SELECT amount, source_type, expense_source FROM expenses WHERE id = ? AND user_id = ?");
                $oldStmt->bind_param("ii", $id, $user_id);
                $oldStmt->execute();
                $oldData = $oldStmt->get_result()->fetch_assoc();
                $oldStmt->close();

                if ($oldData) {
                    $currentBalance = $balanceHelper->getBalanceBySource($user_id, $expense_source, $source_type);

                    // If source is the same, we check if net change is possible
                    if ($oldData['source_type'] === $source_type && $oldData['expense_source'] === $expense_source) {
                        if (($amount - $oldData['amount']) > $currentBalance) {
                            echo json_encode(['success' => false, 'message' => "Insufficient balance for update. Additional funds needed: " . number_format(($amount - $oldData['amount']) - $currentBalance, 2)]);
                            exit;
                        }
                    } else {
                        // If source changed, check if new source has enough for the FULL new amount
                        if ($amount > $currentBalance) {
                            echo json_encode(['success' => false, 'message' => "Insufficient balance in new source ($source_type - $expense_source)."]);
                            exit;
                        }
                    }
                }

                $stmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, description = ?, amount = ?, source_type = ?, expense_source = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sssdssii", $date, $category, $description, $amount, $source_type, $expense_source, $id, $user_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense updated successfully'];
                    logActivity($conn, $user_id, 'expense_edit', "Edited expense ID $id");
                } else {
                    $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Invalid data'];
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
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
