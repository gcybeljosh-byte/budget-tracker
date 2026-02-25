<?php

class BalanceHelper
{
    private $conn;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
    }

    public function getCashBalance($user_id, $monthOnly = false)
    {
        $dateFilter = $monthOnly ? " AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "";
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND TRIM(source_type) = 'Cash' $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND TRIM(source_type) = 'Cash' AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND TRIM(source_type) = 'Cash' $dateFilter) as balance
        ");
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float)($res['balance'] ?? 0);
    }

    public function getDigitalBalance($user_id, $monthOnly = false)
    {
        $dateFilter = $monthOnly ? " AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "";
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND TRIM(source_type) IN ('GCash', 'Maya', 'Bank', 'Electronic') $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND TRIM(source_type) IN ('GCash', 'Maya', 'Bank', 'Electronic') AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND TRIM(source_type) IN ('GCash', 'Maya', 'Bank', 'Electronic') $dateFilter) as balance
        ");
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float)($res['balance'] ?? 0);
    }

    public function getTotalSavings($user_id, $monthOnly = false, $source_type = null)
    {
        $dateFilter = $monthOnly ? " AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "";
        $sourceFilter = $source_type ? " AND TRIM(source_type) = TRIM(?)" : "";

        $sql = "
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? $sourceFilter $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings' $sourceFilter $dateFilter) as total
        ";

        $stmt = $this->conn->prepare($sql);
        if ($source_type) {
            $stmt->bind_param("isis", $user_id, $source_type, $user_id, $source_type);
        } else {
            $stmt->bind_param("ii", $user_id, $user_id);
        }

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (float)($res['total'] ?? 0);
    }

    public function getBalanceBySource($user_id, $expense_source, $source_type)
    {
        // For real-time balance validation (e.g. adding expenses), we use LIFETIME balance
        if ($expense_source === 'Savings') {
            return $this->getTotalSavings($user_id, false, $source_type);
        } else {
            if ($source_type === 'Cash') {
                return $this->getCashBalance($user_id, false);
            } else {
                return $this->getDigitalBalance($user_id, false);
            }
        }
    }

    public function getBalanceDetails($user_id, $expense_source, $source_type)
    {
        $dateFilter = ""; // Lifetime

        if ($expense_source === 'Savings') {
            $stmt = $this->conn->prepare("
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND TRIM(source_type) = TRIM(?) $dateFilter) as total_saved,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND TRIM(source_type) = TRIM(?) AND expense_source = 'Savings' $dateFilter) as total_spent
            ");
            $stmt->bind_param("isis", $user_id, $source_type, $user_id, $source_type);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return [
                'allowance_sum' => (float)$res['total_saved'],
                'expense_sum' => (float)$res['total_spent'],
                'savings_sum' => 0,
                'balance' => (float)$res['total_saved'] - (float)$res['total_spent']
            ];
        } else {
            $stmt = $this->conn->prepare("
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND TRIM(source_type) = TRIM(?) $dateFilter) as total_allowance,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND TRIM(source_type) = TRIM(?) AND expense_source = 'Allowance' $dateFilter) as total_expense,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND TRIM(source_type) = TRIM(?) $dateFilter) as total_savings
            ");
            $stmt->bind_param("isisis", $user_id, $source_type, $user_id, $source_type, $user_id, $source_type);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return [
                'allowance_sum' => (float)$res['total_allowance'],
                'expense_sum' => (float)$res['total_expense'],
                'savings_sum' => (float)$res['total_savings'],
                'balance' => (float)$res['total_allowance'] - (float)$res['total_expense'] - (float)$res['total_savings']
            ];
        }
    }

    public function getBalancesByAllSources($user_id)
    {
        $sources = ['Cash', 'GCash', 'Maya', 'Bank', 'Electronic'];
        $results = [];

        foreach ($sources as $source) {
            $details = $this->getBalanceDetails($user_id, 'Allowance', $source);
            $results[] = [
                'source' => $source,
                'balance' => $details['balance']
            ];
        }

        return $results;
    }

    public function syncBudgetLimits($user_id)
    {
        // 1. Fetch current monthly allowance total
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM allowances WHERE user_id = ? AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $newGoal = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // 2. Fetch old goal to calculate ratio
        $stmt = $this->conn->prepare("SELECT monthly_budget_goal FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $oldGoal = (float)($stmt->get_result()->fetch_assoc()['monthly_budget_goal'] ?? 0);
        $stmt->close();

        // 3. Update the monthly budget goal
        $stmt = $this->conn->prepare("UPDATE users SET monthly_budget_goal = ? WHERE id = ?");
        $stmt->bind_param("di", $newGoal, $user_id);
        $stmt->execute();
        $stmt->close();

        // 4. Proportional scaling of category limits
        if ($oldGoal > 0 && $newGoal != $oldGoal) {
            $ratio = $newGoal / $oldGoal;
            $stmt = $this->conn->prepare("UPDATE category_limits SET limit_amount = limit_amount * ? WHERE user_id = ?");
            $stmt->bind_param("di", $ratio, $user_id);
            $stmt->execute();
            $stmt->close();

            error_log("Budget sync for user $user_id: Ratio $ratio (Old: $oldGoal, New: $newGoal)");
        }

        return $newGoal;
    }
}
