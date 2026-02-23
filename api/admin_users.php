<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

// Security Check: Superadmin or Admin
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update') {
    $user_id = $_POST['user_id'] ?? null;
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_role = $_POST['role'] ?? 'user';

    // Fetch existing user data to check permissions
    $checkStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $existingUser = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$existingUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Permission Logic: Only superadmin can change roles
    if ($_SESSION['role'] !== 'superadmin' && $existingUser['role'] !== $new_role) {
        echo json_encode(['success' => false, 'message' => 'Only Superadmins can change user roles']);
        exit;
    }

    // Permission Logic: Admin cannot edit Superadmin or other Admin basic info
    if ($_SESSION['role'] === 'admin' && in_array($existingUser['role'], ['superadmin', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Admins cannot modify other Administrative accounts']);
        exit;
    }

    // Permission Logic: Only Superadmin can update passwords
    $password = $_POST['password'] ?? null;
    if ($password && $_SESSION['role'] !== 'superadmin' && $password !== $existingUser['plaintext_password']) {
        echo json_encode(['success' => false, 'message' => 'Only Superadmins can modify passwords']);
        exit;
    }

    if (!$user_id || empty($first_name) || empty($last_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Required fields (Name/Email) are missing']);
        exit;
    }

    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already in use by another user']);
        exit;
    }
    $stmt->close();

    // Prepare update query - Only Superadmin can update role and password
    if ($_SESSION['role'] === 'superadmin') {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, password = ?, plaintext_password = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $new_role, $password, $password, $user_id);
    } else {
        // Admin can only update basic info of standard users
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    $stmt->close();
} elseif ($action === 'delete') {
    $user_id = $_POST['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    // Permission Logic: Only superadmin can delete
    if ($_SESSION['role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Only Superadmins can delete accounts']);
        exit;
    }

    // Prevent self-deletion
    if ($user_id == $_SESSION['id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit;
    }

    // Delete user (and related data via ON DELETE CASCADE if configured, or manually if not)
    // Assuming foreign keys are set up to cascade or we just delete the user for now.
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
    $stmt->close();
} elseif ($action === 'request_superadmin') {
    $target_id = $_POST['user_id'] ?? null;
    $target_name = trim($_POST['user_name'] ?? 'Target User');

    if (!$target_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid target user ID']);
        exit;
    }

    // Permission Logic: Only Admins can make this request (Superadmins don't need to)
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only Administrative accounts can perform this request']);
        exit;
    }

    $description = "Admin requested to modify Administrative account: $target_name (ID: $target_id)";
    logActivity($conn, $_SESSION['id'], 'admin_request', $description);

    echo json_encode(['success' => true, 'message' => 'Your request has been logged and sent to the Superadmin for review.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
