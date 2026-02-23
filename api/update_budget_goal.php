<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['monthly_budget_goal'])) {
    echo json_encode(['success' => false, 'message' => 'Missing monthly_budget_goal']);
    exit;
}

$goal = floatval($data['monthly_budget_goal']);

if ($goal < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid goal amount']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET monthly_budget_goal = ? WHERE id = ?");
$stmt->bind_param("di", $goal, $user_id);

if ($stmt->execute()) {
    $_SESSION['monthly_budget_goal'] = $goal;
    echo json_encode(['success' => true, 'message' => 'Monthly budget goal updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
