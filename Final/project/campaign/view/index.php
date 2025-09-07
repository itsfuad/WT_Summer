<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($fund['title'] ?? 'Campaign'); ?> - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php
    require_once '../../shared/includes/session.php';
    require_once '../../shared/includes/functions.php';
    
    $fundManager = new FundManager();
    
    // Get fund ID from URL
    $fund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$fund_id) {
        header('Location: ../home/view/index.php');
        exit;
    }
    
    // Get fund details
    $fund = $fundManager->getFundById($fund_id);
    
    if (!$fund) {
        header('Location: ../home/view/index.php');
        exit;
    }
    
    // Get user info (if logged in)
    $user = isLoggedIn() ? getCurrentUser() : null;
    $userRole = $user ? $user['role'] : 'guest';
    
    // Get recent donations
    $donations = $fundManager->getRecentDonations($fund_id, 5);
    
    // Get comments and engagement data
    $comments = $fundManager->getFundComments($fund_id, 20);
    $commentsCount = $fundManager->getCommentsCount($fund_id);
    $likesCount = $fundManager->getLikesCount($fund_id);
    $userHasLiked = $user ? $fundManager->hasUserLiked($fund_id, $user['id']) : false;
    
    // Calculate statistics
    $percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
    $days_left = getDaysLeft($fund['end_date']);
    ?>
    
    <div class="campaign-container">
        <!-- Navigation Bar -->
        <nav class="nav-bar">
            <div class="nav-content">
                <div class="nav-left">
                    <a href="../home/view/index.php" class="nav-brand">
                        <i class="fas fa-hand-holding-heart"></i>
                        CrowdFund
                    </a>
                </div>
                <div class="nav-right">
                    <?php if ($user): ?>
                        <div class="user-menu">
                            <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                            <?php if ($userRole === 'fundraiser'): ?>
                                <a href="../../fundraiser/view/index.php" class="nav-link">Dashboard</a>
                                <a href="../../fundraiser/view/profile.php" class="nav-link">Profile</a>
                            <?php elseif ($userRole === 'backer'): ?>
                                <a href="../../backer/view/index.php" class="nav-link">My Donations</a>
                                <a href="../../backer/view/profile.php" class="nav-link">Profile</a>
                            <?php elseif ($userRole === 'admin'): ?>
                                <a href="../../admin/view/index.php" class="nav-link">Admin Panel</a>
                                <a href="../../admin/view/profile.php" class="nav-link">Profile</a>
                            <?php endif; ?>
                            <a href="../../includes/logout.php" class="nav-link">Logout</a>
                        </div>
                    <?php else: ?>
                        <div class="auth-links">
                            <a href="../../login/view/index.php" class="nav-link">Login</a>
                            <a href="../../signup/view/index.php" class="nav-link">Sign Up</a>
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
                    <div class="campaign-meta">
                        <span class="category">
                            <i class="<?php echo $fund['category_icon'] ?? 'fas fa-tag'; ?>"></i>
                            <?php echo htmlspecialchars($fund['category_name']); ?>
                        </span>
                        <span class="fundraiser">
                            by <?php echo htmlspecialchars($fund['fundraiser_name']); ?>
                        </span>
                    </div>
                    
                    <h1 class="campaign-title"><?php echo htmlspecialchars($fund['title']); ?></h1>
                    
                    <!-- Progress Stats -->
                    <div class="progress-section">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        
                        <div class="progress-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo formatCurrency($fund['current_amount']); ?></div>
                                <div class="stat-label">raised of <?php echo formatCurrency($fund['goal_amount']); ?></div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo $fund['backer_count']; ?></div>
                                <div class="stat-label">backers</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo $days_left; ?></div>
                                <div class="stat-label">days left</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if ($userRole === 'guest'): ?>
                            <a href="../../login/view/index.php" class="btn btn-primary">
                                <i class="fas fa-hand-holding-usd"></i>
                                Login to donate
                            </a>
                        <?php elseif ($userRole === 'admin'): ?>
                            <button class="btn <?php echo $fund['featured'] ? 'btn-outline' : 'btn-primary'; ?>" onclick="toggleFeature(<?php echo $fund['id']; ?>)" id="feature-btn">
                                <i class="fas fa-star"></i>
                                <?php echo $fund['featured'] ? 'Unfeature' : 'Mark as Featured'; ?>
                            </button>
                            <button class="btn btn-danger" onclick="toggleFreeze(<?php echo $fund['id']; ?>)" id="freeze-btn">
                                <i class="fas fa-pause"></i>
                                <?php echo $fund['status'] === 'frozen' ? 'Unfreeze' : 'Freeze'; ?>
                            </button>
                        <?php elseif ($userRole === 'backer' || ($userRole === 'fundraiser' && $fund['fundraiser_id'] != $user['id'])): ?>
                            <button class="btn btn-primary" onclick="openDonateModal()">
                                <i class="fas fa-hand-holding-usd"></i>
                                Donate
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($userRole === 'fundraiser' && $fund['fundraiser_id'] == $user['id']): ?>
                            <a href="../../fundraiser/view/edit_fund.php?id=<?php echo $fund['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i>
                                Edit Campaign
                            </a>
                            <a href="../fundraiser/view/analytics.php?id=<?php echo $fund['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-chart-bar"></i>
                                Analytics
                            </a>
                        <?php endif; ?>
                        
            <button class="btn btn-outline like-btn <?php echo $userHasLiked ? 'liked' : ''; ?>"
                onclick="toggleLike(<?php echo $fund['id']; ?>)"
                id="like-btn" <?php echo !$user ? 'disabled title="Login required"' : ''; ?>>
                            <i class="fas fa-heart"></i>
                            <span id="like-text"><?php echo $userHasLiked ? 'Liked' : 'Like'; ?></span>
                            <span id="likes-count">(<?php echo $likesCount; ?>)</span>
                        </button>
                        
                        <button class="btn btn-outline" onclick="shareCampaign()">
                            <i class="fas fa-share"></i>
                            Share
                        </button>
                        <?php if ($userRole != 'admin'): ?>
                        <button class="btn btn-outline" onclick="openReportModal()" <?php echo !$user ? 'disabled title="Login required"' : ''; ?>>
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
                <?php if ($user): ?>
                <div class="comment-form-section">
                    <form class="comment-form" onsubmit="submitComment(event)">
                        <div class="comment-input-container">
                            <div class="user-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <textarea id="comment-text" placeholder="Add a comment..." required maxlength="1000" rows="1" oninput="autoResize(this)"></textarea>
                            <button type="submit" class="submit-comment-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <?php elseif ($commentsCount > 0): ?>
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
                                                <i class="fas fa-user-circle"></i>
                                            </div>
                                            <div class="user-info">
                                                <span class="username">
                                                    <?php echo htmlspecialchars($comment['user_name']); ?>
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
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="backer-details">
                                        <div class="backer-name">
                                            <?php echo $donation['anonymous'] ? 'Anonymous' : htmlspecialchars($donation['backer_name']); ?>
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
    <div id="donate-modal" class="modal" style="display:none; position:fixed; inset:0; background: rgba(0,0,0,0.5); align-items:center; justify-content:center;">
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

    <script>
        // Current user ID for comment ownership checks
        const currentUserId = <?php echo $user ? $user['id'] : 'null'; ?>;
        const fundId = <?php echo (int)$fund['id']; ?>;
        let reportCommentId = null;
        
        // Auto-resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        // Like functionality
        function toggleLike(fundId) {
            <?php if (!$user): ?>
                showNotification('Please login to like campaigns', 'error');
                return;
            <?php endif; ?>
            
            const likeBtn = document.getElementById('like-btn');
            const likeText = document.getElementById('like-text');
            const likesCount = document.getElementById('likes-count');
            
            likeBtn.disabled = true;
            
            fetch('../ajax/toggle_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    fund_id: fundId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.liked) {
                        likeBtn.classList.add('liked');
                        likeText.textContent = 'Liked';
                    } else {
                        likeBtn.classList.remove('liked');
                        likeText.textContent = 'Like';
                    }
                    
                    likesCount.textContent = '(' + data.likes_count + ')';
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.error || 'Failed to update like', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                likeBtn.disabled = false;
            });
        }

        // Donate Modal handlers
        function openDonateModal() {
            const m = document.getElementById('donate-modal');
            m.style.display = 'flex';
        }
        function closeDonateModal() {
            const m = document.getElementById('donate-modal');
            m.style.display = 'none';
        }
        function submitDonation(e) {
            e.preventDefault();
            const amount = parseFloat(document.getElementById('donation-amount').value || '0');
            const comment = document.getElementById('donation-comment').value.trim();
            const anonymous = document.getElementById('donation-anonymous').checked ? 1 : 0;
            if (!(amount > 0)) { showNotification('Enter a valid amount', 'error'); return; }
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing';
            fetch('../ajax/donate.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fund_id: fundId, amount, comment, anonymous })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    showNotification('Thank you for your donation!', 'success');
                    // Update progress stats quickly
                    try {
                        const stats = document.querySelectorAll('.progress-stats .stat .stat-value');
                        if (stats && stats[0]) { stats[0].textContent = formatCurrency(data.current_amount); }
                        if (stats && stats[1] && typeof data.backer_count !== 'undefined') { stats[1].textContent = data.backer_count; }
                    } catch (_) {}

                    // Update Recent Backers list without refresh
                    try {
                        ensureBackersSection();
                        prependBackerItem(data.donation);
                    } catch (_) {}
                    closeDonateModal();
                } else {
                    showNotification(data.error || 'Donation failed', 'error');
                }
            }).catch(() => showNotification('Network error', 'error'))
              .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Donation'; });
        }

        function formatCurrency(amount) { try { return '$' + Number(amount).toLocaleString(); } catch { return '$' + amount; } }

        function ensureBackersSection() {
            let section = document.querySelector('.backers-section');
            if (!section) {
                const container = document.querySelector('.campaign-content');
                const div = document.createElement('div');
                div.className = 'backers-section';
                div.innerHTML = '<h3>Recent backers</h3><div class="backers-list" id="backers-list"></div>';
                container.appendChild(div);
            } else {
                let listById = document.getElementById('backers-list');
                if (!listById) {
                    const existing = section.querySelector('.backers-list');
                    if (existing) {
                        existing.id = 'backers-list';
                    } else {
                        const list = document.createElement('div');
                        list.id = 'backers-list';
                        list.className = 'backers-list';
                        section.appendChild(list);
                    }
                }
            }
        }

        function prependBackerItem(d) {
            const list = document.getElementById('backers-list');
            if (!list) return;
            const item = document.createElement('div');
            item.className = 'backer-item';
            item.innerHTML = `
                <div class="backer-info">
                    <div class="backer-avatar"><i class="fas fa-user-circle"></i></div>
                    <div class="backer-details">
                        <div class="backer-name">${d.anonymous ? 'Anonymous' : escapeHtml(d.backer_name)}</div>
                        <div class="backer-time js-timeago" data-time="${new Date().toISOString().slice(0,19).replace('T',' ')}">just now</div>
                    </div>
                </div>
                <div class="backer-amount">${formatCurrency(d.amount)}</div>
            `;
            list.insertAdjacentElement('afterbegin', item);
        }

        function escapeHtml(s){
            const div = document.createElement('div');
            div.textContent = s || '';
            return div.innerHTML;
        }

        // Report Modal handlers
        function openReportModal() {
            reportCommentId = null;
            const m = document.getElementById('report-modal');
            m.style.display = 'flex';
        }
        function openCommentReport(commentId) {
            reportCommentId = commentId;
            const m = document.getElementById('report-modal');
            m.style.display = 'flex';
        }
        function closeReportModal() {
            const m = document.getElementById('report-modal');
            m.style.display = 'none';
        }
        function submitReport(e) {
            e.preventDefault();
            const reason = document.getElementById('report-reason').value;
            const description = document.getElementById('report-description').value.trim();
            if (!reason) { showNotification('Select a reason', 'error'); return; }
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting';
            fetch('../ajax/report.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fund_id: reportCommentId ? null : fundId, comment_id: reportCommentId ? reportCommentId : null, reason, description })
            }).then(r => r.json()).then(data => {
                if (data.success) { showNotification('Report submitted', 'success'); closeReportModal(); }
                else { showNotification(data.error || 'Failed to submit report', 'error'); }
            }).catch(() => showNotification('Network error', 'error'))
              .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-flag"></i> Submit Report'; });
        }

        // Comment functionality
        function submitComment(event) {
            event.preventDefault();
            
            <?php if (!$user): ?>
                showNotification('Please login to comment', 'error');
                return;
            <?php endif; ?>
            
            const commentText = document.getElementById('comment-text').value.trim();
            if (!commentText) {
                showNotification('Please enter a comment', 'error');
                return;
            }
            
            const submitBtn = event.target.querySelector('.submit-comment-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('../ajax/add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    fund_id: <?php echo $fund['id']; ?>,
                    comment: commentText
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response data:', data);  // Debug log
                if (data.success) {
                    addCommentToList(data.comment);
                    document.getElementById('comment-text').value = '';
                    document.getElementById('comment-text').style.height = 'auto';
                    showNotification('Comment added successfully!', 'success');
                } else {
                    showNotification(data.error || 'Failed to add comment', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            });
        }

        function addCommentToList(comment) {
            console.log('Adding comment:', comment);  // Debug log
            let commentsList = document.getElementById('comments-list');
            
            // Create comments section if it doesn't exist
            if (!commentsList) {
                const commentsSection = document.createElement('div');
                commentsSection.className = 'comments-section';
                commentsSection.innerHTML = `
                    <div class="comments-header">
                        <h3>Comments (1)</h3>
                    </div>
                    <div class="comments-list" id="comments-list"></div>
                `;
                
                const commentFormSection = document.querySelector('.comment-form-section');
                commentFormSection.parentNode.insertBefore(commentsSection, commentFormSection.nextSibling);
                commentsList = document.getElementById('comments-list');
            }
            
            const commentHtml = `
                <div class="comment-item" data-comment-id="${comment.id}">
                    <div class="comment-content">
                        <div class="comment-header">
                            <div class="comment-user">
                                <div class="user-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="user-info">
                                    <span class="username">
                                        ${comment.user_name}
                                        ${comment.user_role === 'fundraiser' ? '<span class="role-badge fundraiser">Creator</span>' : ''}
                                        ${comment.user_role === 'backer' ? '<span class="role-badge backer">Backer</span>' : ''}
                                    </span>
                                    <span class="comment-time js-timeago" data-time="${comment.created_at}">just now</span>
                                </div>
                            </div>
                            ${currentUserId == comment.user_id ? `
                            <div class="comment-actions">
                                <button class="comment-action-btn" onclick="editComment(${comment.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="comment-action-btn delete" onclick="deleteComment(${comment.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            ` : ''}
                        </div>
                        <div class="comment-text" id="comment-content-${comment.id}">
                            ${comment.comment.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                </div>
            `;
            
            commentsList.insertAdjacentHTML('afterbegin', commentHtml);
            
            // Update comment count if available
            const currentCount = commentsList.children.length;
            updateCommentCount(currentCount);
        }

        function updateCommentCount(count) {
            const header = document.querySelector('.comments-header h3');
            if (header) {
                header.textContent = `Comments (${count})`;
            }
        }

        function deleteComment(commentId) {
            if (!confirm('Are you sure you want to delete this comment?')) {
                return;
            }
            
            fetch('../ajax/delete_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    fund_id: <?php echo $fund['id']; ?>,
                    comment_id: commentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                    if (commentElement) {
                        commentElement.remove();
                    }
                    showNotification('Comment deleted successfully!', 'success');
                } else {
                    showNotification(data.error || 'Failed to delete comment', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            });
        }

        function editComment(commentId) {
            const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
            const contentElement = document.getElementById(`comment-content-${commentId}`);
            
            if (!commentElement || !contentElement) return;
            
            const currentText = contentElement.textContent.trim();
            
            const editForm = `
                <div class="edit-comment-form">
                    <textarea class="edit-comment-text" maxlength="1000">${currentText}</textarea>
                    <div class="edit-actions">
                        <button type="button" class="btn btn-outline btn-sm" onclick="cancelEdit(${commentId})">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="saveEdit(${commentId})">Save</button>
                    </div>
                </div>
            `;
            
            contentElement.innerHTML = editForm;
            contentElement.querySelector('.edit-comment-text').focus();
        }

        function cancelEdit(commentId) {
            location.reload();
        }

        function saveEdit(commentId) {
            const contentElement = document.getElementById(`comment-content-${commentId}`);
            const textarea = contentElement.querySelector('.edit-comment-text');
            const newText = textarea.value.trim();
            
            if (!newText) {
                showNotification('Comment cannot be empty', 'error');
                return;
            }
            
            fetch('../ajax/edit_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    fund_id: <?php echo $fund['id']; ?>,
                    comment_id: commentId,
                    comment: newText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contentElement.innerHTML = data.comment.replace(/\n/g, '<br>');
                    showNotification('Comment updated successfully!', 'success');
                } else {
                    showNotification(data.error || 'Failed to update comment', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            });
        }

        function shareCampaign() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($fund['title']); ?>',
                    text: 'Check out this amazing campaign!',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showNotification('Campaign link copied to clipboard!', 'success');
                });
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.add('show'), 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }

        // Live time-ago updates for comments and backers
        let timeUpdateInterval;
        
        function timeAgoFrom(dateStr) {
            if (!dateStr) return 'just now';
            
            // Handle both MySQL datetime format and ISO
            let d;
            if (dateStr.includes('T')) {
                d = new Date(dateStr);
            } else {
                // MySQL format: YYYY-MM-DD HH:MM:SS
                d = new Date(dateStr.replace(' ', 'T'));
            }
            
            const now = new Date();
            const diff = Math.floor((now - d) / 1000);
            
            if (isNaN(diff) || diff < 0) return 'just now';
            if (diff < 60) return 'just now';
            
            const mins = Math.floor(diff / 60);
            if (mins < 60) return mins + ' minute' + (mins > 1 ? 's' : '') + ' ago';
            
            const hrs = Math.floor(mins / 60);
            if (hrs < 24) return hrs + ' hour' + (hrs > 1 ? 's' : '') + ' ago';
            
            const days = Math.floor(hrs / 24);
            if (days < 30) return days + ' day' + (days > 1 ? 's' : '') + ' ago';
            
            const months = Math.floor(days / 30);
            if (months < 12) return months + ' month' + (months > 1 ? 's' : '') + ' ago';
            
            const years = Math.floor(months / 12);
            return years + ' year' + (years > 1 ? 's' : '') + ' ago';
        }

        function refreshTimeagos() {
            const elements = document.querySelectorAll('.js-timeago[data-time]');
            elements.forEach(el => {
                const timeStr = el.getAttribute('data-time');
                if (timeStr) {
                    const newText = timeAgoFrom(timeStr);
                    if (el.textContent !== newText) {
                        el.textContent = newText;
                    }
                }
            });
        }

        function startTimeUpdates() {
            // Clear any existing interval
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
            }
            
            // Initial run
            refreshTimeagos();
            
            // Update every 30 seconds for more responsiveness
            timeUpdateInterval = setInterval(refreshTimeagos, 30 * 1000);
        }

        function stopTimeUpdates() {
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
                timeUpdateInterval = null;
            }
        }

        // Start time updates when page loads
        document.addEventListener('DOMContentLoaded', startTimeUpdates);
        
        // Clean up interval on page unload
        window.addEventListener('beforeunload', stopTimeUpdates);
        window.addEventListener('pagehide', stopTimeUpdates);
        
        // Also start immediately if DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startTimeUpdates);
        } else {
            startTimeUpdates();
        }

        // Admin functions
        function toggleFeature(fundId) {
            fetch('../admin/ajax/toggle_feature.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `fund_id=${fundId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('feature-btn');
                    if (data.featured) {
                        btn.className = 'btn btn-outline';
                        btn.innerHTML = '<i class="fas fa-star"></i> Unfeature';
                    } else {
                        btn.className = 'btn btn-primary';
                        btn.innerHTML = '<i class="fas fa-star"></i> Mark as Featured';
                    }
                    showNotification(data.message, 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }

        function toggleFreeze(fundId) {
            const btn = document.getElementById('freeze-btn');
            const action = btn.innerHTML.includes('Freeze') ? 'freeze' : 'unfreeze';
            
            if (!confirm(`Are you sure you want to ${action} this campaign?`)) return;
            
            fetch('../admin/ajax/toggle_freeze.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `fund_id=${fundId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'frozen') {
                        btn.innerHTML = '<i class="fas fa-play"></i> Unfreeze';
                    } else {
                        btn.innerHTML = '<i class="fas fa-pause"></i> Freeze';
                    }
                    showNotification(data.message, 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startTimeUpdates);
        } else {
            startTimeUpdates();
        }
    </script>
</body>
</html>
