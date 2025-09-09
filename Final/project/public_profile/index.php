<?php
require_once '../shared/includes/session.php';
require_once '../shared/includes/functions.php';

// Get user ID from URL parameter
$profileUserId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($profileUserId <= 0) {
    $error = "Invalid user ID provided.";
} else {
    $userManager = new UserManager();
    $profileImageUrl = $userManager->getProfileImageFilename($profileUserId);
    if (!$profileImageUrl) {
        $profileImageUrl = '../images/default-profile.png'; // Fallback to default image
    } else {
        $profileImageUrl = '../uploads/profiles/' . $profileImageUrl; // Construct the path
    }
    // Get the user profile to display
    $profileUser = $userManager->getCompleteUserProfile($profileUserId);
    
    if (!$profileUser) {
        $error = "User not found.";
    }
}

// Get current user for navigation purposes (optional)
$currentUser = null;
try {
    $currentUser = getCurrentUser();
} catch (Exception $e) {
    // User not logged in, which is fine for public profiles
}

// Determine where to redirect back to
$backUrl = '../home/view/index.php'; // Default to home
if ($currentUser) {
    // If user is logged in, determine their role-based dashboard
    switch ($currentUser['role']) {
        case 'admin':
            $backUrl = '../admin/view/index.php';
            break;
        case 'fundraiser':
            $backUrl = '../fundraiser/view/index.php';
            break;
        case 'backer':
            $backUrl = '../backer/view/index.php';
            break;
        default:
            $backUrl = '../home/view/index.php';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($profileUser) ? htmlspecialchars($profileUser['name']) . ' - Profile' : 'User Profile'; ?> | CrowdFund Platform</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../shared/css/profile_manager.css">
</head>
<body>
    <div class="profile-form-container">

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            <div class="header-actions">
                <div class="sub-title">
                    <i class="fas fa-user"></i> View Profile
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <!-- Error State -->
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <!-- Profile Information -->
            <div class="profile-form">
                <!-- Profile Header Section -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($profileUser['name']); ?></h2>
                    <div style="text-align: center; margin-bottom: var(--space-4);">
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                             alt="Profile Picture" 
                             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--white); box-shadow: var(--shadow);">
                    </div>
                    <p class="section-description">
                        <?php 
                        $roleDisplay = ucfirst($profileUser['role']);
                        if ($profileUser['role'] === 'fundraiser') {
                            $roleDisplay = 'Campaign Creator';
                        } elseif ($profileUser['role'] === 'backer') {
                            $roleDisplay = 'Campaign Supporter';
                        }
                        echo $roleDisplay; 
                        ?>
                    </p>
                </div>

                <!-- Basic Information -->
                <div class="form-section">
                    <h2><i class="fas fa-address-card"></i> Contact Information</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">Email Address</div>
                            <div class="stat-value"><?php echo htmlspecialchars($profileUser['email']); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">User ID</div>
                            <div class="stat-value">#<?php echo $profileUser['id']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="form-section">
                    <h2><i class="fas fa-user-cog"></i> Account Information</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">User Role</div>
                            <div class="stat-value"><?php echo ucfirst($profileUser['role']); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Member Since</div>
                            <div class="stat-value"><?php echo date('M d, Y', strtotime($profileUser['created_at'])); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Account Status</div>
                            <div class="stat-value status-<?php echo $profileUser['status']; ?>">
                                <i class="fas fa-circle"></i> <?php echo ucfirst($profileUser['status']); ?>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Email Verified</div>
                            <div class="stat-value">
                                <?php if ($profileUser['email_verified']): ?>
                                    <i class="fas fa-check-circle text-success"></i> Verified
                                <?php else: ?>
                                    <i class="fas fa-clock text-warning"></i> Pending
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role-based Statistics -->
                <?php if ($profileUser['role'] === 'fundraiser'): ?>
                    <?php
                    // Get fundraiser statistics
                    $stats = $userManager->getFundraiserStats($profileUser['id']);
                    $backerStats = $userManager->getBackerStats($profileUser['id']);
                    ?>
                    <div class="form-section">
                        <h2><i class="fas fa-bullhorn"></i> Fundraiser Statistics</h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">Total Campaigns</div>
                                <div class="stat-value"><?php echo $stats['total_campaigns'] ?? 0; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Active Campaigns</div>
                                <div class="stat-value"><?php echo $stats['active_campaigns'] ?? 0; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Funds Raised</div>
                                <div class="stat-value"><?php echo formatCurrency($stats['total_raised'] ?? 0); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total Donated</div>
                                <div class="stat-value"><?php echo formatCurrency($backerStats['total_donated'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($profileUser['role'] === 'backer'): ?>
                    <?php
                    // Get backer statistics
                    $stats = $userManager->getBackerStats($profileUser['id']);
                    ?>
                    <div class="form-section">
                        <h2><i class="fas fa-heart"></i> Supporter Statistics</h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">Campaigns Supported</div>
                                <div class="stat-value"><?php echo $stats['campaigns_supported'] ?? 0; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total Donated</div>
                                <div class="stat-value"><?php echo formatCurrency($stats['total_donated'] ?? 0); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Last Donation</div>
                                <div class="stat-value">
                                    <?php 
                                    if ($stats['last_donation_date']) {
                                        echo date('M d, Y', strtotime($stats['last_donation_date']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Favorite Category</div>
                                <div class="stat-value"><?php echo $stats['favorite_category'] ?? 'None'; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
