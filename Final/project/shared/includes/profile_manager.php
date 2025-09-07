<?php
/**
 * Unified Profile Management Component
 * Used across all user types (fundraiser, backer, admin)
 */

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
