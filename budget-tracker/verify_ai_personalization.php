<?php
// verify_ai_personalization.php
require_once 'includes/db.php';
require_once 'includes/AiHelper.php';

echo "Verifying AI Personalization Logic...\n\n";

// 1. Create a test user with specific personalization
$testUsername = 'ai_personal_test_' . time();
$password = password_hash('Password123!', PASSWORD_DEFAULT);
$currency = 'USD';
$goal = 2500.00;

echo "1. Creating test user with currency=$currency and goal=$goal...\n";
$stmt = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, contact_number, preferred_currency, monthly_budget_goal, onboarding_completed) VALUES (?, ?, 'AI', 'Test', ?, '000', ?, ?, 1)");
$email = $testUsername . "@example.com";
$stmt->bind_param("ssssdd", $testUsername, $password, $email, $currency, $goal);
$stmt->execute();
$userId = $stmt->insert_id;
$stmt->close();

echo "   User created with ID: $userId\n";

// 2. Initialize AiHelper and generate prompt
echo "2. Initializing AiHelper and generating system prompt...\n";
$ai = new AiHelper($conn, $userId);
$prompt = $ai->generateSystemPrompt();

// 3. Verify Prompt Contents
echo "3. Verifying prompt content...\n";

$checks = [
    'Monthly Budget Goal: $2,500.00' => "/Monthly Budget Goal: \\$2,500\\.00/",
    'Preferred Currency: USD ($)' => "/Preferred Currency: USD \\(\\$\\)/",
    'All currency values must use "$"' => "/All currency values must use \"\\$\"/",
    'formatted with 2 decimal places (e.g., $1,234.56)' => "/formatted with 2 decimal places \\(e.g., \\$1,234\\.56\\)/"
];

foreach ($checks as $desc => $pattern) {
    if (preg_match($pattern, $prompt)) {
        echo "   [SUCCESS] Found: $desc\n";
    } else {
        echo "   [FAILED] Could NOT find: $desc\n";
    }
}

// 4. Verify Action Message
echo "4. Verifying action message currency mapping...\n";
// Simulate an expense addition return message logic manually since we can't easily trigger it without real POST
// But we actually just need to see if AiHelper can get the symbol
// We'll call a private method via reflection or just trust the code if simple enough, 
// but even better, just run a simulateResponse check.

$simResponse = $ai->getResponse("check my balance"); // Should use simulation mode if no key
echo "   Simulation check for 'balance': $simResponse\n";
if (strpos($simResponse, '$') !== false) {
     echo "   [SUCCESS] Simulation response used '$' correctly.\n";
} else {
     echo "   [FAILED] Simulation response did not use '$'.\n";
}

// Cleanup
$conn->query("DELETE FROM users WHERE id = $userId");
echo "\nVerification complete.\n";
?>
