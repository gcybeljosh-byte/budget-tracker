<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/AiHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// --- Self-Healing: Ensure chat history table and columns exist ---
$conn->query("CREATE TABLE IF NOT EXISTS ai_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    response TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Migration: If table exists but missing 'response' column
$check = $conn->query("SHOW COLUMNS FROM ai_chat_history LIKE 'response'");
if ($check && $check->num_rows == 0) {
    $conn->query("ALTER TABLE ai_chat_history ADD COLUMN response TEXT DEFAULT NULL AFTER message");
}

// Migration: Remove old 'sender' column if it exists
$check = $conn->query("SHOW COLUMNS FROM ai_chat_history LIKE 'sender'");
if ($check && $check->num_rows > 0) {
    $conn->query("ALTER TABLE ai_chat_history DROP COLUMN sender");
}

// Handle POST Request (Delete Fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $action = $json['action'] ?? $_POST['action'] ?? '';

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM ai_chat_history WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'History cleared']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        $stmt->close();
        exit;
    }
}

// Handle GET Request (Fetch History)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $aiHelper = new AiHelper($conn, $user_id);
    $aiHelper->enforceChatTimeout(5);

    $sql = "SELECT message, response, created_at FROM ai_chat_history WHERE user_id = ?";
    $params = ["i", $user_id];

    // Filter by session if requested for widget
    if (isset($_GET['mode']) && $_GET['mode'] === 'widget') {
        if (isset($_SESSION['login_time'])) {
            $sql .= " AND created_at >= ?";
            $params[0] .= "s";
            $params[] = $_SESSION['login_time'];
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
