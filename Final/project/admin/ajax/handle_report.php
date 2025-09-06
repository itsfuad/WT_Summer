<?php
require_once '../../includes/session.php';
requireLogin();
requireRole('admin');

require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$fund_id = isset($_POST['fund_id']) ? (int)$_POST['fund_id'] : 0;

if (!$report_id || !in_array($action, ['freeze', 'dismiss'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    if ($action === 'freeze' && $fund_id) {
        // Freeze the campaign
        $stmt = $pdo->prepare("UPDATE funds SET status = 'paused' WHERE id = ?");
        $stmt->execute([$fund_id]);
        
        // Mark report as resolved
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$report_id]);
        
        $message = 'Campaign has been frozen and report marked as resolved';
        
    } elseif ($action === 'dismiss') {
        // Mark report as dismissed
        $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed', resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$report_id]);
        
        $message = 'Report has been dismissed';
    } else {
        throw new Exception('Invalid action or missing fund ID');
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
