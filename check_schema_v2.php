<?php
require 'includes/db.php';
$tables = ['allowances', 'expenses', 'savings'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo $row['Field'] . " ";
        }
    } else {
        echo "Error describing $table: " . $conn->error;
    }
    echo "\n";
}
