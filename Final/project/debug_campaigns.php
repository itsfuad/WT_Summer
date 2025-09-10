<?php
require_once 'shared/includes/functions.php';

$fundManager = new FundManager();

// Get campaign statistics
$stats = $fundManager->getCampaignStats();

// Get all campaigns including expired ones
$allCampaigns = $fundManager->getAllFundsIncludingExpired(50);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Debug - CrowdFund</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
        .stats { background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .campaign { background: white; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #2196f3; }
        .expired { border-left-color: #f44336; background: #ffebee; }
        .featured { border-left-color: #ff9800; }
        .stats h2 { margin-top: 0; color: #1976d2; }
        .campaign h4 { margin: 0 0 10px 0; color: #333; }
        .status { font-weight: bold; }
        .expired .status { color: #f44336; }
        .active .status { color: #4caf50; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .btn { display: inline-block; padding: 10px 20px; background: #2196f3; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>🔍 Campaign Debug Information</h1>
    
    <div class="stats">
        <h2>📊 Campaign Statistics</h2>
        <p><strong>Total Campaigns:</strong> <?php echo $stats['total_campaigns']; ?></p>
        <p><strong>Active Campaigns:</strong> <?php echo $stats['active_campaigns']; ?></p>
        <p><strong>Active & Not Expired:</strong> <?php echo $stats['active_not_expired']; ?> ✅</p>
        <p><strong>Active but Expired:</strong> <?php echo $stats['active_expired']; ?> ❌</p>
        <p><strong>Featured Campaigns:</strong> <?php echo $stats['featured_campaigns']; ?> ⭐</p>
    </div>
    
    <h2>📋 All Campaigns (showing first 50)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Fundraiser</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Days Left</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Goal</th>
                <th>Raised</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allCampaigns as $campaign): ?>
                <tr class="<?php echo strtolower($campaign['expiry_status']); ?>">
                    <td><?php echo $campaign['id']; ?></td>
                    <td><?php echo htmlspecialchars(substr($campaign['title'], 0, 30)); ?>...</td>
                    <td><?php echo htmlspecialchars($campaign['fundraiser_name']); ?></td>
                    <td><?php echo $campaign['start_date']; ?></td>
                    <td><?php echo $campaign['end_date']; ?></td>
                    <td><?php echo $campaign['days_left']; ?></td>
                    <td class="status"><?php echo $campaign['expiry_status']; ?></td>
                    <td><?php echo $campaign['featured'] ? '⭐ Yes' : 'No'; ?></td>
                    <td>$<?php echo number_format($campaign['goal_amount']); ?></td>
                    <td>$<?php echo number_format($campaign['current_amount']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px;">
        <h3>🔧 Solutions</h3>
        <p><strong>Problem:</strong> Many campaigns have expired because they were created with random past dates.</p>
        <p><strong>Solutions:</strong></p>
        <ol>
            <li><strong>Regenerate Data:</strong> 
                <a href="database_setup/generate_dummy_data.php" class="btn">Run Updated Data Generator</a>
                (This will create campaigns with better date ranges)
            </li>
            <li><strong>Extend Existing Campaigns:</strong> Run this SQL in phpMyAdmin:<br>
                <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0;">
                UPDATE funds SET end_date = DATE_ADD(CURDATE(), INTERVAL RAND() * 365 + 30 DAY) WHERE status = 'active' AND end_date < CURDATE();
                </code>
            </li>
        </ol>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="home/view/" class="btn">← Back to Home</a>
        <a href="database_setup/generate_dummy_data.php" class="btn">🎲 Regenerate Data</a>
    </div>
</body>
</html>
