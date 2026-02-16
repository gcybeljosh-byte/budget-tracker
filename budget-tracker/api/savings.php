<?php
session_start();
header("Content-Type: application/json");
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// --- Auto-Migration: Create savings table if it doesn't exist ---
$conn->query("CREATE TABLE IF NOT EXISTS savings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $date = $_POST['date'] ?? date('Y-m-d');
            $description = $_POST['description'] ?? 'Savings';
            $amount = $_POST['amount'] ?? 0;
            
            if ($amount > 0) {
                $stmt = $conn->prepare("INSERT INTO savings (user_id, date, amount, description) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isds", $user_id, $date, $amount, $description);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Savings added successfully'];
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

            if ($id > 0 && $amount > 0) {
                $stmt = $conn->prepare("UPDATE savings SET amount = ?, date = ?, description = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("dssii", $amount, $date, $description, $id, $user_id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Savings updated successfully'];
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
        
        // Total (Lifetime)
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM savings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['total'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        // Monthly
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM savings WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
        $stmt->bind_param("is", $user_id, $thisMonth);
        $stmt->execute();
        $stats['monthly'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        // Yearly
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM savings WHERE user_id = ? AND YEAR(date) = ?");
        $stmt->bind_param("is", $user_id, $thisYear);
        $stmt->execute();
        $stats['yearly'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        $response = ['success' => true, 'data' => $stats];
    } else {
        $stmt = $conn->prepare("SELECT * FROM savings WHERE user_id = ? ORDER BY date DESC, id DESC");
        $stmt->bind_param("i", $user_id);
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
