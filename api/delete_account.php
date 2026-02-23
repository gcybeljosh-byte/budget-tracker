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
    // 1. Delete child records first (some have CASCADE, but let's be thorough)
    
    // Journal Lines depend on Journals
    $conn->query("DELETE FROM journal_lines WHERE journal_id IN (SELECT id FROM journals WHERE user_id = $user_id)");
    $conn->query("DELETE FROM journals WHERE user_id = $user_id");
    
    // Financial Records
    $conn->query("DELETE FROM expenses WHERE user_id = $user_id");
    $conn->query("DELETE FROM allowances WHERE user_id = $user_id");
    $conn->query("DELETE FROM savings WHERE user_id = $user_id");
    
    // User Data & Settings
    $conn->query("DELETE FROM categories WHERE user_id = $user_id");
    $conn->query("DELETE FROM ai_chat_history WHERE user_id = $user_id");
    $conn->query("DELETE FROM notifications WHERE user_id = $user_id");
    
    // 2. Finally delete the user account
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
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
?>
