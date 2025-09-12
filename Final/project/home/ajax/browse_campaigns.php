<?php
session_start();
require_once '../../config/database.php';
require_once '../../shared/includes/functions.php';

header('Content-Type: application/json');

try {
    // Initialize fund manager
    $fundManager = new FundManager();
    
    // Get parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'active';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 6;
    
    // Modify category parameters for getAllFunds method
    if ($category !== '' && is_numeric($category)) {
        $categoryParam = (int)$category;
    } else {
        $categoryParam = null;
    }
    
    // Build proper WHERE conditions for database filtering
    $excludeFeatured = ($sort !== 'featured');
    
    // Get total count first for pagination calculation
    $totalFunds = $fundManager->getTotalFundsCount($categoryParam, $search, $excludeFeatured, $status);
    $totalPages = ceil($totalFunds / $limit);
    
    // Get only the records needed for this page from database
    $campaigns = $fundManager->getAllFunds($page, $limit, $categoryParam, $search, $sort, $excludeFeatured, $status);
    
    // Get user likes for all funds if user is logged in
    $userLikedFunds = [];
    if (isset($_SESSION['user_id']) && !empty($campaigns)) {
        $fundIds = array_column($campaigns, 'id');
        $userLikedFunds = $fundManager->getUserLikesForFunds($fundIds, $_SESSION['user_id']);
    }
    
    // Generate campaign cards HTML
    $campaignsHtml = '';
    if (empty($campaigns)) {
        $campaignsHtml = "
        <div class='no-campaigns'>
            <i class='fas fa-search'></i>
            <h3>No campaigns found</h3>
            <p>Try adjusting your search or filter criteria.</p>
        </div>";
    } else {
        foreach ($campaigns as $campaign) {
            $progress = $campaign['goal_amount'] > 0 ?
                min(100, ($campaign['current_amount'] / $campaign['goal_amount']) * 100) : 0;
            $daysLeft = max(0, $campaign['days_left'] ?? 0);
            
            $campaignsHtml .= "
            <div class='campaign-card'>";

            if ($campaign['status'] === 'frozen') {
                $campaignsHtml .= "
                <div class='status-badge status-frozen'>
                    <i class='fas fa-pause'></i> Frozen
                </div>";
            }
            
            if ($campaign['featured']) {
                $campaignsHtml .= "
                <div class='status-badge status-featured'>
                    <i class='fas fa-star'></i> Featured
                </div>";
            }
            
            $campaignsHtml .= "
                <div class='campaign-header'>
                    <div>
                        <a href='../../campaign/view?id=" . $campaign['id'] . "' class='campaign-title'>" . htmlspecialchars($campaign['title']) . "</a>
                        <span class='status-badge no-pad category' style='color: " . (isset($campaign['category_color']) ? $campaign['category_color'] : '#000') . ";'>
                            <i class='" . (isset($campaign['category_icon']) ? $campaign['category_icon'] : 'fas fa-tag') . "'></i>
                            " . htmlspecialchars($campaign['category_name']) . "
                        </span>
                        </div>
                    <div class='by'>
                        by <a href='../../profile/view/index.php?id=" . $campaign['fundraiser_id'] . "'>" . htmlspecialchars($campaign['fundraiser_name']) . "</a>
                    </div>
                </div>
                
                <div class='campaign-description'>" . htmlspecialchars(substr($campaign['description'], 0, 150)) . "...</div>
                
                <div class='campaign-stats'>
                    <div class='stat-box raised'>
                        <div class='stat-value'>$" . number_format($campaign['current_amount'], 0) . "</div>
                        <div class='stat-label'>Raised</div>
                    </div>
                    <div class='stat-box backers'>
                        <div class='stat-value'>" . (int)$campaign['backer_count'] . "</div>
                        <div class='stat-label'>Backers</div>
                    </div>
                    <div class='stat-box days-left'>
                        <div class='stat-value'>" . $daysLeft . "</div>
                        <div class='stat-label'>Days Left</div>
                    </div>
                </div>
                
                <div class='progress-bar'>
                    <div class='progress-fill' style='width: " . $progress . "%'></div>
                </div>
                <div class='progress-text'>
                    " . number_format($progress, 1) . "% of $" . number_format($campaign['goal_amount'], 0) . " goal
                </div>
                
                <div class='engagement-stats'>
                    <div class='engagement-item like-stat'>
                        <button class='engagement-btn like-btn " . (in_array($campaign['id'], $userLikedFunds) ? 'liked' : '') . "' 
                                onclick='toggleLike(" . $campaign['id'] . ", this)'
                                " . (!isset($_SESSION['user_id']) ? 'disabled title=\"Login to like\"' : '') . ">
                            <i class='" . (in_array($campaign['id'], $userLikedFunds) ? 'fas' : 'far') . " fa-heart'></i>
                            <span class='count'>" . (int)$campaign['likes_count'] . "</span>
                        </button>
                    </div>
                    <div class='engagement-item comment-stat'>
                        <div class='engagement-btn comment-btn'>
                            <i class='far fa-comment'></i>
                            <span class='count'>" . (int)$campaign['comments_count'] . "</span>
                        </div>
                    </div>
                </div>
            </div>";
        }
    }
    
    // Generate pagination HTML
    $paginationHtml = '';
    if ($totalPages > 1) {
        $paginationHtml = "<div class='pagination'>";
        
        // Previous button
        if ($page > 1) {
            $paginationHtml .= "<a href='#' class='btn btn-outline page-btn' data-page='" . ($page - 1) . "'><i class='fas fa-chevron-left'></i> Previous</a>";
        }
        
        // Page numbers
        $paginationHtml .= "<div class='page-numbers'>";
        
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        if ($start > 1) {
            $paginationHtml .= "<a href='#' class='page-number' data-page='1'>1</a>";
            if ($start > 2) {
                $paginationHtml .= "<span>...</span>";
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            $active = $i == $page ? 'active' : '';
            if ($i == $page) {
                $paginationHtml .= "<span class='page-number active'>$i</span>";
            } else {
                $paginationHtml .= "<a href='#' class='page-number' data-page='$i'>$i</a>";
            }
        }
        
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $paginationHtml .= "<span>...</span>";
            }
            $paginationHtml .= "<a href='#' class='page-number' data-page='$totalPages'>$totalPages</a>";
        }
        
        $paginationHtml .= "</div>";
        
        // Next button
        if ($page < $totalPages) {
            $paginationHtml .= "<a href='#' class='btn btn-outline page-btn' data-page='" . ($page + 1) . "'>Next <i class='fas fa-chevron-right'></i></a>";
        }
        
        $paginationHtml .= "</div>";
        
        // Pagination info
        $paginationHtml .= "<div class='pagination-info'>Page $page of $totalPages ($totalFunds total campaigns)</div>";
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'campaignsHtml' => $campaignsHtml,
        'paginationHtml' => $paginationHtml,
        'totalFunds' => $totalFunds,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'resultsCount' => count($campaigns),
        'searchInfo' => [
            'searchTerm' => $search,
            'searchFields' => !empty($search) ? 'title, description, fundraiser_name, category_name' : 'N/A',
            'categoryFilter' => $category > 0 ? $category : 'All Categories',
            'sortBy' => $sort
        ],
        'debug' => [
            'category' => $category,
            'categoryParam' => $categoryParam,
            'excludeFeatured' => $excludeFeatured,
            'totalFunds' => $totalFunds,
            'campaignsFound' => count($campaigns),
            'queryParams' => [
                'page' => $page,
                'limit' => $limit,
                'category' => $categoryParam,
                'search' => $search,
                'sort' => $sort,
                'excludeFeatured' => $excludeFeatured
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load campaigns: ' . $e->getMessage()
    ]);
}
