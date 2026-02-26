<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

// Security Check: Only Superadmins can manage system settings
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_maintenance':
        $status = $_POST['status'] ?? 'false';
        $status = ($status === 'true') ? 'true' : 'false';

        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
        $stmt->bind_param("s", $status);

        if ($stmt->execute()) {
            $msg = ($status === 'true') ? "System is now under maintenance." : "System is now live.";
            logActivity($conn, $_SESSION['id'], 'system_toggle', $msg);
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update system settings.']);
        }
        $stmt->close();
        break;

    case 'get_status':
        $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
        $status = ($result && $row = $result->fetch_assoc()) ? $row['setting_value'] : 'false';
        echo json_encode(['success' => true, 'status' => $status]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

$conn->close();
