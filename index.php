<?php
// root index.php - Smart Router
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$role = $_SESSION['role'] ?? 'user';

if ($role === 'superadmin') {
    header("Location: " . SITE_URL . "admin/dashboard.php");
} else {
    header("Location: " . SITE_URL . "core/dashboard.php");
}
exit;
?>
