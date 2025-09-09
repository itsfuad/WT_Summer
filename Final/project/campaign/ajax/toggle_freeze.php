<?php
require_once '../../shared/includes/session.php';
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
    
    $pdo->beginTransaction();
    
    // Get current status and featured status
    $stmt = $pdo->prepare("SELECT status, featured FROM funds WHERE id = ?");
    $stmt->execute([$fund_id]);
    $fund = $stmt->fetch();
    
    if (!$fund) {
        echo json_encode(['success' => false, 'message' => 'Fund not found']);
        exit;
    }
    
    $was_featured = (bool)$fund['featured'];
    
    // Set new status based on action
    $new_status = ($action === 'freeze') ? 'frozen' : 'active';
    $stmt = $pdo->prepare("UPDATE funds SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $fund_id]);
    
    // If freezing, also resolve any pending reports and unfeature the campaign
    if ($action === 'freeze') {
        // Resolve pending reports
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE fund_id = ? AND status = 'pending'");
        $stmt->execute([$fund_id]);
        
        // Auto-unfeature the campaign if it was featured
        $stmt = $pdo->prepare("UPDATE funds SET featured = 0 WHERE id = ? AND featured = 1");
        $stmt->execute([$fund_id]);
    }
    
    $pdo->commit();
    
    $message = $action === 'freeze' ? 'Campaign frozen successfully' : 'Campaign unfrozen successfully';
    
    // Add information about auto-unfeaturing if the campaign was featured and got frozen
    if ($action === 'freeze' && $was_featured) {
        $message .= ' and automatically unfeatured';
    }
    
    echo json_encode([
        'success' => true,
        'status' => $new_status,
        'was_featured' => $was_featured,
        'action' => $action,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    $pdo->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
