<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "budget_tracker";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = 1;

echo "=== WALLET DEBUG (User $user_id) ===\n\n";

// 1. Allowances
echo "--- ALLOWANCES ---\n";
$res = $conn->query("SELECT id, date, description, amount, source_type FROM allowances WHERE user_id = $user_id");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Date: {$row['date']} | Amount: {$row['amount']} | Source: {$row['source_type']} | Desc: {$row['description']}\n";
}

// 2. Expenses (All)
echo "\n--- EXPENSES ---\n";
$res = $conn->query("SELECT id, date, description, amount, source_type, expense_source FROM expenses WHERE user_id = $user_id");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Date: {$row['date']} | Amount: {$row['amount']} | Source: {$row['source_type']} | ExpSource: {$row['expense_source']} | Desc: {$row['description']}\n";
}

// 3. Savings
echo "\n--- SAVINGS ---\n";
$res = $conn->query("SELECT id, date, description, amount, source_type FROM savings WHERE user_id = $user_id");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Date: {$row['date']} | Amount: {$row['amount']} | Source: {$row['source_type']} | Desc: {$row['description']}\n";
}
