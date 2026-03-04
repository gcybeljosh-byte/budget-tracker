<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$current_date = date('Y-m-d');
$month_start = date('Y-m-01');

try {
    // 1. Clear existing sample data for THIS month to prevent duplicates (Optional but recommended for seeding)
    // We only clear if specifically asked or just add more. Let's just add.

    // 2. Initial Allowance
    $conn->query("INSERT INTO allowances (user_id, amount, date, description, source_type) VALUES 
        ($user_id, 25000, '$month_start', 'Initial Monthly Budget', 'Bank'),
        ($user_id, 5000, '$month_start', 'Cash on Hand', 'Cash')");

    // 3. Sample Categories for logic
    $categories = ['Food & Dining', 'Transportation', 'Utilities', 'Entertainment', 'Shopping', 'Healthcare'];

    // 4. Sample Expenses
    $expenses = [
        ['Food & Dining', 450, 'Dinner at Restaurant', 'Cash'],
        ['Transportation', 120, 'Grab Ride', 'Bank'],
        ['Utilities', 3500, 'Electric Bill', 'Bank'],
        ['Food & Dining', 200, 'Lunch', 'Cash'],
        ['Shopping', 1500, 'New Shoes', 'Bank'],
        ['Entertainment', 300, 'Movie Tickets', 'Cash'],
    ];

    foreach ($expenses as $exp) {
        $cat = $exp[0];
        $amt = $exp[1];
        $desc = $exp[2];
        $src = $exp[3];
        $conn->query("INSERT INTO expenses (user_id, category, amount, date, description, expense_source, source_type) 
                      VALUES ($user_id, '$cat', $amt, '$current_date', '$desc', 'Allowance', '$src')");
    }

    // 5. Sample Savings
    $conn->query("INSERT INTO savings (user_id, amount, date, description, source_type) VALUES 
        ($user_id, 2000, '$month_start', 'Monthly Emergency Fund', 'Bank')");

    // 6. Sample Goals
    $goal_deadline = date('Y-m-t', strtotime('+3 months'));
    $conn->query("INSERT INTO financial_goals (user_id, title, target_amount, saved_amount, deadline, category, status) VALUES 
        ($user_id, 'New Laptop', 45000, 5000, '$goal_deadline', 'Electronics', 'active'),
        ($user_id, 'Beach Trip', 15000, 2000, '$goal_deadline', 'Leisure', 'active')");

    // 7. Sample Bills
    $bill_due = date('Y-m-15');
    $conn->query("INSERT INTO recurring_payments (user_id, title, amount, due_date, category, is_active) VALUES 
        ($user_id, 'Internet Subscription', 1500, '$bill_due', 'Utilities', 1),
        ($user_id, 'Netflix', 549, '$bill_due', 'Entertainment', 1)");

    echo json_encode(['success' => true, 'message' => 'Sample data seeded successfully! Dashboard updated.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Seeding failed: ' . $e->getMessage()]);
}

$conn->close();
