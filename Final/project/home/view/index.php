<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrowdFund - Discover Amazing Campaigns</title>
    <link rel="stylesheet" href="../../fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-hand-holding-usd"></i> CrowdFund</h1>
        <p>Discover amazing projects and help bring them to life</p>
        <div class="header-actions">
            <a href="../../login/view/index.php" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="../../signup/view/index.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
        </div>
    </div>

    <div class="navigation">
        <div class="nav-left">
            <h2>Featured Campaigns</h2>
        </div>
        <div class="nav-right">
            <span style="color: #666;">
                <i class="fas fa-info-circle"></i>
                Browsing as Guest
            </span>
        </div>
    </div>

    <div class="container">
        <div class="guest-notice">
            <i class="fas fa-info-circle"></i>
            Want to support these amazing campaigns? <a href="signup/index.php">Create an account</a> to back projects and access more features.
        </div>
        
        <div class="campaigns-grid">
            <?php
            // Sample campaigns data (would come from database in real application)
            $campaigns = [
                [
                    'id' => 1,
                    'title' => 'AI-Powered Educational Platform',
                    'creator' => 'Tech Innovators LLC',
                    'description' => 'Building an AI-powered educational platform to revolutionize online learning with personalized content and adaptive learning algorithms.',
                    'goal' => 50000,
                    'raised' => 32500,
                    'backers' => 156,
                    'days_left' => 23,
                    'icon' => 'fas fa-robot'
                ],
                [
                    'id' => 2,
                    'title' => 'Sustainable Urban Farming',
                    'creator' => 'Green Future Co.',
                    'description' => 'Creating vertical farming solutions for urban environments to promote sustainable food production and reduce carbon footprint.',
                    'goal' => 75000,
                    'raised' => 45000,
                    'backers' => 203,
                    'days_left' => 15,
                    'icon' => 'fas fa-seedling'
                ],
                [
                    'id' => 3,
                    'title' => 'Community Health Initiative',
                    'creator' => 'HealthCare Heroes',
                    'description' => 'Providing free health screenings and medical services to underserved communities with mobile health units.',
                    'goal' => 30000,
                    'raised' => 18750,
                    'backers' => 98,
                    'days_left' => 31,
                    'icon' => 'fas fa-heart'
                ],
                [
                    'id' => 4,
                    'title' => 'Renewable Energy Project',
                    'creator' => 'EcoSolutions Inc.',
                    'description' => 'Installing solar panels in rural communities to provide clean, sustainable energy and reduce dependency on fossil fuels.',
                    'goal' => 100000,
                    'raised' => 67500,
                    'backers' => 245,
                    'days_left' => 12,
                    'icon' => 'fas fa-solar-panel'
                ],
                [
                    'id' => 5,
                    'title' => 'Digital Art Gallery',
                    'creator' => 'Creative Collective',
                    'description' => 'Creating an online platform for emerging digital artists to showcase and sell their work with fair compensation.',
                    'goal' => 25000,
                    'raised' => 12500,
                    'backers' => 89,
                    'days_left' => 45,
                    'icon' => 'fas fa-palette'
                ],
                [
                    'id' => 6,
                    'title' => 'Open Source Learning Tools',
                    'creator' => 'EduTech Foundation',
                    'description' => 'Developing free, open-source educational tools and resources for students and teachers worldwide.',
                    'goal' => 40000,
                    'raised' => 28000,
                    'backers' => 134,
                    'days_left' => 18,
                    'icon' => 'fas fa-graduation-cap'
                ]
            ];

            foreach ($campaigns as $campaign) {
                $percentage = round(($campaign['raised'] / $campaign['goal']) * 100, 1);
                ?>
                <div class="campaign-card">
                    <div class="campaign-header">
                        <i class="campaign-icon <?php echo $campaign['icon']; ?>"></i>
                        <div>
                            <div class="campaign-title"><?php echo htmlspecialchars($campaign['title']); ?></div>
                            <div class="campaign-creator">by <?php echo htmlspecialchars($campaign['creator']); ?></div>
                        </div>
                    </div>

                    <div class="campaign-description">
                        <?php echo htmlspecialchars($campaign['description']); ?>
                    </div>

                    <div class="campaign-stats">
                        <div class="stat-box">
                            <div class="stat-value">$<?php echo number_format($campaign['raised']); ?></div>
                            <div class="stat-label">Raised</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $campaign['backers']; ?></div>
                            <div class="stat-label">Backers</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $campaign['days_left']; ?></div>
                            <div class="stat-label">Days Left</div>
                        </div>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <?php echo $percentage; ?>% of $<?php echo number_format($campaign['goal']); ?> goal
                    </div>

                    <div class="campaign-actions">
                        <a href="signup/index.php" class="btn btn-primary">
                            <i class="fas fa-hand-holding-usd"></i> Support This
                        </a>
                        <a href="login.php" class="btn btn-secondary">
                            <i class="fas fa-sign-in-alt"></i> Login to Back
                        </a>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</body>
</html>
