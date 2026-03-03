<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// Check for any potential errors in db.php include
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Delete child records first to avoid foreign key constraints
    $conn->query("DELETE FROM journal_lines WHERE journal_id IN (SELECT id FROM journals WHERE user_id = $user_id)");
    $conn->query("DELETE FROM journal_tag_relations WHERE journal_id IN (SELECT id FROM journals WHERE user_id = $user_id)");

    // 2. Clear user-specific data from other tables
    $tables = [
        'journals',
        'journal_tags',
        'expenses',
        'allowances',
        'savings',
        'bills',
        'goals',
        'user_achievements',
        'user_streaks',
        'notifications',
        'budget_limits',
        'ai_chat_history',
        'categories'
    ];

    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 3. Reset user-level counters and preferences
    $stmt = $conn->prepare("UPDATE users SET last_forwarded_month = NULL, monthly_budget_goal = 0 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    logActivity($conn, $user_id, 'data_reset', 'User performed a full financial data reset');

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Your financial data has been completely cleared.']);
} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Process failed: ' . $e->getMessage()]);
}
$conn->close();
