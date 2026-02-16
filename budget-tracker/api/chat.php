<?php
// api/chat.php

session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/AiHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Receive User Message
    $data = json_decode(file_get_contents('php://input'), true);
    $userMessage = isset($data['message']) ? trim($data['message']) : '';

    if (empty($userMessage)) {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit;
    }

    // 2. Save User Message
    $stmt = $conn->prepare("INSERT INTO ai_chat_history (user_id, message, sender) VALUES (?, ?, 'user')");
    $stmt->bind_param("is", $user_id, $userMessage);
    $stmt->execute();
    $stmt->close();

    // 3. Generate AI Response
    $aiHelper = new AiHelper($conn, $user_id);
    
    // The AiHelper::getResponse method determines whether to use Real API or Simulation
    // based on the config.php settings.
    $aiResponse = $aiHelper->getResponse($userMessage);
    
    $responseMessage = '';
    $actionPerformed = false;
    $actionType = '';
    
    if (is_array($aiResponse)) {
        $responseMessage = $aiResponse['message'];
        $actionPerformed = $aiResponse['action_performed'] ?? false;
        $actionType = $aiResponse['action_type'] ?? '';
    } else {
        $responseMessage = $aiResponse;
    }

    // 4. Save AI Response
    $stmt = $conn->prepare("INSERT INTO ai_chat_history (user_id, message, sender) VALUES (?, ?, 'bot')");
    $stmt->bind_param("is", $user_id, $responseMessage);
    $stmt->execute();
    $stmt->close();

    // 5. Return Response
    echo json_encode([
        'success' => true, 
        'data' => [
            'message' => $responseMessage,
            'sender' => 'bot',
            'created_at' => date('Y-m-d H:i:s'),
            'action_performed' => $actionPerformed,
            'action_type' => $actionType
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>
