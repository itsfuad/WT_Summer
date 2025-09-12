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
    require_once '../../shared/includes/session.php';
    require_once '../../shared/includes/functions.php';
    
    requireLogin();
    requireRole('fundraiser');
    $user = getCurrentUser();
    
    $fundManager = new FundManager();
    
    // Get fundraiser's funds
    $userFunds = $fundManager->getFundsByFundraiserId($user['id']);
    
    // Get funds the fundraiser has donated to
    $sort = $_GET['sort'] ?? 'latest';
    $donatedFunds = $fundManager->getUserDonatedFunds($user['id'], $sort);
    
    // Calculate statistics
    $totalFunds = count($userFunds);
    $totalRaised = 0;
    $activeFunds = 0;
    $totalDonated = 0;
    
    foreach ($userFunds as $fund) {
        $totalRaised += $fund['current_amount'];
        if ($fund['status'] === 'active') {
            $activeFunds++;
        }
    }
    
    foreach ($donatedFunds as $fund) {
        $totalDonated += $fund['total_donated'];
    }
    ?>
    
    <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <a href="../../home/view/index.php" class="logo">
                    <i class="fas fa-hand-holding-heart"></i>
                    CrowdFund
                </a>
                <div class="user-info">
                    <span class="welcome_title">Welcome, <a href="../../profile/view?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></a>!</span>
                    <a href="../../profile/view/edit.php" class="btn btn-primary">
                        <i class="fas fa-user-edit"></i> Manage Profile
                    </a>
                    <a href="../../shared/includes/logout.php" class="btn-danger">
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
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $activeFunds; ?></h3>
                    <p>Active Campaigns</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($totalDonated); ?></h3>
                    <p>Total Donated</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-section">
            <a href="../../campaign/view/create_fund.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Campaign
            </a>
            <a href="../../donation/view/index.php" class="btn btn-outline">
                <i class="fas fa-chart-bar"></i> Donation Analytics
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
                </div>
            <?php else: ?>
                <div class="campaigns-grid">
                    <?php foreach ($userFunds as $fund): ?>
                        <?php 
                        $percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
                        $daysLeft = getDaysLeft($fund['end_date']);
                        $statusClass = $fund['status'] === 'active' ? 'active' : $fund['status'];
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
                                <div class="stat-box backer">
                                    <div class="stat-value"><?php echo $fund['backer_count']; ?></div>
                                    <div class="stat-label">Backers</div>
                                </div>
                                <div class="stat-box days-left">
                                    <div class="stat-value"><?php echo $daysLeft; ?></div>
                                    <div class="stat-label">Days Left</div>
                                </div>
                            </div>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <?php echo $percentage; ?>% of <?php echo formatCurrency($fund['goal_amount']); ?> goal
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Donated Funds Section -->
        <div class="campaigns-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2><i class="fas fa-hand-holding-heart"></i> Campaigns You've Supported</h2>
                <div class="filter-controls">
                    <select onchange="window.location.href='?sort=' + this.value" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest Donations</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest Donations</option>
                        <option value="top_raised" <?php echo $sort === 'top_raised' ? 'selected' : ''; ?>>Top Raised</option>
                        <option value="less_raised" <?php echo $sort === 'less_raised' ? 'selected' : ''; ?>>Less Raised</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($donatedFunds)): ?>
                <div class="empty-state">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>No donations yet</h3>
                    <p>Support other campaigns to build the community.</p>
                </div>
            <?php else: ?>
                <div class="campaigns-grid">
                    <?php foreach ($donatedFunds as $fund): ?>
                        <?php 
                        $percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
                        $daysLeft = getDaysLeft($fund['end_date']);
                        $statusClass = $fund['status'] === 'active' ? 'active' : $fund['status'];
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

                            <div class="donation-info" style="background: #f3f4f6; padding: 12px; border-radius: 8px; margin: 12px 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span><strong>Your contribution:</strong> <?php echo formatCurrency($fund['total_donated']); ?></span>
                                    <span style="color: #6b7280; font-size: 14px;"><?php echo $fund['donation_count']; ?> donation<?php echo $fund['donation_count'] > 1 ? 's' : ''; ?></span>
                                </div>
                            </div>

                            <div class="campaign-stats">
                                <div class="stat-box raised">
                                    <div class="stat-value"><?php echo formatCurrency($fund['current_amount']); ?></div>
                                    <div class="stat-label">Raised</div>
                                </div>
                                <div class="stat-box backer">
                                    <div class="stat-value"><?php echo $fund['backer_count']; ?></div>
                                    <div class="stat-label">Backers</div>
                                </div>
                                <div class="stat-box days-left">
                                    <div class="stat-value"><?php echo $daysLeft; ?></div>
                                    <div class="stat-label">Days Left</div>
                                </div>
                            </div>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <?php echo $percentage; ?>% of <?php echo formatCurrency($fund['goal_amount']); ?> goal
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
