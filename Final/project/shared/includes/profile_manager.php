<?php
require_once '../../shared/includes/session.php';
require_once '../../shared/includes/functions.php';

requireLogin();
$user = getCurrentUser();

$userManager = new UserManager();

// Get complete user profile
$fullUser = $userManager->getCompleteUserProfile($user['id']);

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    $updateData = [];
    
    // Validate name
    if (empty($name)) {
        $errors['name'] = "Name is required";
    } else if (!preg_match("/^[a-zA-Z-' ]+$/", $name)) {
        $errors['name'] = "Name can only contain letters, spaces, apostrophes, and hyphens";
    } else {
        $updateData['name'] = $name;
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    } else {
        $updateData['email'] = $email;
    }
    
    // Bio is optional
    if (strlen($bio) > 500) {
        $errors['bio'] = "Bio cannot exceed 500 characters";
    } else {
        $updateData['bio'] = $bio;
    }
    
    // Handle password change
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        if (empty($currentPassword)) {
            $errors['current_password'] = "Current password is required to change password";
        } else if (!$userManager->verifyCurrentPassword($user['id'], $currentPassword)) {
            $errors['current_password'] = "Current password is incorrect";
        }
        
        if (empty($newPassword)) {
            $errors['new_password'] = "New password is required";
        } else if (strlen($newPassword) < 6) {
            $errors['new_password'] = "Password must be at least 6 characters long";
        }
        
        if (empty($confirmPassword)) {
            $errors['confirm_password'] = "Please confirm your new password";
        } else if ($newPassword !== $confirmPassword) {
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

function renderProfileForm($config) {
    $user = $config['user'];
    $errors = $config['errors'] ?? [];
    $success = $config['success'] ?? '';
    $showNameField = $config['showNameField'] ?? true; // Admin doesn't have name field
    $backUrl = $config['backUrl'] ?? 'index.php';
    $formAction = $config['formAction'] ?? '';
    
    ?>
    
    <div class="profile-form-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="<?php echo $backUrl; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h1><i class="fas fa-user-edit"></i> Manage Profile</h1>
            </div>
            <div class="header-actions">
                <span class="user-role">
                    <i class="fas fa-<?php echo getRoleIcon($user['role']); ?>"></i>
                    <?php echo ucfirst($user['role']); ?> Account
                </span>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo $formAction; ?>" class="profile-form">
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
                        <div class="stat-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Account Status</div>
                        <div class="stat-value status-<?php echo $user['status']; ?>">
                            <i class="fas fa-circle"></i> <?php echo ucfirst($user['status']); ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Email Verified</div>
                        <div class="stat-value">
                            <?php if ($user['email_verified']): ?>
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
                <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">
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
}

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
