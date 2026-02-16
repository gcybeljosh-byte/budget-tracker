<?php
include 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS journal_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_id INT NOT NULL,
    account_title VARCHAR(255) NOT NULL,
    debit DECIMAL(10, 2) DEFAULT 0.00,
    credit DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table journal_lines created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
