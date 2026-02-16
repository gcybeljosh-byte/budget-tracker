<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency = $_POST['currency'] ?? 'PHP';
    $budget_goal = $_POST['budget_goal'] ?? 0;
    $categories_json = $_POST['selected_categories'] ?? '[]';
    $categories = json_decode($categories_json, true);

    // 1. Update User Preferences and Onboarding Status
    $stmt = $conn->prepare("UPDATE users SET preferred_currency = ?, monthly_budget_goal = ?, onboarding_completed = 1 WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error during preparation: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("sdi", $currency, $budget_goal, $user_id);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update user profile: ' . $conn->error]);
        exit;
    }
    $stmt->close();

    // 2. Ensure Categories Table Exists
    $createTable = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_category (user_id, name)
    )";
    mysqli_query($conn, $createTable);

    // 3. Insert Selected Categories
    if (!empty($categories)) {
        $stmt_cat = $conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
        if ($stmt_cat) {
            foreach ($categories as $cat_name) {
                $cat_name = trim($cat_name);
                if (!empty($cat_name)) {
                    $stmt_cat->bind_param("is", $user_id, $cat_name);
                    $stmt_cat->execute();
                }
            }
            $stmt_cat->close();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Onboarding completed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
