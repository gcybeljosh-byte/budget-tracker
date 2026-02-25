<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "sql312.infinityfree.com";
$user = "if0_41223873";
$pass = "Cybs1203";
$dbname = "if0_41223873_budget_tracker";

echo "Connecting to $host...\n";
$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully!\n";

$res = $conn->query("SELECT COUNT(*) as cnt FROM users");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Found " . $row['cnt'] . " users.\n";
} else {
    echo "Query failed: " . $conn->error . "\n";
}
