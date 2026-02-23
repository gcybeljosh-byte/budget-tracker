<?php
require 'includes/db.php';

echo "--- Allowances with empty source_type ---\n";
$res = $conn->query("SELECT * FROM allowances WHERE source_type IS NULL OR source_type = ''");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Expenses with empty source_type ---\n";
$res = $conn->query("SELECT * FROM expenses WHERE source_type IS NULL OR source_type = ''");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
