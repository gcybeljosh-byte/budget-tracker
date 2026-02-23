<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

// Security Check: Only Admin/Superadmin can access
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$user_id || !$status || !in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Fetch user role to check permissions
$checkStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$existingUser = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$existingUser) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Permission: Only Superadmin can toggle Admin/Superadmin status
if ($_SESSION['role'] !== 'superadmin' && in_array($existingUser['role'], ['superadmin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Only Superadmins can toggle administrative account status']);
    exit;
}

// Cannot deactivate self
if ($user_id == $_SESSION['id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "User account set to $status"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
}

$stmt->close();
$conn->close();
?>
