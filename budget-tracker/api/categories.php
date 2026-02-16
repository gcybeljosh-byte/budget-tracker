<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$method = $_SERVER['REQUEST_METHOD'];

// Ensure table exists
$createTable = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, name)
)";
mysqli_query($conn, $createTable);

// Ensure default categories exist for this user if none found
$checkCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories WHERE user_id = $user_id");
$countRow = mysqli_fetch_assoc($checkCount);
if ($countRow['count'] == 0) {
    $defaultCategories = ['Food', 'Transportation', 'Utilities', 'Entertainment', 'Shopping', 'Health', 'Education', 'Other'];
    foreach ($defaultCategories as $cat) {
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $cat);
        $stmt->execute();
        $stmt->close();
    }
}

switch ($method) {
    case 'GET':
        $stmt = $conn->prepare("SELECT id, name FROM categories WHERE user_id = ? ORDER BY name ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $categories]);
        break;

    case 'POST':
        $action = $_POST['action'] ?? 'add';
        $name = trim($_POST['name'] ?? '');

        if ($action === 'add') {
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                exit;
            }

            $stmt = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $name);
            if ($stmt->execute()) {
                if ($conn->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Category already exists']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Category ID is required']);
                exit;
            }

            // Check if category is in use
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM expenses e JOIN categories c ON e.category = c.name WHERE c.id = ? AND e.user_id = ?");
            $checkStmt->bind_param("ii", $id, $user_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result()->fetch_assoc();
            
            if ($checkResult['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete category that is currently in use by expenses']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
