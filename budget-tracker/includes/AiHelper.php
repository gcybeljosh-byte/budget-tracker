<?php
// includes/AiHelper.php

class AiHelper {
    private $conn;
    private $user_id;
    private $cached_symbol = null;

    public function __construct($db_connection, $user_id) {
        $this->conn = $db_connection;
        $this->user_id = $user_id;
    }

    private function getFinancialStats($startDate, $endDate) {
        // Expenses
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ?");
        $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
        $stmt->execute();
        $expenses = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        // Allowances
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND date BETWEEN ? AND ?");
        $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
        $stmt->execute();
        $allowance = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        // Savings
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND date BETWEEN ? AND ?");
        $stmt->bind_param("iss", $this->user_id, $startDate, $endDate);
        $stmt->execute();
        $savings = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        return [
            'income' => (float)$allowance,
            'expenses' => (float)$expenses,
            'savings' => (float)$savings,
            'net_balance' => (float)($allowance - $expenses - $savings)
        ];
    }

    public function getUserContext() {
        $context = [];

        // 1. Fetch User Profile
        $stmt = $this->conn->prepare("SELECT first_name, last_name, email, currency, ai_tone, notif_budget, notif_low_balance, monthly_budget_goal, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $context['user'] = $user;
        $context['role'] = $user['role'] ?? 'user';
        $context['currency'] = $user['currency'] ?? 'PHP';
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


        // 3. Keep existing legacy structures for compatibility (optional, but good for safety)
        $context['allowance'] = $context['stats']['this_month']['income'] - $context['stats']['this_month']['savings']; // Net Allowance
        $context['gross_allowance'] = $context['stats']['this_month']['income'];
        $context['total_savings'] = $context['stats']['this_month']['savings'];
        $context['total_expenses'] = $context['stats']['this_month']['expenses'];
        $context['balance'] = $context['stats']['this_month']['net_balance'];

        // 4. Fetch Expenses Breakdown (Category) - Keep this as it's useful
        $currentMonth = date('Y-m');
        $stmt = $this->conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ? GROUP BY category");
        $stmt->bind_param("is", $this->user_id, $currentMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        $expenses = [];
        while ($row = $result->fetch_assoc()) {
            $expenses[$row['category']] = $row['total'];
        }
        $context['expenses_by_category'] = $expenses;
        $stmt->close();

        // 5. Inject Recent Datasets (Raw Rows)
        $stmt = $this->conn->prepare("SELECT id, date, category, description, amount, source_type FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 50");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $context['full_datasets']['expenses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT id, date, description, amount, source_type FROM allowances WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 20");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $context['full_datasets']['allowances'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $context;
    }

    public function generateSystemPrompt() {
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
        $prompt .= "You are an expert Budget AI Help Desk integrated into a Budget Tracking System.\n";
        $prompt .= "Your role is to assist users in creating, managing, analyzing, and improving Budget Journals and Budget Plans.\n\n";
        
        $prompt .= "# USER PROFILE\n";
        $prompt .= "User Name: {$name}\n";
        $prompt .= "Monthly Budget Goal: {$symbol}{$budgetGoal}\n";
        $prompt .= "Currency: {$currencyCode} ({$symbol})\n";
        $prompt .= "Today's Date: {$todayDate} ({$currentDate})\n\n";
        
        $prompt .= "# FINANCIAL DATASET\n";
        $prompt .= "The following JSON contains the user's financial profile. \n";
        $prompt .= "**CRITICAL**: Pay attention to the `stats` object which contains `today`, `yesterday`, `this_month`, and `this_year` aggregates.\n";
        $prompt .= "```json\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n```\n\n";
        
        $prompt .= "# CRITICAL RULES\n";
        $prompt .= "1. **Source of Truth**: Use ONLY the provided Financial Dataset. Do not invent numbers.\n";
        $prompt .= "2. **Detailed Context**: When creating journals, ALWAYS cite numbers from the `stats` object (e.g., \"You spent ₱500 today vs ₱100 yesterday..\").\n";
        $prompt .= "3. **Net Allowance**: 'allowance' in the dataset is Net of Savings (Gross - Savings). Use this as disposable income.\n";
        $prompt .= "4. **Currency**: Always use {$symbol} for amounts.\n";
        $prompt .= "5. **JSON Output**: You must output arguments for actions in strictly valid JSON format.\n\n";

        if ($context['role'] === 'admin') {
            $prompt .= "# SUPERADMIN MODE\n";
            $prompt .= "The current user is an **ADMINISTRATOR (Superadmin)**.\n";
            $prompt .= "1. You are operating in a special administrative data context.\n";
            $prompt .= "2. **Strict Dataset**: You must strictly base your responses and insights ONLY on this administrator's personal datasets provided above.\n";
            $prompt .= "3. **No External Hallucinations**: Do not assume details from other users or the wider platform.\n";
            $prompt .= "4. **Acknowledge Role**: You can acknowledge that you are helping an administrator manage their specific financial overview.\n\n";
        }

        $prompt .= "# CAPABILITIES & COMMANDS\n";
        $prompt .= "## 1. JOURNALING\n";
        $prompt .= "- **Action**: `create_journal`\n";
        $prompt .= "- **Behavior**: Summarize their financial day. COMPARE `today` vs `yesterday` or `this_month` vs `budget_goal`.\n";
        $prompt .= "- **Requirement**: Ensure the journal entry mentions specific spending/income amounts found in the `stats`.\n";
        $prompt .= "- **Compound Entries**: You MUST generate a `lines` array for double-entry accounting if the user asks for \"Journal Entries\" or \"Ledger\".\n";
        $prompt .= "  - Example: `lines: [{account: 'Cash', debit: 500, credit: 0}, {account: 'Sales', debit: 0, credit: 500}]`\n";
        $prompt .= "  - Ensure Total Debit = Total Credit.\n";
        $prompt .= "- **Magic Write**: If they give a short note, expand it into a full reflection using the data.\n\n";
        
        $prompt .= "## 2. BUDGET PLANNING\n";
        $prompt .= "- **Action**: `create_budget_plan`\n";
        $prompt .= "- **Behavior**: Convert plans directly into transaction entries (Expenses/Allowances).\n\n";
        
        $prompt .= "## 3. DATASET SUGGESTIONS\n";
        $prompt .= "- **Daily**: 1-5 records. **Weekly**: 5-20 records. **Monthly**: 20-60 records.\n\n";
        
        $prompt .= "## 4. STANDARD CRUD\n";
        $prompt .= "- Supports: `add_expense`, `add_allowance`, `add_savings`, `delete_transaction`.\n\n";

        $prompt .= "# OUTPUT FORMAT (STRICT)\n";
        $prompt .= "You must respond in JSON format if you are performing an action. If you are just chatting, you can respond in plain text, BUT JSON is preferred for structured data.\n\n";
        
        $prompt .= "**Schema for Actions:**\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"response_message\": \"...\",\n";
        $prompt .= "  \"actions\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"type\": \"create_journal | add_expense | add_allowance | add_savings | create_budget_plan\",\n";
        $prompt .= "      \"data\": { ... }\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        
        $prompt .= "**Note**: For `create_budget_plan`, return an array of `add_expense` or `add_allowance` actions.\n";
        
        return $prompt;
    }

    public function getResponse($userMessage) {
        // First check for simple intent to bypass LLM if needed (optional optimization)
        // But for full features, we prefer LLM. However, we'll keep detectIntent as a fallback or for simple commands.
        // Actually, let's rely on LLM for the new "Smart" features.
        
        $prompt = $this->generateSystemPrompt();
        
        if (defined('AI_PROVIDER') && AI_PROVIDER === 'simulation') {
            return $this->getSimulationResponse($userMessage);
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
    
    private function processAiJsonOutput($rawResponse, $userMessage) {
        // Remove markdown code blocks if present
        $cleanJson = preg_replace('/```json\s*|\s*```/', '', $rawResponse);
        $data = json_decode($cleanJson, true);
        
        // If not valid JSON, treat as plain text response
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Attempt to fallback to regex detection if LLM failed to output JSON
            $intentData = $this->detectIntent($userMessage);
            if ($intentData['intent'] !== 'query' && $intentData['confidence'] > 0.8) {
                 $res = $this->executeAction($intentData['intent'], $userMessage);
                 if ($res['success']) {
                     return ['message' => $res['message'], 'action_performed' => true, 'action_type' => $intentData['intent']];
                 }
            }
            return [
                'message' => $rawResponse,
                'action_performed' => false
            ];
        }
        
        $responseMessage = $data['response_message'] ?? "Action completed.";
        $actions = $data['actions'] ?? [];
        $actionPerformed = false;
        $lastActionType = '';

        foreach ($actions as $action) {
            $type = $action['type'];
            $payload = $action['data'];
            
            switch ($type) {
                case 'create_journal':
                    $this->createJournalAction($payload);
                    $actionPerformed = true;
                    $lastActionType = 'create_journal';
                    break;
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
                case 'add_category': // Sometimes LLM might use this
                     $this->addCategoryAction($payload);
                     $actionPerformed = true;
                     $lastActionType = 'add_category';
                     break;
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

    private function createJournalAction($data) {
        $stmt = $this->conn->prepare("INSERT INTO journals (user_id, date, title, notes, financial_status, overspending_warning) VALUES (?, ?, ?, ?, ?, ?)");
        $warning = ($data['overspending_warning'] ?? false) ? 1 : 0;
        $title = $data['title'] ?? 'Journal Entry';
        $notes = $data['notes'] ?? '';
        $status = $data['financial_status'] ?? 'Neutral';
        $date = $data['date'] ?? date('Y-m-d');
        
        $stmt->bind_param("issssi", $this->user_id, $date, $title, $notes, $status, $warning);
        $stmt->execute();
        $journal_id = $stmt->insert_id;
        $stmt->close();

        // Handle Lines (Compound Entry)
        if (isset($data['lines']) && is_array($data['lines'])) {
            $lineStmt = $this->conn->prepare("INSERT INTO journal_lines (journal_id, account_title, debit, credit) VALUES (?, ?, ?, ?)");
            foreach ($data['lines'] as $line) {
                $account = $line['account'] ?? $line['account_title'] ?? 'General';
                $debit = floatval($line['debit'] ?? 0);
                $credit = floatval($line['credit'] ?? 0);
                if ($debit > 0 || $credit > 0) {
                    $lineStmt->bind_param("isdd", $journal_id, $account, $debit, $credit);
                    $lineStmt->execute();
                }
            }
            $lineStmt->close();
        }
        
        return ['success' => true];
    }

    private function addExpenseAction($data) {
        if (!isset($data['amount']) || $data['amount'] <= 0) return ['success' => false, 'message' => "Amount needed."];
        
        $stmt = $this->conn->prepare("INSERT INTO expenses (user_id, date, category, description, amount, source_type) VALUES (?, ?, ?, ?, ?, ?)");
        $source = $data['source_type'] ?? 'Cash';
        $category = $data['category'] ?? 'Other';
        $desc = $data['description'] ?? 'Expense';
        $date = $data['date'] ?? date('Y-m-d');
        
        $stmt->bind_param("isssds", $this->user_id, $date, $category, $desc, $data['amount'], $source);
        $stmt->execute();
        $stmt->close();
        return ['success' => true];
    }
    
    private function addAllowanceAction($data) {
        if (!isset($data['amount']) || $data['amount'] <= 0) return ['success' => false, 'message' => "Amount needed."];
        
        $stmt = $this->conn->prepare("INSERT INTO allowances (user_id, date, description, amount, source_type) VALUES (?, ?, ?, ?, ?)");
        $source = $data['source_type'] ?? 'Cash';
        $desc = $data['description'] ?? 'Allowance';
        $date = $data['date'] ?? date('Y-m-d');
        
        $stmt->bind_param("issds", $this->user_id, $date, $desc, $data['amount'], $source);
        $stmt->execute();
        $stmt->close();
        return ['success' => true];
    }

    private function addSavingsAction($data) {
        if (!isset($data['amount']) || $data['amount'] <= 0) return ['success' => false, 'message' => "Amount needed."];
        
        $stmt = $this->conn->prepare("INSERT INTO savings (user_id, date, description, amount) VALUES (?, ?, ?, ?)");
        $desc = $data['description'] ?? 'Savings';
        $date = $data['date'] ?? date('Y-m-d');
        
        $stmt->bind_param("isss", $this->user_id, $date, $desc, $data['amount']);
        $stmt->execute();
        $stmt->close();
        return ['success' => true];
    }

    private function addCategoryAction($data) {
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

    private function detectIntent($userMessage) {
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
    
    private function executeAction($intent, $userMessage) {
        // Only implementing delete/edit here as add is handled by LLM JSON
        switch($intent) {
            case 'delete_expense': return $this->deleteExpenseAction($userMessage);
            case 'delete_allowance': return $this->deleteAllowanceAction($userMessage);
            case 'edit_expense': return $this->editExpenseAction($userMessage);
            case 'edit_allowance': return $this->editAllowanceAction($userMessage);
        }
        return ['success' => false, 'message' => 'Unknown action'];
    }

    private function extractSpecificDate($userMessage) {
        $msg = strtolower($userMessage);
        if (preg_match('/\b(yesterday)\b/i', $msg)) return date('Y-m-d', strtotime('-1 day'));
        if (preg_match('/\b(today)\b/i', $msg)) return date('Y-m-d');
        if (preg_match('/\b([0-9]{4}-[0-9]{2}-[0-9]{2})\b/', $userMessage, $matches)) return $matches[1];
        return null;
    }

    private function getRecentRecords($type, $date = null, $limit = 5) {
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
    
    private function deleteExpenseAction($userMessage) {
        $date = $this->extractSpecificDate($userMessage);
        $records = $this->getRecentRecords('expense', $date, 1);
        if (empty($records)) return ['success' => false, 'message' => "No record found."];
        $stmt = $this->conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $records[0]['id'], $this->user_id);
        $stmt->execute();
        return ['success' => true, 'message' => "Expense deleted."];
    }
    
    private function deleteAllowanceAction($userMessage) {
        $date = $this->extractSpecificDate($userMessage);
        $records = $this->getRecentRecords('allowance', $date, 1);
        if (empty($records)) return ['success' => false, 'message' => "No record found."];
        $stmt = $this->conn->prepare("DELETE FROM allowances WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $records[0]['id'], $this->user_id);
        $stmt->execute();
        return ['success' => true, 'message' => "Allowance deleted."];
    }
    
    private function editExpenseAction($userMessage) {
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
    
    private function editAllowanceAction($userMessage) {
        return ['success' => false, 'message' => "Edit allowance not fully implemented in fallback mode."];
    }

    // ========================================================================
    // API CALLS
    // ========================================================================

    private function callLLM($messages) {
        $apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';
        $model = defined('AI_MODEL') ? AI_MODEL : 'gemini-2.5-flash';
        
        if (defined('AI_PROVIDER') && AI_PROVIDER === 'gemini') {
            return $this->callGemini($apiKey, $model, $messages);
        } else {
            return $this->callOpenAI($apiKey, $model, $messages);
        }
    }

    private function callGemini($apiKey, $model, $messages) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        $contents = [];
        $systemInstruction = null;
        
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = ['parts' => [['text' => $msg['content']]]];
            } else {
                $role = ($msg['role'] === 'user') ? 'user' : 'model';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $msg['content']]]
                ];
            }
        }
        
        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'responseMimeType' => 'application/json'
            ]
        ];
        
        if ($systemInstruction) {
            $body['systemInstruction'] = $systemInstruction;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($response, true);
        
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }
        
        return json_encode([
            'response_message' => "I'm sorry, I'm having trouble connecting to Gemini right now.", 
            'actions' => []
        ]);
    }

    private function callOpenAI($apiKey, $model, $messages) {
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
        curl_close($ch);
        
        $json = json_decode($response, true);
        return $json['choices'][0]['message']['content'] ?? "{}";
    }

    private function getSimulationResponse($userMessage) {
        return json_encode([
            'response_message' => "Simulation Mode: I received your message: '$userMessage'. Configure AI_KEY for real responses.",
            'actions' => []
        ]);
    }
}
?>
