<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .dashboard { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .welcome { color: #333; }
        .logout { float: right; color: #dc3545; text-decoration: none; }
        .logout:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php
    require_once '../../includes/session.php';
    requireLogin();
    requireRole('admin');
    $user = getCurrentUser();
    ?>
    
    <div class="dashboard">
        <div class="header">
            <h1 class="welcome"><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
            <a href="../../includes/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <p>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px;">
            <h2>Admin Panel</h2>
            <p>This is the admin dashboard. You can manage funds, users, and view reports here.</p>
            <p><strong>Coming soon:</strong> Admin features will be implemented next.</p>
        </div>
    </div>
</body>
</html>
