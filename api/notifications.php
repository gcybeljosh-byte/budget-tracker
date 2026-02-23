<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/NotificationHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$notificationHelper = new NotificationHelper($conn);

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_read') {
    if ($notificationHelper->markAllAsRead($user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
    }
} elseif ($action === 'count') {
    $unreadCount = count($notificationHelper->getUnreadNotifications($user_id));
    echo json_encode(['success' => true, 'count' => $unreadCount]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
