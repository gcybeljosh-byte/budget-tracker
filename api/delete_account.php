<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// Perform Deletion in a Transaction for safety
$conn->begin_transaction();

try {
    // 2. Simply mark the user account as deleted
    $stmt = $conn->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // 3. Clear session and logout
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to delete account: ' . $e->getMessage()]);
}

$conn->close();
