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
if (!in_array($user['role'], ['backer', 'fundraiser'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Only backers and fundraisers can donate']);
    exit;
}

$fundManager = new FundManager();

$input = json_decode(file_get_contents('php://input'), true);
$fund_id = isset($input['fund_id']) ? intval($input['fund_id']) : 0;
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$comment = isset($input['comment']) ? trim($input['comment']) : null;
$anonymous = !empty($input['anonymous']) ? 1 : 0;

if (!$fund_id || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid fund or amount']);
    exit;
}

// Disallow donating to own campaign
$fund = $fundManager->getFundById($fund_id);
if (!$fund) {
    http_response_code(404);
    echo json_encode(['error' => 'Fund not found']);
    exit;
}
if ($fund['fundraiser_id'] == $user['id']) {
    http_response_code(400);
    echo json_encode(['error' => 'You cannot donate to your own campaign']);
    exit;
}

try {
    $donation_id = $fundManager->createDonation($fund_id, $user['id'], $amount, $comment, $anonymous);
    // Return updated quick stats and the donation payload for UI update
    $updated = $fundManager->getFundById($fund_id);
    echo json_encode([
        'success' => true,
        'donation_id' => $donation_id,
        'current_amount' => $updated['current_amount'],
        'backer_count' => $updated['backer_count'],
        'donation' => [
            'amount' => $amount,
            'anonymous' => (int)$anonymous,
            'backer_name' => $anonymous ? 'Anonymous' : $user['name'],
            'created_at' => date('Y-m-d H:i:s')
        ],
        'message' => 'Donation successful!',
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>