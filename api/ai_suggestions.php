<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// Pool of possible suggestions
$prompts = [
    ['icon' => '💰', 'label' => 'Balance', 'text' => 'How much is my total balance?'],
    ['icon' => '📊', 'label' => 'Monthly Summary', 'text' => 'Summarize my expenses this month'],
    ['icon' => '📝', 'label' => 'Add Expense', 'text' => 'Help me add a new expense'],
    ['icon' => '🛒', 'label' => 'Spending Habit', 'text' => 'What is my top spending category?'],
    ['icon' => '💡', 'label' => 'Savings Tip', 'text' => 'Give me a tip to save more money'],
    ['icon' => '📅', 'label' => 'Weekly Report', 'text' => 'Show my weekly financial summary'],
    ['icon' => '⚠️', 'label' => 'Overspending', 'text' => 'Am I overspending on anything?']
];

// Shuffle and pick 3
shuffle($prompts);
$selected = array_slice($prompts, 0, 3);

echo json_encode(['success' => true, 'data' => $selected]);
?>
