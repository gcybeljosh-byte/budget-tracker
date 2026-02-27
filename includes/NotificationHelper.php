<?php
// includes/NotificationHelper.php

class NotificationHelper
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function addNotification($user_id, $type, $message)
    {
        if (!$this->conn) return false;
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $type, $message);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        return false;
    }

    public function getUnreadNotifications($user_id, $role = 'user')
    {
        if (!$this->conn) return [];
        $query = "SELECT id, type, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0";
        if ($role === 'superadmin') {
            $query .= " AND type = 'new_user'";
        }
        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        if ($stmt) {
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
        return [];
    }

    public function markAllAsRead($user_id)
    {
        if (!$this->conn) return false;
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        return false;
    }

    public function checkScheduledReminders($user_id)
    {
        if (!$this->conn) return;
        try {
            $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        } catch (Exception $e) {
            $now = new DateTime('now');
        }
        $currentHour = (int)$now->format('H');
        $today = $now->format('Y-m-d');
        $targetHours = [10, 17, 21];

        foreach ($targetHours as $hour) {
            if ($currentHour >= $hour) {
                $prefStmt = $this->conn->prepare("SELECT notif_budget FROM users WHERE id = ?");
                if ($prefStmt) {
                    $prefStmt->bind_param("i", $user_id);
                    $prefStmt->execute();
                    $pref = $prefStmt->get_result()->fetch_assoc();
                    $prefStmt->close();
                    if (($pref['notif_budget'] ?? 1) == 0) continue;
                }

                $type = "reminder_$hour";
                $checkStmt = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND DATE(created_at) = ?");
                if ($checkStmt) {
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
    }

    public function checkBudgetLimit($user_id)
    {
        if (!$this->conn) return;
        $stmt = $this->conn->prepare("SELECT monthly_budget_goal, preferred_currency, first_name FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $goal = (float)($user['monthly_budget_goal'] ?? 0);
            if ($goal <= 0) return;

            $currency = $user['preferred_currency'] ?? 'PHP';
            $firstName = $user['first_name'] ?? 'User';
            require_once __DIR__ . '/CurrencyHelper.php';
            $symbol = CurrencyHelper::getSymbol($currency);

            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $totalSpent = (float)$stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                $today = date('Y-m-d');
                $formattedGoal = number_format($goal, 2);
                $formattedSpent = number_format($totalSpent, 2);

                if ($totalSpent >= $goal) {
                    $type = 'budget_exceeded';
                    $check = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND DATE(created_at) = ?");
                    if ($check) {
                        $check->bind_param("iss", $user_id, $type, $today);
                        $check->execute();
                        if ($check->get_result()->num_rows == 0) {
                            $this->addNotification($user_id, $type, "🚨 Attention {$firstName}! You have exceeded your monthly budget of {$symbol}{$formattedGoal}. Current spending: {$symbol}{$formattedSpent}.");
                        }
                        $check->close();
                    }
                } elseif ($totalSpent >= ($goal * 0.9)) {
                    $type = 'budget_near';
                    $check = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND DATE(created_at) = ?");
                    if ($check) {
                        $check->bind_param("iss", $user_id, $type, $today);
                        $check->execute();
                        if ($check->get_result()->num_rows == 0) {
                            $this->addNotification($user_id, $type, "⚠️ Warning {$firstName}! You've used 90%+ of your monthly budget ({$symbol}{$formattedSpent} / {$symbol}{$formattedGoal}).");
                        }
                        $check->close();
                    }
                }
            }
        }
    }

    public function checkLowAllowance($user_id)
    {
        if (!$this->conn) return;
        $prefStmt = $this->conn->prepare("SELECT notif_low_balance, preferred_currency, first_name FROM users WHERE id = ?");
        if ($prefStmt) {
            $prefStmt->bind_param("i", $user_id);
            $prefStmt->execute();
            $pref = $prefStmt->get_result()->fetch_assoc();
            $prefStmt->close();

            if (($pref['notif_low_balance'] ?? 1) == 0) return;

            $currency = $pref['preferred_currency'] ?? 'PHP';
            $firstName = $pref['first_name'] ?? 'User';
            require_once __DIR__ . '/CurrencyHelper.php';
            $symbol = CurrencyHelper::getSymbol($currency);

            $stmt = $this->conn->prepare("SELECT (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ?) - (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Allowance') - (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ?) as balance");
            if ($stmt) {
                $stmt->bind_param("iii", $user_id, $user_id, $user_id);
                $stmt->execute();
                $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
                $stmt->close();

                $threshold = ($currency === 'USD') ? 10 : 500;
                if ($balance < $threshold) {
                    $today = date('Y-m-d');
                    $type = 'low_balance';
                    $checkStmt = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND DATE(created_at) = ?");
                    if ($checkStmt) {
                        $checkStmt->bind_param("iss", $user_id, $type, $today);
                        $checkStmt->execute();
                        if ($checkStmt->get_result()->num_rows == 0) {
                            $formattedBalance = number_format($balance, 2);
                            $this->addNotification($user_id, $type, "⚠️ Hello {$firstName}! Your balance is now {$symbol}{$formattedBalance}. Please spend wisely!");
                        }
                        $checkStmt->close();
                    }
                }
            }
        }
    }

    private function getDailySpendingSummary($user_id)
    {
        if (!$this->conn) return "";
        require_once __DIR__ . '/CurrencyHelper.php';
        $symbol = CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP');
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');

        $allowance = 0;
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM allowances WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $currentMonth);
            $stmt->execute();
            $allowance = (float)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
        }

        $expenses = 0;
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND expense_source = 'Allowance' AND DATE_FORMAT(date, '%Y-%m') = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $currentMonth);
            $stmt->execute();
            $expenses = (float)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
        }

        $balance = $allowance - $expenses;
        $daysRemaining = (int)date('t') - (int)date('j') + 1;
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

    public function checkBillDeadlines($user_id)
    {
        if (!$this->conn) return;
        try {
            $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
        } catch (Exception $e) {
            $today = new DateTime('now');
        }
        $today->setTime(0, 0, 0);

        $stmt = $this->conn->prepare("SELECT id, title, amount, due_date, category FROM recurring_payments WHERE user_id = ? AND is_active = 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($bill = $result->fetch_assoc()) {
                $dueDate = new DateTime($bill['due_date']);
                $dueDate->setTime(0, 0, 0);
                $daysLeft = (int)$today->diff($dueDate)->format('%r%a');
                if (in_array($daysLeft, [7, 3, 1, 0])) {
                    $type = "bill_deadline_{$bill['id']}_{$daysLeft}_{$bill['due_date']}";
                    $checkStmt = $this->conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ?");
                    if ($checkStmt) {
                        $checkStmt->bind_param("is", $user_id, $type);
                        $checkStmt->execute();
                        if ($checkStmt->get_result()->num_rows == 0) {
                            $msg = ($daysLeft == 0) ? "🔔 Bill Due Today: Your bill '{$bill['title']}' (" . number_format($bill['amount'], 2) . ") is due today!" : "📅 Bill Reminder: Your bill '{$bill['title']}' (" . number_format($bill['amount'], 2) . ") is due in {$daysLeft} days.";
                            $this->addNotification($user_id, $type, $msg);
                        }
                        $checkStmt->close();
                    }
                }
            }
            $stmt->close();
        }
    }
}
