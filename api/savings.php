<?php
session_start();
header("Content-Type: application/json");
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// --- Auto-Migration: Ensure necessary columns exist ---
$conn->query("CREATE TABLE IF NOT EXISTS savings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    source_type VARCHAR(50) DEFAULT 'Cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Ensure source_type exists if table already created
$checkCol = $conn->query("SHOW COLUMNS FROM savings LIKE 'source_type'");
if ($checkCol->num_rows == 0) {
    $conn->query("ALTER TABLE savings ADD COLUMN source_type VARCHAR(50) DEFAULT 'Cash' AFTER description");
}

// Ensure expense_source exists in expenses table as it's used in stats
$checkSource = $conn->query("SHOW COLUMNS FROM expenses LIKE 'expense_source'");
if ($checkSource->num_rows == 0) {
    $conn->query("ALTER TABLE expenses ADD COLUMN expense_source VARCHAR(50) DEFAULT 'Allowance'");
}

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
                $stmt = $conn->prepare("INSERT INTO savings (user_id, date, amount, description, source_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdss", $user_id, $date, $amount, $description, $source_type);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Savings added successfully'];
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
                $stmt = $conn->prepare("UPDATE savings SET amount = ?, date = ?, description = ?, source_type = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("dssii", $amount, $date, $description, $source_type, $id, $user_id);
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
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');
        $thisYear = date('Y');
        
        $stats = [
            'total' => 0,
            'monthly' => 0,
            'yearly' => 0
        ];
        
        // Total (Lifetime) - Net of Expenses
        $stmt = $conn->prepare("
            SELECT (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ?) - 
                   (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings') as total
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $stats['total'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        // Monthly - Net of Expenses
        $stmt = $conn->prepare("
            SELECT (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?) - 
                   (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings' AND DATE_FORMAT(date, '%Y-%m') = ?) as total
        ");
        $stmt->bind_param("isis", $user_id, $thisMonth, $user_id, $thisMonth);
        $stmt->execute();
        $stats['monthly'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        // Yearly - Net of Expenses
        $stmt = $conn->prepare("
            SELECT (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND YEAR(date) = ?) - 
                   (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings' AND YEAR(date) = ?) as total
        ");
        $stmt->bind_param("isis", $user_id, $thisYear, $user_id, $thisYear);
        $stmt->execute();
        $stats['yearly'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        $response = ['success' => true, 'data' => $stats];
    } else {
        $sql = "
            (SELECT id, date, amount, description, 'deposit' as type FROM savings WHERE user_id = ?)
            UNION ALL
            (SELECT id, date, amount, description, 'withdrawal' as type FROM expenses WHERE user_id = ? AND expense_source = 'Savings')
            ORDER BY date DESC, id DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $savings = [];
        while ($row = $result->fetch_assoc()) {
            $savings[] = $row;
        }
        $stmt->close();
        $response = ['success' => true, 'data' => $savings];
    }
}

echo json_encode($response);
$conn->close();
?>
