<?php
require 'includes/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = 1; // Assuming primary user for debug

echo "=== WALLET DEBUG (User $user_id) ===\n\n";

// 1. Allowances
echo "--- ALLOWANCES ---\n";
$res = $conn->query("SELECT source_type, SUM(amount) as total FROM allowances WHERE user_id = $user_id GROUP BY source_type");
while ($row = $res->fetch_assoc()) {
    echo "{$row['source_type']}: {$row['total']}\n";
}

// 2. Expenses (Source: Allowance)
echo "\n--- EXPENSES (from Allowance) ---\n";
$res = $conn->query("SELECT source_type, SUM(amount) as total FROM expenses WHERE user_id = $user_id AND expense_source = 'Allowance' GROUP BY source_type");
while ($row = $res->fetch_assoc()) {
    echo "{$row['source_type']}: {$row['total']}\n";
}

// 3. Savings Deposits
echo "\n--- SAVINGS DEPOSITS ---\n";
$res = $conn->query("SELECT source_type, SUM(amount) as total FROM savings WHERE user_id = $user_id GROUP BY source_type");
while ($row = $res->fetch_assoc()) {
    echo "{$row['source_type']}: {$row['total']}\n";
}

// 4. Expenses (Source: Savings / Withdrawals)
echo "\n--- EXPENSES (from Savings / Withdrawals) ---\n";
$res = $conn->query("SELECT source_type, SUM(amount) as total FROM expenses WHERE user_id = $user_id AND expense_source = 'Savings' GROUP BY source_type");
while ($row = $res->fetch_assoc()) {
    echo "{$row['source_type']}: {$row['total']}\n";
}

// 5. Financial Goals
echo "\n--- FINANCIAL GOALS (saved_amount) ---\n";
$res = $conn->query("SELECT title, saved_amount FROM financial_goals WHERE user_id = $user_id");
while ($row = $res->fetch_assoc()) {
    echo "{$row['title']}: {$row['saved_amount']}\n";
}

// 6. Savings vs Goals Check
echo "\n--- SAVINGS TABLE VS GOALS TOTAL ---\n";
$savings_total = $conn->query("SELECT SUM(amount) FROM savings WHERE user_id = $user_id")->fetch_row()[0];
$goals_total = $conn->query("SELECT SUM(saved_amount) FROM financial_goals WHERE user_id = $user_id")->fetch_row()[0];
echo "Savings Table Total: $savings_total\n";
echo "Goals Saved Amount: $goals_total\n";
