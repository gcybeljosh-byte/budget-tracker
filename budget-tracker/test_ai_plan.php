<?php
// test_ai_plan.php
session_start();
$_SESSION['id'] = 1;

require_once 'includes/db.php';
require_once 'includes/config.php';
require_once 'includes/AiHelper.php';

$ai = new AiHelper($conn, 1);

// Mock a budget plan request
$msg = "Create a weekly budget plan. 300 pesos per day for food, 100 pesos for transport.";
// This implies 7 days * 300 = 2100 food, 7 * 100 = 700 transport.
// Or maybe just "daily limits". The prompt says "Generate 7 entries of 500 for Food" if they say "500/day".
// Let's see what it does.

echo "Testing Message: $msg\n";
$response = $ai->getResponse($msg);

print_r($response);

// Check if expenses were created (or allowances? Plan usually means "Budget" as in "Allowance allocation" or "Planned Expense"?)
// The prompt says "Convert plans directly into transaction entries".
// If it creates expenses, it means "I expect to spend this".
// If it creates allowances, it means "I am allocating this".
// Let's see what actions it performed.
?>
