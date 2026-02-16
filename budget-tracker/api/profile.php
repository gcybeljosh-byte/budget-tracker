<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT username, first_name, last_name, email, contact_number, profile_picture, currency, ai_tone, notif_budget, notif_low_balance, security_question FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'info'; // 'info' or 'username'
    
    if ($action === 'username') {
        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username cannot be empty.']);
            exit;
        }

        // Check for username uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already taken.']);
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $user_id);
        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            echo json_encode(['success' => true, 'message' => 'Username updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating username.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'security_question') {
        $question = $_POST['security_question'] ?? '';
        $answer = trim($_POST['security_answer'] ?? '');
        
        if (empty($question) || empty($answer)) {
            echo json_encode(['success' => false, 'message' => 'Question and answer are required.']);
            exit;
        }

        // We store the answer as a hash for security (case-insensitive by default if we strtolower it)
        $hashedAnswer = password_hash(strtolower($answer), PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET security_question = ?, security_answer = ? WHERE id = ?");
        $stmt->bind_param("ssi", $question, $hashedAnswer, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Security recovery set successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving security recovery.']);
        }
        $stmt->close();
        exit;
    }

    // Default 'info' action
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
        exit;
    }

    $profile_pic_path = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
            exit;
        }

        if ($file['size'] > $maxSize) {
             echo json_encode(['success' => false, 'message' => 'File size exceeds 2MB limit.']);
             exit;
        }

        $uploadDir = '../uploads/profile_pics/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('profile_', true) . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        $dbPath = 'uploads/profile_pics/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $profile_pic_path = $dbPath;
        }
    }

    if ($profile_pic_path) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, contact_number = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $contact_number, $profile_pic_path, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, contact_number = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $contact_number, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        if ($profile_pic_path) $_SESSION['profile_picture'] = $profile_pic_path;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully.', 
            'profile_picture' => $profile_pic_path,
            'first_name' => $first_name,
            'last_name' => $last_name
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating profile.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
