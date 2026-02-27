<?php
// includes/AchievementHelper.php

class AchievementHelper
{
    private $conn;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
    }

    /**
     * Update the "No-Spend" streak for a user.
     * Should be called during daily login or dashboard view.
     */
    public function updateNoSpendStreak($user_id)
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Check if user had any expenses yesterday
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM expenses WHERE user_id = ? AND date = ?");
        $stmt->bind_param("is", $user_id, $yesterday);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $hadExpensesYesterday = ($row[0] > 0);
        $stmt->close();

        // Get current streak
        $stmt = $this->conn->prepare("SELECT current_count, max_count, last_triggered_date FROM user_streaks WHERE user_id = ? AND streak_type = 'no_spend'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $streak = $res->fetch_assoc();
        $stmt->close();

        if (!$streak) {
            // Initialize streak
            $stmt = $this->conn->prepare("INSERT INTO user_streaks (user_id, streak_type, current_count, max_count, last_triggered_date) VALUES (?, 'no_spend', 0, 0, NULL)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            return;
        }

        $lastDate = $streak['last_triggered_date'];
        $currentCount = $streak['current_count'];
        $maxCount = $streak['max_count'];

        // If already updated today, skip
        if ($lastDate === $today) return;

        if (!$hadExpensesYesterday) {
            // Increment streak if yesterday was a no-spend day and last update was yesterday
            if ($lastDate === $yesterday || $lastDate === null) {
                $currentCount++;
            } else {
                // Streak broken previously but yesterday was clean, start at 1
                $currentCount = 1;
            }
        } else {
            // Streak broken yesterday
            $currentCount = 0;
        }

        $newMax = max($maxCount, $currentCount);

        $stmt = $this->conn->prepare("UPDATE user_streaks SET current_count = ?, max_count = ?, last_triggered_date = ? WHERE user_id = ? AND streak_type = 'no_spend'");
        $stmt->bind_param("iisi", $currentCount, $newMax, $today, $user_id);
        $stmt->execute();
        $stmt->close();

        // Check for streak milestones
        if ($currentCount >= 3) $this->unlockBySlug($user_id, 'streak_3');
        if ($currentCount >= 7) $this->unlockBySlug($user_id, 'streak_7');
    }

    /**
     * Unlock an achievement for a user by its slug.
     */
    public function unlockBySlug($user_id, $slug)
    {
        $stmt = $this->conn->prepare("SELECT id FROM achievements WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $res = $stmt->get_result();
        $ach = $res->fetch_assoc();
        $stmt->close();

        if ($ach) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $ach['id']);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0) {
                // Record activity
                require_once __DIR__ . '/db.php';
                logActivity($this->conn, $user_id, 'achievement_unlock', "Unlocked achievement: " . $slug);
                return true;
            }
        }
        return false;
    }

    /**
     * Get all achievements for a user, marking which ones are unlocked.
     */
    public function getUserAchievements($user_id)
    {
        $sql = "SELECT a.*, (ua.unlocked_at IS NOT NULL) as is_unlocked, ua.unlocked_at 
                FROM achievements a 
                LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
                ORDER BY a.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get current streak stats for a user.
     */
    public function getStreakStats($user_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
