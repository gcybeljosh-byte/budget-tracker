<?php
// includes/NotificationHelper.php

class NotificationHelper
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Add a new notification for a user
     */
    public function addNotification($user_id, $type, $message)
    {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $type, $message);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications($user_id, $role = 'user')
    {
        $query = "SELECT id, type, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0";

        // Superadmin only sees new_user notifications
        if ($role === 'superadmin') {
            $query .= " AND type = 'new_user'";
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
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
    public function markAllAsRead($user_id)
    {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Check and trigger scheduled reminders (10am, 5pm, 9pm)
     */
    public function checkScheduledReminders($user_id)
    {
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
    public function checkLowAllowance($user_id)
    {
        // Check user preference
        $prefStmt = $this->conn->prepare("SELECT notif_low_balance, preferred_currency FROM users WHERE id = ?");
        $prefStmt->bind_param("i", $user_id);
        $prefStmt->execute();
        $pref = $prefStmt->get_result()->fetch_assoc();
        $prefStmt->close();

        if (($pref['notif_low_balance'] ?? 1) == 0) return;

        $currency = $pref['preferred_currency'] ?? 'PHP';
        require_once __DIR__ . '/CurrencyHelper.php';
        $symbol = CurrencyHelper::getSymbol($currency);

        // Calculate current liquid balance (Total Allowance - Savings transfers - Allowance-based Expenses)
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ?) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Allowance') -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ?) as balance
        ");
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
        $stmt->close();

        // Threshold (₱500 equivalent)
        $threshold = ($currency === 'USD') ? 10 : 500;

        if ($balance < $threshold) {
            // Check if a "low_allowance" notification was already sent today
            $today = date('Y-m-d');
            $type = 'low_allowance';
            $checkStmt = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND DATE(created_at) = ?");
            $checkStmt->bind_param("iss", $user_id, $type, $today);
            $checkStmt->execute();

            if ($checkStmt->get_result()->num_rows == 0) {
                $formattedBalance = number_format($balance, 2);
                $this->addNotification($user_id, $type, "\u26a0\ufe0f Low Balance Alert: Your balance is now {$symbol}{$formattedBalance}. Please spend wisely!");
            }
            $checkStmt->close();
        }
    }

    private function getDailySpendingSummary($user_id)
    {
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');

        // 1. Get total monthly allowance
        require_once __DIR__ . '/CurrencyHelper.php';
        $symbol = CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP');

        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM allowances WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $monthlyAllowance = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Get monthly expenses
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND DATE_FORMAT(date, '%Y-%m') = ?");
        $stmt->bind_param("is", $user_id, $currentMonth);
        $stmt->execute();
        $monthlyExpenses = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $balance = $monthlyAllowance - $monthlyExpenses;
        $daysInMonth = (int)date('t');
        $daysRemaining = $daysInMonth - (int)date('j') + 1;
        $dailyLimit = ($daysRemaining > 0) ? $balance / $daysRemaining : 0;

        return "Daily Limit Info: You have {$symbol}" . number_format($balance, 2) . " remaining for this month. That's approx. {$symbol}" . number_format($dailyLimit, 2) . "/day.";
    }

    private function getReminderMessage($hour, $user_id)
    {
        $summary = $this->getDailySpendingSummary($user_id);
        switch ($hour) {
            case 10:
                return "☕ Good morning! Don't forget to track your coffee. $summary";
            case 17:
                return "🌇 Good afternoon! Review your daily purchases. $summary";
            case 21:
                return "🌙 Good evening! Finalize your logs for today. $summary";
            default:
                return "Reminder to record your expenses. $summary";
        }
    }

    /**
     * Check for bill deadlines (7 days, 3 days, 1 day before, and on the day)
     */
    public function checkBillDeadlines($user_id)
    {
        $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $today->setTime(0, 0, 0); // Reset time to compare days correctly

        // Fetch active bills
        $stmt = $this->conn->prepare("SELECT id, title, amount, due_date, category FROM recurring_payments WHERE user_id = ? AND is_active = 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($bill = $result->fetch_assoc()) {
            $dueDate = new DateTime($bill['due_date'], new DateTimeZone('Asia/Manila'));
            $dueDate->setTime(0, 0, 0);

            $interval = $today->diff($dueDate);
            $daysLeft = (int)$interval->format('%r%a');

            // We only care about 7, 3, 1, and 0 days
            $targets = [7, 3, 1, 0];

            if (in_array($daysLeft, $targets)) {
                $type = "bill_deadline_{$bill['id']}_{$daysLeft}_{$bill['due_date']}";

                // Check if already notified for this specific bill, target day, and due date cycle
                $checkStmt = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ?");
                $checkStmt->bind_param("is", $user_id, $type);
                $checkStmt->execute();

                if ($checkStmt->get_result()->num_rows == 0) {
                    $msg = "";
                    if ($daysLeft == 0) {
                        $msg = "🔔 Bill Due Today: Your bill '{$bill['title']}' (" . number_format($bill['amount'], 2) . ") is due today!";
                    } else {
                        $msg = "📅 Bill Reminder: Your bill '{$bill['title']}' (" . number_format($bill['amount'], 2) . ") is due in {$daysLeft} " . ($daysLeft == 1 ? 'day' : 'days') . ".";
                    }
                    $this->addNotification($user_id, $type, $msg);
                }
                $checkStmt->close();
            }
        }
        $stmt->close();
    }
}
