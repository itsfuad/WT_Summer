<?php
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = getCurrentUser();
$fundManager = new FundManager();
$pdo = $fundManager->getPdo();

$input = json_decode(file_get_contents('php://input'), true);
$fund_id = isset($input['fund_id']) ? intval($input['fund_id']) : null;
$comment_id = isset($input['comment_id']) ? intval($input['comment_id']) : null;
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$description = isset($input['description']) ? trim($input['description']) : null;

if (!$fund_id && !$comment_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Provide fund_id or comment_id']);
    exit;
}

$valid_reasons = ['spam','misleading','abuse','other'];
if (!$reason || !in_array($reason, $valid_reasons)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid reason']);
    exit;
}

if ($fund_id) {
    $fund = $fundManager->getFundById($fund_id);
    if (!$fund) {
        http_response_code(404);
        echo json_encode(['error' => 'Fund not found']);
        exit;
    }
}

if ($comment_id) {
    $stmt = $pdo->prepare('SELECT id FROM comments WHERE id = ?');
    $stmt->execute([$comment_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Comment not found']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare('INSERT INTO reports (fund_id, comment_id, reported_by, reason, description, status, created_at) VALUES (?, ?, ?, ?, ?, "pending", NOW())');
    $stmt->execute([$fund_id, $comment_id, $user['id'], $reason, $description]);
    echo json_encode(['success' => true, 'message' => 'Report submitted']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

// no closing tag
