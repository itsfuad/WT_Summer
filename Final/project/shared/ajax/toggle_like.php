<?php
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = getCurrentUser();
$fundManager = new FundManager();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$fund_id = isset($input['fund_id']) ? intval($input['fund_id']) : 0;

if (!$fund_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid fund ID']);
    exit;
}

// Check if fund exists
$fund = $fundManager->getFundById($fund_id);
if (!$fund) {
    http_response_code(404);
    echo json_encode(['error' => 'Fund not found']);
    exit;
}

try {
    // Toggle like
    $isLiked = $fundManager->toggleLike($fund_id, $user['id']);
    $likesCount = $fundManager->getLikesCount($fund_id);
    
    echo json_encode([
        'success' => true,
        'liked' => $isLiked,
        'likes_count' => $likesCount,
        'message' => $isLiked ? 'Fund liked!' : 'Fund unliked!'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
