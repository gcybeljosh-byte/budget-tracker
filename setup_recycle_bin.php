<?php
require_once 'includes/db.php';

echo "Running Recycle Bin migration...\n";

// Add deleted_at column to users table
if (ensureColumnExists($conn, 'users', 'deleted_at', "TIMESTAMP NULL DEFAULT NULL")) {
    echo "Successfully added 'deleted_at' column to 'users' table or it already exists.\n";
} else {
    echo "Error adding 'deleted_at' column.\n";
}

$conn->close();
echo "Migration finished.\n";
