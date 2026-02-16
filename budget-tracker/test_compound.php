<?php
session_start();
include 'includes/db.php';
include 'includes/AiHelper.php';

// Mock User ID
$user_id = $_SESSION['id'] ?? 1; // Default to 1 if no session
$ai = new AiHelper($conn, $user_id);

// Simulate AI Response
$jsonResponse = '{
    "response_message": "Compund Entry Created.",
    "actions": [
        {
            "type": "create_journal",
            "data": {
                "title": "Test Compound Entry",
                "date": "' . date('Y-m-d') . '",
                "notes": "Testing ledger lines insertion.",
                "financial_status": "Healthy",
                "lines": [
                    { "account": "Cash", "debit": 1000, "credit": 0 },
                    { "account": "Sales", "debit": 0, "credit": 1000 }
                ]
            }
        }
    ]
}';

// Process
$result = $ai->getResponse("Create a test compound journal"); 
// Note: We are mocking the internal processing. processAiJsonOutput is private, so we might need to reflect or just mock the call flow if we can't call private.
// Wait, getResponse calls LLM. I can't easily mock the LLM return without modifying AiHelper or using a mock class.
// BUT, I can just verify the createJournalAction if I make it public temporarily or just use a raw insert to test the DB, 
// OR simpler: I can just copy the logic from processAiJsonOutput into this script to test the method logic, 
// since I can't access private method.
// ACTUALLY, I can use Reflection to invoke the private method 'createJournalAction' to test it directly.

echo "Testing createJournalAction via Reflection...\n";

try {
    $reflection = new ReflectionClass('AiHelper');
    $method = $reflection->getMethod('createJournalAction');
    $method->setAccessible(true);

    $data = [
        "title" => "Reflection Test Entry",
        "date" => date('Y-m-d'),
        "notes" => "Inserted via Reflection test.",
        "financial_status" => "Healthy",
        "lines" => [
            ["account" => "Equipment", "debit" => 5000, "credit" => 0],
            ["account" => "Cash", "debit" => 0, "credit" => 5000]
        ]
    ];

    $result = $method->invoke($ai, $data);
    
    if ($result['success']) {
        echo "Success! Checking DB...\n";
        $stmt = $conn->prepare("SELECT id FROM journals WHERE title = ? ORDER BY id DESC LIMIT 1");
        $title = "Reflection Test Entry";
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $journal_id = $stmt->get_result()->fetch_assoc()['id'];
        
        echo "Journal ID: $journal_id\n";
        
        $linesStmt = $conn->prepare("SELECT * FROM journal_lines WHERE journal_id = ?");
        $linesStmt->bind_param("i", $journal_id);
        $linesStmt->execute();
        $lines = $linesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($lines as $line) {
            echo "Line: {$line['account_title']} | Dr: {$line['debit']} | Cr: {$line['credit']}\n";
        }
    } else {
        echo "Failed to create entry.\n";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
