<?php
session_start();
header('Content-Type: application/json');
require_once '../../includes/db.php';

// Only Superadmins can manage permissions
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'get') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT permissions FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $permissions = [];
    if ($res && $res['permissions']) {
        $permissions = json_decode($res['permissions'], true) ?? [];
    }

    echo json_encode(['success' => true, 'permissions' => $permissions]);
    exit;
}

if ($action === 'update') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $permissions_json = $_POST['permissions'] ?? '{}';

    // Validate JSON
    $decoded = json_decode($permissions_json, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid permissions format.']);
        exit;
    }

    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }

    // Prevent Superadmin from accidentally removing their own permissions
    if ($user_id === $_SESSION['id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot modify your own Superadmin permissions.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET permissions = ? WHERE id = ?");
    $stmt->bind_param("si", $permissions_json, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Permissions updated successfully.']);
        logActivity($conn, $_SESSION['id'], 'update_permissions', "Updated permissions for user ID $user_id");
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update permissions: ' . $conn->error]);
    }

    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
$conn->close();
