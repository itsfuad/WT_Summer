<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../shared/css/profile_manager.css">
</head>
<body>
    <?php
    require_once '../../includes/session.php';
    require_once '../../includes/functions.php';
    require_once '../../shared/includes/profile_manager.php';
    
    requireLogin();
    requireRole('admin');
    $user = getCurrentUser();
    
    $userManager = new UserManager();
    
    // Get complete user profile
    $fullUser = $userManager->getCompleteUserProfile($user['id']);
    
    $success = '';
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        $updateData = [];
        
        // Validate email
        if (empty($email)) {
            $errors['email'] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format";
        } else {
            $updateData['email'] = $email;
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
    
    <?php
    renderProfileForm([
        'user' => $fullUser,
        'errors' => $errors,
        'success' => $success,
        'showNameField' => false, // Admin doesn't have name field
        'backUrl' => 'index.php',
        'formAction' => ''
    ]);
    ?>
</body>
</html>
