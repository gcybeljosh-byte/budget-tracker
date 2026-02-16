<?php
// debug_sync.php
require_once 'includes/db.php';

echo "--- Table Structure: expenses ---\n";
$result = $conn->query("DESCRIBE expenses");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Recent Savings ---\n";
$result = $conn->query("SELECT * FROM savings ORDER BY id DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Recent Expenses (Linked to Savings) ---\n";
$result = $conn->query("SELECT * FROM expenses WHERE saving_id IS NOT NULL ORDER BY id DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Dashboard Stats Emulation ---\n";
$user_id = 1; // Assuming user ID 1 for debug
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM allowances WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
echo "Total Allowance: " . $stmt->get_result()->fetch_assoc()['total'] . "\n";

$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
echo "Total Expenses: " . $stmt->get_result()->fetch_assoc()['total'] . "\n";
?>
