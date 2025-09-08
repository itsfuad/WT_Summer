<?php
require_once '../../shared/includes/session.php';
require_once '../../shared/includes/functions.php';

requireLogin();
// Allow both backer and fundraiser roles
$user = getCurrentUser();
if (!in_array($user['role'], ['backer', 'fundraiser'])) {
    header('Location: ../../login/view/index.php');
    exit;
}

$fundManager = new FundManager();

// Get backer's donation data
$donatedFunds = $fundManager->getUserDonatedFunds($user['id'], 'latest', 1000);
$backerAnalytics = $fundManager->getBackerAnalytics($user['id']);
$monthlyDonations = $fundManager->getMonthlyDonationData($user['id']);
$categoryBreakdown = $fundManager->getDonationsByCategory($user['id']);
$recentDonations = $fundManager->getUserRecentDonations($user['id'], 10);

// Calculate key metrics
$totalDonated = 0;
$totalCampaigns = count($donatedFunds);
$activeCampaigns = 0;
$completedCampaigns = 0;
$totalDonationCount = 0;
$avgDonationAmount = 0;

// Debug: Let's see what data we actually have
// echo '<pre>'; print_r($donatedFunds); echo '</pre>'; // Uncomment for debugging

foreach ($donatedFunds as $fund) {
    $totalDonated += $fund['total_donated'];
    $totalDonationCount += $fund['donation_count'];
    if ($fund['status'] === 'active') {
        $activeCampaigns++;
    } elseif ($fund['status'] === 'completed') {
        $completedCampaigns++;
    }
}

$avgDonationAmount = $totalDonationCount > 0 ? $totalDonated / $totalDonationCount : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backer Analytics - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/analytics.css">
    <script>
        const monthlyData = <?php echo json_encode($monthlyDonations); ?>;
        const categoryData = <?php echo json_encode($categoryBreakdown); ?>;
    </script>
    <script src="../../shared/libs/chart.min.js"></script>
    <script src="../js/script.js" defer></script>
</head>
<body>

    <div class="analytics-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="<?php echo $user['role'] === 'backer' ? 'index.php' : '../../fundraiser/view/index.php'; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="header-actions">
                <div class="sub-title">
                    <i class="fas fa-chart-bar"></i> Donation Analytics
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo formatCurrency($totalDonated); ?></div>
                    <div class="metric-label">Total Donated</div>
                    <div class="metric-progress">
                        Across <?php echo $totalCampaigns; ?> campaigns
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $totalDonationCount; ?></div>
                    <div class="metric-label">Total Donations</div>
                    <div class="metric-progress">
                        <?php echo formatCurrency($avgDonationAmount); ?> average
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $activeCampaigns; ?></div>
                    <div class="metric-label">Active Campaigns</div>
                    <div class="metric-progress">
                        <?php echo $completedCampaigns; ?> completed
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <!-- Monthly Donation Timeline -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Monthly Donation Trend</h3>
                <canvas id="monthlyChart"></canvas>
            </div>
            
            <!-- Category Breakdown -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Donations by Category</h3>
                <div class="progress-chart">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Tables Row -->
        <div class="tables-row">
            <!-- Recent Donations -->
            <div class="table-card">
                <h3><i class="fas fa-history"></i> Recent Donations</h3>
                <?php if (empty($recentDonations)): ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>No donations yet</p>
                    </div>
                <?php else: ?>
                    <div class="donations-list">
                        <?php foreach ($recentDonations as $donation): ?>
                            <div class="donation-item">
                                <div class="donation-info">
                                    <div class="donation-title">
                                        <a href="../../campaign/view?id=<?php echo $donation['fund_id']; ?>">
                                            <?php echo htmlspecialchars($donation['fund_title']); ?>
                                        </a>
                                    </div>
                                    <div class="donation-meta">
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

            <!-- Top Supported Campaigns -->
            <div class="table-card">
                <h3><i class="fas fa-star"></i> Top Supported Campaigns</h3>
                <?php if (empty($donatedFunds)): ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>No campaigns supported yet</p>
                    </div>
                <?php else: ?>
                    <div class="campaigns-list">
                        <?php 
                        // Sort by total donated descending
                        usort($donatedFunds, function($a, $b) {
                            return $b['total_donated'] <=> $a['total_donated'];
                        });
                        $topCampaigns = array_slice($donatedFunds, 0, 5);
                        ?>
                        <?php foreach ($topCampaigns as $campaign): ?>
                            <div class="campaign-item">
                                <div class="campaign-info">
                                    <div class="campaign-title">
                                        <a href="../../campaign/view?id=<?php echo $campaign['id']; ?>">
                                            <?php echo htmlspecialchars($campaign['title']); ?>
                                        </a>
                                    </div>
                                    <div class="campaign-meta">
                                        <?php echo $campaign['donation_count']; ?> donation<?php echo $campaign['donation_count'] > 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                                <div class="campaign-amount">
                                    <?php echo formatCurrency($campaign['total_donated']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
