<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

// Security Check: Superadmin only
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Auto-migrate: Ensure soft-delete column exists
ensureColumnExists($conn, 'users', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');

$action = $_POST['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $conn->prepare(
        "SELECT id, username, first_name, last_name, email, contact_number, role, status, profile_picture, created_at, deleted_at
         FROM users
         WHERE deleted_at IS NOT NULL
         ORDER BY deleted_at DESC"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $users]);
} elseif ($action === 'restore') {
    $user_id = intval($_POST['user_id'] ?? 0);
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE users SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'User restored successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found in recycle bin']);
    }
    $stmt->close();
} elseif ($action === 'permanent_delete') {
    $user_id = intval($_POST['user_id'] ?? 0);
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM journal_lines WHERE journal_id IN (SELECT id FROM journals WHERE user_id = $user_id)");
        $conn->query("DELETE FROM journals WHERE user_id = $user_id");
        $conn->query("DELETE FROM expenses WHERE user_id = $user_id");
        $conn->query("DELETE FROM allowances WHERE user_id = $user_id");
        $conn->query("DELETE FROM savings WHERE user_id = $user_id");
        $conn->query("DELETE FROM categories WHERE user_id = $user_id");
        $conn->query("DELETE FROM ai_chat_history WHERE user_id = $user_id");
        $conn->query("DELETE FROM notifications WHERE user_id = $user_id");
        $conn->query("DELETE FROM financial_goals WHERE user_id = $user_id");
        $conn->query("DELETE FROM bills WHERE user_id = $user_id");

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User permanently deleted']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
