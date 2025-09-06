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

if ($fund_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid fund ID']);
    exit;
}

try {
    require_once '../../config/database.php';
    
    // Get current featured status
    $stmt = $pdo->prepare("SELECT featured FROM funds WHERE id = ?");
    $stmt->execute([$fund_id]);
    $fund = $stmt->fetch();
    
    if (!$fund) {
        echo json_encode(['success' => false, 'message' => 'Fund not found']);
        exit;
    }
    
    // Toggle featured status
    $new_featured = $fund['featured'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE funds SET featured = ? WHERE id = ?");
    $stmt->execute([$new_featured, $fund_id]);
    
    echo json_encode([
        'success' => true, 
        'featured' => $new_featured,
        'message' => $new_featured ? 'Campaign featured successfully' : 'Campaign unfeatured successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
