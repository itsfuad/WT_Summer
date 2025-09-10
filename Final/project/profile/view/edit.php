<?php
require_once '../../shared/includes/session.php';
require_once '../../shared/includes/functions.php';
require_once '../../shared/includes/upload_manager.php';

requireLogin();

$user = getCurrentUser();

$userManager = new UserManager();
$uploadManager = new UploadManager();

// Get complete user profile
$fullUser = $userManager->getCompleteUserProfile($user['id']);

$success = '';
$errors = [];
// dont show name field for 'admin' role
$showNameField = ($user['role'] !== 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    $updateData = [];
    
    // Validate name (if the user role allows name change)
    if (isset($_POST['name'])) {
        if (empty($name)) {
            $errors['name'] = "Name is required";
        } else if (!preg_match("/^[a-zA-Z-' ]+$/", $name)) {
            $errors['name'] = "Name can only contain letters, spaces, apostrophes, and hyphens";
        } else {
            $updateData['name'] = $name;
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    } else {
        $updateData['email'] = $email;
    }
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = $uploadManager->uploadProfileImage($_FILES['profile_image'], $user['id']);
        if ($uploadResult['success']) {
            $imageUpdateResult = $userManager->updateProfileImage($user['id'], $uploadResult['filename']);
            if ($imageUpdateResult['success']) {
                // Refresh user data immediately after successful image upload
                $fullUser = $userManager->getCompleteUserProfile($user['id']);
            } else {
                $errors['profile_image'] = $imageUpdateResult['error'];
            }
        } else {
            $errors['profile_image'] = $uploadResult['message'];
        }
    }
    
    // Handle profile image removal
    if (isset($_POST['remove_profile_image']) && $_POST['remove_profile_image'] === '1') {
        $removeResult = $userManager->removeProfileImage($user['id']);
        if ($removeResult['success']) {
            // Refresh user data immediately after successful image removal
            $fullUser = $userManager->getCompleteUserProfile($user['id']);
        } else {
            $errors['profile_image'] = $removeResult['error'];
        }
    }
    
    // Handle password change
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        if (empty($currentPassword)) {
            $errors['current_password'] = "Current password is required to change password";
        } elseif (!$userManager->verifyCurrentPassword($user['id'], $currentPassword)) {
            $errors['current_password'] = "Current password is incorrect";
        }
        
        if (empty($newPassword)) {
            $errors['new_password'] = "New password is required";
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = "Password must be at least 6 characters long";
        }
        
        if (empty($confirmPassword)) {
            $errors['confirm_password'] = "Please confirm your new password";
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = "Passwords do not match";
        }
        
        if (empty($errors['current_password']) && empty($errors['new_password']) && empty($errors['confirm_password'])) {
            $updateData['password'] = $newPassword;
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        $result = $userManager->updateProfile($user['id'], $updateData);
        
        if (isset($result['success'])) {
            $success = "Profile updated successfully!";
            
            // Update session data
            if (isset($updateData['name'])) {
                $_SESSION['user_name'] = $updateData['name'];
            }
            if (isset($updateData['email'])) {
                $_SESSION['user_email'] = $updateData['email'];
            }
            
            // Refresh user data
            $fullUser = $userManager->getCompleteUserProfile($user['id']);
        } else {
            $errors['general'] = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../../shared/css/upload.css">
    <script src="../js/edit.js"></script>
</head>

<div class="profile-form-container">
<!-- Header -->
<div class="header">
    <div class="header-left">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    <div class="header-actions">
        <div class="sub-title">
            <i class="fas fa-user-edit"></i> Manage Profile
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<form method="POST" action="" class="profile-form" enctype="multipart/form-data">
    <!-- Account Information -->
    <div class="form-section">
        <h2><i class="fas fa-user"></i> Account Information</h2>
        
        <?php if ($showNameField): ?>
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" 
                        value="<?php echo htmlspecialchars($fullUser['name'] ?? ''); ?>" 
                        placeholder="Enter your full name" required>
                <?php if (isset($errors['name'])): ?>
                    <span class="error-message"><?php echo $errors['name']; ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" 
                    value="<?php echo htmlspecialchars($fullUser['email']); ?>" 
                    placeholder="Enter your email address" required>
            <?php if (isset($errors['email'])): ?>
                <span class="error-message"><?php echo $errors['email']; ?></span>
            <?php endif; ?>
            <small>This email is used for login and notifications</small>
        </div>
    </div>

    <!-- Profile Image -->
    <div class="form-section">
        <h2><i class="fas fa-camera"></i> Profile Picture</h2>
        <div class="upload-section">
            <div class="image-upload-container profile-upload-container" onclick="document.getElementById('profile_image').click()">
                <?php 
                global $uploadManager;
                if (!$uploadManager) $uploadManager = new UploadManager();
                $profileImageUrl = $uploadManager->getImageUrl('profile', $fullUser['profile_image']); 
                ?>
                
                <?php if ($fullUser['profile_image']): ?>
                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                            alt="Profile picture" 
                            class="profile-preview" id="profile-preview">
                    <div class="upload-overlay">
                        <i class="fas fa-camera"></i>
                        <span>Change Photo</span>
                    </div>
                <?php else: ?>
                    <div class="upload-placeholder">
                        <i class="fas fa-user"></i>
                        <span>Click to Upload</span>
                    </div>
                <?php endif; ?>
                
                <input type="file" id="profile_image" name="profile_image" 
                        accept="image/jpeg,image/jpg,image/png,image/webp" 
                        class="upload-input"
                        onchange="previewImage(this, 'profile')">
            </div>
            
            <div class="upload-actions">
                <?php if ($fullUser['profile_image']): ?>
                    <button type="button" class="btn btn-secondary" onclick="removeProfileImage()">
                        <i class="fas fa-trash"></i> Remove Photo
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="upload-info">
                <small>Max size: 10MB. Formats: JPG, PNG, WebP</small>
                <small>Recommended: 400x400px square image</small>
            </div>
            
            <?php if (isset($errors['profile_image'])): ?>
                <span class="error-message"><?php echo $errors['profile_image']; ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Hidden input for profile image removal -->
        <input type="hidden" id="remove_profile_image" name="remove_profile_image" value="0">
    </div>

    <!-- Password Change -->
    <div class="form-section">
        <h2><i class="fas fa-lock"></i> Change Password</h2>
        <p class="section-description">Leave blank to keep your current password</p>
        
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <div class="password-container">
                <input type="password" id="current_password" name="current_password" 
                        placeholder="Enter your current password" class="password-field">
                <i class="fas fa-eye password-toggle" onclick="toggleAllPasswords()" id="password-toggle"></i>
            </div>
            <?php if (isset($errors['current_password'])): ?>
                <span class="error-message"><?php echo $errors['current_password']; ?></span>
            <?php endif; ?>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" 
                        placeholder="Enter new password" class="password-field">
                <?php if (isset($errors['new_password'])): ?>
                    <span class="error-message"><?php echo $errors['new_password']; ?></span>
                <?php endif; ?>
                <small>Password must be at least 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                        placeholder="Confirm new password" class="password-field">
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Account Statistics (Read-only) -->
    <div class="form-section">
        <h2><i class="fas fa-chart-bar"></i> Account Statistics</h2>
        
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label">Member Since</div>
                <div class="stat-value"><?php echo date('M d, Y', strtotime($fullUser['created_at'])); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Account Status</div>
                <div class="stat-value status-<?php echo $fullUser['status']; ?>">
                    <i class="fas fa-circle"></i> <?php echo ucfirst($fullUser['status']); ?>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Email Verified</div>
                <div class="stat-value">
                    <?php if ($fullUser['email_verified']): ?>
                        <i class="fas fa-check-circle text-success"></i> Verified
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle text-warning"></i> Pending
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">User ID</div>
                <div class="stat-value">#<?php echo $fullUser['id']; ?></div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
</form>
</div>