<?php
require_once '../../shared/includes/session.php';
require_once '../../shared/includes/functions.php';

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
$comment_id = isset($input['comment_id']) ? intval($input['comment_id']) : 0;

if (!$comment_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid comment ID']);
    exit;
}

try {
    // Check if comment exists and belongs to the user
    $stmt = $fundManager->getPdo()->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user['id']]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['error' => 'Comment not found or access denied']);
        exit;
    }
    
    // Delete comment (soft delete by changing status)
    $success = $fundManager->deleteComment($comment_id);
    
    if ($success) {
        // Get the new comment count for the fund
        $commentsCount = $fundManager->getCommentsCount($comment['fund_id']);
        
        echo json_encode([
            'success' => true,
            'comments_count' => $commentsCount,
            'message' => 'Comment deleted successfully!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete comment']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
