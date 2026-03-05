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

// Use categories sent from frontend (actual dropdown options) + any from DB
$categories = [];

// From request (the actual dropdown values — most reliable)
if (!empty($data['categories']) && is_array($data['categories'])) {
    $categories = array_values(array_filter($data['categories']));
}

// Supplement with DB categories if needed
if (empty($categories)) {
    $res = $conn->query("SELECT name FROM categories WHERE user_id = $user_id");
    while ($row = $res->fetch_assoc()) $categories[] = $row['name'];
}

if (empty($categories)) {
    echo json_encode(['success' => false, 'message' => 'No categories available']);
    exit;
}

$aiHelper = new AiHelper($conn, $user_id);
$prompt = "Based on the transaction description: '$description', and these available categories: " . implode(', ', $categories) . ". 
Predict the most likely category. Return ONLY the exact category name from the list, nothing else. If none fit, return the closest match.";

$prediction = $aiHelper->getResponse($prompt);
$predictedCategory = trim(is_array($prediction) ? ($prediction['message'] ?? '') : (string)$prediction);

// Match against known categories (case-insensitive)
$matched = '';
foreach ($categories as $cat) {
    if (strcasecmp($predictedCategory, $cat) === 0 || stripos($predictedCategory, $cat) !== false) {
        $matched = $cat;
        break;
    }
}

// If no match, just return what AI said (frontend will do its own matching)
if (empty($matched)) $matched = $predictedCategory;

echo json_encode([
    'success' => true,
    'category' => $matched
]);
