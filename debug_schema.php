<?php
require_once 'c:\Users\User\OneDrive\Documents\GitHub\budget-tracker\includes\db.php';

function getTableDetails($conn, $table)
{
    echo "\n--- $table ---\n";
    $res = $conn->query("SHOW CREATE TABLE $table");
    if ($res) {
        $row = $res->fetch_assoc();
        echo $row['Create Table'] . "\n";
    } else {
        echo "Error showing create table $table: " . $conn->error . "\n";
    }
}

getTableDetails($conn, 'shared_groups');
getTableDetails($conn, 'allowances');
getTableDetails($conn, 'expenses');
getTableDetails($conn, 'savings');
