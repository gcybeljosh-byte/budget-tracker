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

// Auto-migrate: Ensure soft-delete column exists
ensureColumnExists($conn, 'expenses', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');

// Auto-migrate: Ensure receipt_path column exists
ensureColumnExists($conn, 'expenses', 'receipt_path', 'VARCHAR(255) DEFAULT NULL');

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
            $receipt_path = null;

            // Handle Receipt Upload
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/uploads/receipts/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileTmpPath = $_FILES['receipt']['tmp_name'];
                $fileName = time() . '_' . $_FILES['receipt']['name'];
                $destPath = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $receipt_path = 'assets/uploads/receipts/' . $fileName;
                }
            }

            if ($amount > 0) {
                // Balance Validation
                $currentBalance = $balanceHelper->getBalanceBySource($user_id, $expense_source, $source_type);

                if ($amount > $currentBalance) {
                    echo json_encode(['success' => false, 'message' => "Insufficient balance in $expense_source ($source_type). Available: " . number_format($currentBalance, 2)]);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, source_type, expense_source, receipt_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssisss", $user_id, $date, $category, $description, $amount, $source_type, $expense_source, $receipt_path);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense added successfully'];
                    logActivity($conn, $user_id, 'expense_add', "Added $category expense: $amount");
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
                // Fetch old data for balance reversal check and receipt path
                $oldStmt = $conn->prepare("SELECT amount, source_type, expense_source, receipt_path FROM expenses WHERE id = ? AND user_id = ?");
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

                    $receipt_path = $oldData['receipt_path'];
                    // Handle New Receipt Upload
                    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../assets/uploads/receipts/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                        $fileTmpPath = $_FILES['receipt']['tmp_name'];
                        $fileName = time() . '_' . $_FILES['receipt']['name'];
                        $destPath = $uploadDir . $fileName;

                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $receipt_path = 'assets/uploads/receipts/' . $fileName;
                        }
                    }

                    $stmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, description = ?, amount = ?, source_type = ?, expense_source = ?, receipt_path = ? WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("sssdsssii", $date, $category, $description, $amount, $source_type, $expense_source, $receipt_path, $id, $user_id);

                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Expense updated successfully'];
                        logActivity($conn, $user_id, 'expense_edit', "Edited expense ID $id");
                    } else {
                        $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                    }
                    $stmt->close();
                } else {
                    $response = ['success' => false, 'message' => 'Expense not found'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid data'];
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE expenses SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
                $stmt->bind_param("ii", $id, $user_id);

                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $response = ['success' => true, 'message' => 'Expense deleted successfully'];
                    logActivity($conn, $user_id, 'expense_delete', "Soft-deleted expense ID $id");
                } else {
                    $response = ['success' => false, 'message' => 'Record not found or already deleted'];
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
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ? AND deleted_at IS NULL ORDER BY date DESC");
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
