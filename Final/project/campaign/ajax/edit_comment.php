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
$comment_id = isset($input['comment_id']) ? intval($input['comment_id']) : 0;
$new_comment = isset($input['comment']) ? trim($input['comment']) : '';

if (!$comment_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid comment ID']);
    exit;
}

if (empty($new_comment)) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment cannot be empty']);
    exit;
}

if (strlen($new_comment) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Comment too long (max 1000 characters)']);
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
    
    // Update comment
    $success = $fundManager->updateComment($comment_id, $new_comment);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'comment' => $new_comment,
            'message' => 'Comment updated successfully!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update comment']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
