<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/AiHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);
$description = trim($data['description'] ?? '');

if (empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Description required']);
    exit;
}

// Fetch user's categories for context
$categories = [];
$res = $conn->query("SELECT name FROM categories WHERE user_id = $user_id");
while ($row = $res->fetch_assoc()) $categories[] = $row['name'];

$aiHelper = new AiHelper($conn, $user_id);
$prompt = "Based on the transaction description: '$description', and these available categories: " . implode(', ', $categories) . ". 
Predict the most likely category. Return ONLY the category name and nothing else. If none fit well, return 'Other'.";

$prediction = $aiHelper->getResponse($prompt);
$predictedCategory = trim(is_array($prediction) ? ($prediction['message'] ?? 'Other') : (string)$prediction);

// Clean up prediction (sometimes AI adds extra text)
foreach ($categories as $cat) {
    if (stripos($predictedCategory, $cat) !== false) {
        $predictedCategory = $cat;
        break;
    }
}

echo json_encode([
    'success' => true,
    'category' => $predictedCategory
]);
