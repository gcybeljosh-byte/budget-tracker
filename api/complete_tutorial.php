<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$page = $_GET['page'] ?? 'general';

// Fetch current tutorials seen
$stmt = $conn->prepare("SELECT page_tutorials_json FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($json);
$stmt->fetch();
$stmt->close();

$tutorials = json_decode($json, true) ?: [];
$tutorials[$page] = 1;
$new_json = json_encode($tutorials);

$stmt = $conn->prepare("UPDATE users SET page_tutorials_json = ? WHERE id = ?");
$stmt->bind_param("si", $new_json, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "Tutorial for $page marked as seen"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update tutorial status']);
}

$stmt->close();
$conn->close();
?>
