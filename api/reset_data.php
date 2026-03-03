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
    // List of tables to clear for this user
    $tables = [
        'expenses',
        'allowances',
        'savings',
        'bills',
        'goals',
        'journal',
        'achievements_unlocked',
        'streaks',
        'notifications',
        'budget_limits'
    ];

    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Reset last_forwarded_month in users table
    $stmt = $conn->prepare("UPDATE users SET last_forwarded_month = NULL WHERE id = ?");
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
