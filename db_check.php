<?php
require 'includes/db.php';
$tables = ['allowances', 'expenses', 'savings', 'categories'];
foreach ($tables as $t) {
    echo "Table: $t\n";
    $res = $conn->query("SHOW COLUMNS FROM $t");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "  Error: " . $conn->error . "\n";
    }
}
