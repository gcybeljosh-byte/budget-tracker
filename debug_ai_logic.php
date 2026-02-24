<?php
// debug_ai_logic.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/AiHelper.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Database connected.\n";

$user_id = 1; // Assuming user ID 1 exists
$userMessage = "Hello";

echo "Initializing AiHelper...\n";
try {
    $aiHelper = new AiHelper($conn, $user_id);
    echo "AiHelper initialized.\n";

    echo "Generating prompt...\n";
    $prompt = $aiHelper->generateSystemPrompt();
    echo "Prompt generated (Length: " . strlen($prompt) . ")\n";

    // echo "System Prompt Preview:\n" . substr($prompt, 0, 500) . "...\n";

    echo "Fetching response (this calls the API)...\n";
    $response = $aiHelper->getResponse($userMessage);

    echo "Response received:\n";
    print_r($response);
} catch (Throwable $e) {
    echo "FATAL ERROR CAUGHT: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
