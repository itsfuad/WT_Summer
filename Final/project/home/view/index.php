<?php
// Include database functions
require_once '../../includes/functions.php';
require_once '../../includes/session.php';

// Initialize managers
$fundManager = new FundManager();

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getCurrentUser() : null;

// Get featured funds (top 6 for homepage)
$funds = $fundManager->getFeaturedFunds(6);
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
                <a href="../../signup/view/index.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="navigation">
        <div class="nav-left">
            <h2>Featured Campaigns</h2>
        </div>
        <div class="nav-right">
            <?php if ($isLoggedIn): ?>
                <span style="color: #666;">
                    <i class="fas fa-star"></i>
                    Browsing as <?php echo ucfirst($user['role']); ?>
                </span>
            <?php else: ?>
                <span style="color: #666;">
                    <i class="fas fa-info-circle"></i>
                    Browsing as Guest
                </span>
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
        
        <div class="campaigns-grid">
            <?php if (empty($funds)): ?>
                <div class="no-campaigns">
                    <i class="fas fa-info-circle"></i>
                    <h3>No active campaigns found</h3>
                    <p>Check back later for new exciting projects!</p>
                </div>
            <?php else: ?>
                <?php foreach ($funds as $fund): ?>
                    <?php 
                    $percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
                    $days_left = getDaysLeft($fund['end_date']);
                    ?>
                    <div class="campaign-card">
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

                        <div class="campaign-actions">
                            <?php if ($isLoggedIn): ?>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                <?php elseif ($user['role'] === 'backer'): ?>
                                    <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-hand-holding-usd"></i> Donate
                                    </a>
                                    <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                <?php else: ?>
                                    <a href="../../<?php echo $user['role']; ?>/view/index.php" class="btn btn-primary">
                                        <i class="fas fa-tachometer-alt"></i> View Dashboard
                                    </a>
                                    <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="../../login/view/index.php" class="btn btn-primary">
                                    <i class="fas fa-hand-holding-usd"></i> Donate
                                </a>
                                <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
