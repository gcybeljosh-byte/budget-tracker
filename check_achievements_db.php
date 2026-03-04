<?php
require_once 'includes/db.php';

$tables = ['achievements', 'user_achievements', 'user_streaks'];

foreach ($tables as $table) {
    echo "\n--- Table: $table ---\n";
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows == 0) {
        echo "Table $table DOES NOT EXIST.\n";
        continue;
    }

    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    $res = $conn->query("SELECT COUNT(*) as count FROM $table");
    $row = $res->fetch_assoc();
    echo "Total Rows: " . $row['count'] . "\n";

    if ($table === 'achievements') {
        $res = $conn->query("SELECT * FROM $table");
        while ($row = $res->fetch_assoc()) {
            echo "Achievement: " . $row['name'] . " (" . $row['slug'] . ")\n";
        }
    }
}
