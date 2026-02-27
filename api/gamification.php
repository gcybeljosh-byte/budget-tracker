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

// Update streak on every fetch (throttled inside the helper)
$helper->updateNoSpendStreak($user_id);

$achievements = $helper->getUserAchievements($user_id);
$streaks = $helper->getStreakStats($user_id);

echo json_encode([
    'success' => true,
    'achievements' => $achievements,
    'streaks' => $streaks
]);
