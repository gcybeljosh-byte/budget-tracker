<?php
// api/update_theme.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

if (isset($_GET['theme']) && isset($_SESSION['id'])) {
    $theme = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    $_SESSION['theme'] = $theme;

    // Persist to DB
    $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->bind_param("si", $theme, $_SESSION['id']);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'theme' => $theme]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
