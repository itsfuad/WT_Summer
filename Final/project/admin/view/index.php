<?php
require_once '../../shared/includes/session.php';
require_once '../../shared/includes/functions.php';

requireLogin();
requireRole('admin');


$user = getCurrentUser();
$fundManager = new FundManager();

// Get platform statistics
$stats = $fundManager->getPlatformStats();
$monthlyData = $fundManager->getMonthlyPlatformData();
$fundReports = $fundManager->getFundReports();
$commentReports = $fundManager->getCommentReports();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../../backer/css/analytics.css">
    <script>
        const monthlyData = <?php echo json_encode($monthlyData); ?>;
    </script>
    <script src="../../shared/libs/chart.min.js"></script>
    <script src="../js/script.js" defer></script>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <a href="../../home/view/index.php" class="logo">
                    <i class="fas fa-hand-holding-heart"></i>
                    CrowdFund
                </a>
                <div class="user-info">
                    <a href="../../home/view/index.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Browse Campaigns
                    </a>
                    <a href="profile.php" class="btn btn-primary">
                        <i class="fas fa-user-edit"></i> Manage Profile
                    </a>
                    <a href="../../shared/includes/logout.php" class="btn-destructive">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Platform Statistics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo formatCurrency($stats['total_raised']); ?></div>
                    <div class="metric-label">Total Raised</div>
                    <div class="metric-progress">Platform lifetime</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $stats['total_funds']; ?></div>
                    <div class="metric-label">Total Campaigns</div>
                    <div class="metric-progress"><?php echo $stats['active_funds']; ?> active</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $stats['total_users']; ?></div>
                    <div class="metric-label">Total Users</div>
                    <div class="metric-progress">Platform members</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-flag"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $stats['pending_reports']; ?></div>
                    <div class="metric-label">Pending Reports</div>
                    <div class="metric-progress">Need attention</div>
                </div>
            </div>
        </div>

        <!-- Management Sections -->
        <div class="tables-row">
            <!-- Fund Reports -->
            <div class="table-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Fund Reports (<?php echo count($fundReports); ?>)</h3>
                <?php if (empty($fundReports)): ?>
                    <div class="no-data">
                        <i class="fas fa-check-circle"></i>
                        <p>No pending fund reports</p>
                    </div>
                <?php else: ?>
                    <div class="reports-list">
                        <?php foreach ($fundReports as $report): ?>
                            <div class="report-item">
                                <div class="report-info">
                                    <h4><?php echo htmlspecialchars($report['fund_title']); ?></h4>
                                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($report['reason']); ?></p>
                                    <p><strong>Reporter:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                                    <small><?php echo date('M j, Y', strtotime($report['created_at'])); ?></small>
                                </div>
                                <div class="report-actions">
                                    <button class="btn btn-primary btn-sm" onclick="openCampaignView(<?php echo $report['fund_id']; ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="handleFundReport(<?php echo $report['id']; ?>, <?php echo $report['fund_id']; ?>, 'freeze')">
                                        <i class="fas fa-pause"></i> Freeze
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="handleFundReport(<?php echo $report['id']; ?>, <?php echo $report['fund_id']; ?>, 'dismiss')">
                                        <i class="fas fa-times"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Comment Reports -->
            <div class="table-card">
                <h3><i class="fas fa-comment-slash"></i> Comment Reports (<?php echo count($commentReports); ?>)</h3>
                <?php if (empty($commentReports)): ?>
                    <div class="no-data">
                        <i class="fas fa-check-circle"></i>
                        <p>No pending comment reports</p>
                    </div>
                <?php else: ?>
                    <div class="reports-list">
                        <?php foreach ($commentReports as $report): ?>
                            <div class="report-item">
                                <div class="report-info">
                                    <h4><?php echo htmlspecialchars($report['fund_title']); ?></h4>
                                    <p><strong>Comment:</strong> "<?php echo htmlspecialchars(substr($report['comment_content'], 0, 100)); ?>..."</p>
                                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($report['reason']); ?></p>
                                    <p><strong>Reporter:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                                    <small><?php echo date('M j, Y', strtotime($report['created_at'])); ?></small>
                                </div>
                                <div class="report-actions">
                                    <button class="btn btn-danger btn-sm" onclick="handleCommentReport(<?php echo $report['id']; ?>, <?php echo $report['comment_id']; ?>, 'delete')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="handleCommentReport(<?php echo $report['id']; ?>, <?php echo $report['comment_id']; ?>, 'dismiss')">
                                        <i class="fas fa-times"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Analytics -->
        <div class="charts-row">
            <div class="chart-card">
                <h3><i class="fas fa-dollar-sign"></i> Monthly Revenue & Donations</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Platform Growth</h3>
                <canvas id="growthChart"></canvas>
            </div>
        </div>
    </div>
    </div>
</body>
</html>