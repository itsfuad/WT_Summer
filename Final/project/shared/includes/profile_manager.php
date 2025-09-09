<?php
require_once 'session.php';
require_once 'functions.php';
require_once 'upload_manager.php';

requireLogin();

$user = getCurrentUser();

$userManager = new UserManager();
$uploadManager = new UploadManager();

// Get complete user profile
$fullUser = $userManager->getCompleteUserProfile($user['id']);

$success = '';
$errors = [];
$showNameField = TRUE; // Default to showing name field

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
                        value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" 
                        placeholder="Enter your full name" required>
                <?php if (isset($errors['name'])): ?>
                    <span class="error-message"><?php echo $errors['name']; ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" 
                    value="<?php echo htmlspecialchars($user['email']); ?>" 
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
                <div class="stat-value">#<?php echo $user['id']; ?></div>
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

<script>
function toggleAllPasswords() {
    const passwordFields = document.querySelectorAll('.password-field');
    const toggleIcon = document.getElementById('password-toggle');
    
    const isHidden = passwordFields[0].type === 'password';
    
    passwordFields.forEach(field => {
        field.type = isHidden ? 'text' : 'password';
    });
    
    if (isHidden) {
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>
<?php

function getRoleIcon($role) {
    switch ($role) {
        case 'admin':
            return 'shield-alt';
        case 'fundraiser':
            return 'lightbulb';
        case 'backer':
            return 'hand-holding-heart';
        default:
            return 'user';
    }
}
?>

<script>
function removeProfileImage() {
    // Set the hidden field to indicate removal
    document.getElementById('remove_profile_image').value = '1';
    
    // Immediately update the UI to show placeholder state
    updateProfileImageUI(true);
    
    // Show removal feedback toast
    showToast('Image removed', 'success');
}

function updateProfileImageUI(isRemoved) {
    const container = document.querySelector('.profile-upload-container');
    const preview = document.getElementById('profile-preview');
    const overlay = container.querySelector('.upload-overlay');
    let placeholder = container.querySelector('.upload-placeholder');
    const removeButton = container.parentElement.querySelector('.upload-actions');
    
    if (isRemoved) {
        // Hide the current image and overlay
        if (preview) {
            preview.style.display = 'none';
        }
        if (overlay) {
            overlay.style.display = 'none';
        }
        
        // Show placeholder - create if it doesn't exist
        if (!placeholder) {
            placeholder = document.createElement('div');
            placeholder.className = 'upload-placeholder';
            placeholder.innerHTML = `
                <i class="fas fa-user"></i>
                <span>Click to Upload</span>
            `;
            container.appendChild(placeholder);
        }
        
        // Ensure placeholder is visible
        placeholder.style.display = 'flex';
        
        // Hide the remove button
        if (removeButton) {
            removeButton.style.display = 'none';
        }
        
        // Clear the file input
        const fileInput = document.getElementById('profile_image');
        if (fileInput) {
            fileInput.value = '';
        }
    }
}

function showToast(message, type = 'success') {
    // Remove any existing toasts
    const existingToast = document.querySelector('.profile-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = 'profile-toast';
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';
    const bgColor = type === 'success' ? '#10b981' : '#3b82f6';
    
    toast.innerHTML = `
        <i class="${icon}"></i>
        ${message}
    `;
    
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        animation: slideIn 0.3s ease-out;
        max-width: 300px;
    `;
    
    // Add animation styles if not already present
    if (!document.querySelector('#toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Remove the toast after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

function previewImage(input, type) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const container = document.querySelector('.profile-upload-container');
            if (!container) {
                console.error('Profile upload container not found');
                return;
            }
            
            const previewId = type + '-preview';
            let preview = document.getElementById(previewId);
            
            // Hide placeholder when image is loaded
            const placeholder = container.querySelector('.upload-placeholder');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
            
            // Create preview image if it doesn't exist
            if (!preview) {
                preview = document.createElement('img');
                if (!preview) {
                    alert('Failed to create img element!');
                    return;
                }
                preview.id = previewId;
                preview.className = 'profile-preview';
                preview.alt = 'Profile picture';
                preview.style.width = '100%';
                preview.style.height = '100%';
                preview.style.objectFit = 'cover';
                container.appendChild(preview);
            }
            
            // Set the image - double check preview exists
            if (!preview) {
                alert('Preview is null before setting src!');
                return;
            }
            preview.src = e.target.result;
            preview.style.display = 'block';
            
            // Create/show overlay
            let overlay = container.querySelector('.upload-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'upload-overlay';
                overlay.innerHTML = `
                    <i class="fas fa-camera"></i>
                    <span>Change Photo</span>
                `;
                container.appendChild(overlay);
            }
            overlay.style.display = 'flex';
            
            // Create/show remove button
            let actionsContainer = container.parentElement.querySelector('.upload-actions');
            if (!actionsContainer) {
                // Create the actions container if it doesn't exist
                actionsContainer = document.createElement('div');
                actionsContainer.className = 'upload-actions';
                
                const uploadInfo = container.parentElement.querySelector('.upload-info');
                if (uploadInfo) {
                    container.parentElement.insertBefore(actionsContainer, uploadInfo);
                } else {
                    container.parentElement.appendChild(actionsContainer);
                }
            }
            
            // Add remove button if it doesn't exist
            if (!actionsContainer.querySelector('button')) {
                actionsContainer.innerHTML = `
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeProfileImage()">
                        <i class="fas fa-trash"></i> Remove Photo
                    </button>
                `;
            }
            actionsContainer.style.display = 'flex';
            
            // Reset the removal flag since user is uploading a new image
            const removeFlag = document.getElementById('remove_profile_image');
            if (removeFlag) {
                removeFlag.value = '0';
            }
            
            // Show success toast
            showToast('Image selected', 'success');
        };
        
        reader.readAsDataURL(file);
    }
}
</script>
