<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fundraiser Dashboard - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php
    require_once '../../includes/session.php';
    require_once '../../includes/functions.php';
    
    requireLogin();
    requireRole('fundraiser');
    $user = getCurrentUser();
    
    $fundManager = new FundManager();
    
    // Get fundraiser's funds
    $userFunds = $fundManager->getFundsByFundraiserId($user['id']);
    
    // Calculate statistics
    $totalFunds = count($userFunds);
    $totalRaised = 0;
    $totalBackers = 0;
    $totalLikes = 0;
    $activeFunds = 0;
    
    foreach ($userFunds as $fund) {
        $totalRaised += $fund['current_amount'];
        $totalBackers += $fund['backer_count'];
        $totalLikes += $fundManager->getLikesCount($fund['id']);
        if ($fund['status'] === 'active') {
            $activeFunds++;
        }
    }
    ?>
    
    <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-lightbulb"></i> Fundraiser Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
                    <a href="../../includes/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalFunds; ?></h3>
                    <p>Total Campaigns</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($totalRaised); ?></h3>
                    <p>Total Raised</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalBackers; ?></h3>
                    <p>Total Backers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $activeFunds; ?></h3>
                    <p>Active Campaigns</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-content">
                    <h3 style="color: #ff6b9d;"><?php echo $totalLikes; ?></h3>
                    <p>Total Likes</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-section">
            <a href="create_fund.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Campaign
            </a>
            <a href="../../home/view/index.php" class="btn btn-secondary">
                <i class="fas fa-eye"></i> Browse Campaigns
            </a>
        </div>

        <!-- Campaigns List -->
        <div class="campaigns-section">
            <h2><i class="fas fa-list"></i> Your Campaigns</h2>
            
            <?php if (empty($userFunds)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No campaigns yet</h3>
                    <p>Start your fundraising journey by creating your first campaign.</p>
                    <a href="create_fund.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Your First Campaign
                    </a>
                </div>
            <?php else: ?>
                <div class="campaigns-grid">
                    <?php foreach ($userFunds as $fund): ?>
                        <?php 
                        $percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
                        $daysLeft = getDaysLeft($fund['end_date']);
                        $statusClass = $fund['status'] === 'active' ? 'active' : $fund['status'];
                        $likesCount = $fundManager->getLikesCount($fund['id']);
                        ?>
                        <div class="campaign-card">
                            <div class="campaign-status status-<?php echo $statusClass; ?>">
                                <?php echo ucfirst($fund['status']); ?>
                            </div>
                            
                            <div class="campaign-content">
                                <h3><?php echo htmlspecialchars($fund['title']); ?></h3>
                                <p><?php echo htmlspecialchars($fund['short_description'] ?? substr($fund['description'], 0, 100) . '...'); ?></p>
                                
                                <div class="campaign-stats">
                                    <div class="stat">
                                        <strong><?php echo formatCurrency($fund['current_amount']); ?></strong>
                                        <span>of <?php echo formatCurrency($fund['goal_amount']); ?></span>
                                    </div>
                                    <div class="stat">
                                        <strong><?php echo $fund['backer_count']; ?></strong>
                                        <span>backers</span>
                                    </div>
                                    <div class="stat">
                                        <strong><?php echo $daysLeft; ?></strong>
                                        <span>days left</span>
                                    </div>
                                    <div class="stat">
                                        <strong style="color: #ff6b9d;"><?php echo $likesCount; ?></strong>
                                        <span><i class="fas fa-heart" style="color: #ff6b9d;"></i> likes</span>
                                    </div>
                                </div>
                                
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="progress-text"><?php echo $percentage; ?>% funded</div>
                                
                                <div class="campaign-actions">
                                    <a href="../../campaign/view.php?id=<?php echo $fund['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit_fund.php?id=<?php echo $fund['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="analytics.php?id=<?php echo $fund['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-chart-bar"></i> Analytics
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
