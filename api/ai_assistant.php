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

// ── Maintenance Mode Guard ──────────────────────────────────────────────────
// Superadmin bypasses maintenance so they can verify the AI is working.
$userRole = $_SESSION['role'] ?? 'user';
if (defined('AI_MAINTENANCE_MODE') && AI_MAINTENANCE_MODE && $userRole !== 'superadmin') {
    echo json_encode([
        'success' => false,
        'message' => '🔧 The AI Help Desk is currently under scheduled maintenance. It will be back shortly!'
    ]);
    exit;
}

$user_id = $_SESSION['id'];

// ── GET Actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'count_prompts') {
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) FROM ai_chat_history WHERE user_id = ? AND DATE(created_at) = ?");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'count' => $count,
            'limit' => 10
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Receive User Message
    $data = json_decode(file_get_contents('php://input'), true);
    $userMessage = isset($data['message']) ? trim($data['message']) : '';

    if (empty($userMessage)) {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit;
    }

    // ── AI Prompt Limit Guard (10 prompts per day) ──────────────────────────
    // Superadmin is exempt from limits for testing
    if ($userRole !== 'superadmin') {
        $today = date('Y-m-d');
        $stmtLimit = $conn->prepare("SELECT COUNT(*) FROM ai_chat_history WHERE user_id = ? AND DATE(created_at) = ?");
        $stmtLimit->bind_param("is", $user_id, $today);
        $stmtLimit->execute();
        $stmtLimit->bind_result($promptCount);
        $stmtLimit->fetch();
        $stmtLimit->close();

        if ($promptCount >= 10) {
            echo json_encode([
                'success' => false,
                'message' => '🚀 **Daily Limit Reached!** You have used your 10 free AI prompts for today. Upgrade your knowledge by reviewing your History Log, or come back tomorrow for more advice!'
            ]);
            exit;
        }
    }

    // --- Self-Healing: Ensure chat history table and columns exist ---
    $conn->query("CREATE TABLE IF NOT EXISTS ai_chat_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        response TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Migration: If table exists but missing 'response' column (from older version)
    $check = $conn->query("SHOW COLUMNS FROM ai_chat_history LIKE 'response'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE ai_chat_history ADD COLUMN response TEXT DEFAULT NULL AFTER message");
    }

    // Migration: Remove old 'sender' column if it exists
    $check = $conn->query("SHOW COLUMNS FROM ai_chat_history LIKE 'sender'");
    if ($check && $check->num_rows > 0) {
        $conn->query("ALTER TABLE ai_chat_history DROP COLUMN sender");
    }

    // 2. Save User Message and Get AI Response
    $aiHelper = new AiHelper($conn, $user_id);

    // First prompt fix: Ensure history is enforced before fetching response
    $aiHelper->enforceChatTimeout(5);

    // Generate AI Response
    $aiResponse = $aiHelper->getResponse($userMessage);
    $responseMessage = is_array($aiResponse) ? ($aiResponse['message'] ?? '') : (string)$aiResponse;

    if (empty($responseMessage)) {
        $responseMessage = "I'm sorry, I couldn't generate a response. Please try again.";
    }

    // 3. Save Message and Response in one row (better for conversation flow)
    // ONLY save if it was a successful AI response (not a quota/technical error)
    // This ensures that technical errors don't count towards the user's 10-prompt daily limit.
    if (!isset($aiResponse['error']) || $aiResponse['error'] !== true) {
        $stmt = $conn->prepare("INSERT INTO ai_chat_history (user_id, message, response) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $userMessage, $responseMessage);
        $stmt->execute();
        $stmt->close();
    }

    // 5. Return Response
    echo json_encode([
        'success' => true,
        'data'    => [
            'message'    => $responseMessage,
            'sender'     => 'bot',
            'created_at' => date('Y-m-d H:i:s'),
        ],
        'debug_info' => [
            'api_key_hint' => substr(defined('AI_API_KEY') ? AI_API_KEY : '', 0, 4) . '...',
            'proxy_active' => (defined('AI_PROXY_URL') && !empty(AI_PROXY_URL)) ? 'Yes' : 'No'
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
