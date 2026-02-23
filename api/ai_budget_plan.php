<?php
// api/ai_budget_plan.php
// Generates AI-suggested per-category budget limits based on a given allowance amount.

session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/config.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id  = $_SESSION['id'];
$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$allowance = floatval($data['allowance'] ?? 0);
$currency  = $_SESSION['user_currency'] ?? 'PHP';

if ($allowance <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid allowance amount.']);
    exit;
}

// Fetch user's categories
$stmt = $conn->prepare("SELECT name FROM categories WHERE user_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['name'];
}
$stmt->close();

if (empty($categories)) {
    $categories = ['Food & Dining', 'Transportation', 'Utilities', 'Entertainment', 'Shopping', 'Health', 'Education', 'Other'];
}

// Also fetch last 3 months of spending patterns to personalize suggestions
$stmt = $conn->prepare("
    SELECT category, AVG(monthly_total) as avg_spend
    FROM (
        SELECT category, DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as monthly_total
        FROM expenses
        WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY category, DATE_FORMAT(date, '%Y-%m')
    ) monthly
    GROUP BY category
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$spendingHistory = [];
while ($row = $result->fetch_assoc()) {
    $spendingHistory[$row['category']] = round($row['avg_spend'], 2);
}
$stmt->close();

$categoryList = implode(', ', $categories);
$historyText  = empty($spendingHistory)
    ? "No spending history available yet."
    : "Average monthly spending per category (last 3 months): " . json_encode($spendingHistory);

// Build AI prompt
$prompt = <<<PROMPT
You are an expert personal finance advisor. Generate a practical monthly budget limit plan.

Monthly Allowance: {$allowance} {$currency}
Categories: {$categoryList}
{$historyText}

Instructions:
1. Distribute the allowance across ALL listed categories based on the provided allowance.
2. Total of all suggested amounts must NOT exceed {$allowance} {$currency}.
3. Prioritize essentials (Food, Transportation, Utilities, Health) with larger shares.
4. Use real-world budgeting (50/30/20 rule as base).
5. Suggest amounts for every category provided.
6. Each suggestion must have a concise "reason" (max 8 words).
7. Return strictly valid JSON.

Required JSON structure:
{
  "suggestions": [
    {"category": "Food & Dining", "amount": 1500, "reason": "Essential daily meals and nutrition"},
    {"category": "Transportation", "amount": 800, "reason": "Commute and travel costs"}
  ]
}
PROMPT;

$apiKey = trim(AI_API_KEY);
$model  = trim(AI_MODEL ?: 'gemini-1.5-flash');

if (AI_PROVIDER === 'gemini') {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generation_config' => [
            'temperature' => 0.2,
            'response_mime_type' => 'application/json'
        ]
    ]);
    $headers = ['Content-Type: application/json'];
} else {
    // OpenAI
    $url = "https://api.openai.com/v1/chat/completions";
    $payload = json_encode([
        'model'       => $model,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.2,
        'response_format' => ['type' => 'json_object']
    ]);
    $headers = ['Content-Type: application/json', "Authorization: Bearer {$apiKey}"];
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false, // For local dev
    CURLOPT_SSL_VERIFYHOST => false, // For local dev
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'Connection error: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

// Extract text from response
$text = '';
if (AI_PROVIDER === 'gemini') {
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $result['candidates'][0]['content']['parts'][0]['text'];
    } else {
        $msg = $result['error']['message'] ?? 'Unknown Gemini Error';
        echo json_encode(['success' => false, 'message' => 'Gemini Error: ' . $msg]);
        exit;
    }
} else {
    $text = $result['choices'][0]['message']['content'] ?? '';
    if (!$text) {
        $msg = $result['error']['message'] ?? 'Unknown OpenAI Error';
        echo json_encode(['success' => false, 'message' => 'OpenAI Error: ' . $msg]);
        exit;
    }
}

// Robust parsing
$cleanJson = preg_replace('/```json\s*|\s*```/', '', $text);
$parsed = json_decode(trim($cleanJson), true);

if (isset($parsed['suggestions']) && is_array($parsed['suggestions'])) {
    echo json_encode([
        'success'     => true,
        'suggestions' => $parsed['suggestions'],
        'allowance'   => $allowance,
        'currency'    => $currency
    ]);
} else {
    // Log the raw text for debugging if we can't parse it
    error_log("AI Budget Plan Suggestion raw output: " . $text);
    echo json_encode([
        'success' => false, 
        'message' => 'AI returned an unexpected format. Please check logs or try again.',
        'debug' => (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? $text : null
    ]);
}
