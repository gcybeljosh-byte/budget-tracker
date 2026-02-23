<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all limits with current month spending
    $currentMonth = date('Y-m');
    $stmt = $conn->prepare("
        SELECT cl.category, cl.limit_amount,
               COALESCE(SUM(e.amount), 0) as spent
        FROM category_limits cl
        LEFT JOIN expenses e ON e.user_id = cl.user_id
            AND e.category = cl.category
            AND DATE_FORMAT(e.date, '%Y-%m') = ?
        WHERE cl.user_id = ?
        GROUP BY cl.id, cl.category, cl.limit_amount
        ORDER BY cl.category ASC
    ");
    $stmt->bind_param("si", $currentMonth, $user_id);
    $stmt->execute();
    $response = ['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)];
    $stmt->close();

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $data['action'] ?? '';

    if ($action === 'save') {
        $category = trim($data['category'] ?? '');
        $limit_amount = floatval($data['limit_amount'] ?? 0);

        if ($category && $limit_amount > 0) {
            $stmt = $conn->prepare("
                INSERT INTO category_limits (user_id, category, limit_amount)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE limit_amount = VALUES(limit_amount)
            ");
            $stmt->bind_param("isd", $user_id, $category, $limit_amount);
            $response = $stmt->execute()
                ? ['success' => true, 'message' => 'Budget limit saved']
                : ['success' => false, 'message' => $conn->error];
            $stmt->close();
            logActivity($conn, $user_id, 'budget_limit_set', "Set budget limit for '$category': $limit_amount");
        } else {
            $response = ['success' => false, 'message' => 'Category and limit amount required'];
        }

    } elseif ($action === 'delete') {
        $category = trim($data['category'] ?? '');
        if ($category) {
            $stmt = $conn->prepare("DELETE FROM category_limits WHERE user_id = ? AND category = ?");
            $stmt->bind_param("is", $user_id, $category);
            $response = $stmt->execute()
                ? ['success' => true, 'message' => 'Limit removed']
                : ['success' => false, 'message' => $conn->error];
            $stmt->close();
        }
    }
}

echo json_encode($response);
$conn->close();
?>
