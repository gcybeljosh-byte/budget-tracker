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
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND source_type = 'Cash' $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type = 'Cash' AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type = 'Cash' $dateFilter) as balance
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
                (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND source_type IN ('GCash', 'Maya', 'Bank', 'Electronic') $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type IN ('GCash', 'Maya', 'Bank', 'Electronic') AND expense_source = 'Allowance' $dateFilter) -
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type IN ('GCash', 'Maya', 'Bank', 'Electronic') $dateFilter) as balance
        ");
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float)($res['balance'] ?? 0);
    }

    public function getTotalSavings($user_id, $monthOnly = false)
    {
        $dateFilter = $monthOnly ? " AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')" : "";
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? $dateFilter) - 
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings' $dateFilter) as total
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Note: For goals UI, we use financial_goals table. 
        // For Wallet balance, we trust the savings transaction history.
        return (float)($res['total'] ?? 0);
    }

    public function getBalanceBySource($user_id, $expense_source, $source_type)
    {
        // For real-time balance validation (e.g. adding expenses), we use LIFETIME balance
        if ($expense_source === 'Savings') {
            return $this->getTotalSavings($user_id, false);
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
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? $dateFilter) as total_saved,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_source = 'Savings' $dateFilter) as total_spent
            ");
            $stmt->bind_param("ii", $user_id, $user_id);
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
            $sourceFilter = ($source_type === 'Cash') ? " = 'Cash'" : " IN ('GCash', 'Maya', 'Bank', 'Electronic')";
            $stmt = $this->conn->prepare("
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND source_type $sourceFilter $dateFilter) as total_allowance,
                    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND source_type $sourceFilter AND expense_source = 'Allowance' $dateFilter) as total_expense,
                    (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND source_type $sourceFilter $dateFilter) as total_savings
            ");
            $stmt->bind_param("iii", $user_id, $user_id, $user_id);
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
}
