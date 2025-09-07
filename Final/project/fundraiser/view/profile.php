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
    requireRole('fundraiser');
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
    ?>
    
    <?php 
    renderProfileForm([
        'user' => $fullUser,
        'errors' => $errors,
        'success' => $success,
        'showNameField' => true,
        'backUrl' => 'index.php',
        'formAction' => ''
    ]); 
    ?>
</body>
</html>
