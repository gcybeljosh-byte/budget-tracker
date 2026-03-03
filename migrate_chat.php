<?php
require_once 'includes/db.php';

// Check if response column exists
$result = $conn->query("SHOW COLUMNS FROM ai_chat_history LIKE 'response'");
if ($result->num_rows == 0) {
    echo "Adding 'response' column..." . PHP_EOL;
    $conn->query("ALTER TABLE ai_chat_history ADD COLUMN response TEXT DEFAULT NULL AFTER message");
}

// Optionally drop 'sender' if it exists and we're committed to the new schema
$result = $conn->query("SHOW COLUMNS FROM ai_chat_history LIKE 'sender'");
if ($result->num_rows > 0) {
    echo "Dropping 'sender' column..." . PHP_EOL;
    $conn->query("ALTER TABLE ai_chat_history DROP COLUMN sender");
}

echo "Database migration complete." . PHP_EOL;
