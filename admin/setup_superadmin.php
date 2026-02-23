<?php
include '../includes/db.php';
echo "<h2>System Initialization</h2>";

$username = 'superadmin';
$password = 'SuperAdmin2026!';
$first_name = 'System';
$last_name = 'Superadmin';
$email = 'superadmin@example.com';
$role = 'superadmin';

// Migration: Ensure 'role' column formally includes 'superadmin' categorization
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'superadmin') DEFAULT 'user'");
if ($conn->error) {
    // Fallback to VARCHAR if ENUM fails (e.g. existing invalid data)
    $conn->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) DEFAULT 'user'");
}

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO users (username, password, plaintext_password, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssssss", $username, $password, $password, $first_name, $last_name, $email, $role);
    if ($stmt->execute()) {
        echo "<div style='color: green;'><strong>Success!</strong> Superadmin account created.</div>";
        echo "<ul><li>Username: <strong>$username</strong></li><li>Password: <strong>$password</strong></li></ul>";
        echo "<p><a href='login.php'>Go to Login</a></p>";
    } else {
        echo "<div style='color: red;'>Error: " . $conn->error . "</div>";
    }
} else {
    echo "<div style='color: orange;'>Superadmin already exists.</div>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
}
?>
