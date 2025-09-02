<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Admin - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-icon">
            <i class="fas fa-user-shield"></i>
        </div>
        
        <h1 class="welcome-title">Welcome Admin!</h1>
        <p class="welcome-message">
            You have successfully logged into the CrowdFund platform with administrator privileges.
        </p>

        <div class="user-info">
            <h3><i class="fas fa-info-circle"></i> Your Information</h3>
            <div class="user-detail">
                <span class="label">Role:</span>
                <span class="value">Administrator</span>
            </div>
            <div class="user-detail">
                <span class="label">Email:</span>
                <span class="value">admin@crowdfund.com</span>
            </div>
            <div class="user-detail">
                <span class="label">Access Level:</span>
                <span class="value">Full System Access</span>
            </div>
            <div class="user-detail">
                <span class="label">Login Time:</span>
                <span class="value"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
        </div>

        <div class="actions">
            <a href="" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
            <a href="" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
