<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    $dir = '../reports_archive/';
    // --- Auto-Migration: Ensure reports table exists ---
    $conn->query("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        report_type VARCHAR(50) DEFAULT 'monthly',
        period VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $fileName = $_POST['name'] ?? 'Report_' . time() . '.pdf';
    // Clean filename
    $fileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileName);
    
    $targetPath = $dir . $fileName;
    
    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $targetPath)) {
        $report_type = $_POST['type'] ?? 'monthly';
        $period = $_POST['period'] ?? '';
        $stmt = $conn->prepare("INSERT INTO reports (user_id, filename, report_type, period) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $_SESSION['id'], $fileName, $report_type, $period);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Report archived']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive report']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
