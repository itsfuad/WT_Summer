<?php
require_once __DIR__ . '/../../database/database.php';
require_once 'upload_manager.php';


class FundManager {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    // Getter method for PDO (needed for comment operations)
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Get featured/top funds for homepage
     */
    public function getFeaturedFunds($limit = 6) {
        // Ensure limit is an integer to prevent SQL injection
        $limit = (int)$limit;
        
        $sql = "SELECT 
                f.*,
                u.name as fundraiser_name,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                COUNT(d.id) as backer_count,
                DATEDIFF(f.end_date, CURDATE()) as days_left
            FROM funds f
            LEFT JOIN users u ON f.fundraiser_id = u.id
            LEFT JOIN categories c ON f.category_id = c.id
            LEFT JOIN donations d ON f.id = d.fund_id AND d.payment_status = 'completed'
            WHERE f.status = 'active' AND f.end_date >= CURDATE()
            GROUP BY f.id
            ORDER BY f.featured DESC, f.current_amount DESC, f.created_at DESC
            LIMIT $limit
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get all active funds with pagination
     */
    public function getAllFunds($page = 1, $limit = 12, $category = null, $search = null, $sort = 'featured', $excludeFeatured = false) {
        // Ensure numeric values are integers
        $page = (int)$page;
        $limit = (int)$limit;
        $offset = ($page - 1) * $limit;
        
        // Base conditions - show only frozen campaigns for 'frozen' category, otherwise only active
        if ($category === 'frozen') {
            $conditions = ["f.status = 'frozen'", "f.end_date >= CURDATE()"];
        } else {
            $conditions = ["f.status = 'active'", "f.end_date >= CURDATE()"];
        }
        $params = [];
        
        if ($category && $category !== 'frozen') {
            $conditions[] = "f.category_id = ?";
            $params[] = $category;
        }
        
        if ($search) {
            $conditions[] = "(f.title LIKE ? OR f.description LIKE ? OR u.name LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($excludeFeatured) {
            $conditions[] = "f.featured = 0";
        }
        
        // Handle sort order
        $orderBy = "";
        switch ($sort) {
            case 'featured':
                $orderBy = "f.featured DESC, f.current_amount DESC, f.created_at DESC";
                break;
            case 'newest':
                $orderBy = "f.created_at DESC";
                break;
            case 'oldest':
                $orderBy = "f.created_at ASC";
                break;
            case 'most_funded':
                $orderBy = "f.current_amount DESC";
                break;
            case 'least_funded':
                $orderBy = "f.current_amount ASC";
                break;
            default:
                $orderBy = "f.featured DESC, f.current_amount DESC, f.created_at DESC";
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT 
                f.*,
                u.name as fundraiser_name,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                COUNT(DISTINCT d.id) as backer_count,
                COUNT(DISTINCT l.id) as likes_count,
                COUNT(DISTINCT cm.id) as comments_count,
                DATEDIFF(f.end_date, CURDATE()) as days_left
            FROM funds f
            LEFT JOIN users u ON f.fundraiser_id = u.id
            LEFT JOIN categories c ON f.category_id = c.id
            LEFT JOIN donations d ON f.id = d.fund_id AND d.payment_status = 'completed'
            LEFT JOIN fund_likes l ON f.id = l.fund_id
            LEFT JOIN comments cm ON f.id = cm.fund_id
            WHERE $whereClause
            GROUP BY f.id, u.name, c.name, c.icon, c.color
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset
        ";
        
        // Debug: Log the query if category is frozen
        if ($category === 'frozen') {
            error_log("Frozen query: " . $sql);
            error_log("Frozen params: " . json_encode($params));
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $funds = $stmt->fetchAll();
        
        // Add cover image URLs to each fund
        foreach ($funds as &$fund) {
            $fund['image_url'] = $this->getFundCoverImage($fund['id']);
        }
        
        return $funds;
    }
    
    /**
     * Get total count of active funds
     */
    public function getTotalFundsCount($category = null, $search = null, $excludeFeatured = false) {
        // Base conditions - show only frozen campaigns for 'frozen' category, otherwise only active
        if ($category === 'frozen') {
            $conditions = ["f.status = 'frozen'", "f.end_date >= CURDATE()"];
        } else {
            $conditions = ["f.status = 'active'", "f.end_date >= CURDATE()"];
        }
        $params = [];
        
        if ($category && $category !== 'frozen') {
            $conditions[] = "f.category_id = ?";
            $params[] = $category;
        }
        
        if ($search) {
            $conditions[] = "(f.title LIKE ? OR f.description LIKE ? OR u.name LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($excludeFeatured) {
            $conditions[] = "f.featured = 0";
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) 
            FROM funds f
            LEFT JOIN users u ON f.fundraiser_id = u.id
            LEFT JOIN categories c ON f.category_id = c.id
            WHERE $whereClause
        ");
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get fund details by ID
     */
    public function getFundById($id) {
        $stmt = $this->pdo->prepare("SELECT 
                f.*,
                u.name as fundraiser_name,
                u.email as fundraiser_email,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                COUNT(d.id) as backer_count,
                DATEDIFF(f.end_date, CURDATE()) as days_left
            FROM funds f
            LEFT JOIN users u ON f.fundraiser_id = u.id
            LEFT JOIN categories c ON f.category_id = c.id
            LEFT JOIN donations d ON f.id = d.fund_id AND d.payment_status = 'completed'
            WHERE f.id = ?
            GROUP BY f.id
        ");
        
        $stmt->execute([$id]);
        $fund = $stmt->fetch();
        
        // Add cover image URL
        if ($fund) {
            $fund['image_url'] = $this->getFundCoverImage($fund['id']);
        }
        
        return $fund;
    }
    
    /**
     * Get all categories
     */
    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    }
    
    /**
     * Update fund views
     */
    public function incrementViews($fund_id) {
        $stmt = $this->pdo->prepare("UPDATE funds SET views_count = views_count + 1 WHERE id = ?");
        $stmt->execute([$fund_id]);
    }
    
    /**
     * Get funds by fundraiser ID
     */
    public function getFundsByFundraiserId($fundraiser_id) {
        $stmt = $this->pdo->prepare("SELECT 
                f.*,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                COUNT(d.id) as backer_count,
                DATEDIFF(f.end_date, CURDATE()) as days_left
            FROM funds f
            LEFT JOIN categories c ON f.category_id = c.id
            LEFT JOIN donations d ON f.id = d.fund_id AND d.payment_status = 'completed'
            WHERE f.fundraiser_id = ?
            GROUP BY f.id
            ORDER BY f.created_at DESC
        ");
        
        $stmt->execute([$fundraiser_id]);
        $funds = $stmt->fetchAll();
        
        // Add cover image URLs to each fund
        foreach ($funds as &$fund) {
            $fund['image_url'] = $this->getFundCoverImage($fund['id']);
        }
        
        return $funds;
    }
    
    /**
     * Create a new fund
     */
    public function createFund($data) {
        $stmt = $this->pdo->prepare("INSERT INTO funds (
                title, short_description, description, goal_amount, category_id, 
                fundraiser_id, end_date, featured, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            $data['title'],
            $data['short_description'],
            $data['description'],
            $data['goal_amount'],
            $data['category_id'],
            $data['fundraiser_id'],
            $data['end_date'],
            $data['featured'] ?? 0
        ]);
        
        return $result ? $this->pdo->lastInsertId() : false;
    }
    
    /**
     * Update an existing fund
     */
    public function updateFund($fund_id, $data) {
        // Get current fund status to prevent unauthorized status changes
        $currentStmt = $this->pdo->prepare("SELECT status FROM funds WHERE id = ?");
        $currentStmt->execute([$fund_id]);
        $currentStatus = $currentStmt->fetchColumn();
        
        // If fund is in admin-controlled status (frozen/removed), preserve it
        if (in_array($currentStatus, ['frozen', 'removed'])) {
            $data['status'] = $currentStatus;
        }
        
        $stmt = $this->pdo->prepare("UPDATE funds SET 
                title = ?, short_description = ?, description = ?, 
                goal_amount = ?, category_id = ?, end_date = ?, 
                status = ?, featured = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['title'],
            $data['short_description'],
            $data['description'],
            $data['goal_amount'],
            $data['category_id'],
            $data['end_date'],
            $data['status'],
            $data['featured'] ?? 0,
            $fund_id
        ]);
    }
    
    /**
     * Get fund donations with flexible sorting options
     */
    public function getFundDonations($fund_id, $sort = 'recent', $limit = 10) {
        $validSorts = ['recent', 'top', 'oldest'];
        if (!in_array($sort, $validSorts)) {
            $sort = 'recent';
        }
        
        $orderBy = match($sort) {
            'recent' => 'd.created_at DESC',
            'top' => 'd.amount DESC',
            'oldest' => 'd.created_at ASC'
        };
        
        $stmt = $this->pdo->prepare("SELECT d.*, u.name as backer_name, u.role as backer_role, u.profile_image
            FROM donations d
            LEFT JOIN users u ON d.backer_id = u.id
            WHERE d.fund_id = ? AND d.payment_status = 'completed'
            ORDER BY $orderBy
            LIMIT " . (int)$limit . "
        ");
        
        $stmt->execute([$fund_id]);
        $donations = $stmt->fetchAll();
        
        // Add profile image URLs
        $uploadManager = new UploadManager();
        foreach ($donations as &$donation) {
            $donation['profile_image_url'] = $uploadManager->getImageUrl('profile', $donation['profile_image']);
        }
        
        return $donations;
    }

    /**
     * Legacy function - use getFundDonations with sort='recent' instead
     * @deprecated
     */
    public function getRecentDonations($fund_id, $limit = 10) {
        return $this->getFundDonations($fund_id, 'recent', $limit);
    }

    /**
     * Legacy function - use getFundDonations with sort='top' instead  
     * @deprecated
     */
    public function getTopDonations($fund_id, $limit = 5) {
        return $this->getFundDonations($fund_id, 'top', $limit);
    }

    /**
     * Legacy function - use getFundDonations with sort='recent' instead
     * @deprecated
     */
    public function getRecentActivity($fund_id, $limit = 10) {
        return $this->getFundDonations($fund_id, 'recent', $limit);
    }
    
    /**
     * Get fund analytics data
     */
    public function getFundAnalytics($fund_id) {
        $stmt = $this->pdo->prepare("SELECT 
                COUNT(d.id) as total_donations,
                SUM(d.amount) as total_raised,
                AVG(d.amount) as avg_donation,
                COUNT(DISTINCT d.backer_id) as unique_backers
            FROM donations d
            WHERE d.fund_id = ? AND d.payment_status = 'completed'
        ");
        
        $stmt->execute([$fund_id]);
        return $stmt->fetch();
    }
    
    /**
     * Get daily donation data for charts
     */
    public function getDailyDonationData($fund_id) {
        $stmt = $this->pdo->prepare("SELECT 
                DATE(created_at) as date,
                SUM(amount) as amount,
                COUNT(*) as count
            FROM donations 
            WHERE fund_id = ? AND payment_status = 'completed'
            GROUP BY DATE(created_at)
            ORDER BY date ASC
            LIMIT 30
        ");
        
        $stmt->execute([$fund_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get comments for a fund
     */
    public function getFundComments($fund_id, $limit = 20) {
        $stmt = $this->pdo->prepare("SELECT c.*, u.name as user_name, u.role as user_role, u.profile_image
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.fund_id = ? AND c.status = 'active'
            ORDER BY c.created_at DESC
            LIMIT " . (int)$limit . "
        ");
        
        $stmt->execute([$fund_id]);
        $comments = $stmt->fetchAll();
        
        // Add profile image URLs
        $uploadManager = new UploadManager();
        foreach ($comments as &$comment) {
            $comment['profile_image_url'] = $uploadManager->getImageUrl('profile', $comment['profile_image']);
        }
        
        return $comments;
    }
    
    /**
     * Add a comment to a fund
     */
    public function addComment($fund_id, $user_id, $comment) {
        $stmt = $this->pdo->prepare("INSERT INTO comments (fund_id, user_id, comment) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$fund_id, $user_id, $comment])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    /**
     * Get comments count for a fund
     */
    public function getCommentsCount($fund_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments 
            WHERE fund_id = ? AND status = 'active'
        ");
        
        $stmt->execute([$fund_id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Update a comment
     */
    public function updateComment($comment_id, $comment_text) {
        $stmt = $this->pdo->prepare("UPDATE comments 
            SET comment = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$comment_text, $comment_id]);
    }
    
    /**
     * Delete a comment (soft delete)
     */
    public function deleteComment($comment_id) {
        $stmt = $this->pdo->prepare("UPDATE comments 
            SET status = 'deleted', updated_at = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$comment_id]);
    }
    
    /**
     * Like/Unlike a fund
     */
    public function toggleLike($fund_id, $user_id) {
        // Check if user already liked this fund
        $stmt = $this->pdo->prepare("SELECT id FROM fund_likes WHERE fund_id = ? AND user_id = ?");
        $stmt->execute([$fund_id, $user_id]);
        $existingLike = $stmt->fetch();
        
        if ($existingLike) {
            // Unlike - remove the like
            $stmt = $this->pdo->prepare("DELETE FROM fund_likes WHERE fund_id = ? AND user_id = ?");
            $stmt->execute([$fund_id, $user_id]);
            
            // Decrease likes count
            $stmt = $this->pdo->prepare("UPDATE funds SET likes_count = likes_count - 1 WHERE id = ?");
            $stmt->execute([$fund_id]);
            
            return false; // unliked
        } else {
            // Like - add the like
            $stmt = $this->pdo->prepare("INSERT INTO fund_likes (fund_id, user_id) VALUES (?, ?)");
            $stmt->execute([$fund_id, $user_id]);
            
            // Increase likes count
            $stmt = $this->pdo->prepare("UPDATE funds SET likes_count = likes_count + 1 WHERE id = ?");
            $stmt->execute([$fund_id]);
            
            return true; // liked
        }
    }
    
    /**
     * Check if user liked a fund
     */
    public function hasUserLiked($fund_id, $user_id) {
        $stmt = $this->pdo->prepare("SELECT id FROM fund_likes WHERE fund_id = ? AND user_id = ?");
        $stmt->execute([$fund_id, $user_id]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get likes count for a fund
     */
    public function getLikesCount($fund_id) {
        $stmt = $this->pdo->prepare("SELECT likes_count FROM funds WHERE id = ?");
        $stmt->execute([$fund_id]);
        $result = $stmt->fetchColumn();
        return $result ? (int)$result : 0;
    }
    
    /**
     * Get users who liked a fund
     */
    public function getFundLikes($fund_id, $limit = 10) {
        $stmt = $this->pdo->prepare("SELECT fl.*, u.name as user_name, u.role as user_role
            FROM fund_likes fl
            LEFT JOIN users u ON fl.user_id = u.id
            WHERE fl.fund_id = ?
            ORDER BY fl.created_at DESC
            LIMIT " . (int)$limit . "
        ");
        
        $stmt->execute([$fund_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get user likes for multiple funds (for bulk checking)
     */
    public function getUserLikesForFunds($fund_ids, $user_id) {
        if (empty($fund_ids) || !$user_id) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($fund_ids) - 1) . '?';
        $params = array_merge($fund_ids, [$user_id]);
        
        $stmt = $this->pdo->prepare("SELECT fund_id 
            FROM fund_likes 
            WHERE fund_id IN ($placeholders) AND user_id = ?
        ");
        
        $stmt->execute($params);
        return array_column($stmt->fetchAll(), 'fund_id');
    }

    /**
     * Create a donation (simple immediate completion flow)
     */
    public function createDonation($fund_id, $backer_id, $amount, $comment = null, $anonymous = 0) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }

        // Ensure fund exists and is active/not ended
        $fund = $this->getFundById($fund_id);
        if (!$fund) {
            throw new RuntimeException('Fund not found');
        }
        if ($fund['status'] !== 'active') {
            throw new RuntimeException('Fund is not accepting donations');
        }
        if (strtotime($fund['end_date']) < strtotime(date('Y-m-d'))) {
            throw new RuntimeException('Campaign has ended');
        }

        // Create donation as completed (no external payment for now)
        $stmt = $this->pdo->prepare("INSERT INTO donations (fund_id, backer_id, amount, payment_status, comment, anonymous, created_at)
            VALUES (?, ?, ?, 'completed', ?, ?, NOW())
        ");
        $stmt->execute([$fund_id, $backer_id, $amount, $comment, (int)$anonymous]);

        // Increase current amount cache on funds
        $stmt2 = $this->pdo->prepare("UPDATE funds SET current_amount = current_amount + ? WHERE id = ?");
        $stmt2->execute([$amount, $fund_id]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get donations made by a user (individual donation records)
     */
    public function getUserDonations($user_id, $limit = 50) {
        $stmt = $this->pdo->prepare("SELECT d.*, f.title as fund_title, f.fundraiser_id, u.name as fundraiser_name
            FROM donations d
            INNER JOIN funds f ON d.fund_id = f.id
            LEFT JOIN users u ON f.fundraiser_id = u.id
            WHERE d.backer_id = ? AND d.payment_status = 'completed'
            ORDER BY d.created_at DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get funds a user has donated to (aggregated by fund with sorting options)
     * This is the main function for dashboard display
     */
    public function getUserDonatedFunds($user_id, $sort = 'latest', $limit = 50) {
        $validSorts = ['latest', 'oldest', 'top_raised', 'less_raised'];
        if (!in_array($sort, $validSorts)) {
            $sort = 'latest';
        }
        
        $orderBy = match($sort) {
            'latest' => 'first_donation_date DESC',
            'oldest' => 'first_donation_date ASC',
            'top_raised' => 'f.current_amount DESC',
            'less_raised' => 'f.current_amount ASC'
        };
        
        $stmt = $this->pdo->prepare("SELECT 
                f.*,
                u.name as fundraiser_name,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                SUM(d.amount) as total_donated,
                COUNT(d.id) as donation_count,
                MIN(d.created_at) as first_donation_date,
                MAX(d.created_at) as last_donation_date,
                (SELECT COUNT(DISTINCT backer_id) 
                 FROM donations 
                 WHERE fund_id = f.id AND payment_status = 'completed') as backer_count,
                DATEDIFF(f.end_date, CURDATE()) as days_left
            FROM donations d
            INNER JOIN funds f ON d.fund_id = f.id
            LEFT JOIN users u ON f.fundraiser_id = u.id
            LEFT JOIN categories c ON f.category_id = c.id
            WHERE d.backer_id = ? AND d.payment_status = 'completed'
            GROUP BY f.id
            ORDER BY $orderBy
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get funds a user liked
     */
    public function getUserLikedFunds($user_id, $limit = 50) {
        $stmt = $this->pdo->prepare("SELECT f.*, u.name as fundraiser_name
            FROM fund_likes fl
            INNER JOIN funds f ON fl.fund_id = f.id
            LEFT JOIN users u ON f.fundraiser_id = u.id
            WHERE fl.user_id = ?
            ORDER BY fl.created_at DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get backer analytics data
     */
    public function getBackerAnalytics($user_id) {
        $stmt = $this->pdo->prepare("SELECT 
                COUNT(DISTINCT d.fund_id) as campaigns_supported,
                COUNT(d.id) as total_donations,
                SUM(d.amount) as total_donated,
                AVG(d.amount) as avg_donation,
                MIN(d.created_at) as first_donation_date
            FROM donations d
            WHERE d.backer_id = ? AND d.payment_status = 'completed'
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    /**
     * Get monthly donation data for charts
     */
    public function getMonthlyDonationData($user_id) {
        $stmt = $this->pdo->prepare("SELECT 
                DATE_FORMAT(d.created_at, '%Y-%m') as month,
                SUM(d.amount) as amount,
                COUNT(d.id) as count
            FROM donations d
            WHERE d.backer_id = ? AND d.payment_status = 'completed'
            AND d.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(d.created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll();
        
        // Fill in missing months with zero values
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $months[$month] = ['month' => date('M Y', strtotime("-$i months")), 'amount' => 0, 'count' => 0];
        }
        
        foreach ($results as $result) {
            if (isset($months[$result['month']])) {
                $months[$result['month']]['amount'] = (float)$result['amount'];
                $months[$result['month']]['count'] = (int)$result['count'];
            }
        }
        
        return array_values($months);
    }
    
    /**
     * Get donations breakdown by category
     */
    public function getDonationsByCategory($user_id) {
        $stmt = $this->pdo->prepare("SELECT 
                c.name as category_name,
                c.color as category_color,
                SUM(d.amount) as total_amount,
                COUNT(d.id) as donation_count
            FROM donations d
            INNER JOIN funds f ON d.fund_id = f.id
            LEFT JOIN categories c ON f.category_id = c.id
            WHERE d.backer_id = ? AND d.payment_status = 'completed'
            GROUP BY c.id, c.name
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent donations by a user (for analytics)
     */
    public function getUserRecentDonations($user_id, $limit = 10) {
        $stmt = $this->pdo->prepare("SELECT 
                d.*,
                f.title as fund_title,
                f.id as fund_id
            FROM donations d
            INNER JOIN funds f ON d.fund_id = f.id
            WHERE d.backer_id = ? AND d.payment_status = 'completed'
            ORDER BY d.created_at DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Admin Functions
     */
    
    /**
     * Get platform statistics for admin dashboard
     */
    public function getPlatformStats() {
        $stats = [];
        
        // Total raised
        $stmt = $this->pdo->query("SELECT SUM(current_amount) FROM funds WHERE status != 'cancelled'");
        $stats['total_raised'] = $stmt->fetchColumn() ?: 0;
        
        // Total funds
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM funds WHERE status != 'cancelled'");
        $stats['total_funds'] = $stmt->fetchColumn();
        
        // Active funds
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM funds WHERE status = 'active'");
        $stats['active_funds'] = $stmt->fetchColumn();
        
        // Total users
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
        $stats['total_users'] = $stmt->fetchColumn();
        
        // Pending reports
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'");
        $stats['pending_reports'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Get top performing campaigns
     */
    public function getTopCampaigns($limit = 5) {
        $limit = intval($limit); // Ensure it's an integer
        $stmt = $this->pdo->query("SELECT f.*, u.name as fundraiser_name, c.name as category_name,
                   (f.current_amount / f.goal_amount * 100) as progress_percentage,
                   COUNT(d.id) as donation_count
            FROM funds f
            LEFT JOIN users u ON f.fundraiser_id = u.id
            LEFT JOIN categories c ON f.category_id = c.id
            LEFT JOIN donations d ON f.id = d.fund_id AND d.payment_status = 'completed'
            WHERE f.status IN ('active', 'completed')
            GROUP BY f.id
            ORDER BY f.current_amount DESC
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Get enhanced monthly platform data for admin dashboard
     */
    public function getMonthlyPlatformData() {
        // Get donation data
        $stmt = $this->pdo->query("SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(amount) as total_donations,
                COUNT(*) as donation_count
            FROM donations 
            WHERE payment_status = 'completed' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get new campaigns data
        $stmt = $this->pdo->query("SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_campaigns
            FROM funds 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get new users data
        $stmt = $this->pdo->query("SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_users
            FROM users 
            WHERE role != 'admin' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get completed campaigns data
        $stmt = $this->pdo->query("SELECT 
                DATE_FORMAT(updated_at, '%Y-%m') as month,
                COUNT(*) as completed_campaigns
            FROM funds 
            WHERE status = 'completed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $completed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge all data by month
        $monthlyData = [];
        $months = [];
        
        // Collect all unique months
        foreach ([$donations, $campaigns, $users, $completed] as $dataset) {
            foreach ($dataset as $row) {
                if (!in_array($row['month'], $months)) {
                    $months[] = $row['month'];
                }
            }
        }
        
        sort($months);
        
        // Initialize data for each month
        foreach ($months as $month) {
            $monthlyData[$month] = [
                'month' => $month,
                'total_donations' => 0,
                'donation_count' => 0,
                'new_campaigns' => 0,
                'new_users' => 0,
                'completed_campaigns' => 0
            ];
        }
        
        // Fill in the data
        foreach ($donations as $row) {
            if (isset($monthlyData[$row['month']])) {
                $monthlyData[$row['month']]['total_donations'] = floatval($row['total_donations']);
                $monthlyData[$row['month']]['donation_count'] = intval($row['donation_count']);
            }
        }
        
        foreach ($campaigns as $row) {
            if (isset($monthlyData[$row['month']])) {
                $monthlyData[$row['month']]['new_campaigns'] = intval($row['new_campaigns']);
            }
        }
        
        foreach ($users as $row) {
            if (isset($monthlyData[$row['month']])) {
                $monthlyData[$row['month']]['new_users'] = intval($row['new_users']);
            }
        }
        
        foreach ($completed as $row) {
            if (isset($monthlyData[$row['month']])) {
                $monthlyData[$row['month']]['completed_campaigns'] = intval($row['completed_campaigns']);
            }
        }
        
        return array_values($monthlyData);
    }
    
    /**
     * Get top backers/donors for admin dashboard
     */
    public function getTopBackers($limit = 5) {
        $limit = intval($limit);
        $stmt = $this->pdo->query("SELECT 
                u.id,
                u.name,
                u.email,
                u.profile_image,
                SUM(d.amount) as total_donated,
                COUNT(d.id) as total_donations,
                COUNT(DISTINCT d.fund_id) as campaigns_supported,
                MAX(d.created_at) as last_donation_date
            FROM users u
            INNER JOIN donations d ON u.id = d.backer_id
            WHERE d.payment_status = 'completed' AND u.role != 'admin'
            GROUP BY u.id, u.name, u.email, u.profile_image
            ORDER BY total_donated DESC
            LIMIT $limit
        ");
        $backers = $stmt->fetchAll();
        
        // Add profile image URLs
        $uploadManager = new UploadManager();
        foreach ($backers as &$backer) {
            $backer['profile_image_url'] = $uploadManager->getImageUrl('profile', $backer['profile_image']);
        }
        
        return $backers;
    }
    
    /**
     * Get fund reports for admin
     */
    public function getFundReports($status = 'pending') {
        $stmt = $this->pdo->prepare("SELECT r.*, f.title as fund_title, f.current_amount, f.goal_amount,
                   u.name as reporter_name, fr.name as fundraiser_name
            FROM reports r
            LEFT JOIN funds f ON r.fund_id = f.id
            LEFT JOIN users u ON r.reported_by = u.id
            LEFT JOIN users fr ON f.fundraiser_id = fr.id
            WHERE r.fund_id IS NOT NULL AND r.status = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get comment reports for admin
     */
    public function getCommentReports($status = 'pending') {
        $stmt = $this->pdo->prepare("SELECT r.*, c.comment as comment_content, f.title as fund_title,
                   u.name as reporter_name, cu.name as commenter_name
            FROM reports r
            LEFT JOIN comments c ON r.comment_id = c.id
            LEFT JOIN funds f ON c.fund_id = f.id
            LEFT JOIN users u ON r.reported_by = u.id
            LEFT JOIN users cu ON c.user_id = cu.id
            WHERE r.comment_id IS NOT NULL AND r.status = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get funds for feature management
     */
    public function getFundsForFeatureManagement($limit = 50) {
        $stmt = $this->pdo->prepare("SELECT f.*, u.name as fundraiser_name
            FROM funds f
            LEFT JOIN users u ON f.fundraiser_id = u.id
            WHERE f.status IN ('active', 'paused', 'frozen')
            ORDER BY f.status = 'frozen' ASC, f.featured DESC, f.current_amount DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute();
        $funds = $stmt->fetchAll();
        
        // Add cover image URLs to each fund
        foreach ($funds as &$fund) {
            $fund['image_url'] = $this->getFundCoverImage($fund['id']);
        }
        
        return $funds;
    }
    
    /**
     * Update fund cover image
     */
    public function updateFundCoverImage($fundId, $imagePath) {
        $stmt = $this->pdo->prepare("UPDATE funds SET image_url = ?, updated_at = NOW() WHERE id = ?");
        try {
            $result = $stmt->execute([$imagePath, $fundId]);
            return $result ? ['success' => true] : ['error' => 'Failed to update fund cover image'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get fund's current cover image path
     */
    public function getFundCoverImage($fundId) {
        $uploadManager = new UploadManager();
        $filename = $this->getFundCoverFilename($fundId);
        return $uploadManager->getImageUrl('cover', $filename);
    }
    
    /**
     * Get fund cover filename (for upload manager)
     */
    public function getFundCoverFilename($fundId) {
        $stmt = $this->pdo->prepare("SELECT image_url FROM funds WHERE id = ?");
        $stmt->execute([$fundId]);
        $fund = $stmt->fetch();
        return $fund ? $fund['image_url'] : null;
    }
}

class UserManager {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Create new user
     */
    public function createUser($name, $email, $password, $role, $bio = null) {
        // Check if email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return false; // Email already exists
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, role, bio, email_verified) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([$name, $email, $hashedPassword, $role, $bio]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $fields = [];
        $params = [];
        
        // Handle name update (not for admin)
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        
        // Handle email update
        if (isset($data['email'])) {
            // Check if email already exists for another user
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $userId]);
            if ($stmt->fetch()) {
                return ['error' => 'Email already exists for another user'];
            }
            
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }

        // Handle password update
        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return ['error' => 'No fields to update'];
        }
        
        $fields[] = "updated_at = NOW()";
        $params[] = $userId;
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        try {
            $result = $stmt->execute($params);
            return $result ? ['success' => true] : ['error' => 'Failed to update profile'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verify current password
     */
    public function verifyCurrentPassword($userId, $password) {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user profile with complete information
     */
    public function getCompleteUserProfile($userId) {
        $stmt = $this->pdo->prepare("SELECT id, name, email, role, bio, status, email_verified, profile_image, created_at, updated_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update user profile image
     */
    public function updateProfileImage($userId, $imagePath) {
        $stmt = $this->pdo->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
        try {
            $result = $stmt->execute([$imagePath, $userId]);
            return $result ? ['success' => true] : ['error' => 'Failed to update profile image'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remove user profile image (set to NULL to use default)
     */
    public function removeProfileImage($userId) {
        // Get current profile image filename before removing
        $currentFilename = $this->getProfileImageFilename($userId);
        
        // Only delete file if it's not the default and exists
        if ($currentFilename && $currentFilename !== 'default-profile.png') {
            $uploadManager = new UploadManager();
            $currentImagePath = '../../uploads/profiles/' . $currentFilename;
            if (file_exists($currentImagePath)) {
                unlink($currentImagePath);
            }
        }
        
        // Set profile_image to NULL in database (will use default)
        $stmt = $this->pdo->prepare("UPDATE users SET profile_image = NULL, updated_at = NOW() WHERE id = ?");
        try {
            $result = $stmt->execute([$userId]);
            return $result ? ['success' => true] : ['error' => 'Failed to remove profile image'];
        } catch (PDOException $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user's current profile image path
     */
    public function getUserProfileImage($userId) {
        $stmt = $this->pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? $user['profile_image'] : null;
    }
    
    /**
     * Get user's profile image URL
     */
    public function getProfileImage($userId) {
        $uploadManager = new UploadManager();
        $filename = $this->getProfileImageFilename($userId);
        return $uploadManager->getImageUrl('profile', $filename);
    }
    
    /**
     * Get profile image filename (for upload manager)
     */
    public function getProfileImageFilename($userId) {
        $stmt = $this->pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? $user['profile_image'] : null;
    }
}

// Utility functions
function formatCurrency($amount) {
    return '$' . number_format($amount, 0);
}

function calculatePercentage($current, $goal) {
    if ($goal <= 0) return 0;
    return min(100, round(($current / $goal) * 100, 1));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function getDaysLeft($end_date) {
    $days = (strtotime($end_date) - time()) / (60 * 60 * 24);
    return max(0, floor($days));
}
