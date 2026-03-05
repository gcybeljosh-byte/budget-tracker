<?php
// includes/AchievementHelper.php

class AchievementHelper
{
    private $conn;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
        $this->ensureTablesExist();
    }

    private function ensureTablesExist()
    {
        if (!$this->conn) return;

        // 1. Achievements Master Table
        $this->conn->query("CREATE TABLE IF NOT EXISTS achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'fas fa-trophy',
            badge_color VARCHAR(20) DEFAULT '#6366f1',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // 2. User Achievements (Pivot)
        $this->conn->query("CREATE TABLE IF NOT EXISTS user_achievements (
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
        $this->conn->query("CREATE TABLE IF NOT EXISTS user_streaks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            streak_type VARCHAR(50) NOT NULL,
            current_count INT DEFAULT 0,
            max_count INT DEFAULT 0,
            last_triggered_date DATE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY user_streak_type (user_id, streak_type)
        )");

        // 4. Seed Achievements if empty
        $check = $this->conn->query("SELECT COUNT(*) FROM achievements");
        if ($check && $check->fetch_row()[0] == 0) {
            $default_achievements = [
                ['first_expense', 'First Step', 'You logged your first expense!', 'fas fa-shoe-prints', '#10b981'],
                ['savings_starter', 'Penny Pincher', 'You added your first savings entry.', 'fas fa-piggy-bank', '#f59e0b'],
                ['streak_3', 'Consistency King', 'You maintained a 3-day no-spending streak.', 'fas fa-fire', '#f43f5e'],
                ['streak_7', 'Unstoppable', 'You maintained a 7-day no-spending streak!', 'fas fa-bolt', '#6366f1'],
                ['goal_reacher', 'Goal Getter', 'You successfully completed a financial goal.', 'fas fa-bullseye', '#8b5cf6'],
                ['power_user', 'System Master', 'You customized your preferences and currency.', 'fas fa-gears', '#64748b']
            ];

            $stmt = $this->conn->prepare("INSERT IGNORE INTO achievements (slug, name, description, icon, badge_color) VALUES (?, ?, ?, ?, ?)");
            foreach ($default_achievements as $ach) {
                $stmt->bind_param("sssss", $ach[0], $ach[1], $ach[2], $ach[3], $ach[4]);
                $stmt->execute();
            }
            $stmt->close();
        }
    }

    public function updateNoSpendStreak($user_id)
    {
        if (!$this->conn) return;
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM expenses WHERE user_id = ? AND date = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $yesterday);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $hadExpensesYesterday = ($row[0] > 0);
            $stmt->close();
        } else {
            return;
        }

        $stmt = $this->conn->prepare("SELECT current_count, max_count, last_triggered_date FROM user_streaks WHERE user_id = ? AND streak_type = 'no_spend'");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $streak = $res->fetch_assoc();
            $stmt->close();

            if (!$streak) {
                $stmt = $this->conn->prepare("INSERT INTO user_streaks (user_id, streak_type, current_count, max_count, last_triggered_date) VALUES (?, 'no_spend', 0, 0, NULL)");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                return;
            }

            $lastDate = $streak['last_triggered_date'];
            $currentCount = $streak['current_count'];
            $maxCount = $streak['max_count'];

            if ($lastDate === $today) return;

            if (!$hadExpensesYesterday) {
                if ($lastDate === $yesterday) {
                    // Continue an existing streak from yesterday
                    $currentCount++;
                } elseif ($lastDate === null) {
                    // Fresh start — don't count yet, set today as the first check but keep at 0
                    $currentCount = 0;
                } else {
                    // Streak broken — restart from 1
                    $currentCount = 1;
                }
            } else {
                $currentCount = 0;
            }

            $newMax = max($maxCount, $currentCount);
            $stmt = $this->conn->prepare("UPDATE user_streaks SET current_count = ?, max_count = ?, last_triggered_date = ? WHERE user_id = ? AND streak_type = 'no_spend'");
            if ($stmt) {
                $stmt->bind_param("iisi", $currentCount, $newMax, $today, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            if ($currentCount >= 3) $this->unlockBySlug($user_id, 'streak_3');
            if ($currentCount >= 7) $this->unlockBySlug($user_id, 'streak_7');
        }
    }

    public function unlockBySlug($user_id, $slug)
    {
        if (!$this->conn) return false;
        $stmt = $this->conn->prepare("SELECT id FROM achievements WHERE slug = ?");
        if ($stmt) {
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $res = $stmt->get_result();
            $ach = $res->fetch_assoc();
            $stmt->close();

            if ($ach) {
                // Modified: explicitly set is_notified to 0 for new unlocks
                $stmt = $this->conn->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id, is_notified) VALUES (?, ?, 0)");
                if ($stmt) {
                    $stmt->bind_param("ii", $user_id, $ach['id']);
                    $stmt->execute();
                    $affected = $stmt->affected_rows;
                    $stmt->close();

                    if ($affected > 0 && function_exists('logActivity')) {
                        logActivity($this->conn, $user_id, 'achievement_unlock', "Unlocked achievement: " . $slug);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getUnnotifiedAchievements($user_id)
    {
        if (!$this->conn) return [];
        $stmt = $this->conn->prepare("SELECT a.*, ua.achievement_id FROM achievements a JOIN user_achievements ua ON a.id = ua.achievement_id WHERE ua.user_id = ? AND ua.is_notified = 0");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function markAsNotified($user_id, $achievement_id)
    {
        if (!$this->conn) return false;
        $stmt = $this->conn->prepare("UPDATE user_achievements SET is_notified = 1 WHERE user_id = ? AND achievement_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $user_id, $achievement_id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        return false;
    }

    public function getUserAchievements($user_id)
    {
        if (!$this->conn) return [];
        $stmt = $this->conn->prepare("SELECT a.*, (ua.unlocked_at IS NOT NULL) as is_unlocked, ua.unlocked_at, ua.is_notified FROM achievements a LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ? ORDER BY a.id ASC");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) return $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        return [];
    }

    public function getStreakStats($user_id)
    {
        if (!$this->conn) return [];
        $stmt = $this->conn->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) return $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        return [];
    }

    public function checkRetroactiveAchievements($user_id)
    {
        if (!$this->conn) return;

        // 1. First Expense
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM expenses WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()[0] > 0) {
            $this->unlockBySlug($user_id, 'first_expense');
        }
        $stmt->close();

        // 2. Savings Starter
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM savings WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()[0] > 0) {
            $this->unlockBySlug($user_id, 'savings_starter');
        }
        $stmt->close();

        // 3. Goal Reacher
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM financial_goals WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()[0] > 0) {
            $this->unlockBySlug($user_id, 'goal_reacher');
        }
        $stmt->close();

        // 4. Power User
        $stmt = $this->conn->prepare("SELECT onboarding_completed FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && $row['onboarding_completed']) {
            $this->unlockBySlug($user_id, 'power_user');
        }
        $stmt->close();
    }
}
