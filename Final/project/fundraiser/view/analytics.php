<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Analytics - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/analytics.css">
    <script src="../../shared/libs/chart.min.js"></script>
</head>
<body>
    <?php
    require_once '../../shared/includes/session.php';
    require_once '../../shared/includes/functions.php';
    
    requireLogin();
    requireRole('fundraiser');
    $user = getCurrentUser();
    
    $fundManager = new FundManager();
    
    // Get fund ID from URL
    $fund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$fund_id) {
        header('Location: index.php');
        exit;
    }
    
    // Get fund details
    $fund = $fundManager->getFundById($fund_id);
    
    if (!$fund || $fund['fundraiser_id'] != $user['id']) {
        header('Location: index.php');
        exit;
    }
    
    // Get analytics data
    $analytics = $fundManager->getFundAnalytics($fund_id);
    $dailyData = $fundManager->getDailyDonationData($fund_id);
    $topDonations = $fundManager->getTopDonations($fund_id, 5);
    $recentActivity = $fundManager->getRecentActivity($fund_id, 10);
    
    // Get engagement data
    $commentsCount = $fundManager->getCommentsCount($fund_id);
    $likesCount = $fundManager->getLikesCount($fund_id);
    
    // Calculate key metrics
    $percentage = calculatePercentage($fund['current_amount'], $fund['goal_amount']);
    $days_left = getDaysLeft($fund['end_date']);
    
    // Calculate campaign duration (how long it's been running)
    $days_running = max(1, floor((time() - strtotime($fund['created_at'])) / (60 * 60 * 24)));
    
    // Calculate total campaign duration (from start to end)
    $total_duration = max(1, floor((strtotime($fund['end_date']) - strtotime($fund['created_at'])) / (60 * 60 * 24)));
    
    $avg_daily_raise = $days_running > 0 ? $fund['current_amount'] / $days_running : 0;
    ?>
    
    <div class="analytics-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="../../campaign/view?id=<?php echo $fund['id']; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Campaign
                </a>
            </div>
            <div class="header-actions">
                <div class="sub-title">
                    <i class="fas fa-chart-bar"></i> Campaign Analytics
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo formatCurrency($fund['current_amount']); ?></div>
                    <div class="metric-label">Total Raised</div>
                    <div class="metric-progress">
                        <?php echo $percentage; ?>% of goal
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $fund['backer_count']; ?></div>
                    <div class="metric-label">Total Backers</div>
                    <div class="metric-progress">
                        <?php echo $fund['backer_count'] > 0 ? formatCurrency($fund['current_amount'] / $fund['backer_count']) : '$0'; ?> avg
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo formatCurrency($avg_daily_raise); ?></div>
                    <div class="metric-label">Daily Average</div>
                    <div class="metric-progress">
                                            <div class="campaign-info">
                        <div class="campaign-status">
                            <span class="status-badge status-<?php echo $fund['status']; ?>">
                                Status: <?php echo ucfirst($fund['status']); ?>
                            </span>
                        </div>
                        <?php echo $days_running; ?> days running • <?php echo $days_left; ?> days left
                    </div>
                    </div>
                </div>
            </div>
            
            <!-- Engagement Metrics -->
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $likesCount; ?></div>
                    <div class="metric-label">Total Likes</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $commentsCount; ?></div>
                    <div class="metric-label">Comments</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <!-- Donation Timeline -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Donation Timeline</h3>
                <canvas id="donationChart"></canvas>
            </div>
            
            <!-- Funding Progress -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Funding Progress</h3>
                <div class="progress-chart">
                    <canvas id="progressChart"></canvas>
                    <div class="progress-stats">
                        <div class="progress-stat">
                            <span class="stat-label">Raised</span>
                            <span class="stat-value"><?php echo formatCurrency($fund['current_amount']); ?></span>
                        </div>
                        <div class="progress-stat">
                            <span class="stat-label">Remaining</span>
                            <span class="stat-value"><?php echo formatCurrency($fund['goal_amount'] - $fund['current_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables Row -->
        <div class="tables-row">
            <!-- Top Donations -->
            <div class="table-card">
                <h3><i class="fas fa-trophy"></i> Top Donations</h3>
                <?php if (empty($topDonations)): ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>No donations yet</p>
                    </div>
                <?php else: ?>
                    <div class="donations-table">
                        <?php foreach ($topDonations as $donation): ?>
                            <div class="donation-row">
                                <div class="donor-info">
                                    <div class="donor-name">
                                        <?php echo $donation['anonymous'] ? 'Anonymous' : htmlspecialchars($donation['backer_name']); ?>
                                    </div>
                                    <div class="donation-date">
                                        <?php echo date('M j, Y', strtotime($donation['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="donation-amount">
                                    <?php echo formatCurrency($donation['amount']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="table-card">
                <h3><i class="fas fa-clock"></i> Recent Activity</h3>
                <?php if (empty($recentActivity)): ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>No recent activity</p>
                    </div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">
                                        <?php if ($activity['anonymous']): ?>
                                            Anonymous donated <?php echo formatCurrency($activity['amount']); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($activity['backer_name']); ?> donated <?php echo formatCurrency($activity['amount']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo timeAgo($activity['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Campaign Performance -->
        <div class="performance-card">
            <h3><i class="fas fa-chart-bar"></i> Campaign Performance Summary</h3>
            <div class="performance-grid">
                <div class="performance-item">
                    <div class="performance-label">Total Campaign Duration</div>
                    <div class="performance-value"><?php echo $total_duration; ?> days</div>
                </div>
                <div class="performance-item">
                    <div class="performance-label">Days Running</div>
                    <div class="performance-value"><?php echo $days_running; ?> days</div>
                </div>
                <div class="performance-item">
                    <div class="performance-label">Days Remaining</div>
                    <div class="performance-value"><?php echo $days_left; ?> days</div>
                </div>
                <div class="performance-item">
                    <div class="performance-label">Completion Rate</div>
                    <div class="performance-value"><?php echo $percentage; ?>%</div>
                </div>
                <div class="performance-item">
                    <div class="performance-label">Projected Total</div>
                    <div class="performance-value">
                        <?php 
                        $daily_rate = $avg_daily_raise;
                        $projected = $fund['current_amount'] + ($daily_rate * $days_left);
                        echo formatCurrency($projected);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Donation Timeline Chart
        const donationCtx = document.getElementById('donationChart').getContext('2d');
        const donationChart = new Chart(donationCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($dailyData, 'date')); ?>,
                datasets: [{
                    label: 'Daily Donations',
                    data: <?php echo json_encode(array_column($dailyData, 'amount')); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Progress Chart
        const progressCtx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(progressCtx, {
            type: 'doughnut',
            data: {
                labels: ['Raised', 'Remaining'],
                datasets: [{
                    data: [<?php echo $fund['current_amount']; ?>, <?php echo max(0, $fund['goal_amount'] - $fund['current_amount']); ?>],
                    backgroundColor: ['#27ae60', '#ecf0f1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
