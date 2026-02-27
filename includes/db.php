<?php
// includes/db.php
// Optimized for InfinityFree - Zero auto-migrations to prevent 502 timeouts

$host = "sql312.infinityfree.com";
$user = "if0_41223873";
$pass = "Cybs1203";
$dbname = "if0_41223873_budget_tracker";

// Establish connection
$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set Timezone
date_default_timezone_set('Asia/Manila');
mysqli_query($conn, "SET time_zone = '+08:00'");

// Production Error Handling - SET TO 1 FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper Functions
if (!function_exists('logActivity')) {
    function logActivity($conn, $user_id, $action_type, $description)
    {
        if (!$conn || !$user_id) return;

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $action_type, $description, $ip, $agent);
            $stmt->execute();
            $stmt->close();
        } else {
            // Fallback for older schema
            $stmt2 = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
            if ($stmt2) {
                $stmt2->bind_param("iss", $user_id, $action_type, $description);
                $stmt2->execute();
                $stmt2->close();
            }
        }
    }
}

if (!function_exists('isMaintenanceMode')) {
    function isMaintenanceMode($conn)
    {
        if (!$conn) return false;
        if (isset($_SESSION['role']) && ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin')) return false;

        $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
        if ($result && $row = $result->fetch_assoc()) {
            return $row['setting_value'] === 'true';
        }
        return false;
    }
}
