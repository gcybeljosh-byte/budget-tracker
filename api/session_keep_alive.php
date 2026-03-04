<?php
// api/session_keep_alive.php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$current_time = date('Y-m-d H:i:s');

// Update last activity to keep session alive in DB
$stmt = $conn->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
$stmt->bind_param("si", $current_time, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
exit;
