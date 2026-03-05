<?php
// api/gamification.php
header('Content-Type: application/json');
require_once '../includes/db.php';

session_start();
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];

// Achievement feature and helper removed

// Update streak on every fetch (throttled inside the helper)
// Skip achievement checks as requested

$achievements = []; // Achievement feature removed
$unnotified = [];
$streaks = []; // Can be kept or removed; assuming strict removal of "features" includes streaks if they rely on AchievementHelper

echo json_encode([
    'success' => true,
    'achievements' => [],
    'unnotified' => [],
    'streaks' => []
]);
