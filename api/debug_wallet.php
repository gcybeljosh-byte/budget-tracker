<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

$data = [];

// 1. Check Allowances with 'Cash'
$data['cash_allowances'] = $conn->query("SELECT id, amount, source_type, description FROM allowances WHERE user_id = $user_id AND source_type = 'Cash'")->fetch_all(MYSQLI_ASSOC);

// 2. Check Expenses with 'Cash' (from Allowance)
$data['cash_expenses'] = $conn->query("SELECT id, amount, source_type, expense_source, description FROM expenses WHERE user_id = $user_id AND source_type = 'Cash' AND expense_source = 'Allowance'")->fetch_all(MYSQLI_ASSOC);

// 3. Check Savings with 'Cash'
$data['cash_savings'] = $conn->query("SELECT id, amount, source_type, description FROM savings WHERE user_id = $user_id AND source_type = 'Cash'")->fetch_all(MYSQLI_ASSOC);

// 4. Check Raw Source Types (to find case sensitivity issues or typos)
$data['all_allowance_sources'] = $conn->query("SELECT DISTINCT source_type FROM allowances WHERE user_id = $user_id")->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'id' => $user_id, 'data' => $data]);
