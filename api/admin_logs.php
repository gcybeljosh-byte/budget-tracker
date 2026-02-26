<?php
session_start();
header("Content-Type: application/json");
require_once '../includes/db.php';

// Security Check: Superadmin Only
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'fetch_user_logs') {
        $target_user_id = $_GET['user_id'] ?? 0;
        if ($target_user_id > 0) {
            $stmt = $conn->prepare("
                SELECT al.*, u.username, u.first_name, u.last_name 
                FROM activity_logs al 
                JOIN users u ON al.user_id = u.id 
                WHERE al.user_id = ? 
                ORDER BY al.id DESC 
                LIMIT 500
            ");
            $stmt->bind_param("i", $target_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $response = ['success' => true, 'data' => $logs];
        } else {
            $response = ['success' => false, 'message' => 'Missing user ID'];
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_user_logs') {
        $target_user_id = $_POST['user_id'] ?? 0;
        if ($target_user_id > 0) {
            $stmt = $conn->prepare("DELETE FROM activity_logs WHERE user_id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['id'], 'admin_delete_logs', "Deleted all logs for user ID $target_user_id");
                $response = ['success' => true, 'message' => 'Logs deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Database error during deletion'];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Missing user ID'];
        }
    }
}

echo json_encode($response);
$conn->close();
