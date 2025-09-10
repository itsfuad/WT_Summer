<?php
require_once '../../shared/includes/session.php';
require_once '../../shared/includes/functions.php';

$fundManager = new FundManager();

// Get fund ID from URL
$fund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$fund_id) {
    header('Location: ../../home/view/index.php');
    exit;
}

// Get fund details
$fund = $fundManager->getFundById($fund_id);

if (!$fund) {
    header('Location: ../../home/view/index.php');
    exit;
}

// Get user info (if logged in)
$user = isLoggedIn() ? getCurrentUser() : null;
$userRole = $user ? $user['role'] : 'guest';

// Get recent donations
$donations = $fundManager->getFundDonations($fund_id, 'recent', 5);

// Get comments and engagement data
$comments = $fundManager->getFundComments($fund_id, 20);
$commentsCount = $fundManager->getCommentsCount($fund_id);
$likesCount = $fundManager->getLikesCount($fund_id);
$userHasLiked = $user ? $fundManager->hasUserLiked($fund_id, $user['id']) : false;

// Calculate statistics
$percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
$days_left = getDaysLeft($fund['end_date']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($fund['title'] ?? 'Campaign'); ?> - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        // Current user ID for comment ownership checks
        const currentUserId = <?php echo $user ? $user['id'] : 'null'; ?>;
        const fundId = <?php echo $fund['id']; ?>;
        const fundTitle = <?php echo json_encode($fund['title']); ?>;
        const goalAmount = <?php echo $fund['goal_amount']; ?>;
    </script>
    <script src="../js/script.js" defer></script>
</head>
<body>
    
    <div class="campaign-container <?php echo $fund['status'] === 'frozen' ? 'frozen' : ''; ?>">
        <!-- Navigation Bar -->
        <nav class="nav-bar">
            <div class="nav-content">
                <div class="nav-left">
                    <a href="../../home/view/index.php" class="nav-brand">
                        <i class="fas fa-hand-holding-heart"></i>
                        CrowdFund
                    </a>
                </div>
                <div class="nav-right">
                    <?php if (!$user): ?>
                        <div class="auth-links">
                            <a href="../../login/view/index.php" class="nav-link">Login</a>
                            <a href="../../signup/view/index.php" class="nav-link">Sign Up</a>
                        </div>
                    <?php else: ?>
                        <div class="user-menu">
                            <a href="../../<?php echo $userRole; ?>/view/index.php" class="nav-link">Back to Dashboard</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Campaign Header -->
            <div class="campaign-header">
                <div class="campaign-image">
                    <?php if (!empty($fund['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($fund['image_url']); ?>" alt="<?php echo htmlspecialchars($fund['title']); ?>">
                    <?php else: ?>
                        <div class="placeholder-image">
                            <i class="fas fa-image"></i>
                            <span>No Image</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="campaign-info">
                    <div class="badges">
                        <div class="status-badge status-frozen" id="status-badge" style="display: <?php echo $fund['status'] === 'frozen' ? 'inline-block' : 'none'; ?>;">
                            <i class="fas fa-pause"></i> Frozen
                        </div>
                        <div class="status-badge status-featured" id="featured-badge" style="display: <?php echo $fund['featured'] ? 'inline-block' : 'none'; ?>;">
                            <i class="fas fa-star"></i> Featured
                        </div>
                    </div>
                    <div class="title-section">
                        <h1 class="campaign-title">
                            <?php echo htmlspecialchars($fund['title']); ?>
                        </h1>
                        <span class="status-badge no-pad category" style="color: <?php echo $fund['category_color'] ?? '#000'; ?>;">
                            <i class="<?php echo $fund['category_icon'] ?? 'fas fa-tag'; ?>"></i>
                            <?php echo htmlspecialchars($fund['category_name']); ?>
                        </span>
                    </div>
                    <span class="by">
                        by <a href="../../profile/view/index.php?id=<?php echo $fund['fundraiser_id']; ?>"><?php echo htmlspecialchars($fund['fundraiser_name']); ?></a>
                    </span>
                    <!-- Progress Stats -->
                    <div class="progress-section">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        
                        <div class="progress-stats">
                            <div class="stat">
                                <div class="stat-value" id="current-amount"><?php echo formatCurrency($fund['current_amount']); ?></div>
                                <div class="stat-label">raised of <span id="goal-amount"><?php echo formatCurrency($fund['goal_amount']); ?></span></div>
                            </div>
                            <div class="stat">
                                <div class="stat-value" id="backer-count"><?php echo $fund['backer_count']; ?></div>
                                <div class="stat-label">backers</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value" id="days-left"><?php echo $days_left; ?></div>
                                <div class="stat-label">days left</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if ($fund['status'] === 'frozen' && $userRole !== 'admin'): ?>
                            <button class="btn btn-disabled" disabled>
                                <i class="fas fa-pause"></i>
                                Campaign Frozen
                            </button>
                        <?php elseif ($userRole === 'guest'): ?>
                            <a href="../../login/view/index.php" class="btn btn-primary">
                                <i class="fas fa-hand-holding-usd"></i>
                                Login to donate
                            </a>
                        <?php elseif ($userRole === 'admin'): ?>
                            <button class="btn <?php echo $fund['featured'] ? 'btn-outline' : 'btn-primary'; ?>" onclick="toggleFeature(<?php echo $fund['id']; ?>)" id="feature-btn" 
                                <?php echo $fund['status'] === 'frozen' ? 'disabled title="Cannot feature a frozen campaign"' : ''; ?>>
                                <i class="fas fa-star"></i>
                                <?php echo $fund['featured'] ? 'Unfeature' : 'Mark as Featured'; ?>
                            </button>
                            <button class="btn btn-danger" onclick="toggleFreeze(<?php echo $fund['id']; ?>)" id="freeze-btn">
                                <i class="fas fa-pause"></i>
                                <?php echo $fund['status'] === 'frozen' ? 'Unfreeze' : 'Freeze'; ?>
                            </button>
                        <?php elseif ($userRole === 'backer' || ($userRole === 'fundraiser' && $fund['fundraiser_id'] != $user['id'])): ?>
                            <button class="btn btn-primary freeze-toggle" onclick="openDonateModal()" <?php echo in_array($fund['status'], ['paused', 'frozen', 'removed']) ? 'disabled title="Campaign is not accepting donations because it is ' . htmlspecialchars($fund['status']) . '"' : ''; ?>>
                                <i class="fas fa-hand-holding-usd"></i>
                                Donate
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($userRole === 'fundraiser' && $fund['fundraiser_id'] == $user['id']): ?>
                            <a href="edit_fund.php?id=<?php echo $fund['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i>
                                Edit
                            </a>
                            <a href="analytics.php?id=<?php echo $fund['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-chart-bar"></i>
                                Analytics
                            </a>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline like-btn freeze-toggle <?php echo $userHasLiked ? 'liked' : ''; ?>"
                            onclick="toggleLike(<?php echo $fund['id']; ?>)"
                            id="like-btn" <?php echo (!$user || $fund['status'] === 'frozen') ? 'disabled title="' . ($fund['status'] === 'frozen' ? 'Campaign is frozen' : 'Login required') . '"' : ''; ?>>
                            <i class="fas fa-heart"></i>
                            <span id="like-text"><?php echo $userHasLiked ? 'Liked' : 'Like'; ?></span>
                            <span id="likes-count">(<?php echo $likesCount; ?>)</span>
                        </button>
                        
                        <button class="btn btn-outline freeze-toggle" onclick="shareCampaign()" <?php echo $fund['status'] === 'frozen' ? 'disabled title="Campaign is frozen"' : ''; ?>>
                            <i class="fas fa-share"></i>
                            Share
                        </button>
                        <?php if ($userRole != 'admin'): ?>
                        <button class="btn btn-outline freeze-toggle" onclick="openReportModal()" <?php echo (!$user || $fund['status'] === 'frozen') ? 'disabled title="' . ($fund['status'] === 'frozen' ? 'Campaign is frozen' : 'Login required') . '"' : ''; ?>>
                            <i class="fas fa-flag"></i>
                            Report
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Campaign Content -->
            <div class="campaign-content">
                <!-- Description -->
                <div class="description-section">
                    <h2>About this campaign</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($fund['description'])); ?>
                    </div>
                </div>

                <!-- Comment Form (Always Visible for Logged-in Users) -->
                <div class="comment-form-section" style="display: <?php echo ($user && $fund['status'] !== 'frozen') ? '' : 'none'; ?>;">
                    <form class="comment-form" onsubmit="submitComment(event)">
                        <div class="comment-input-container">
                            <div class="user-avatar">
                                <?php 
                                if ($user) {
                                    $userManager = new UserManager();
                                    $currentUserProfileImage = $userManager->getProfileImage($user['id']);
                                    if (!empty($currentUserProfileImage)): ?>
                                        <img src="<?php echo htmlspecialchars($currentUserProfileImage); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="user-profile-img">
                                    <?php else: 
                                        // Show default profile image for users without profile images
                                        $uploadManager = new UploadManager();
                                        $defaultProfileUrl = $uploadManager->getImageUrl('profile', 'default-profile.png');
                                    ?>
                                        <img src="<?php echo htmlspecialchars($defaultProfileUrl); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="user-profile-img default">
                                    <?php endif;
                                } else { 
                                    // Show default profile image for guests
                                    $uploadManager = new UploadManager();
                                    $defaultProfileUrl = $uploadManager->getImageUrl('profile', 'default-profile.png');
                                ?>
                                    <img src="<?php echo htmlspecialchars($defaultProfileUrl); ?>" alt="Guest" class="user-profile-img default">
                                <?php } ?>
                            </div>
                            <textarea id="comment-text" placeholder="Add a comment..." required maxlength="1000" rows="1" oninput="autoResize(this)"></textarea>
                            <button type="submit" class="submit-comment-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if ($fund['status'] === 'frozen'): ?>
                <div class="login-prompt">
                    <span style="color: black;"><i class="fas fa-pause"></i> This campaign is currently frozen</span>
                </div>
                <?php elseif (!$user && $commentsCount > 0): ?>
                <div class="login-prompt">
                    <p><a href="../../login/view/index.php">Login</a> to join the conversation</p>
                </div>
                <?php endif; ?>

                <!-- Comments Section -->
                <?php if ($commentsCount > 0): ?>
                <div class="comments-section">
                    <div class="comments-header">
                        <h3>Comments (<?php echo $commentsCount; ?>)</h3>
                    </div>
                    
                    <div class="comments-list" id="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item" data-comment-id="<?php echo $comment['id']; ?>">
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <div class="comment-user">
                                            <div class="user-avatar">
                                                <?php if (!empty($comment['profile_image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($comment['profile_image_url']); ?>" alt="<?php echo htmlspecialchars($comment['user_name']); ?>" class="user-profile-img">
                                                <?php else: 
                                                    // Show default profile image for users without profile images
                                                    $uploadManager = new UploadManager();
                                                    $defaultProfileUrl = $uploadManager->getImageUrl('profile', 'default-profile.png');
                                                ?>
                                                    <img src="<?php echo htmlspecialchars($defaultProfileUrl); ?>" alt="<?php echo htmlspecialchars($comment['user_name']); ?>" class="user-profile-img default">
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-info">
                                                <span class="username">
                                                    <a class="username" href="../../profile/view?id=<?php echo htmlspecialchars($comment['user_id']); ?>"><?php echo htmlspecialchars($comment['user_name']); ?></a>
                                                    <?php if ($comment['user_role'] === 'fundraiser'): ?>
                                                        <span class="role-badge fundraiser">Creator</span>
                                                    <?php elseif ($comment['user_role'] === 'backer'): ?>
                                                        <span class="role-badge backer">Backer</span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="comment-time js-timeago" data-time="<?php echo htmlspecialchars($comment['created_at']); ?>"><?php echo timeAgo($comment['created_at']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($user && $comment['user_id'] == $user['id']): ?>
                                            <div class="comment-actions">
                                                <button class="comment-action-btn" onclick="editComment(<?php echo $comment['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="comment-action-btn delete" onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php elseif ($user): ?>
                                            <div class="comment-actions">
                                                <button class="comment-action-btn" title="Report comment" onclick="openCommentReport(<?php echo $comment['id']; ?>)">
                                                    <i class="fas fa-flag"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="comment-text" id="comment-content-<?php echo $comment['id']; ?>">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Backers -->
                <?php if (count($donations) > 0): ?>
                <div class="backers-section">
                    <h3>Recent backers</h3>
                    <div class="backers-list">
                        <?php foreach ($donations as $donation): ?>
                            <div class="backer-item">
                                <div class="backer-info">
                                    <div class="backer-avatar">
                                        <?php if (!$donation['anonymous'] && !empty($donation['profile_image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($donation['profile_image_url']); ?>" alt="<?php echo htmlspecialchars($donation['backer_name']); ?>" class="user-profile-img">
                                        <?php else: 
                                            // Show default profile image for anonymous users or users without profile images
                                            $uploadManager = new UploadManager();
                                            $defaultProfileUrl = $uploadManager->getImageUrl('profile', 'default-profile.png');
                                        ?>
                                            <img src="<?php echo htmlspecialchars($defaultProfileUrl); ?>" alt="User" class="user-profile-img <?php echo $donation['anonymous'] ? 'anonymous' : 'default'; ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="backer-details">
                                        <div class="backer-name">
                                            <?php 
                                                echo $donation['anonymous'] ? 'Anonymous' : 
                                                '<a class="username" href="../../profile/view?id=' . htmlspecialchars($donation['backer_id']) . '">' . htmlspecialchars($donation['backer_name']) . '</a>'; 
                                            ?>
                                        </div>
                                        <div class="backer-time js-timeago" data-time="<?php echo htmlspecialchars($donation['created_at']); ?>">
                                            <?php echo timeAgo($donation['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="backer-amount">
                                    <?php echo formatCurrency($donation['amount']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Donate Modal -->
    <div id="donate-modal" class="modal" style="display:none; position:fixed; inset:0; background: rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index: 999;">
        <div class="modal-content" style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:420px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <h3 style="margin:0;">Donate to <?php echo htmlspecialchars($fund['title']); ?></h3>
                <button onclick="closeDonateModal()" class="btn btn-outline btn-sm">Close</button>
            </div>
            <form onsubmit="submitDonation(event)">
                <label for="donation-amount">Amount</label>
                <input type="number" step="0.01" min="1" id="donation-amount" class="form-control" placeholder="Enter amount" required>
                <label for="donation-comment" style="margin-top:12px;">Comment (optional)</label>
                <textarea id="donation-comment" class="form-control" rows="2" placeholder="Say something nice (optional)"></textarea>
                <label style="display:flex; align-items:center; gap:8px; margin-top:12px;">
                    <input type="checkbox" id="donation-anonymous"> Donate anonymously
                </label>
                <div style="display:flex; gap:8px; margin-top:16px;">
                    <button type="button" class="btn btn-outline" onclick="closeDonateModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Donation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Modal -->
    <div id="report-modal" class="modal" style="display:none; position:fixed; inset:0; background: rgba(0,0,0,0.5); align-items:center; justify-content:center;">
        <div class="modal-content" style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:420px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <h3 style="margin:0;">Report Campaign</h3>
                <button onclick="closeReportModal()" class="btn btn-outline btn-sm">Close</button>
            </div>
            <form onsubmit="submitReport(event)">
                <label for="report-reason">Reason</label>
                <select id="report-reason" class="form-control" required>
                    <option value="">Select a reason</option>
                    <option value="spam">Spam</option>
                    <option value="misleading">Misleading/Fraud</option>
                    <option value="abuse">Harassment/Abuse</option>
                    <option value="other">Other</option>
                </select>
                <label for="report-description" style="margin-top:12px;">Details (optional)</label>
                <textarea id="report-description" class="form-control" rows="3" placeholder="Provide details..."></textarea>
                <div style="display:flex; gap:8px; margin-top:16px;">
                    <button type="button" class="btn btn-outline" onclick="closeReportModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-flag"></i> Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
