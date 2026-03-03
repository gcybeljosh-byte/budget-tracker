<?php
require_once 'includes/db.php';

// Check if last_forwarded_month exists
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'last_forwarded_month'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN last_forwarded_month VARCHAR(7) DEFAULT NULL");
    echo "Column last_forwarded_month added to users table.\n";
} else {
    echo "Column last_forwarded_month already exists.\n";
}

// Add a column for tracker/stats version if needed or just clear tables for reset
echo "Migration complete.";
