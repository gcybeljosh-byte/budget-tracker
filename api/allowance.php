<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/NotificationHelper.php';
$notifications = new NotificationHelper($conn);

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

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
            
            if ($date && $description && $amount) {
                $stmt = $conn->prepare("INSERT INTO allowances (user_id, date, description, amount, source_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issds", $user_id, $date, $description, $amount, $source_type);
                
                if ($stmt->execute()) {
                    require_once '../includes/CurrencyHelper.php';
                    $symbol = CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP');
                    $response = ['success' => true, 'message' => 'Allowance added successfully', 'id' => $stmt->insert_id];
                    $notifications->addNotification($user_id, 'allowance_added', "New allowance of " . $symbol . number_format($amount, 2) . " added: $description");
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
    // Fetch all allowances for the user
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

echo json_encode($response);
$conn->close();
?>