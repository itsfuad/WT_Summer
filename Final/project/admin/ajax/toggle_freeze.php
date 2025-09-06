<?php
require_once '../../includes/session.php';
requireLogin();
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$fund_id = intval($_POST['fund_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'freeze' or 'unfreeze'

if ($fund_id <= 0 || !in_array($action, ['freeze', 'unfreeze'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    require_once '../../config/database.php';
    
    // Get current status
    $stmt = $pdo->prepare("SELECT status FROM funds WHERE id = ?");
    $stmt->execute([$fund_id]);
    $fund = $stmt->fetch();
    
    if (!$fund) {
        echo json_encode(['success' => false, 'message' => 'Fund not found']);
        exit;
    }
    
    // Set new status based on action
    $new_status = ($action === 'freeze') ? 'paused' : 'active';
    $stmt = $pdo->prepare("UPDATE funds SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $fund_id]);
    
    echo json_encode([
        'success' => true,
        'status' => $new_status,
        'message' => $action === 'freeze' ? 'Campaign frozen successfully' : 'Campaign unfrozen successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
