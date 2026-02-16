<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$action = $_POST['action'] ?? 'preferences';

switch ($action) {
    case 'verify_password':
        $password = $_POST['password'] ?? '';
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(['success' => true, 'message' => 'Password verified']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        }
        break;

    case 'preferences':
    default:
        $currency = $_POST['currency'] ?? 'PHP';
        $ai_tone = $_POST['ai_tone'] ?? 'Professional';
        $notif_budget = isset($_POST['notif_budget']) ? 1 : 0;
        $notif_low_balance = isset($_POST['notif_low_balance']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE users SET currency = ?, ai_tone = ?, notif_budget = ?, notif_low_balance = ? WHERE id = ?");
        $stmt->bind_param("ssiii", $currency, $ai_tone, $notif_budget, $notif_low_balance, $user_id);

        if ($stmt->execute()) {
            $_SESSION['user_currency'] = $currency;
            echo json_encode(['success' => true, 'message' => 'Preferences updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update preferences']);
        }
        $stmt->close();
        break;
}

$conn->close();
?>
