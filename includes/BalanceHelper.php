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
        $sourcePart = "(source_type = 'Cash' OR source_type = '0' OR source_type IS NULL)";

        $sql = "
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND (LOWER(source_type) = 'cash' OR source_type = '0' OR source_type IS NULL) $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND (LOWER(source_type) = 'cash' OR source_type = '0' OR source_type IS NULL) AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND (LOWER(source_type) = 'cash' OR source_type = '0' OR source_type IS NULL) $dateFilter) as balance
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
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND LOWER(source_type) IN ('gcash', 'maya', 'bank', 'electronic') $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND LOWER(source_type) IN ('gcash', 'maya', 'bank', 'electronic') AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND LOWER(source_type) IN ('gcash', 'maya', 'bank', 'electronic') $dateFilter) as balance
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
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND (LOWER(source_type) = LOWER(?) OR ? IS NULL) $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND expense_source = 'Savings' AND (LOWER(source_type) = LOWER(?) OR ? IS NULL) $dateFilter) as total
        ";
        $stmt = $this->conn->prepare($sql);
        if ($source_type) {
            $stmt->bind_param("isssisss", $user_id, $source_type, $source_type, $user_id, $source_type, $source_type);
        } else {
            $stmt->bind_param("isssisss", $user_id, $source_type, $source_type, $user_id, $source_type, $source_type); // Handle potential null bind
        }

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (float)($res['total'] ?? 0);
    }

    public function getTotalBalance($user_id, $includeSavings = false)
    {
        $source_balances = $this->getBalancesByAllSources($user_id);
        $total = 0;
        foreach ($source_balances as $sb) {
            if ($sb['source'] !== 'Savings') {
                $total += $sb['balance'];
            }
        }

        if ($includeSavings) {
            $total += $this->getTotalSavings($user_id);
        }

        return $total;
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
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND LOWER(source_type) = LOWER(?)) as total_saved,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND LOWER(source_type) = LOWER(?) AND expense_source = 'Savings') as total_spent,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND LOWER(source_type) = LOWER(?) $monthFilter) as monthly_saved,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND LOWER(source_type) = LOWER(?) AND expense_source = 'Savings' $monthFilter) as monthly_spent
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
            $sourcePart = (strtolower($source_type) === 'cash') ? "(LOWER(source_type) = 'cash' OR source_type = '0' OR source_type IS NULL)" : "LOWER(source_type) = LOWER(?)";
            $bindSource = (strtolower($source_type) === 'cash') ? null : $source_type;

            $sql = "
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND $sourcePart) as total_allowance,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND $sourcePart AND expense_source = 'Allowance') as total_expense,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND $sourcePart) as total_savings,
                    (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND $sourcePart $monthFilter) as monthly_allowance,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND $sourcePart AND expense_source = 'Allowance' $monthFilter) as monthly_expense,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND $sourcePart $monthFilter) as monthly_savings
            ";
            $stmt = $this->conn->prepare($sql);
            if ($bindSource) {
                $stmt->bind_param("isisisisisis", $user_id, $bindSource, $user_id, $bindSource, $user_id, $bindSource, $user_id, $bindSource, $user_id, $bindSource, $user_id, $bindSource);
            } else {
                $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
            }
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
        $stmt = $this->conn->prepare("SELECT DISTINCT source_type FROM allowances WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_row()) {
            $type = trim($row[0]);
            if ($type && $type !== '0') {
                // Case-insensitive check to avoid duplicates (e.g., 'GCash' vs 'GCASH')
                $exists = false;
                foreach ($sources as $s) {
                    if (strcasecmp($s, $type) === 0) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $sources[] = $type;
                }
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
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
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
