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
    $nickname = trim($_POST['nickname'] ?? '');
    $categories_json = $_POST['selected_categories'] ?? '[]';
    $categories = json_decode($categories_json, true);

    // Ensure nickname column exists
    ensureColumnExists($conn, 'users', 'nickname', "VARCHAR(100) AFTER username");

    // Handle Profile Picture Upload
    $profile_path = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $fileName = $user_id . '_' . time() . '.' . $fileExtension;
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
            $profile_path = 'assets/uploads/profiles/' . $fileName;
        }
    }

    // 1. Update User Preferences and Onboarding Status
    $updateFields = "preferred_currency = ?, monthly_budget_goal = ?, onboarding_completed = 1";
    $params = [$currency, $budget_goal];
    $types = "sd";

    if (!empty($nickname)) {
        $updateFields .= ", nickname = ?";
        $params[] = $nickname;
        $types .= "s";
    }

    if ($profile_path) {
        $updateFields .= ", profile_picture = ?";
        $params[] = $profile_path;
        $types .= "s";
    }

    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare("UPDATE users SET $updateFields WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error during preparation: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update user profile: ' . $conn->error]);
        exit;
    }
    $stmt->close();

    // Update Session
    if (!empty($nickname)) $_SESSION['nickname'] = $nickname;
    if ($profile_path) $_SESSION['profile_picture'] = $profile_path;

    // Clear temporary registration data after successful onboarding
    unset($_SESSION['google_registered_password']);
    unset($_SESSION['temp_registration_password']);

    $_SESSION['user_currency'] = $currency; // Track currency in session

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
    if ($categories_json !== '[]') {
        // Clear existing categories first to ensure exact match with onboarding choices
        $conn->query("DELETE FROM categories WHERE user_id = $user_id");

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
    } else {
        // User explicitly chose NO categories
        $conn->query("DELETE FROM categories WHERE user_id = $user_id");
    }

    echo json_encode(['success' => true, 'message' => 'Onboarding completed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
