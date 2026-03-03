<?php
session_start();
require_once 'includes/db.php';

$user_id = $_SESSION['id'] ?? null;
if (!$user_id) {
    die("No session ID found.");
}

echo "User ID: " . $user_id . "\n";
echo "Current Date: " . date('Y-m-d H:i:s') . "\n";

$tables = ['allowances', 'expenses', 'savings'];
foreach ($tables as $table) {
    echo "\n--- $table ---\n";
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    echo "Count: $count\n";
    $stmt->close();

    if ($count > 0) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ? ORDER BY id DESC LIMIT 5");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
        $stmt->close();
    }
}
