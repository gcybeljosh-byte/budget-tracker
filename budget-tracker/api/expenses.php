<?php
session_start();
header("Content-Type: application/json");
include '../includes/db.php';
require_once '../includes/NotificationHelper.php';
$notifications = new NotificationHelper($conn);

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// --- Auto-Migration: Add source_type column to expenses if it doesn't exist ---
$checkColumn = $conn->query("SHOW COLUMNS FROM expenses LIKE 'source_type'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE expenses ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash' AFTER amount");
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
            
            if ($date && $category && $description && $amount) {
                $stmt = $conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, source_type) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssds", $user_id, $date, $category, $description, $amount, $source_type);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense added successfully', 'id' => $stmt->insert_id];
                    $notifications->checkLowAllowance($user_id);
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
            
            if ($id && $date && $category && $description && $amount) {
                $stmt = $conn->prepare("UPDATE expenses SET date = ?, category = ?, description = ?, amount = ?, source_type = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sssdssi", $date, $category, $description, $amount, $source_type, $id, $user_id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Expense updated successfully'];
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
?>
