<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$action  = $_POST['action'] ?? 'list';
$type    = $_POST['type']   ?? 'expenses';
$id      = intval($_POST['id'] ?? 0);

// Map type to table + key label
$map = [
    'expenses'       => ['table' => 'expenses',           'label_col' => 'description', 'amount_col' => 'amount', 'date_col' => 'date'],
    'allowances'     => ['table' => 'allowances',         'label_col' => 'description', 'amount_col' => 'amount', 'date_col' => 'date'],
    'savings'        => ['table' => 'savings',            'label_col' => 'description', 'amount_col' => 'amount', 'date_col' => 'date'],
    'bills'          => ['table' => 'recurring_payments', 'label_col' => 'title',        'amount_col' => 'amount', 'date_col' => 'due_date'],
    'goals'          => ['table' => 'financial_goals',    'label_col' => 'title',        'amount_col' => 'target_amount', 'date_col' => 'created_at'],
];

// Ensure columns exist to prevent MySQL warnings that break JSON output
foreach ($map as $info) {
    ensureColumnExists($conn, $info['table'], 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
}

if ($action === 'list') {
    $result = [];
    foreach ($map as $typeKey => $info) {
        $tbl   = $info['table'];
        $label = $info['label_col'];
        $amt   = $info['amount_col'];
        $dt    = $info['date_col'];
        $rows  = $conn->query("SELECT id, `$label` as label, `$amt` as amount, `$dt` as record_date, deleted_at FROM `$tbl` WHERE user_id = $user_id AND deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        if ($rows) {
            while ($r = $rows->fetch_assoc()) {
                $r['type'] = $typeKey;
                $result[] = $r;
            }
        }
    }
    // Sort by deleted_at desc
    usort($result, fn($a, $b) => strcmp($b['deleted_at'], $a['deleted_at']));
    echo json_encode(['success' => true, 'data' => $result]);
    $conn->close();
    exit;
}

if (!$id || !isset($map[$type])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$tbl = $map[$type]['table'];

if ($action === 'restore') {
    $stmt = $conn->prepare("UPDATE `$tbl` SET deleted_at = NULL WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' record restored!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found in recycle bin']);
    }
    $stmt->close();
} elseif ($action === 'permanent_delete') {
    $stmt = $conn->prepare("DELETE FROM `$tbl` WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Record permanently deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
