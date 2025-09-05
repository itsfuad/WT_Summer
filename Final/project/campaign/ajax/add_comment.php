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
$comment_text = isset($input['comment']) ? trim($input['comment']) : '';

if (!$fund_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid fund ID']);
    exit;
}

if (empty($comment_text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment cannot be empty']);
    exit;
}

if (strlen($comment_text) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment too long (max 1000 characters)']);
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
    // Add comment
    $comment_id = $fundManager->addComment($fund_id, $user['id'], $comment_text);
    
    if ($comment_id) {
        // Get the new comment count
        $commentsCount = $fundManager->getCommentsCount($fund_id);
        
        // Return the new comment data
        echo json_encode([
            'success' => true,
            'comment' => [
                'id' => $comment_id,
                'user_name' => $user['name'],
                'user_role' => $user['role'],
                'comment' => $comment_text,
                'created_at' => date('Y-m-d H:i:s'),
                'user_id' => $user['id']
            ],
            'comments_count' => $commentsCount,
            'message' => 'Comment added successfully!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add comment']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
