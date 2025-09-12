<?php
// Include database functions
require_once '../../shared/includes/functions.php';
require_once '../../shared/includes/session.php';

// Initialize managers
$fundManager = new FundManager();

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getCurrentUser() : null;


$topCampaignCount = 5; // Number of top campaigns to show
$topBackerCount = 5;   // Number of top backers to show

$topCampaigns = $fundManager->getTopCampaigns($topCampaignCount);
$topBackers = $fundManager->getTopBackers($topBackerCount);

// Get parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? 'active'; // all, active, completed, paused, frozen
$sort = $_GET['sort'] ?? 'featured'; // featured, newest, oldest, most_funded, least_funded
$limit = 6;

// Get categories for filter dropdown
$categories = $fundManager->getCategories();

// Get all funds with pagination and filters
$excludeFeatured = ($sort !== 'featured');
$allFunds = $fundManager->getAllFunds($page, $limit, $category, $search, $sort, $excludeFeatured, $status);

// Get user likes for all funds if user is logged in
$userLikedFunds = [];
if ($isLoggedIn && !empty($allFunds)) {
    $fundIds = array_column($allFunds, 'id');
    $userLikedFunds = $fundManager->getUserLikesForFunds($fundIds, $user['id']);
}

// Get total count for pagination
$totalFunds = $fundManager->getTotalFundsCount($category, $search, $excludeFeatured, $status);
$totalPages = ceil($totalFunds / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrowdFund - Discover Amazing Campaigns</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/script.js"></script>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-hand-holding-usd"></i> CrowdFund</h1>
        <p>Discover amazing projects and help bring them to life</p>
        <div class="header-actions">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-text">
                    <i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($user['name']); ?>!
                </span>
                <a href="../../<?php echo $user['role']; ?>/view/index.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            <?php else: ?>
                <a href="../../login/view/index.php" class="btn btn-outline">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="../../signup/view/index.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <?php if (!$isLoggedIn): ?>
            <div class="guest-notice">
                <i class="fas fa-info-circle"></i>
                Want to support these amazing campaigns? <a href="../../signup/view/index.php">Create an account</a> to back projects and access more features.
            </div>
        <?php endif; ?>

        <div class="feed">
            <!-- Browse All Campaigns Section -->
            <div class="browse-section">
                <div class="section-header">
                    <h2><i class="fas fa-search"></i> Browse All Campaigns</h2>
                    <p>Explore <?php echo $totalFunds; ?> active campaigns</p>
                </div>
    
                <!-- Filters and Search -->
                <div class="filters-container">
                    <form id="searchForm" class="filters-form">
                        <div class="search-bar">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    id="searchInput"
                                    name="search" 
                                    placeholder="Search by title, description, fundraiser, or category..." 
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    class="search-input"
                                    title="Search in: Campaign title, description, fundraiser name, and category"
                                >
                            </div>
                        </div>
    
                        <div class="filter-row">
                            <div class="filter-item">
                                <select id="categoryFilter" name="category" class="filter-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <select id="statusFilter" name="status" class="filter-select">
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="paused" <?php echo $status === 'paused' ? 'selected' : ''; ?>>Paused</option>
                                    <?php if ($isLoggedIn && $user['role'] === 'admin'): ?>
                                        <option value="frozen" <?php echo $status === 'frozen' ? 'selected' : ''; ?>>Frozen</option>
                                    <?php endif; ?>
                                </select>
                            </div>
    
                            <div class="filter-item">
                                <select id="sortFilter" name="sort" class="filter-select">
                                    <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                                    <option value="most_funded" <?php echo $sort === 'most_funded' ? 'selected' : ''; ?>>Most Funded</option>
                                    <option value="least_funded" <?php echo $sort === 'least_funded' ? 'selected' : ''; ?>>Least Funded</option>
                                </select>
                            </div>
    
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary btn-large">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if ($search || $category || $status !== 'active' || $sort !== 'featured'): ?>
                                    <button type="button" id="clearFilters" class="btn btn-large btn-outline">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
    
                <!-- Results Summary -->
                <div class="results-summary">
                    <span class="results-count">
                        <strong id="resultsCount">Showing <?php echo count($allFunds); ?></strong> of <strong id="totalCount"><?php echo $totalFunds; ?></strong> campaigns
                    </span>
                    <span class="page-info" id="pageInfo">
                        <?php if ($totalPages > 1): ?>
                            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                        <?php endif; ?>
                    </span>
                </div>
    
                <!-- Campaigns Grid -->
                <div id="campaignsGrid" class="campaigns-grid">
                    <?php if (empty($allFunds)): ?>
                        <div class="no-campaigns">
                            <i class="fas fa-search"></i>
                            <h3>No campaigns found</h3>
                            <p>Try adjusting your search or filter criteria.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allFunds as $fund): ?>
                            <?php 
                            $percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
                            $days_left = getDaysLeft($fund['end_date']);
                            ?>
                            <div class="campaign-card">
                                <?php if ($fund['status'] === 'frozen'): ?>
                                    <div class="status-badge status-frozen">
                                        <i class="fas fa-pause"></i> Frozen
                                    </div>
                                <?php endif; ?>
                                <?php if ($fund['featured']): ?>
                                    <div class="status-badge status-featured">
                                        <i class="fas fa-star"></i> Featured
                                    </div>
                                <?php endif; ?>
                                <div class="campaign-header">
                                    <div>
                                        <a href="../../campaign/view?id=<?php echo $fund['id']; ?>" class="campaign-title"><?php echo htmlspecialchars($fund['title']); ?></a>
                                        <span class="status-badge no-pad category" style="color: <?php echo $fund['category_color'] ?? '#000'; ?>;">
                                            <i class="<?php echo $fund['category_icon'] ?? 'fas fa-tag'; ?>"></i>
                                            <?php echo htmlspecialchars($fund['category_name']); ?>
                                        </span>
                                    </div>
                                    <div class="by">
                                       by <a href="../../profile/view/index.php?id=<?php echo $fund['fundraiser_id']; ?>"><?php echo htmlspecialchars($fund['fundraiser_name']); ?></a>
                                    </div>
                                </div>
    
                                <div class="campaign-description">
                                    <?php echo htmlspecialchars($fund['short_description'] ?? substr($fund['description'], 0, 150) . '...'); ?>
                                </div>
    
                                <div class="campaign-stats">
                                    <div class="stat-box raised">
                                        <div class="stat-value"><?php echo formatCurrency($fund['current_amount']); ?></div>
                                        <div class="stat-label">Raised</div>
                                    </div>
                                    <div class="stat-box backers">
                                        <div class="stat-value"><?php echo $fund['backer_count']; ?></div>
                                        <div class="stat-label">Backers</div>
                                    </div>
                                    <div class="stat-box days-left">
                                        <div class="stat-value"><?php echo $days_left; ?></div>
                                        <div class="stat-label">Days Left</div>
                                    </div>
                                </div>
    
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    <?php echo $percentage; ?>% of <?php echo formatCurrency($fund['goal_amount']); ?> goal
                                </div>
    
                                <!-- Engagement Stats -->
                                <div class="engagement-stats">
                                    <div class="engagement-item like-stat">
                                        <button class="engagement-btn like-btn <?php echo in_array($fund['id'], $userLikedFunds) ? 'liked' : ''; ?>" 
                                                onclick="toggleLike(<?php echo $fund['id']; ?>, this)"
                                                <?php echo !$isLoggedIn ? 'disabled title="Login to like"' : ''; ?>>
                                            <i class="<?php echo in_array($fund['id'], $userLikedFunds) ? 'fas' : 'far'; ?> fa-heart"></i>
                                            <span class="count"><?php echo $fund['likes_count']; ?></span>
                                        </button>
                                    </div>
                                    <div class="engagement-item comment-stat">
                                        <div class="engagement-btn comment-btn">
                                            <i class="far fa-comment"></i>
                                            <span class="count"><?php echo $fund['comments_count']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
    
                <!-- Pagination -->
                <div id="paginationContainer">
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="#" class="btn btn-outline page-btn" data-page="<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
    
                            <div class="page-numbers">
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                ?>
                                
                                <?php if ($start > 1): ?>
                                    <a href="#" class="page-number" data-page="1">1</a>
                                    <?php if ($start > 2): ?><span>...</span><?php endif; ?>
                                <?php endif; ?>
    
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="page-number active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="#" class="page-number" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
    
                                <?php if ($end < $totalPages): ?>
                                    <?php if ($end < $totalPages - 1): ?><span>...</span><?php endif; ?>
                                    <a href="#" class="page-number" data-page="<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                            </div>
    
                            <?php if ($page < $totalPages): ?>
                                <a href="#" class="btn btn-outline page-btn" data-page="<?php echo $page + 1; ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
    
                        <div class="pagination-info">
                            Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                            (<?php echo $totalFunds; ?> total campaigns)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Top Performers -->
            <div class="charts-cols">
                <div class="chart-card">
                    <h3><i class="fas fa-trophy"></i> Top <?php echo $topCampaignCount; ?> Campaigns</h3>
                    <?php if (empty($topCampaigns)): ?>
                        <div class="no-data">
                            <i class="fas fa-chart-bar"></i>
                            <p>No campaigns found</p>
                        </div>
                    <?php else: ?>
                        <div class="top-campaigns-list">
                            <?php foreach ($topCampaigns as $index => $campaign): ?>
                                <div class="campaign-item border-middle">
                                    <div class="campaign-rank">
                                        <span class="rank-number"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="campaign-info">
                                        <h4><a href="../../campaign/view?id=<?php echo $campaign['id']; ?>" target="_blank"><?php echo htmlspecialchars($campaign['title']); ?></a></h4>
                                        <p class="top-campaign-meta">
                                            <span class="by" style="font-size: 0.7rem;">by <a href="../../profile/view?id=<?php echo $campaign['fundraiser_id']; ?>"><?php echo htmlspecialchars($campaign['fundraiser_name']); ?></a></span>
                                        </p>
                                        <div class="campaign-stats">
                                            <span class="raised"><?php echo formatCurrency($campaign['current_amount']); ?></span>
                                            <!-- color1 for < 50%, color2 for >= 50%, color3 for >100% --> 
                                            <span class="progress" style="<?php echo "color: " . (($campaign['progress_percentage'] >= 100 ? 'var(--success)' : ($campaign['progress_percentage'] >= 50 ? 'var(--warning)' : 'var(--error)'))) ?>"><?php echo number_format($campaign['progress_percentage'], 1); ?>% funded</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chart-card stick">
                    <h3><i class="fas fa-star"></i> Top <?php echo $topBackerCount; ?> Backers</h3>
                    <?php if (empty($topBackers)): ?>
                        <div class="no-data">
                            <i class="fas fa-users"></i>
                            <p>No backers found</p>
                        </div>
                    <?php else: ?>
                        <div class="top-backers-list">
                            <?php foreach ($topBackers as $index => $backer): ?>
                                <div class="backer-item border-middle">
                                    <div class="backer-rank">
                                        <span class="rank-number"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="backer-avatar">
                                        <img src="<?php echo $backer['profile_image_url'] ?: '../../images/default-profile.png'; ?>"
                                             alt="<?php echo htmlspecialchars($backer['name']); ?>" class="profile-img">
                                    </div>
                                    <div class="backer-info">
                                        <h4><a href="../../profile/view?id=<?php echo $backer['id']; ?>"><?php echo htmlspecialchars($backer['name']); ?></a></h4>
                                        <p class="backer-email"><?php echo htmlspecialchars($backer['email']); ?></p>
                                        <div class="backer-stats">
                                            <span class="total-donated"><?php echo formatCurrency($backer['total_donated']); ?></span>
                                            <span class="donations-count"><?php echo $backer['total_donations']; ?> donations</span>
                                            <span class="campaigns-supported"><?php echo $backer['campaigns_supported']; ?> campaigns</span>
                                        </div>
                                        <small class="last-donation">
                                            Last donation: <?php echo date('M j, Y', strtotime($backer['last_donation_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center;">
        <div style="text-align: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--gray-600); margin-bottom: 1rem;"></i>
            <p style="color: var(--gray-600); font-weight: 500;">Loading campaigns...</p>
        </div>
    </div>
</body>
</html>
