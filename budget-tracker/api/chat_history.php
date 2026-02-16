<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// Handle POST Request (Save Message)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['message']) || !isset($data['sender'])) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']);
        exit;
    }

    $message = trim($data['message']);
    $sender = $data['sender']; // 'user' or 'bot'

    // Basic Validation
    if (empty($message) || !in_array($sender, ['user', 'bot'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO ai_chat_history (user_id, message, sender) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $sender);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// Handle GET Request (Fetch History)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT message, sender, created_at FROM ai_chat_history WHERE user_id = ?";
    $params = ["i", $user_id];

    // Filter by session if requested for widget
    if (isset($_GET['mode']) && $_GET['mode'] === 'widget') {
        if (isset($_SESSION['login_time'])) {
            $sql .= " AND created_at >= ?";
            $params[0] .= "s";
            $params[] = $_SESSION['login_time'];
        } else {
            // Fallback for current session if not logged out yet: show all or limit?
            // Let's show all for now to avoid empty chat on immediate reload without re-login
        }
    }

    $sql .= " ORDER BY created_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $messages]);
    $stmt->close();
    exit;
}
// Handle DELETE Request (Clear History)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $stmt = $conn->prepare("DELETE FROM ai_chat_history WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete history']);
    }
    $stmt->close();
    exit;
}
?>
