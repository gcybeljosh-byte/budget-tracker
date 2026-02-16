<?php
// test_ai.php
session_start();
$_SESSION['id'] = 1; // Simulate logged in user

require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/AiHelper.php';

// Force Gem provider for test if not set (or use simulation)
// define('AI_PROVIDER', 'simulation'); // Uncomment to test fallback

$ai = new AiHelper($conn, 1);

// Mock a user message that should trigger a journal creation
$msg = "Create a journal entry for today. I saved 500 pesos and felt productive.";

// To test strictly without burning API credits, we can inspect detectIntent or just run it.
// But since we want to test the JSON parsing which comes from LLM, we might need to mock the LLM response 
// OR just trust the logic if we don't want to make an actual API call.
// Let's make an actual call if API key is set, otherwise fallback.

echo "Testing Message: $msg\n";
$response = $ai->getResponse($msg);

print_r($response);

// Check if journal was created
$stmt = $conn->prepare("SELECT * FROM journals ORDER BY id DESC LIMIT 1");
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo "\nLatset Journal Entry:\n";
print_r($res);
?>
