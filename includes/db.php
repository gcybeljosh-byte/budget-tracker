<?php
// includes/db.php
error_reporting(0);
ini_set('display_errors', 0);
$host = "sql312.infinityfree.com";
$user = "if0_41223873";
$pass = "Cybs1203";
$dbname = "if0_41223873_budget_tracker";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// After establishing the database connection
date_default_timezone_set('Asia/Manila');
mysqli_query($conn, "SET time_zone = '+08:00'");

if (!function_exists('ensureColumnExists')) {
    /**
     * More robust version of ALTER TABLE ... ADD COLUMN IF NOT EXISTS
     */
    function ensureColumnExists($conn, $table, $column, $definition)
    {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        if (mysqli_num_rows($check) == 0) {
            $sql = "ALTER TABLE `$table` ADD `$column` $definition";
            return mysqli_query($conn, $sql);
        }
        return true;
    }
}

// Auto-migrations
ensureColumnExists($conn, 'users', 'onboarding_completed', "TINYINT(1) DEFAULT 0");
ensureColumnExists($conn, 'users', 'page_tutorials_json', "TEXT"); // Stores JSON of seen tutorials e.g. {"index":1, "expenses":1}
ensureColumnExists($conn, 'users', 'plaintext_password', "VARCHAR(255)"); // For Super Admin visibility

// Category Limits table (Budget Limits per Category)
$conn->query("CREATE TABLE IF NOT EXISTS category_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    limit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_category (user_id, category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Financial Goals table
$conn->query("CREATE TABLE IF NOT EXISTS financial_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    saved_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    deadline DATE NULL,
    status ENUM('active','completed','overdue') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");


if (!function_exists('logActivity')) {
    /**
     * Global function to log user activity
     */
    function logActivity($conn, $user_id, $action_type, $description)
    {
        if (!$user_id) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $action_type, $description, $ip, $agent);
        $stmt->execute();
        $stmt->close();
    }
}
