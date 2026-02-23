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

// Auto-update overdue goals
$conn->query("UPDATE financial_goals SET status = 'overdue'
              WHERE user_id = $user_id AND status = 'active' AND deadline < CURDATE() AND saved_amount < target_amount");
$conn->query("UPDATE financial_goals SET status = 'completed'
              WHERE user_id = $user_id AND saved_amount >= target_amount");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT * FROM financial_goals WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $response = ['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)];
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'add':
            $title = trim($data['title'] ?? '');
            $target = floatval($data['target_amount'] ?? 0);
            $deadline = !empty($data['deadline']) ? $data['deadline'] : null;

            if ($title && $target > 0) {
                $stmt = $conn->prepare("INSERT INTO financial_goals (user_id, title, target_amount, deadline) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isds", $user_id, $title, $target, $deadline);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Goal created!', 'id' => $stmt->insert_id];
                    logActivity($conn, $user_id, 'goal_add', "Created goal: '$title' (Target: $target)");
                } else {
                    $response = ['success' => false, 'message' => $conn->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Title and target amount required'];
            }
            break;

        case 'contribute':
            $id = intval($data['id'] ?? 0);
            $amount = floatval($data['amount'] ?? 0);
            $source_type = $data['source_type'] ?? 'Cash';

            if ($id && $amount > 0) {
                // 1. Update Goal Amount
                $stmt = $conn->prepare("UPDATE financial_goals SET saved_amount = saved_amount + ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("dii", $amount, $id, $user_id);

                if ($stmt->execute()) {
                    // 2. Fetch Goal Title for description
                    $titleStmt = $conn->prepare("SELECT title FROM financial_goals WHERE id = ?");
                    $titleStmt->bind_param("i", $id);
                    $titleStmt->execute();
                    $goalTitle = $titleStmt->get_result()->fetch_assoc()['title'] ?? 'Goal';
                    $titleStmt->close();

                    // 3. Record as Savings Deposit (This will update the wallet widget)
                    $savingsStmt = $conn->prepare("INSERT INTO savings (user_id, date, amount, description, source_type) VALUES (?, CURDATE(), ?, ?, ?)");
                    $desc = "Goal Contribution: " . $goalTitle;
                    $savingsStmt->bind_param("idss", $user_id, $amount, $desc, $source_type);
                    $savingsStmt->execute();
                    $savingsStmt->close();

                    $response = ['success' => true, 'message' => 'Contribution added!'];
                    logActivity($conn, $user_id, 'goal_contribute', "Contributed $amount to goal '$goalTitle' from $source_type");
                } else {
                    $response = ['success' => false, 'message' => $conn->error];
                }
                $stmt->close();
            }
            break;

        case 'edit':
            $id = intval($data['id'] ?? 0);
            $title = trim($data['title'] ?? '');
            $target = floatval($data['target_amount'] ?? 0);
            $deadline = !empty($data['deadline']) ? $data['deadline'] : null;

            if ($id && $title && $target > 0) {
                $stmt = $conn->prepare("UPDATE financial_goals SET title = ?, target_amount = ?, deadline = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sdsii", $title, $target, $deadline, $id, $user_id);
                $response = $stmt->execute()
                    ? ['success' => true, 'message' => 'Goal updated']
                    : ['success' => false, 'message' => $conn->error];
                $stmt->close();
            }
            break;

        case 'delete':
            $id = intval($data['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM financial_goals WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $id, $user_id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Goal deleted'];
                    logActivity($conn, $user_id, 'goal_delete', "Deleted goal ID $id");
                } else {
                    $response = ['success' => false, 'message' => $conn->error];
                }
                $stmt->close();
            }
            break;
    }
}

echo json_encode($response);
$conn->close();
