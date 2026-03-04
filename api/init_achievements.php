<?php
// api/init_achievements.php
if (basename($_SERVER['PHP_SELF']) == 'init_achievements.php') {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // 1. Achievements Master Table
    $conn->query("CREATE TABLE IF NOT EXISTS achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50) DEFAULT 'fas fa-trophy',
        badge_color VARCHAR(20) DEFAULT '#6366f1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. User Achievements (Pivot)
    $conn->query("CREATE TABLE IF NOT EXISTS user_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        achievement_id INT NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_notified TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
        UNIQUE KEY user_ach (user_id, achievement_id)
    )");

    // 3. User Streaks Table
    $conn->query("CREATE TABLE IF NOT EXISTS user_streaks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        streak_type VARCHAR(50) NOT NULL,
        current_count INT DEFAULT 0,
        max_count INT DEFAULT 0,
        last_triggered_date DATE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY user_streak_type (user_id, streak_type)
    )");

    // 4. Ensure is_notified column exists (if table was created without it)
    $res = $conn->query("SHOW COLUMNS FROM user_achievements LIKE 'is_notified'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE user_achievements ADD COLUMN is_notified TINYINT(1) DEFAULT 0");
    }

    // 5. Seed Achievements
    $default_achievements = [
        ['first_expense', 'First Step', 'You logged your first expense!', 'fas fa-shoe-prints', '#10b981'],
        ['savings_starter', 'Penny Pincher', 'You added your first savings entry.', 'fas fa-piggy-bank', '#f59e0b'],
        ['streak_3', 'Consistency King', 'You maintained a 3-day no-spending streak.', 'fas fa-fire', '#f43f5e'],
        ['streak_7', 'Unstoppable', 'You maintained a 7-day no-spending streak!', 'fas fa-bolt', '#6366f1'],
        ['goal_reacher', 'Goal Getter', 'You successfully completed a financial goal.', 'fas fa-bullseye', '#8b5cf6'],
        ['power_user', 'System Master', 'You customized your preferences and currency.', 'fas fa-gears', '#64748b']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO achievements (slug, name, description, icon, badge_color) VALUES (?, ?, ?, ?, ?)");
    foreach ($default_achievements as $ach) {
        $stmt->bind_param("sssss", $ach[0], $ach[1], $ach[2], $ach[3], $ach[4]);
        $stmt->execute();
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Achievements system initialized and seeded.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Init failed: ' . $e->getMessage()]);
}

$conn->close();
