<?php
// includes/AiHelper.php

class AiHelper
{
    private $conn;
    private $user_id;
    private $balanceHelper;
    private $notificationHelper;

    public function __construct($db_connection, $user_id)
    {
        $this->conn = $db_connection;
        $this->user_id = $user_id;
        require_once __DIR__ . '/BalanceHelper.php';
        require_once __DIR__ . '/NotificationHelper.php';
        $this->balanceHelper = new BalanceHelper($this->conn);
        $this->notificationHelper = new NotificationHelper($this->conn);
    }

    // ========================================================================
    // FINANCIAL DATA CONTEXT
    // ========================================================================

    private function getFinancialStats($startDate = null, $endDate = null)
    {
        $expenses = 0;
        $allowance = 0;
        $savings = 0;

        if ($startDate && $endDate) {
            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $expenses = $row ? $row[0] : 0;
            $stmt->close();

            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $allowance = $row ? $row[0] : 0;
            $stmt->close();

            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $savings = $row ? $row[0] : 0;
            $stmt->close();
        }

        return [
            'income'      => (float)$allowance,
            'expenses'    => (float)$expenses,
            'savings'     => (float)$savings,
            'net_balance' => (float)($allowance - $expenses - $savings)
        ];
    }

    public function getUserContext()
    {
        $context = [];

        // 1. User Profile
        $stmt = $this->conn->prepare("SELECT first_name, last_name, email, preferred_currency, ai_tone, notif_budget, notif_low_balance, monthly_budget_goal, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = ($res) ? $res->fetch_assoc() : null;
        $context['user']         = $user;
        $context['role']         = $user['role'] ?? 'user';
        $context['currency']     = $user['preferred_currency'] ?? 'PHP';
        $context['ai_tone']      = $user['ai_tone'] ?? 'Professional';
        $context['budget_goal']  = $user['monthly_budget_goal'] ?? 0;
        $stmt->close();

        // 2. Time-based financial stats
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');
        $yearStart  = date('Y-01-01');
        $yearEnd    = date('Y-12-31');

        $context['stats']['today']      = $this->getFinancialStats($today, $today);
        $context['stats']['yesterday']  = $this->getFinancialStats($yesterday, $yesterday);
        $context['stats']['this_month'] = $this->getFinancialStats($monthStart, $monthEnd);
        $context['stats']['this_year']  = $this->getFinancialStats($yearStart, $yearEnd);

        // 3. Live balances (via BalanceHelper)
        $context['cash_balance']    = $this->balanceHelper->getCashBalance($this->user_id);
        $context['digital_balance'] = $this->balanceHelper->getDigitalBalance($this->user_id);
        $context['total_savings']   = $this->balanceHelper->getTotalSavings($this->user_id);
        $context['allowance']       = $context['cash_balance'] + $context['digital_balance'];
        $context['gross_allowance'] = $context['stats']['this_month']['income'];
        $context['total_expenses']  = $context['stats']['this_month']['expenses'];
        $context['balance']         = $context['allowance'];

        // 4. Expenses by category (current month)
        $currentMonth = date('Y-m');
        $stmt = $this->conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ? GROUP BY category");
        $stmt->bind_param("is", $this->user_id, $currentMonth);
        $stmt->execute();
        $result   = $stmt->get_result();
        $expenses = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $expenses[$row['category']] = $row['total'];
            }
        }
        $context['expenses_by_category'] = $expenses;
        $stmt->close();

        // 5. Recent expenses (last 20)
        $stmt = $this->conn->prepare("SELECT id, date, category, description, amount, source_type FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 20");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['expenses'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 6. Recent allowances (last 10)
        $stmt = $this->conn->prepare("SELECT id, date, description, amount, source_type FROM allowances WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 10");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['allowances'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 7. Recent journals
        $context['full_datasets']['journals'] = $this->getRecentJournals(5);

        // 8. Report history
        $context['full_datasets']['reports'] = $this->getReportHistory(5);

        // 9. Financial goals
        $stmt = $this->conn->prepare("SELECT id, title, target_amount, saved_amount, deadline, status FROM financial_goals WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['financial_goals'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 10. Budget limits with current-month spending
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

        // 11. Upcoming bills (next 7 days)
        $stmt = $this->conn->prepare("SELECT id, title, amount, due_date, category, frequency FROM recurring_payments WHERE user_id = ? AND is_active = 1 AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY due_date ASC");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $context['full_datasets']['upcoming_bills'] = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 12. Hub summary snapshot
        $context['hub_summary'] = [
            'journal'            => !empty($context['full_datasets']['journals']) ? $context['full_datasets']['journals'][0]['date'] : 'No entries',
            'goals'              => !empty($context['full_datasets']['financial_goals'])
                ? count(array_filter($context['full_datasets']['financial_goals'], fn($g) => $g['status'] === 'Active')) . "/" . count($context['full_datasets']['financial_goals']) . " active"
                : "0/0 active",
            'top_category'       => !empty($context['expenses_by_category'])
                ? array_keys($context['expenses_by_category'], max($context['expenses_by_category']))[0]
                : 'None'
        ];

        // 13. Role-based system data (admin/superadmin)
        if ($context['role'] === 'superadmin' || $context['role'] === 'admin') {
            $system = [];

            $res = $this->conn->query("SELECT COUNT(*) as total FROM users");
            $system['total_users'] = ($res && $row = $res->fetch_assoc()) ? $row['total'] : 0;

            $res = $this->conn->query("SELECT COUNT(*) as active FROM users WHERE role != 'inactive'");
            $system['active_users'] = ($res && $row = $res->fetch_assoc()) ? $row['active'] : 0;

            $res = $this->conn->query("SELECT COUNT(*) as count FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = '" . date('Y-m') . "'");
            $system['total_expenses_count'] = ($res && $row = $res->fetch_assoc()) ? $row['count'] : 0;

            if ($context['role'] === 'superadmin') {
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
        $res      = $stmt->get_result();
        $journals = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (!empty($journals)) {
            foreach ($journals as &$journal) {
                $stmt = $this->conn->prepare("SELECT account_title, debit, credit FROM journal_lines WHERE journal_id = ?");
                $stmt->bind_param("i", $journal['id']);
                $stmt->execute();
                $resLines        = $stmt->get_result();
                $journal['lines'] = ($resLines) ? $resLines->fetch_all(MYSQLI_ASSOC) : [];
                $stmt->close();
            }
        }
        return $journals;
    }

    private function getReportHistory($limit = 10)
    {
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
        $res     = $stmt->get_result();
        $reports = ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $reports;
    }

    private function getKnowledgeBase()
    {
        return [
            'General & Identity' => [
                'What is Budget Buddy?' => 'Budget Buddy is an AI Help Desk and Financial Advisor within the Budget Tracker system, designed to guide, advise, and educate users on their personal finances.',
                'Who created Budget Buddy?' => 'Budget Buddy was built by Cybel Josh A. Gamido (Superadmin) from USM.',
                'What can Budget Buddy do?' => 'I can answer financial questions, give personalized advice, guide you through system features, explain modules, highlight risks, and suggest actions. I cannot perform system actions like adding or editing records.',
            ],
            'System FAQs' => [
                'How to reset financial data?' => 'Navigate to **Settings** -> **Security** section. Click the **Edit** button to unlock fields, then click **Reset All Financial Data**. This is irreversible.',
                'How to set a budget goal?'    => 'Go to **Settings** -> **Preferences**. You can set your **Monthly Budget Goal** there. This goal is used to calculate your safe-to-spend limit.',
                'Where are my old transactions?' => 'You can view historical transactions in the **Reports** or **Statements** pages by selecting a past month.',
                'How do budget limits work?' => 'When you set a limit for a category (e.g., Food), the system tracks your spending against it. You see progress bars on the **Expenses** page and warnings if you exceed your limit.',
                'What is Safe-to-Spend?' => 'It is a real-time calculation: (Remaining Balance - Upcoming Bills) / Days left in month. It tells you exactly how much you can spend per day without going broke.',
                'How do I add an expense?' => 'Go to the Expenses page, click "+ Add Expense", fill in the details (Amount, Category, Description, Source, Source Type), and click "Save".',
                'How do I track my savings?' => 'Use the Savings page to add amounts you set aside. These funds are deducted from your wallet balance and tracked separately.',
                'How do I change my AI Tone?' => 'On the Settings page, under "AI Tone", you can choose between Professional, Friendly, or Concise.',
            ],
            'Expert Financial Rules' => [
                'Rule 1: Savings First' => 'Always treat savings as a non-negotiable expense. Try to save at least 20% of your income.',
                'Rule 2: The 50/30/20' => '50% for Needs (Bills, Food), 30% for Wants (Entertainment, Hobbies), 20% for Savings/Debt.',
                'Rule 3: Emergency Fund' => 'Aim for 3-6 months of expenses in a liquid savings wallet for unexpected life events.',
            ]
        ];
    }

    private function getActionLinks()
    {
        return [
            'Dashboard'   => '[🏠 Dashboard](core/dashboard.php)',
            'Expenses'    => '[💸 Expenses](core/expenses.php)',
            'Allowance'   => '[💰 Allowance](core/allowance.php)',
            'Savings'     => '[🐷 Savings](core/savings.php)',
            'Bills'       => '[🧾 Bills Hub](core/bills.php)',
            'Goals'       => '[🎯 Goals](core/goals.php)',
            'Analytics'   => '[📊 Trends & Analytics](core/analytics.php)',
            'Reports'     => '[📋 Reports](core/reports.php)',
            'Statements'  => '[📜 Statements](core/statements.php)',
            'Settings'    => '[⚙️ Settings](core/settings.php)',
            'Profile'     => '[👤 My Profile](core/profile.php)',
            'History'     => '[📝 Chat History](core/history_log.php)',
        ];
    }

    // ========================================================================
    // SYSTEM PROMPT — INFORMATIONAL ADVISOR (NO ACTIONS)
    // ========================================================================

    public function generateSystemPrompt()
    {
        $context      = $this->getUserContext();
        $name         = $context['user']['first_name'] ?? 'User';
        $lastName     = $context['user']['last_name'] ?? '';
        $fullName     = trim("$name $lastName");
        $currentMonth = date('F Y');
        $currentDate  = date('F j, Y');
        $todayDate    = date('Y-m-d');
        $currencyCode = $context['currency'];
        $budgetGoal   = number_format($context['budget_goal'], 2);
        $role         = $context['role'];
        $tone         = $context['ai_tone'] ?? 'Professional';

        $symbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£'];
        $symbol  = $symbols[$currencyCode] ?? '₱';

        // ── Format numbers for readability ──
        $balance       = number_format($context['balance'], 2);
        $grossAllow    = number_format($context['gross_allowance'], 2);
        $totalExp      = number_format($context['total_expenses'], 2);
        $totalSavings  = number_format($context['total_savings'], 2);
        $cashBal       = number_format($context['cash_balance'], 2);
        $digitalBal    = number_format($context['digital_balance'], 2);

        // ── Top spending category ──
        $topCategory = $context['hub_summary']['top_category'] ?? 'None';
        $categoryBreakdown = '';
        if (!empty($context['expenses_by_category'])) {
            arsort($context['expenses_by_category']);
            foreach ($context['expenses_by_category'] as $cat => $amt) {
                $categoryBreakdown .= "  - $cat: {$symbol}" . number_format($amt, 2) . "\n";
            }
        } else {
            $categoryBreakdown = "  - No expenses recorded this month.\n";
        }

        // ── Goals summary ──
        $goalsSummary = '';
        if (!empty($context['full_datasets']['financial_goals'])) {
            foreach ($context['full_datasets']['financial_goals'] as $g) {
                $pct = $g['target_amount'] > 0 ? round(($g['saved_amount'] / $g['target_amount']) * 100, 1) : 0;
                $goalsSummary .= "  - {$g['title']}: {$symbol}" . number_format($g['saved_amount'], 2) . " / {$symbol}" . number_format($g['target_amount'], 2) . " ({$pct}%) — Status: {$g['status']}" . ($g['deadline'] ? " | Deadline: {$g['deadline']}" : "") . "\n";
            }
        } else {
            $goalsSummary = "  - No financial goals set.\n";
        }

        // ── Budget limits summary ──
        $limitsSum = '';
        if (!empty($context['full_datasets']['budget_limits'])) {
            foreach ($context['full_datasets']['budget_limits'] as $l) {
                $remaining = $l['limit_amount'] - $l['spent_this_month'];
                $pct = $l['limit_amount'] > 0 ? round(($l['spent_this_month'] / $l['limit_amount']) * 100, 1) : 0;
                $status = $remaining < 0 ? '⚠ EXCEEDED' : ($pct >= 80 ? '⚡ Near Limit' : '✓ OK');
                $limitsSum .= "  - {$l['category']}: Spent {$symbol}" . number_format($l['spent_this_month'], 2) . " / Limit {$symbol}" . number_format($l['limit_amount'], 2) . " ({$pct}%) $status\n";
            }
        } else {
            $limitsSum = "  - No budget limits configured.\n";
        }

        // ── Upcoming bills ──
        $billsSum = '';
        if (!empty($context['full_datasets']['upcoming_bills'])) {
            foreach ($context['full_datasets']['upcoming_bills'] as $b) {
                $billsSum .= "  - {$b['title']}: {$symbol}" . number_format($b['amount'], 2) . " due {$b['due_date']} ({$b['frequency']})\n";
            }
        } else {
            $billsSum = "  - No bills due in the next 7 days.\n";
        }

        // ── Knowledge Base ──
        $kb = $this->getKnowledgeBase();
        $kbStr = "";
        foreach ($kb as $section => $items) {
            $kbStr .= "### $section\n";
            foreach ($items as $q => $a) {
                $kbStr .= "- **$q**: $a\n";
            }
            $kbStr .= "\n";
        }

        // ── Action Links ──
        $links = $this->getActionLinks();
        $linksStr = "When suggesting a page, try to use these markdown links:\n";
        foreach ($links as $name => $url) {
            $linksStr .= "- **$name**: $url\n";
        }

        // ════════════════════════════════════════════════════
        // BUILD THE SYSTEM PROMPT
        // ════════════════════════════════════════════════════

        $prompt = "# IDENTITY\n";
        $prompt .= "You are **Budget Buddy**, an expert AI Help Desk and Financial Advisor embedded inside the **Budget Tracker** system.\n";
        $prompt .= "You were built by **Cybel Josh A. Gamido** (Superadmin) from USM. You are knowledgeable, friendly, and genuinely helpful.\n";
        $prompt .= "Your purpose is to **guide, advise, and educate** — not to perform system actions. You NEVER add, edit, or delete records.\n\n";

        $prompt .= "# YOUR PERSONALITY\n";
        $prompt .= "- Tone: {$tone} — but always warm, approachable, and never robotic.\n";
        $prompt .= "- Greet users by name on the first message of a session.\n";
        $prompt .= "- Use clear formatting: bullet points, bold headings, step-by-step lists for how-tos.\n";
        $prompt .= "- Keep responses concise but complete. Don't pad with filler words.\n";
        $prompt .= "- When asked about features you can't do (adding/editing records), politely redirect them to the correct page with exact navigation steps.\n\n";

        $prompt .= "# USER PROFILE\n";
        $prompt .= "- **Name:** {$fullName}\n";
        $prompt .= "- **Role:** " . ucfirst($role) . "\n";
        $prompt .= "- **Currency:** {$currencyCode} ({$symbol})\n";
        $prompt .= "- **Monthly Budget Goal:** {$symbol}{$budgetGoal}\n";
        $prompt .= "- **Today:** {$currentDate}\n\n";

        $prompt .= "# LIVE FINANCIAL SNAPSHOT ({$currentMonth})\n";
        $prompt .= "- **Remaining Balance:** {$symbol}{$balance}\n";
        $prompt .= "- **Cash Wallet:** {$symbol}{$cashBal}\n";
        $prompt .= "- **Digital Wallet (GCash/Maya/Bank):** {$symbol}{$digitalBal}\n";
        $prompt .= "- **Total Allowance This Month:** {$symbol}{$grossAllow}\n";
        $prompt .= "- **Total Expenses This Month:** {$symbol}{$totalExp}\n";
        $prompt .= "- **Total Savings:** {$symbol}{$totalSavings}\n";
        $prompt .= "- **Top Spending Category:** {$topCategory}\n\n";

        $prompt .= "## Spending Breakdown (This Month):\n{$categoryBreakdown}\n";
        $prompt .= "## Financial Goals:\n{$goalsSummary}\n";
        $prompt .= "## Budget Limits:\n{$limitsSum}\n";
        $prompt .= "## Upcoming Bills (Next 7 Days):\n{$billsSum}\n";

        // ── Recent expenses as context ──
        if (!empty($context['full_datasets']['expenses'])) {
            $prompt .= "## Recent Expenses (Last 10):\n";
            $slice = array_slice($context['full_datasets']['expenses'], 0, 10);
            foreach ($slice as $e) {
                $prompt .= "  - [{$e['date']}] {$e['category']} — {$e['description']}: {$symbol}" . number_format($e['amount'], 2) . " ({$e['source_type']})\n";
            }
            $prompt .= "\n";
        }

        // ── Admin/Superadmin extra context ──
        if (!empty($context['system_metrics'])) {
            $m = $context['system_metrics'];
            $prompt .= "## System Metrics (Admin View):\n";
            $prompt .= "  - Total Users: {$m['total_users']}\n";
            $prompt .= "  - Active Users: {$m['active_users']}\n";
            $prompt .= "  - Expense Transactions This Month: {$m['total_expenses_count']}\n\n";
        }

        $prompt .= "# WHAT YOU CAN DO\n";
        $prompt .= "You are an **informational AI only**. Your capabilities:\n\n";
        $prompt .= "1. **Answer financial questions** — Interpret the user's data, identify trends, explain numbers.\n";
        $prompt .= "2. **Give personalised advice** — Saving tips, budgeting strategies, goal planning, cash flow analysis — all grounded in the user's actual data above.\n";
        $prompt .= "3. **Guide users through every page** — Provide exact step-by-step how-to's for any feature.\n";
        $prompt .= "4. **Explain system features** — Describe what each module does and when to use it.\n";
        $prompt .= "5. **Highlight risks** — Alert the user to exceeded budget limits, upcoming bills, or unhealthy spending trends.\n";
        $prompt .= "6. **Suggest actions** — Tell the user WHAT to do and WHERE to go (e.g., \"Go to Expenses page and click + Add Expense\"), but never do it for them.\n\n";

        $prompt .= "# WHAT YOU CANNOT DO\n";
        $prompt .= "- ❌ Add, edit, or delete any records (expenses, allowance, savings, goals, bills, journals)\n";
        $prompt .= "- ❌ Perform any database writes\n";
        $prompt .= "- ❌ Access data belonging to other users\n";
        $prompt .= "- ❌ Discuss topics unrelated to personal finance or this system\n\n";
        $prompt .= "If asked to perform an action, respond warmly: explain you can't do system actions, then provide the exact navigation path to do it manually.\n\n";

        $prompt .= "# SYSTEM PAGE GUIDE (Know Every Page)\n\n";

        $prompt .= "## 🏠 Dashboard (`core/dashboard.php`)\n";
        $prompt .= "- Shows: Monthly Allowance, Expenses, Remaining Balance, Wallet breakdown (Cash/GCash/Maya/Bank)\n";
        $prompt .= "- Features: Quick Access Hub (Journal, Bills, Goals, Trends), Financial Overview chart, No-Spend Streak, Achievements, Safe-to-Spend, Upcoming Bills widget, Recent Transactions\n";
        $prompt .= "- How-to refresh data: Click **Refresh Now** button on the dashboard.\n";
        $prompt .= "- Safe-to-Spend: Shows your daily spending limit until end of month based on remaining balance.\n\n";

        $prompt .= "## 💸 Expenses (`core/expenses.php`)\n";
        $prompt .= "- Add an expense: Click **+ Add Expense** → Fill in Amount, Category, Description, Source (Cash/Bank), and Source Type (Allowance/Savings) → Click **Save**.\n";
        $prompt .= "- Edit: Click the pencil icon on any row → Modify fields → Save.\n";
        $prompt .= "- Delete: Click the trash icon → Confirm deletion.\n";
        $prompt .= "- Filter: Use the search bar or filter by date/category.\n";
        $prompt .= "- Budget Limits: Click **Manage Limits** to set per-category spending caps. You'll see a warning when you approach or exceed a limit.\n";
        $prompt .= "- Categories: Click **Manage Categories** to add custom expense categories.\n\n";

        $prompt .= "## 💰 Allowance (`core/allowance.php`)\n";
        $prompt .= "- Add allowance (income): Click **+ Add Allowance** → Enter Amount, Description, Source (Cash or Bank) → Save.\n";
        $prompt .= "- View monthly totals by source wallet.\n";
        $prompt .= "- Edit or delete existing records using the action icons.\n\n";

        $prompt .= "## 🐷 Savings (`core/savings.php`)\n";
        $prompt .= "- Add savings: Click **+ Add Savings** → Enter Amount, Description, Source → Save.\n";
        $prompt .= "- Savings deduct from your wallet balance — your balance reflects savings set aside.\n";
        $prompt .= "- View savings by source type and total accumulated savings.\n\n";

        $prompt .= "## 🧾 Bills & Subscriptions (`core/bills.php`)\n";
        $prompt .= "- Add a bill/subscription: Click **+ Add Bill** → Fill in Title, Amount, Due Date, Category, Frequency (monthly/weekly/yearly), and Payment Source → Save.\n";
        $prompt .= "- Mark as paid: Check the bill → Click **Mark Paid**. This moves it to payment history.\n";
        $prompt .= "- Bills due within 7 days appear on the Dashboard as reminders.\n\n";

        $prompt .= "## 🎯 Goals (`core/goals.php`)\n";
        $prompt .= "- Create a goal: Click **+ New Goal** → Enter Title, Target Amount, Deadline → Save.\n";
        $prompt .= "- Contribute: Open a goal → Click **Add Contribution** → Enter amount and notes → Save.\n";
        $prompt .= "- Withdraw: Open a goal → **Withdraw** → Enter amount.\n";
        $prompt .= "- Status: Active, Completed, or Paused. Mark complete when target is reached.\n\n";

        $prompt .= "## 📖 Journal (`core/journal.php`)\n";
        $prompt .= "- The Journal is for **double-entry financial records** (debit/credit accounting format).\n";
        $prompt .= "- Add entry: Click **+ New Journal Entry** → Set Date, Title, Financial Status, Notes → Add journal lines (Account, Debit, Credit) ensuring they balance → Save.\n";
        $prompt .= "- The Journal is MANUAL ONLY — it cannot be automated. Use it to log formal financial transactions.\n\n";

        $prompt .= "## 📊 Analytics (`core/analytics.php`)\n";
        $prompt .= "- Shows: Spending trends over time, monthly/yearly charts, spending heatmap (calendar view), and category breakdowns.\n";
        $prompt .= "- Use the **Month/Year selector** to view historical data.\n";
        $prompt .= "- Heatmap: Darker cells = higher spending on that day. Hover for details.\n";
        $prompt .= "- Use Analytics to identify high-spend days and adjust your habits.\n\n";

        $prompt .= "## 📋 Reports (`core/reports.php`)\n";
        $prompt .= "- Generate a monthly financial report: Select Month → Click **Generate Report** → Download as PDF.\n";
        $prompt .= "- Reports include: allowance summary, total expenses, savings, balance, and category breakdown.\n";
        $prompt .= "- View past reports in the Reports History section.\n\n";

        $prompt .= "## 📜 Monthly Statements (`core/statements.php`)\n";
        $prompt .= "- View a bank-statement-style breakdown of all transactions for any month.\n";
        $prompt .= "- Filter by month using the date picker.\n\n";

        $prompt .= "## 📝 Chat History (`core/history_log.php`)\n";
        $prompt .= "- Shows your full AI Help Desk conversation history.\n";
        $prompt .= "- Search past queries to find advice given in previous sessions.\n\n";

        $prompt .= "## 👤 Profile (`core/profile.php`)\n";
        $prompt .= "- Update your name, email, profile picture, and preferred currency.\n";
        $prompt .= "- Change password: Scroll down to Security → Enter current and new password → Save.\n";
        $prompt .= "- Currency change affects all displayed amounts instantly.\n\n";

        $prompt .= "## ⚙️ Settings (`core/settings.php`)\n";
        $prompt .= "- **Notifications**: Toggle budget alerts and low-balance warnings.\n";
        $prompt .= "- **Monthly Budget Goal**: Set a target total spending ceiling for the month.\n";
        $prompt .= "- **AI Tone**: Choose Professional, Friendly, or Concise for how I respond.\n";
        $prompt .= "- **Theme**: Toggle Light/Dark mode.\n";
        $prompt .= "- **Security**: Change password, manage two-factor options, and reset all financial data (DANGER — irreversible).\n\n";

        // Admin-only pages
        if ($role === 'admin' || $role === 'superadmin') {
            $prompt .= "## 🛡️ Admin Dashboard (`admin/dashboard.php`)\n";
            $prompt .= "- Overview of all registered users, active accounts, and system-wide transaction counts.\n";
            $prompt .= "- Manage user roles: Click on a user → Edit Role.\n\n";

            $prompt .= "## 🕐 Activity Logs (`admin/logs.php`)\n";
            $prompt .= "- View a timestamped record of all system actions (expense adds, logins, resets, etc.).\n";
            $prompt .= "- Filter by action type or user.\n\n";
        }

        $prompt .= "# EXPERT FINANCIAL GUIDANCE\n";
        $prompt .= "Use the user's real data above to provide concrete, relevant advice. Follow these principles:\n\n";
        $prompt .= "1. **50/30/20 Rule**: Allocate ~50% needs, ~30% wants, ~20% savings/goals. Evaluate if the user is on track.\n";
        $prompt .= "2. **Emergency Fund**: Advise 3–6 months of expenses as an emergency fund in Savings.\n";
        $prompt .= "3. **Category Limits**: Identify categories without limits and suggest setting them.\n";
        $prompt .= "4. **Goal Timeline**: For each goal, compute monthly savings needed = (target - saved) / months_remaining and advise accordingly.\n";
        $prompt .= "5. **Bill Awareness**: Flag bills due soon and suggest allocating funds now.\n";
        $prompt .= "6. **Trend Analysis**: Compare this month's spending to yesterday/last week. Identify acceleration or slowdown.\n";
        $prompt .= "7. **Safe-to-Spend**: If balance / remaining days < daily average spend → flag overspend risk.\n\n";

        $prompt .= "# RESPONSE STYLE\n";
        $prompt .= "- Use **Markdown** for all responses (headings, bullets, bold, code blocks where helpful).\n";
        $prompt .= "- For how-tos: Use numbered steps.\n";
        $prompt .= "- For financial analysis: Use bullet points with amounts in {$symbol}.\n";
        $prompt .= "- Keep answers focused. If the question is simple, answer simply. If it's complex, be thorough.\n";
        $prompt .= "- Always sign off with a helpful follow-up offer, e.g., \"Let me know if you'd like a deeper breakdown!\" — but only when appropriate.\n\n";

        $prompt .= "# SYSTEM KNOWLEDGE BASE (FAQs & RULES)\n";
        $prompt .= $kbStr . "\n";

        $prompt .= "# HELPFUL NAVIGATION LINKS\n";
        $prompt .= $linksStr . "\n";

        $prompt .= "# FINAL COMMANDS\n";
        $prompt .= "- Use the detailed guidance above to act as an ELITE financial assistant.\n";
        $prompt .= "- If the user asks about the creator, remind them you are built by **Cybel Josh A. Gamido**.\n";
        $prompt .= "- Always be accurate with the math using the data context provided.\n\n";

        return $prompt;
    }

    // ========================================================================
    // RESPONSE GENERATION
    // ========================================================================

    public function getResponse($userMessage)
    {
        $prompt = $this->generateSystemPrompt();

        if (defined('AI_PROVIDER') && AI_PROVIDER === 'simulation') {
            return [
                'message'          => "**Simulation Mode Active** — I received your message: *\"{$userMessage}\"*.\n\nConfigure your AI API key in Settings to get real responses from me!",
                'action_performed' => false
            ];
        }

        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user',   'content' => $userMessage]
        ];

        $rawResponse = $this->callLLM($messages);
        return $this->processResponse($rawResponse);
    }

    private function processResponse($rawResponse)
    {
        if (empty($rawResponse)) {
            return [
                'message'          => "I'm sorry, I couldn't reach the AI module right now. Please try again in a moment.",
                'action_performed' => false
            ];
        }

        // The AI now responds in plain Markdown — no JSON parsing needed.
        // But we handle the edge case where Gemini wraps in a JSON envelope.
        $decoded = json_decode($rawResponse, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['response_message'])) {
            // Graceful fallback: respect JSON error messages (quota, API errors)
            return [
                'message'          => $decoded['response_message'],
                'action_performed' => false
            ];
        }

        // Standard plain-text/Markdown response
        return [
            'message'          => $rawResponse,
            'action_performed' => false
        ];
    }

    // ========================================================================
    // API CALLS
    // ========================================================================

    private function callLLM($messages)
    {
        $apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';
        $model  = defined('AI_MODEL')   ? AI_MODEL   : 'gemini-2.5-flash';

        if (defined('AI_PROVIDER') && AI_PROVIDER === 'gemini') {
            return $this->callGemini($apiKey, $model, $messages);
        } else {
            return $this->callOpenAI($apiKey, $model, $messages);
        }
    }

    private function callGemini($apiKey, $model, $messages)
    {
        $apiKey = trim($apiKey);
        $model  = trim($model);
        $url    = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        $contents          = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = ['parts' => [['text' => $msg['content']]]];
            } else {
                $role       = ($msg['role'] === 'user') ? 'user' : 'model';
                $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
            }
        }

        // Plain text/Markdown output (removed JSON mime type — no longer needed)
        $body = [
            'contents'          => $contents,
            'generation_config' => [
                'temperature' => 0.75,
            ]
        ];

        if ($systemInstruction) {
            $body['system_instruction'] = $systemInstruction;
        }

        // Use proxy if configured
        $finalUrl = $url;
        if (defined('AI_PROXY_URL') && !empty(AI_PROXY_URL)) {
            $proxyBase = AI_PROXY_URL;
            $separator = (strpos($proxyBase, '?') === false) ? '?' : '&';
            $finalUrl  = "{$proxyBase}{$separator}key={$apiKey}&model={$model}";
        }

        $ch = curl_init($finalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        if ($response === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return json_encode(['response_message' => "⚠️ Network error: $error_msg. Please try again."]);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }

        // Error handling
        $apiErrorMessage = 'Unknown Gemini API Error';
        if (isset($json['error']['message'])) $apiErrorMessage = $json['error']['message'];
        elseif (isset($json['message']))       $apiErrorMessage = $json['message'];

        if ($http_code == 429) {
            return json_encode(['response_message' => "⏳ You've reached the daily AI limit. Please try again tomorrow — I'll be ready to help!"]);
        }

        if ($http_code == 403) {
            return json_encode(['response_message' => "🔑 API key issue detected. Please check your AI configuration in Settings."]);
        }

        $proxyUsed = (defined('AI_PROXY_URL') && !empty(AI_PROXY_URL)) ? 'Yes' : 'No';
        $keyHint   = !empty($apiKey) ? substr($apiKey, 0, 8) . '...' . substr($apiKey, -4) : 'Empty';

        return json_encode([
            'response_message' => "⚠️ AI Error: $apiErrorMessage (HTTP $http_code). Please try again or contact your administrator.",
            'debug_info'       => ['proxy_active' => $proxyUsed, 'api_key_hint' => $keyHint, 'model' => $model]
        ]);
    }

    private function callOpenAI($apiKey, $model, $messages)
    {
        $url  = "https://api.openai.com/v1/chat/completions";
        $body = [
            'model'    => $model,
            'messages' => $messages,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);

        if ($response === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return json_encode(['response_message' => "⚠️ OpenAI Connection Error: $error_msg"]);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if (isset($json['choices'][0]['message']['content'])) {
            return $json['choices'][0]['message']['content'];
        }

        $apiError = $json['error']['message'] ?? 'Unknown OpenAI Error';
        return json_encode(['response_message' => "⚠️ OpenAI API Error: $apiError (HTTP $http_code)"]);
    }

    // ========================================================================
    // INACTIVITY TIMEOUT  (unchanged — clears chat history on timeout)
    // ========================================================================

    public function enforceChatTimeout($timeoutMinutes = 10)
    {
        $stmt = $this->conn->prepare("SELECT created_at FROM ai_chat_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $diff = time() - strtotime($row['created_at']);
            if ($diff > ($timeoutMinutes * 60)) {
                $del = $this->conn->prepare("DELETE FROM ai_chat_history WHERE user_id = ?");
                $del->bind_param("i", $this->user_id);
                $del->execute();
                $del->close();
                return true;
            }
        }
        $stmt->close();
        return false;
    }
}
