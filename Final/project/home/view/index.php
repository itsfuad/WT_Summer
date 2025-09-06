<?php
// Include database functions
require_once '../../includes/functions.php';
require_once '../../includes/session.php';

// Initialize managers
$fundManager = new FundManager();

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getCurrentUser() : null;

// Get parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'featured'; // featured, newest, oldest, most_funded, least_funded
$limit = 6;

// Get categories for filter dropdown
$categories = $fundManager->getCategories();

// Get all funds with pagination and filters
$excludeFeatured = ($sort !== 'featured');
$allFunds = $fundManager->getAllFunds($page, $limit, $category, $search, $sort, $excludeFeatured);

// Get user likes for all funds if user is logged in
$userLikedFunds = [];
if ($isLoggedIn && !empty($allFunds)) {
    $fundIds = array_column($allFunds, 'id');
    $userLikedFunds = $fundManager->getUserLikesForFunds($fundIds, $user['id']);
}

// Get total count for pagination
$totalFunds = $fundManager->getTotalFundsCount($category, $search, $excludeFeatured);
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
                <a href="../../includes/logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="../../login/view/index.php" class="btn btn-secondary">
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
                    <div class="search-help">
                        <small><i class="fas fa-info-circle"></i> Search across campaign titles, descriptions, fundraiser names, and categories</small>
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
                                <?php if ($isLoggedIn && $user['role'] === 'admin'): ?>
                                    <option value="frozen" <?php echo $category === 'frozen' ? 'selected' : ''; ?>>Frozen Campaigns</option>
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
                            <?php if ($search || $category || $sort !== 'newest'): ?>
                                <button type="button" id="clearFilters" class="btn btn-large btn-secondary">
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
                    <strong id="resultsCount"><?php echo count($allFunds); ?></strong> of <strong id="totalCount"><?php echo $totalFunds; ?></strong> campaigns
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
                            <?php if ($fund['featured']): ?>
                                <div class="featured-badge">
                                    <i class="fas fa-star"></i> Featured
                                </div>
                            <?php endif; ?>
                            <?php if ($fund['status'] === 'frozen'): ?>
                                <div class="frozen-badge">
                                    <i class="fas fa-pause"></i> Frozen
                                </div>
                            <?php endif; ?>
                            <div class="campaign-header">
                                <i class="campaign-icon <?php echo htmlspecialchars($fund['category_icon'] ?? 'fas fa-folder'); ?>"></i>
                                <div>
                                    <div class="campaign-title"><?php echo htmlspecialchars($fund['title']); ?></div>
                                    <div class="campaign-fundraiser">by <?php echo htmlspecialchars($fund['fundraiser_name']); ?></div>
                                </div>
                            </div>

                            <div class="campaign-description">
                                <?php echo htmlspecialchars($fund['short_description'] ?? substr($fund['description'], 0, 150) . '...'); ?>
                            </div>

                            <div class="campaign-stats">
                                <div class="stat-box">
                                    <div class="stat-value"><?php echo formatCurrency($fund['current_amount']); ?></div>
                                    <div class="stat-label">Raised</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-value"><?php echo $fund['backer_count']; ?></div>
                                    <div class="stat-label">Backers</div>
                                </div>
                                <div class="stat-box">
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

                            <div class="campaign-actions">
                                <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
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
                            <a href="#" class="btn btn-secondary page-btn" data-page="<?php echo $page - 1; ?>">
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
                            <a href="#" class="btn btn-secondary page-btn" data-page="<?php echo $page + 1; ?>">
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
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center;">
        <div style="text-align: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--gray-600); margin-bottom: 1rem;"></i>
            <p style="color: var(--gray-600); font-weight: 500;">Loading campaigns...</p>
        </div>
    </div>

    <script>
    // Like toggle functionality
    function toggleLike(fundId, buttonElement) {
        <?php if (!$isLoggedIn): ?>
            showNotification('Please login to like campaigns', 'error');
            return;
        <?php endif; ?>
        
        const wasLiked = buttonElement.classList.contains('liked');
        const heartIcon = buttonElement.querySelector('i');
        const countSpan = buttonElement.querySelector('.count');
        
        // Optimistic update
        buttonElement.disabled = true;
        if (wasLiked) {
            buttonElement.classList.remove('liked');
            heartIcon.className = 'far fa-heart';
            countSpan.textContent = Math.max(0, parseInt(countSpan.textContent) - 1);
        } else {
            buttonElement.classList.add('liked');
            heartIcon.className = 'fas fa-heart';
            countSpan.textContent = parseInt(countSpan.textContent) + 1;
        }
        
        fetch('../../shared/ajax/toggle_like.php', {
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
                // Update with actual count from server
                countSpan.textContent = data.likes_count;
                showNotification(data.message, 'success');
            } else {
                // Revert optimistic update
                if (wasLiked) {
                    buttonElement.classList.add('liked');
                    heartIcon.className = 'fas fa-heart';
                } else {
                    buttonElement.classList.remove('liked');
                    heartIcon.className = 'far fa-heart';
                }
                countSpan.textContent = Math.max(0, parseInt(countSpan.textContent) + (wasLiked ? 1 : -1));
                showNotification(data.error || 'Failed to update like', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revert optimistic update
            if (wasLiked) {
                buttonElement.classList.add('liked');
                heartIcon.className = 'fas fa-heart';
            } else {
                buttonElement.classList.remove('liked');
                heartIcon.className = 'far fa-heart';
            }
            countSpan.textContent = Math.max(0, parseInt(countSpan.textContent) + (wasLiked ? 1 : -1));
            showNotification('Network error occurred', 'error');
        })
        .finally(() => {
            buttonElement.disabled = false;
        });
    }

    // Notification system
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.style.transform = 'translateX(0)', 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const sortFilter = document.getElementById('sortFilter');
        const searchForm = document.getElementById('searchForm');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const campaignsGrid = document.getElementById('campaignsGrid');
        const paginationContainer = document.getElementById('paginationContainer');
        const resultsCount = document.getElementById('resultsCount');
        const totalCount = document.getElementById('totalCount');
        const pageInfo = document.getElementById('pageInfo');
        const loadingOverlay = document.getElementById('loadingOverlay');

        let currentPage = 1;

        // Initialize from URL parameters
        function initializeFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search') || '';
            const category = urlParams.get('category') || '';
            const sort = urlParams.get('sort') || 'featured';
            const page = parseInt(urlParams.get('page')) || 1;

            searchInput.value = search;
            categoryFilter.value = category;
            sortFilter.value = sort;
            currentPage = page;

            // Only load campaigns via AJAX if we have active filters
            // Otherwise, let the PHP-rendered page show (with featured section)
            if (search || category || sort !== 'featured' || page > 1) {
                loadCampaigns();
            }
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            if (event.state) {
                searchInput.value = event.state.search || '';
                categoryFilter.value = event.state.category || '';
                sortFilter.value = event.state.sort || 'featured';
                currentPage = event.state.page || 1;
                loadCampaigns();
            }
        });

        // Initialize on page load
        initializeFromURL();

        // Search form submission (Enter key or Search button)
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadCampaigns();
        });

        // Auto-filter on category/sort change
        categoryFilter.addEventListener('change', function() {
            currentPage = 1;
            loadCampaigns();
        });

        sortFilter.addEventListener('change', function() {
            currentPage = 1;
            loadCampaigns();
        });

        // Clear filters
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                // Redirect to clean URL to show featured section
                window.location.href = window.location.pathname;
            });
        }

        // Pagination click handler
        document.addEventListener('click', function(e) {
            if (e.target.matches('.page-number[data-page], .page-btn[data-page]')) {
                e.preventDefault();
                currentPage = parseInt(e.target.getAttribute('data-page'));
                loadCampaigns();
            }
        });

        function loadCampaigns() {
            const search = searchInput.value.trim();
            const category = categoryFilter.value;
            const sort = sortFilter.value;

            // Build clean params object (exclude empty values)
            const params = new URLSearchParams();
            if (search) params.set('search', search);
            if (category) params.set('category', category);
            if (sort && sort !== 'featured') params.set('sort', sort);
            if (currentPage > 1) params.set('page', currentPage);

            // Update URL - use clean URL if no params
            const newUrl = params.toString() ? 
                window.location.pathname + '?' + params.toString() : 
                window.location.pathname;
            window.history.pushState({search, category, sort, page: currentPage}, '', newUrl);

            // For AJAX call, include all params even if default
            const ajaxParams = new URLSearchParams({
                search: search,
                category: category,
                sort: sort,
                page: currentPage
            });

            // Show loading
            loadingOverlay.style.display = 'flex';

            fetch('../ajax/browse_campaigns.php?' + ajaxParams.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update campaigns grid
                        campaignsGrid.innerHTML = data.campaignsHtml;

                        // Update pagination
                        paginationContainer.innerHTML = data.paginationHtml;

                        // Update results summary
                        resultsCount.textContent = data.resultsCount;
                        totalCount.textContent = data.totalFunds;
                        
                        if (data.totalPages > 1) {
                            pageInfo.textContent = `Page ${data.currentPage} of ${data.totalPages}`;
                            pageInfo.style.display = 'inline';
                        } else {
                            pageInfo.style.display = 'none';
                        }

                        // Update clear button visibility
                        const hasFilters = search || category || sort !== 'featured';
                        if (clearFiltersBtn) {
                            clearFiltersBtn.style.display = hasFilters ? 'inline-flex' : 'none';
                        }

                        // Log search information for debugging
                        if (data.searchInfo) {
                            console.log('Search Info:', data.searchInfo);
                        }
                        
                        // Log debug information
                        if (data.debug) {
                            console.log('Debug Info:', data.debug);
                        }

                        // Scroll to top of results
                        document.querySelector('.browse-section').scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });

                    } else {
                        console.error('Error loading campaigns:', data.error);
                        campaignsGrid.innerHTML = `
                            <div class="no-campaigns">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Error Loading Campaigns</h3>
                                <p>${data.error}</p>
                                <button onclick="loadCampaigns()" class="btn btn-primary">
                                    <i class="fas fa-refresh"></i> Try Again
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Network error:', error);
                    campaignsGrid.innerHTML = `
                        <div class="no-campaigns">
                            <i class="fas fa-wifi"></i>
                            <h3>Connection Error</h3>
                            <p>Please check your internet connection and try again.</p>
                            <button onclick="loadCampaigns()" class="btn btn-primary">
                                <i class="fas fa-refresh"></i> Retry
                            </button>
                        </div>
                    `;
                })
                .finally(() => {
                    // Hide loading
                    loadingOverlay.style.display = 'none';
                });
        }

        // Make loadCampaigns available globally for error buttons
        window.loadCampaigns = loadCampaigns;
    });
    </script>
</body>
</html>
