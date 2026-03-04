<?php
// api/gamification.php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/AchievementHelper.php';

session_start();
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$helper = new AchievementHelper($conn);

// Handle marking as notified if requested
if (isset($_GET['action']) && $_GET['action'] === 'mark_notified' && isset($_GET['id'])) {
    $success = $helper->markAsNotified($user_id, (int)$_GET['id']);
    echo json_encode(['success' => $success]);
    exit;
}

// Update streak on every fetch (throttled inside the helper)
$helper->updateNoSpendStreak($user_id);

$achievements = $helper->getUserAchievements($user_id);
$unnotified = $helper->getUnnotifiedAchievements($user_id);
$streaks = $helper->getStreakStats($user_id);

echo json_encode([
    'success' => true,
    'achievements' => $achievements,
    'unnotified' => $unnotified,
    'streaks' => $streaks
]);
