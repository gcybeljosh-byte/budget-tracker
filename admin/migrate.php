<?php
// migrate.php
require_once '../includes/db.php';

echo "Running migration...\n";

// Add saving_id to expenses
$sql = "ALTER TABLE expenses ADD COLUMN IF NOT EXISTS saving_id INT DEFAULT NULL";
if ($conn->query($sql)) {
    echo "Successfully updated expenses table.\n";
} else {
    // Fallback if IF NOT EXISTS failed
    echo "IF NOT EXISTS failed, trying fallback...\n";
    $conn->query("ALTER TABLE expenses ADD COLUMN saving_id INT DEFAULT NULL");
    echo "Fallback attempted.\n";
}

// Check structural result
$result = $conn->query("DESCRIBE expenses");
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'saving_id') {
        echo "Column 'saving_id' confirmed.\n";
    }
}
?>
