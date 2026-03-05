<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];

// --- Auto-Migration: Ensure recurring_payments table exists ---
$conn->query("CREATE TABLE IF NOT EXISTS recurring_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    category VARCHAR(50) DEFAULT 'Utilities',
    due_date DATE NOT NULL,
    frequency ENUM('monthly', 'yearly', 'weekly') DEFAULT 'monthly',
    source_type VARCHAR(50) DEFAULT 'Cash',
    is_active TINYINT(1) DEFAULT 1,
    last_paid_at DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

// Auto-migrate: Ensure soft-delete and description columns exist
ensureColumnExists($conn, 'recurring_payments', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
ensureColumnExists($conn, 'recurring_payments', 'description', 'TEXT DEFAULT NULL');

if ($method === 'GET') {
    if ($action === 'list') {
        $stmt = $conn->prepare("SELECT * FROM recurring_payments WHERE user_id = ? AND deleted_at IS NULL ORDER BY due_date ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $bills = [];
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $bills]);
    } elseif ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM recurring_payments WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();
        if ($bill) {
            echo json_encode(['success' => true, 'data' => $bill]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
        }
    } elseif ($action === 'fetch_events') {
        $stmt = $conn->prepare("SELECT id, title, amount, due_date, category, description FROM recurring_payments WHERE user_id = ? AND deleted_at IS NULL AND is_active = 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'] . ' (' . $row['amount'] . ')',
                'start' => $row['due_date'],
                'allDay' => true,
                'extendedProps' => [
                    'amount' => $row['amount'],
                    'category' => $row['category'],
                    'description' => $row['description']
                ],
                'description' => $row['description'], // Also put at top level for convenience
                'backgroundColor' => '#6366f1',
                'borderColor' => '#6366f1'
            ];
        }
        echo json_encode(['success' => true, 'events' => $events]);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add') {
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $amount = (float)($data['amount'] ?? 0);
        $category = $data['category'] ?? 'Utilities';
        $due_date = $data['due_date'] ?? '';
        $frequency = $data['frequency'] ?? 'monthly';
        $source = $data['source_type'] ?? 'Cash';

        if (empty($title) || $amount <= 0 || empty($due_date)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO recurring_payments (user_id, title, description, amount, category, due_date, frequency, source_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdssss", $user_id, $title, $description, $amount, $category, $due_date, $frequency, $source);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bill added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add bill']);
        }
    } elseif ($action === 'edit') {
        $id = (int)($data['id'] ?? 0);
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $amount = (float)($data['amount'] ?? 0);
        $category = $data['category'] ?? 'Utilities';
        $due_date = $data['due_date'] ?? '';
        $frequency = $data['frequency'] ?? 'monthly';
        $source = $data['source_type'] ?? 'Cash';

        if (!$id || empty($title) || $amount <= 0 || empty($due_date)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE recurring_payments SET title = ?, description = ?, amount = ?, category = ?, due_date = ?, frequency = ?, source_type = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssdssssii", $title, $description, $amount, $category, $due_date, $frequency, $source, $id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bill updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update bill']);
        }
    } elseif ($action === 'pay') {
        $bill_id = (int)($data['id'] ?? 0);

        // 1. Fetch bill details
        $stmt = $conn->prepare("SELECT * FROM recurring_payments WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $bill_id, $user_id);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();

        if (!$bill) {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
            exit;
        }

        // 2. Log as expense
        $desc = "Paid Bill: " . $bill['title'];
        $stmt_exp = $conn->prepare("INSERT INTO expenses (user_id, category, description, amount, date, source_type, expense_source) VALUES (?, ?, ?, ?, CURDATE(), ?, 'Allowance')");
        $stmt_exp->bind_param("issds", $user_id, $bill['category'], $desc, $bill['amount'], $bill['source_type']);

        if ($stmt_exp->execute()) {
            // 3. Update bill due date based on frequency
            $current_due = new DateTime($bill['due_date']);
            if ($bill['frequency'] === 'monthly') {
                $current_due->modify('+1 month');
            } elseif ($bill['frequency'] === 'yearly') {
                $current_due->modify('+1 year');
            } elseif ($bill['frequency'] === 'weekly') {
                $current_due->modify('+1 week');
            }
            $next_due = $current_due->format('Y-m-d');

            $stmt_upd = $conn->prepare("UPDATE recurring_payments SET due_date = ?, last_paid_at = CURDATE() WHERE id = ?");
            $stmt_upd->bind_param("si", $next_due, $bill_id);
            $stmt_upd->execute();

            echo json_encode(['success' => true, 'message' => 'Bill marked as paid and logged as expense']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to log expense: ' . $conn->error]);
        }
    } elseif ($action === 'delete') {
        $bill_id = (int)($data['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE recurring_payments SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ii", $bill_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Bill moved to recycle bin']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed or already deleted']);
        }
    }
}
$conn->close();
