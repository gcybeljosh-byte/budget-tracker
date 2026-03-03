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

        $sql = "
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND source_type = 'Cash' $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type = 'Cash' AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type = 'Cash' $dateFilter) as balance
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float)($res['balance'] ?? 0);
    }

    public function getDigitalBalance($user_id, $monthOnly = false)
    {
        $dateFilter = $monthOnly ? " AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "";

        $sql = "
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND source_type IN ('GCash', 'Maya', 'Bank', 'Electronic') $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type IN ('GCash', 'Maya', 'Bank', 'Electronic') AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type IN ('GCash', 'Maya', 'Bank', 'Electronic') $dateFilter) as balance
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float)($res['balance'] ?? 0);
    }

    public function getTotalSavings($user_id, $monthOnly = false, $source_type = null)
    {
        $dateFilter = $monthOnly ? " AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "";
        $sourceFilter = $source_type ? " AND source_type = ?" : "";

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
        $details = $this->getBalanceDetails($user_id, $expense_source, $source_type);
        return $details['balance'];
    }

    public function getBalanceDetails($user_id, $expense_source, $source_type)
    {
        $monthFilter = " AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')";

        if ($expense_source === 'Savings') {
            $sql = "
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type = ?) as total_saved,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type = ? AND expense_source = 'Savings') as total_spent,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type = ? $monthFilter) as monthly_saved,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type = ? AND expense_source = 'Savings' $monthFilter) as monthly_spent
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isisisis", $user_id, $source_type, $user_id, $source_type, $user_id, $source_type, $user_id, $source_type);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return [
                'allowance_sum' => (float)$res['total_saved'],
                'expense_sum' => (float)$res['total_spent'],
                'monthly_allowance' => (float)$res['monthly_saved'],
                'monthly_expense' => (float)$res['monthly_spent'],
                'savings_sum' => 0,
                'balance' => (float)$res['total_saved'] - (float)$res['total_spent']
            ];
        } else {
            $sql = "
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND source_type = ?) as total_allowance,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type = ? AND expense_source = 'Allowance') as total_expense,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type = ?) as total_savings,
                    (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND source_type = ? $monthFilter) as monthly_allowance,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type = ? AND expense_source = 'Allowance' $monthFilter) as monthly_expense,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type = ? $monthFilter) as monthly_savings
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isisisisisis", $user_id, $source_type, $user_id, $source_type, $user_id, $source_type, $user_id, $source_type, $user_id, $source_type, $user_id, $source_type);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return [
                'allowance_sum' => (float)$res['total_allowance'],
                'expense_sum' => (float)$res['total_expense'],
                'savings_sum' => (float)$res['total_savings'],
                'monthly_allowance' => (float)$res['monthly_allowance'],
                'monthly_expense' => (float)$res['monthly_expense'],
                'monthly_savings' => (float)$res['monthly_savings'],
                'balance' => (float)$res['total_allowance'] - (float)$res['total_expense'] - (float)$res['total_savings']
            ];
        }
    }

    public function getBalancesByAllSources($user_id)
    {
        // Start with default expected sources to ensure they show up even if empty
        $sources = ['Cash', 'GCash', 'Maya', 'Bank', 'Electronic'];

        // Supplement with any other sources found in the database for this user
        $stmt = $this->conn->prepare("SELECT DISTINCT source_type FROM allowances WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_row()) {
            if ($row[0] && !in_array($row[0], $sources)) {
                $sources[] = $row[0];
            }
        }
        $stmt->close();

        $results = [];
        foreach ($sources as $source) {
            $details = $this->getBalanceDetails($user_id, 'Allowance', $source);
            $results[] = [
                'source' => $source,
                'balance' => $details['balance'],
                'allowance_sum' => $details['allowance_sum'],
                'expense_sum' => $details['expense_sum'],
                'monthly_allowance' => $details['monthly_allowance'],
                'monthly_expense' => $details['monthly_expense']
            ];
        }

        return $results;
    }

    public function syncBudgetLimits($user_id)
    {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM allowances WHERE user_id = ? AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $newGoal = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT monthly_budget_goal FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $oldGoal = (float)($stmt->get_result()->fetch_assoc()['monthly_budget_goal'] ?? 0);
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE users SET monthly_budget_goal = ? WHERE id = ?");
        $stmt->bind_param("di", $newGoal, $user_id);
        $stmt->execute();
        $stmt->close();

        if ($oldGoal > 0 && $newGoal != $oldGoal) {
            $ratio = $newGoal / $oldGoal;
            $stmt = $this->conn->prepare("UPDATE category_limits SET limit_amount = limit_amount * ? WHERE user_id = ?");
            $stmt->bind_param("di", $ratio, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        return $newGoal;
    }
}
