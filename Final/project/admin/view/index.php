<?php
require_once '../../includes/session.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../../shared/libs/chart.min.js">
</head>
<body>
   <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-hand-holding-heart"></i> Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
                    <a href="../../includes/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
