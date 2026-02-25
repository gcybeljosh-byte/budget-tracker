<?php
// includes/AiHelper.php

class AiHelper
{
    private $conn;
    private $user_id;
    private $balanceHelper;
    private $notificationHelper;
    private $cached_symbol = null;

    public function __construct($db_connection, $user_id)
    {
        $this->conn = $db_connection;
        $this->user_id = $user_id;
        require_once __DIR__ . '/BalanceHelper.php';
        require_once __DIR__ . '/NotificationHelper.php';
        $this->balanceHelper = new BalanceHelper($this->conn);
        $this->notificationHelper = new NotificationHelper($this->conn);
    }

    private function getFinancialStats($startDate = null, $endDate = null)
    {
        // If specific dates are provided, we use aggregates (for today/yesterday)
        // Otherwise, for current balances, we use BalanceHelper

        $expenses = 0;
        $allowance = 0;
        $savings = 0;

        if ($startDate && $endDate) {
            // Expenses
            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $expenses = $row ? $row[0] : 0;
            $stmt->close();

            // Allowances
            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $allowance = $row ? $row[0] : 0;
            $stmt->close();

            // Savings
            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $savings = $row ? $row[0] : 0;
            $stmt->close();
        }

        return [
            'income' => (float)$allowance,
            'expenses' => (float)$expenses,
            'savings' => (float)$savings,
            'net_balance' => (float)($allowance - $expenses - $savings)
        ];
    }

    public function getUserContext()
    {
        $context = [];

        // 1. Fetch User Profile
        $stmt = $this->conn->prepare("SELECT first_name, last_name, email, preferred_currency, ai_tone, notif_budget, notif_low_balance, monthly_budget_goal, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = ($res) ? $res->fetch_assoc() : null;
        $context['user'] = $user;
        $context['role'] = $user['role'] ?? 'user';
        $context['currency'] = $user['preferred_currency'] ?? 'PHP';
        $context['ai_tone'] = $user['ai_tone'] ?? 'Professional';
        $context['budget_goal'] = $user['monthly_budget_goal'] ?? 0;
        $stmt->close();

        // 2. Detailed Time-Based Stats
        // Today
        $today = date('Y-m-d');
        $context['stats']['today'] = $this->getFinancialStats($today, $today);

        // Yesterday
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $context['stats']['yesterday'] = $this->getFinancialStats($yesterday, $yesterday);

        // This Month
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $context['stats']['this_month'] = $this->getFinancialStats($monthStart, $monthEnd);

        // This Year
        $yearStart = date('Y-01-01');
        $yearEnd = date('Y-12-31');
        $context['stats']['this_year'] = $this->getFinancialStats($yearStart, $yearEnd);


        // 3. Official Balances (Unified with BalanceHelper)
        $context['cash_balance'] = $this->balanceHelper->getCashBalance($this->user_id);
        $context['digital_balance'] = $this->balanceHelper->getDigitalBalance($this->user_id);
        $context['total_savings'] = $this->balanceHelper->getTotalSavings($this->user_id);

        $context['allowance'] = $context['cash_balance'] + $context['digital_balance']; // This is the standard "Remaining Balance"
        $context['gross_allowance'] = $context['stats']['this_month']['income'];
        $context['total_expenses'] = $context['stats']['this_month']['expenses'];
        $context['balance'] = $context['allowance']; // Alias for consistency

        // 4. Fetch Expenses Breakdown (Category) - Keep this as it's useful
        $currentMonth = date('Y-m');
        $stmt = $this->conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ? GROUP BY category");
        $stmt->bind_param("is", $this->user_id, $currentMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        $expenses = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $expenses[$row['category']] = $row['total'];
            }
        }
        $context['expenses_by_category'] = $expenses;
        $stmt->close();

        // 5. Inject Recent Datasets (Raw Rows) - Trimmed for speed
        $stmt = $this->conn->prepare("SELECT id, date, category, description, amount, source_type FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 20");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['expenses'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT id, date, description, amount, source_type FROM allowances WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 10");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['allowances'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 6. Recent Journals
        $context['full_datasets']['journals'] = $this->getRecentJournals(5);

        // 7. Report History
        $context['full_datasets']['reports'] = $this->getReportHistory(5);

        // 8. Financial Goals
        $stmt = $this->conn->prepare("SELECT id, title, target_amount, saved_amount, deadline, status FROM financial_goals WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['financial_goals'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 9. Budget Limits (with current-month spending per category)
        $currentMonth = date('Y-m');
        $stmt = $this->conn->prepare("
            SELECT cl.category, cl.limit_amount,
                   COALESCE(SUM(e.amount), 0) as spent_this_month
            FROM category_limits cl
            LEFT JOIN expenses e ON e.user_id = cl.user_id
                AND e.category = cl.category
                AND DATE_FORMAT(e.date, '%Y-%m') = ?
            WHERE cl.user_id = ?
            GROUP BY cl.id, cl.category, cl.limit_amount
            ORDER BY cl.category ASC
        ");
        $stmt->bind_param("si", $currentMonth, $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['budget_limits'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 11. Upcoming Bills (Next 7 days)
        $stmt = $this->conn->prepare("SELECT id, title, amount, due_date, category, frequency FROM recurring_payments WHERE user_id = ? AND is_active = 1 AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY due_date ASC");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['upcoming_bills'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 10. Hub Summary (Snapshot for Dashboard Hub parity)
        $context['hub_summary'] = [
            'journal' => !empty($context['full_datasets']['journals']) ? $context['full_datasets']['journals'][0]['date'] : 'No entries',
            'goals' => !empty($context['full_datasets']['financial_goals']) ? count(array_filter($context['full_datasets']['financial_goals'], fn($g) => $g['status'] === 'Active')) . "/" . count($context['full_datasets']['financial_goals']) . " active" : "0/0 active",
            'forecast' => $context['stats']['this_month']['expenses'] > 0 ? $context['balance'] / ($context['stats']['this_month']['expenses'] / max(1, (int)date('j'))) : 0, // Simplified runway
            'reports_this_month' => count(array_filter($context['full_datasets']['reports'] ?? [], fn($r) => strpos($r['created_at'], date('Y-m-d')) === 0)),
            'top_category' => !empty($context['expenses_by_category']) ? array_keys($context['expenses_by_category'], max($context['expenses_by_category']))[0] : 'None'
        ];

        // 12. ROLE-BASED SYSTEM DATA (Admins/Superadmins only)
        if ($context['role'] === 'superadmin' || $context['role'] === 'admin') {
            // Platform-wide counts
            $system = [];

            $res = $this->conn->query("SELECT COUNT(*) as total FROM users");
            $system['total_users'] = ($res && $row = $res->fetch_assoc()) ? $row['total'] : 0;

            $res = $this->conn->query("SELECT COUNT(*) as active FROM users WHERE role != 'inactive'");
            $system['active_users'] = ($res && $row = $res->fetch_assoc()) ? $row['active'] : 0;

            // Monthly transaction volume (Sanitized)
            $res = $this->conn->query("SELECT COUNT(*) as count FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = '" . date('Y-m') . "'");
            $system['total_expenses_count'] = ($res && $row = $res->fetch_assoc()) ? $row['count'] : 0;

            if ($context['role'] === 'superadmin') {
                // Sensitive Superadmin logs (Fixed column: action_type)
                $res = $this->conn->query("SELECT action_type, description, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 15");
                $system['recent_system_activity'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];

                $res = $this->conn->query("SELECT first_name, last_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                $system['newest_users'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
            }

            $context['system_metrics'] = $system;
        }

        return $context;
    }

    private function getRecentJournals($limit = 10)
    {
        $stmt = $this->conn->prepare("SELECT j.id, j.date, j.end_date, j.title, j.notes, j.financial_status FROM journals j WHERE j.user_id = ? ORDER BY j.date DESC, j.id DESC LIMIT ?");
        $stmt->bind_param("ii", $this->user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $journals = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // Fetch lines for each journal
        if (!empty($journals)) {
            foreach ($journals as &$journal) {
                $stmt = $this->conn->prepare("SELECT account_title, debit, credit FROM journal_lines WHERE journal_id = ?");
                $stmt->bind_param("i", $journal['id']);
                $stmt->execute();
                $resLines = $stmt->get_result();
                $journal['lines'] = ($resLines) ? $resLines->fetch_all(MYSQLI_ASSOC) : [];
                $stmt->close();
            }
        }
        return $journals;
    }

    private function getReportHistory($limit = 10)
    {
        // Ensure table exists first in case save_report hasn't run yet
        $this->conn->query("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            report_type VARCHAR(50) DEFAULT 'monthly',
            period VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $stmt = $this->conn->prepare("SELECT id, filename, report_type, period, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $this->user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $reports = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $reports;
    }

    public function generateSystemPrompt()
    {
        $context = $this->getUserContext();
        $name = $context['user']['first_name'] ?? 'User';
        $currentMonth = date('F Y');
        $currentDate = date('F j, Y');
        $todayDate = date('Y-m-d');
        $currencyCode = $context['currency'];
        $budgetGoal = number_format($context['budget_goal'], 2);

        $symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥'];
        $symbol = $symbols[$currencyCode] ?? '₱';

        $prompt = "# IDENTITY\n";
        $prompt .= "You are an EXPERT AI Help Desk and Financial Advisor for the Budget Tracker System. Your goal is to be precise, restrictive, and highly professional.\n";
        $prompt .= "You were engineered by Cybel Josh A. Gamido (Super Admin) from USM.\n\n";

        $prompt .= "# GREETING PROTOCOL\n";
        $prompt .= "1. RESPONSE TO GREETINGS: Respond with a professional greeting to {$name}.\n";
        $prompt .= "2. FOCUS: Immediately pivot to financial assistance. Do not engage in extended small talk.\n\n";

        $prompt .= "# USER PROFILE\n";
        $prompt .= "User Name: {$name}\n";
        $prompt .= "Monthly Budget Goal: {$symbol}{$budgetGoal}\n";
        $prompt .= "Currency: {$currencyCode} ({$symbol})\n";
        $prompt .= "Today's Date: {$todayDate} ({$currentDate})\n\n";

        $prompt .= "# FINANCIAL DATASET\n";
        $jsonContext = json_encode($context);
        if ($jsonContext === false) {
            // Trim context if too large/complex for JSON
            unset($context['full_datasets']);
            $jsonContext = json_encode($context);
        }
        $prompt .= "```json\n" . ($jsonContext ?: "{}") . "\n```\n\n";

        $prompt .= "# CRITICAL RULES (EXPERT DOMAIN)\n";
        $prompt .= "1. CAPABILITIES: You can ONLY perform these actions: `add_expense`, `add_allowance`, `add_savings`, `create_goal`, `add_bill`.\n";
        $prompt .= "2. NO JOURNALS: You CANNOT generate or add journals. Journals are manual-only on the Journal page. If asked, guide the user to that page.\n";
        $prompt .= "3. ALLOWANCE SOURCE: If a user asks to add an allowance, you MUST ensure they specify the source (Cash or Bank). If they don't, you MUST ask for it before processing the action.\n";
        $prompt .= "4. STEP-BY-STEP GUIDES: For any feature or page NOT related to adding records (e.g., Analytics, Settings, Profile), you MUST only provide a concise step-by-step guide on how to use it. Do not perform actions for these.\n";
        $prompt .= "5. SCOPE: Limit your responses strictly to the user's prompts. Do not provide unsolicited advice unless it's a direct saving tip related to their data.\n";
        $prompt .= "6. ROLE-BASED ISOLATION: Strictly adhere to the data isolation rules for Regular User, Admin, and Superadmin as defined in your training.\n";
        $prompt .= "7. Currency: Always use {$symbol} for amounts.\n";
        $prompt .= "8. JSON Output: Output actions in strictly valid JSON format.\n\n";

        $prompt .= "# CAPABILITIES & COMMANDS\n";
        $prompt .= "## 1. TRANSACTIONS\n";
        $prompt .= "- `add_expense`: Capture `amount`, `category`, `description`, `source_type` (Cash/Bank), and `expense_source` (Allowance/Savings).\n";
        $prompt .= "- `add_allowance`: Capture `amount`, `description`, and `source_type` (Cash/Bank). **MANDATORY: Ask for source_type if missing.**\n";
        $prompt .= "- `add_savings`: Capture `amount`, `description`, and `source_type` (Cash/Bank).\n";
        $prompt .= "## 2. GOALS & BILLS\n";
        $prompt .= "- `create_goal`: Capture `title`, `target_amount`, and `deadline` (YYYY-MM-DD).\n";
        $prompt .= "- `add_bill`: Capture `title`, `amount`, `due_date` (YYYY-MM-DD), `category`, `frequency` (monthly/yearly/weekly), and `source_type` (Cash/Bank). This also applies to Subscriptions.\n\n";

        $prompt .= "# OUTPUT FORMAT\n";
        $prompt .= "Respond in JSON format IF performing an action. Otherwise, use clean Markdown.\n\n";
        $prompt .= "Schema for Actions:\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"response_message\": \"...\",\n";
        $prompt .= "  \"actions\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"type\": \"add_expense | add_allowance | add_savings | create_goal | add_bill\",\n";
        $prompt .= "      \"data\": { ... }\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n";
        $prompt .= "```\n";

        return $prompt;
    }

    public function getResponse($userMessage)
    {
        // First check for simple intent to bypass LLM if needed (optional optimization)
        // But for full features, we prefer LLM. However, we'll keep detectIntent as a fallback or for simple commands.
        // Actually, let's rely on LLM for the new "Smart" features.

        $prompt = $this->generateSystemPrompt();

        if (defined('AI_PROVIDER') && AI_PROVIDER === 'simulation') {
            $simResponse = $this->getSimulationResponse($userMessage);
            return $this->processAiJsonOutput($simResponse, $userMessage);
        }

        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => $userMessage]
        ];

        // Call LLM
        $response = $this->callLLM($messages);

        // Parse JSON response
        return $this->processAiJsonOutput($response, $userMessage);
    }

    private function processAiJsonOutput($rawResponse, $userMessage)
    {
        if (empty($rawResponse)) {
            return ['message' => "I'm sorry, I couldn't reach the AI module. Let's try again.", 'action_performed' => false];
        }

        // Handle case where rawResponse might be already a JSON error string from callGemini
        $testJson = json_decode($rawResponse, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($testJson['response_message'])) {
            return ['message' => $testJson['response_message'], 'action_performed' => false];
        }

        // Clean markdown and find the first JSON object
        $cleanJson = trim($rawResponse);
        if (preg_match('/\{.*\}/s', $rawResponse, $matches)) {
            $cleanJson = $matches[0];
        }

        // Final safety: Remove any trailing text after the last }
        $lastBrace = strrpos($cleanJson, '}');
        if ($lastBrace !== false) {
            $cleanJson = substr($cleanJson, 0, $lastBrace + 1);
        }

        $data = json_decode($cleanJson, true);

        // If not valid JSON, treat as plain text response (empathetic fallback)
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'message' => strip_tags($rawResponse),
                'action_performed' => false
            ];
        }

        $responseMessage = $data['response_message'] ?? "I've processed your request based on your records.";
        $actions = (isset($data['actions']) && is_array($data['actions'])) ? $data['actions'] : [];
        $actionPerformed = false;
        $lastActionType = '';

        foreach ($actions as $action) {
            if (!isset($action['type']) || !isset($action['data'])) continue;

            $type = $action['type'];
            $payload = $action['data'];

            try {
                switch ($type) {
                    case 'add_expense':
                        $this->addExpenseAction($payload);
                        $actionPerformed = true;
                        $lastActionType = 'add_expense';
                        break;
                    case 'add_allowance':
                        $this->addAllowanceAction($payload);
                        $actionPerformed = true;
                        $lastActionType = 'add_allowance';
                        break;
                    case 'add_savings':
                        $this->addSavingsAction($payload);
                        $actionPerformed = true;
                        $lastActionType = 'add_savings';
                        break;
                    case 'create_goal':
                        $this->createGoalAction($payload);
                        $actionPerformed = true;
                        $lastActionType = 'create_goal';
                        break;
                    case 'add_bill':
                        $this->addBillAction($payload);
                        $actionPerformed = true;
                        $lastActionType = 'add_bill';
                        break;
                }
            } catch (Exception $e) {
                // Silently fail or log for background actions
                continue;
            }
        }

        return [
            'message' => $responseMessage,
            'action_performed' => $actionPerformed,
            'action_type' => $lastActionType
        ];
    }

    // ========================================================================
    // ACTION HANDLERS
    // ========================================================================

    private function addBillAction($data)
    {
        $title = $data['title'] ?? 'New Bill';
        $amount = floatval($data['amount'] ?? 0);
        $due_date = $data['due_date'] ?? date('Y-m-d');
        $category = $data['category'] ?? 'Utilities';
        $frequency = $data['frequency'] ?? 'monthly';
        $source = $data['source_type'] ?? 'Cash';

        if ($amount <= 0) return ['success' => false];

        $stmt = $this->conn->prepare("INSERT INTO recurring_payments (user_id, title, amount, category, due_date, frequency, source_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdssss", $this->user_id, $title, $amount, $category, $due_date, $frequency, $source);
        $stmt->execute();
        $stmt->close();

        $this->notificationHelper->addNotification($this->user_id, 'bill_added', "Expert AI recorded your bill: $title ($amount)");
        logActivity($this->conn, $this->user_id, 'bill_add', "AI added bill: '$title' ($amount)");
        return ['success' => true];
    }

    private function addExpenseAction($data)
    {
        if (!isset($data['amount']) || $data['amount'] <= 0) return ['success' => false, 'message' => "Amount needed."];

        $stmt = $this->conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, source_type, expense_source) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $source_type = $data['source_type'] ?? 'Cash';
        $expense_source = $data['expense_source'] ?? 'Allowance';
        $category = $data['category'] ?? 'Other';
        $desc = $data['description'] ?? 'Expense';
        $date = $data['date'] ?? date('Y-m-d');

        $stmt->bind_param("isssdss", $this->user_id, $date, $category, $desc, $data['amount'], $source_type, $expense_source);
        $stmt->execute();
        $stmt->close();

        $this->notificationHelper->checkLowAllowance($this->user_id);
        $this->notificationHelper->checkBudgetLimit($this->user_id);
        logActivity($this->conn, $this->user_id, 'expense_add', "AI added expense: $desc ($category) - {$data['amount']}");
        return ['success' => true];
    }

    private function addAllowanceAction($data)
    {
        if (!isset($data['amount']) || $data['amount'] <= 0) return ['success' => false, 'message' => "Amount needed."];

        $stmt = $this->conn->prepare("INSERT INTO allowances (user_id, date, description, amount, source_type) VALUES (?, ?, ?, ?, ?)");
        $source = $data['source_type'] ?? 'Cash';
        $desc = $data['description'] ?? 'Allowance';
        $date = $data['date'] ?? date('Y-m-d');

        $stmt->bind_param("issds", $this->user_id, $date, $desc, $data['amount'], $source);
        $stmt->execute();
        $stmt->close();

        $this->notificationHelper->addNotification($this->user_id, 'allowance_added', "Expert AI recorded an allowance: $desc ({$data['amount']})");
        $this->balanceHelper->syncBudgetLimits($this->user_id);
        logActivity($this->conn, $this->user_id, 'allowance_add', "AI added allowance: $desc - {$data['amount']} ($source)");
        return ['success' => true];
    }

    private function addSavingsAction($data)
    {
        if (!isset($data['amount']) || $data['amount'] <= 0) return ['success' => false, 'message' => "Amount needed."];

        $stmt = $this->conn->prepare("INSERT INTO savings (user_id, date, description, amount, source_type) VALUES (?, ?, ?, ?, ?)");
        $desc = $data['description'] ?? 'Savings';
        $date = $data['date'] ?? date('Y-m-d');
        $source_type = $data['source_type'] ?? 'Cash';

        $stmt->bind_param("issds", $this->user_id, $date, $desc, $data['amount'], $source_type);
        $stmt->execute();
        $stmt->close();

        $this->notificationHelper->addNotification($this->user_id, 'savings_added', "Expert AI recorded a savings deposit: $desc ({$data['amount']})");
        logActivity($this->conn, $this->user_id, 'savings_add', "AI added savings: $desc - {$data['amount']} ($source_type)");
        return ['success' => true];
    }

    private function createGoalAction($data)
    {
        $title  = trim($data['title'] ?? '');
        $target = floatval($data['target_amount'] ?? 0);
        if (!$title || $target <= 0) return ['success' => false, 'message' => 'Title and target amount are required.'];

        $deadline = !empty($data['deadline']) ? $data['deadline'] : null;
        $stmt = $this->conn->prepare("INSERT INTO financial_goals (user_id, title, target_amount, deadline) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $this->user_id, $title, $target, $deadline);
        $stmt->execute();
        $stmt->close();
        logActivity($this->conn, $this->user_id, 'goal_add', "AI created goal: '$title' (Target: $target)");
        return ['success' => true];
    }

    private function addCategoryAction($data)
    {
        if (empty($data['category_name'])) return ['success' => false];
        $stmt = $this->conn->prepare("INSERT IGNORE INTO categories (user_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $this->user_id, $data['category_name']);
        $stmt->execute();
        $stmt->close();
        return ['success' => true];
    }

    // ========================================================================
    // LEGACY / REGEX HELPERS (Kept for fallback/utility)
    // ========================================================================

    private function detectIntent($userMessage)
    {
        $msg = strtolower(trim($userMessage));
        // Simplified detection mainly for fallback
        if (preg_match('/\b(delete|remove)\b/i', $msg)) {
            if (preg_match('/\b(allowance)\b/i', $msg)) return ['intent' => 'delete_allowance', 'confidence' => 0.6];
            return ['intent' => 'delete_expense', 'confidence' => 0.6];
        }
        if (preg_match('/\b(edit|update)\b/i', $msg)) {
            if (preg_match('/\b(allowance)\b/i', $msg)) return ['intent' => 'edit_allowance', 'confidence' => 0.6];
            return ['intent' => 'edit_expense', 'confidence' => 0.6];
        }
        return ['intent' => 'query', 'confidence' => 0];
    }

    private function executeAction($intent, $userMessage)
    {
        // Only implementing delete/edit here as add is handled by LLM JSON
        switch ($intent) {
            case 'delete_expense':
                return $this->deleteExpenseAction($userMessage);
            case 'delete_allowance':
                return $this->deleteAllowanceAction($userMessage);
            case 'edit_expense':
                return $this->editExpenseAction($userMessage);
            case 'edit_allowance':
                return $this->editAllowanceAction($userMessage);
        }
        return ['success' => false, 'message' => 'Unknown action'];
    }

    private function extractSpecificDate($userMessage)
    {
        $msg = strtolower($userMessage);
        if (preg_match('/\b(yesterday)\b/i', $msg)) return date('Y-m-d', strtotime('-1 day'));
        if (preg_match('/\b(today)\b/i', $msg)) return date('Y-m-d');
        if (preg_match('/\b([0-9]{4}-[0-9]{2}-[0-9]{2})\b/', $userMessage, $matches)) return $matches[1];
        return null;
    }

    private function getRecentRecords($type, $date = null, $limit = 5)
    {
        $table = ($type === 'expense') ? 'expenses' : 'allowances';
        $sql = "SELECT id, date, description, amount FROM {$table} WHERE user_id = ?";
        if ($date) $sql .= " AND date = ?";
        $sql .= " ORDER BY id DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if ($date) $stmt->bind_param("isi", $this->user_id, $date, $limit);
        else $stmt->bind_param("ii", $this->user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function deleteExpenseAction($userMessage)
    {
        $date = $this->extractSpecificDate($userMessage);
        $records = $this->getRecentRecords('expense', $date, 1);
        if (empty($records)) return ['success' => false, 'message' => "No record found."];
        $stmt = $this->conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $records[0]['id'], $this->user_id);
        $stmt->execute();
        return ['success' => true, 'message' => "Expense deleted."];
    }

    private function deleteAllowanceAction($userMessage)
    {
        $date = $this->extractSpecificDate($userMessage);
        $records = $this->getRecentRecords('allowance', $date, 1);
        if (empty($records)) return ['success' => false, 'message' => "No record found."];
        $stmt = $this->conn->prepare("DELETE FROM allowances WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $records[0]['id'], $this->user_id);
        $stmt->execute();
        return ['success' => true, 'message' => "Allowance deleted."];
    }

    private function editExpenseAction($userMessage)
    {
        // Simplified edit - mostly placeholder as LLM handles complex stuff better
        // But for "edit last expense to 500", we can try.
        $date = $this->extractSpecificDate($userMessage);
        $records = $this->getRecentRecords('expense', $date, 1);
        if (empty($records)) return ['success' => false, 'message' => "No record found."];

        if (preg_match('/\b([0-9]+)\b/', $userMessage, $matches)) {
            $amount = $matches[1];
            $stmt = $this->conn->prepare("UPDATE expenses SET amount = ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $records[0]['id']);
            $stmt->execute();
            return ['success' => true, 'message' => "Expense updated to {$amount}."];
        }
        return ['success' => false, 'message' => "Could not understand update."];
    }

    private function editAllowanceAction($userMessage)
    {
        return ['success' => false, 'message' => "Edit allowance not fully implemented in fallback mode."];
    }

    // ========================================================================
    // API CALLS
    // ========================================================================

    private function callLLM($messages)
    {
        $apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';
        $model = defined('AI_MODEL') ? AI_MODEL : 'gemini-2.5-flash';

        if (defined('AI_PROVIDER') && AI_PROVIDER === 'gemini') {
            return $this->callGemini($apiKey, $model, $messages);
        } else {
            return $this->callOpenAI($apiKey, $model, $messages);
        }
    }

    private function callGemini($apiKey, $model, $messages)
    {
        $apiKey = trim($apiKey);
        $model = trim($model);
        // Use v1beta for support of system_instruction and response_mime_type
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $messages_item) {
            if ($messages_item['role'] === 'system') {
                $systemInstruction = ['parts' => [['text' => $messages_item['content']]]];
            } else {
                $role = ($messages_item['role'] === 'user') ? 'user' : 'model';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $messages_item['content']]]
                ];
            }
        }

        $body = [
            'contents' => $contents,
            'generation_config' => [
                'temperature' => 0.7,
                'response_mime_type' => 'application/json'
            ]
        ];

        if ($systemInstruction) {
            $body['system_instruction'] = $systemInstruction;
        }


        // USE PROXY IF DEFINED
        $finalUrl = $url;
        if (defined('AI_PROXY_URL') && !empty(AI_PROXY_URL)) {
            // Append key and model to the proxy URL to ensure the proxy can forward them correctly
            $proxyBase = AI_PROXY_URL;
            $separator = (strpos($proxyBase, '?') === false) ? '?' : '&';
            $finalUrl = "{$proxyBase}{$separator}key={$apiKey}&model={$model}";
        }

        $ch = curl_init($finalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);           // max 25s for full response
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);      // max 8s to establish connection

        // Handle local development SSL issues
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        if ($response === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return json_encode(['response_message' => "Network error: $error_msg", 'actions' => []]);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }

        $apiErrorMessage = 'Unknown Gemini API Error';
        if (isset($json['error']['message'])) {
            $apiErrorMessage = $json['error']['message'];
        } elseif (isset($json['error']) && is_string($json['error'])) {
            $apiErrorMessage = $json['error'];
        } elseif (isset($json['message'])) {
            $apiErrorMessage = $json['message'];
        }

        // DIAGNOSTIC INFO
        $proxyUsed = (defined('AI_PROXY_URL') && !empty(AI_PROXY_URL)) ? 'Yes' : 'No';
        $keyHint = !empty($apiKey) ? substr($apiKey, 0, 8) . '...' . substr($apiKey, -4) : 'Empty';

        // Graceful Error Handling for Quota (HTTP 429)
        if ($http_code == 429) {
            return json_encode([
                'response_message' => "It looks like your daily AI limit has been reached! Please try again tomorrow to continue our chat.",
                'actions' => []
            ]);
        }

        return json_encode([
            'response_message' => "Gemini API Error: $apiErrorMessage (HTTP $http_code)",
            'debug_info' => [
                'proxy_active' => $proxyUsed,
                'api_key_hint' => $keyHint,
                'model' => $model
            ],
            'actions' => []
        ]);
    }

    private function callOpenAI($apiKey, $model, $messages)
    {
        $url = "https://api.openai.com/v1/chat/completions";

        $body = [
            'model' => $model,
            'messages' => $messages,
            'response_format' => ['type' => 'json_object']
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return json_encode(['response_message' => "OpenAI Connection Error: $error_msg", 'actions' => []]);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if (isset($json['choices'][0]['message']['content'])) {
            return $json['choices'][0]['message']['content'];
        }

        $apiError = $json['error']['message'] ?? 'Unknown OpenAI Error';
        return json_encode(['response_message' => "OpenAI API Error: $apiError (HTTP $http_code)", 'actions' => []]);
    }

    // --- Inactivity Timeout ---
    public function enforceChatTimeout($timeoutMinutes = 10)
    {
        // Find the latest message timestamp for this user
        $stmt = $this->conn->prepare("SELECT created_at FROM ai_chat_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $lastMessageTime = strtotime($row['created_at']);
            $diff = time() - $lastMessageTime;

            // If more than $timeoutMinutes minutes (e.g. 600 seconds) have passed, clear history
            if ($diff > ($timeoutMinutes * 60)) {
                $delStmt = $this->conn->prepare("DELETE FROM ai_chat_history WHERE user_id = ?");
                $delStmt->bind_param("i", $this->user_id);
                $delStmt->execute();
                $delStmt->close();
                return true; // History was cleared
            }
        }
        $stmt->close();
        return false;
    }

    private function getSimulationResponse($userMessage)
    {
        return json_encode([
            'response_message' => "Simulation Mode: I received your message: '$userMessage'. Configure AI_KEY for real responses.",
            'actions' => []
        ]);
    }
}
