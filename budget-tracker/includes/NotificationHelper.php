<?php
// includes/NotificationHelper.php

class NotificationHelper {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Add a new notification for a user
     */
    public function addNotification($user_id, $type, $message) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $type, $message);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications($user_id) {
        $stmt = $this->conn->prepare("SELECT id, type, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        return $notifications;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Check and trigger scheduled reminders (10am, 5pm, 9pm)
     */
    public function checkScheduledReminders($user_id) {
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $currentHour = (int)$now->format('H');
        $today = $now->format('Y-m-d');

        // Target hours for reminders
        $targetHours = [10, 17, 21]; // 10am, 5pm, 9pm
        
        foreach ($targetHours as $hour) {
            if ($currentHour >= $hour) {
                // Check user preference
                $prefStmt = $this->conn->prepare("SELECT notif_budget FROM users WHERE id = ?");
                $prefStmt->bind_param("i", $user_id);
                $prefStmt->execute();
                $pref = $prefStmt->get_result()->fetch_assoc();
                $prefStmt->close();

                if (($pref['notif_budget'] ?? 1) == 0) continue;

                // Check if a reminder for this specific hour has already been sent today
                $type = "reminder_$hour";
                $checkStmt = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND DATE(created_at) = ?");
                $checkStmt->bind_param("iss", $user_id, $type, $today);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows == 0) {
                    $message = $this->getReminderMessage($hour, $user_id);
                    $this->addNotification($user_id, $type, $message);
                }
                $checkStmt->close();
            }
        }
    }

    /**
     * Check if balance is low (below ₱500)
     */
    public function checkLowAllowance($user_id) {
        // Check user preference
        $prefStmt = $this->conn->prepare("SELECT notif_low_balance FROM users WHERE id = ?");
        $prefStmt->bind_param("i", $user_id);
        $prefStmt->execute();
        $pref = $prefStmt->get_result()->fetch_assoc();
        $prefStmt->close();

        if (($pref['notif_low_balance'] ?? 1) == 0) return;

        // Calculate current balance
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ?) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?) as balance
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
        $stmt->close();

        // Fixed ₱500 Threshold
        $threshold = 500;
        
        if ($balance < $threshold) {
             // Check if a "low_allowance" notification was already sent today
            $today = date('Y-m-d');
            $type = 'low_allowance';
            $checkStmt = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND DATE(created_at) = ?");
            $checkStmt->bind_param("iss", $user_id, $type, $today);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows == 0) {
                $formattedBalance = number_format($balance, 2);
                $this->addNotification($user_id, $type, "⚠️ Low Balance Alert: Your balance is now ₱{$formattedBalance}, which is below ₱500. Please spend wisely!");
            }
            $checkStmt->close();
        }
    }

    private function getDailySpendingSummary($user_id) {
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        
        // 1. Get total monthly allowance
        require_once 'CurrencyHelper.php';
        $symbol = CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP');

        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM allowances WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $monthlyAllowance = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Get monthly expenses
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
        $stmt->bind_param("is", $user_id, $currentMonth);
        $stmt->execute();
        $monthlyExpenses = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $balance = $monthlyAllowance - $monthlyExpenses;
        $daysInMonth = (int)date('t');
        $daysRemaining = $daysInMonth - (int)date('j') + 1;
        $dailyLimit = ($daysRemaining > 0) ? $balance / $daysRemaining : 0;

        return "Daily Limit Info: You have ₱" . number_format($balance, 2) . " remaining for this month. That's approx. ₱" . number_format($dailyLimit, 2) . "/day.";
    }

    private function getReminderMessage($hour, $user_id) {
        $summary = $this->getDailySpendingSummary($user_id);
        switch ($hour) {
            case 10: return "☕ Good morning! Don't forget to track your coffee. $summary";
            case 17: return "🌇 Good afternoon! Review your daily purchases. $summary";
            case 21: return "🌙 Good evening! Finalize your logs for today. $summary";
            default: return "Reminder to record your expenses. $summary";
        }
    }
}
?>
