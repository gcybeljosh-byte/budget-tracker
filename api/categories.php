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

// Ensure default categories exist for this user ONLY IF onboarding is NOT completed
$userCheck = mysqli_query($conn, "SELECT onboarding_completed FROM users WHERE id = $user_id");
$userRow = mysqli_fetch_assoc($userCheck);
$isOnboardingCompleted = ($userRow && $userRow['onboarding_completed'] == 1);

if (!$isOnboardingCompleted) {
    $checkCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories WHERE user_id = $user_id");
    if ($checkCount) {
        $countRow = mysqli_fetch_assoc($checkCount);
        if ($countRow && $countRow['count'] == 0) {
            $defaultCategories = ['Food & Dining', 'Transportation', 'Rent & Utilities', 'Entertainment', 'Shopping', 'Healthcare', 'Education', 'Savings', 'Other'];
            foreach ($defaultCategories as $cat) {
                $stmt = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $cat);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Migration Logic: Move 'Food' to 'Other' once
if (!isset($_SESSION['migration_food_done'])) {
    $checkFood = mysqli_query($conn, "SELECT id FROM categories WHERE user_id = $user_id AND name = 'Food'");
    if ($checkFood && mysqli_num_rows($checkFood) > 0) {
        // 1. Ensure 'Other' exists
        mysqli_query($conn, "INSERT IGNORE INTO categories (user_id, name) VALUES ($user_id, 'Other')");

        // 2. Update expenses (Suppress errors if table/column missing)
        @mysqli_query($conn, "UPDATE expenses SET category = 'Other' WHERE user_id = $user_id AND category = 'Food'");

        // 3. Delete 'Food' category
        mysqli_query($conn, "DELETE FROM categories WHERE user_id = $user_id AND name = 'Food'");

        $_SESSION['migration_food_done'] = true;
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
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Category already exists or could not be added']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
            }
            $stmt->close();
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
