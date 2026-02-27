<?php
// includes/AchievementHelper.php

class AchievementHelper
{
    private $conn;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
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
                if ($lastDate === $yesterday || $lastDate === null) {
                    $currentCount++;
                } else {
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
                $stmt = $this->conn->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
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

    public function getUserAchievements($user_id)
    {
        if (!$this->conn) return [];
        $stmt = $this->conn->prepare("SELECT a.*, (ua.unlocked_at IS NOT NULL) as is_unlocked, ua.unlocked_at FROM achievements a LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ? ORDER BY a.id ASC");
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
}
