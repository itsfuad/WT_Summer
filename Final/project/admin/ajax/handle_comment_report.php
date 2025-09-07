<?php
require_once '../../shared/includes/session.php';
require_once '../../shared/includes/functions.php';

requireLogin();
requireRole('admin');


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

if (!$report_id || !in_array($action, ['delete', 'dismiss'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $fundManager = new FundManager();
    $pdo = $fundManager->getPdo();
    
    $pdo->beginTransaction();
    
    if ($action === 'delete' && $comment_id) {
        // Delete the comment
        $fundManager->deleteComment($comment_id);
        
        // Mark report as resolved
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$report_id]);
        
        $message = 'Comment has been deleted and report marked as resolved';
        
    } elseif ($action === 'dismiss') {
        // Mark report as dismissed
        $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE id = ?");
        $stmt->execute([$report_id]);
        
        $message = 'Report has been dismissed';
    } else {
        throw new Exception('Invalid action or missing comment ID');
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing report: ' . $e->getMessage()
    ]);
}
?>
