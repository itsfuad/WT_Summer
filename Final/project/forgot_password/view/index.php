<?php

session_start();

require_once '../../config/database.php';
require_once '../../config/email.php';

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($action === 'send_otp') {
            $email = trim($_POST['email'] ?? '');
            
            // Validate email
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Please enter a valid email address';
                echo json_encode($response);
                exit;
            }
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if (!$stmt->fetch()) {
                $response['message'] = 'No account found with this email address';
                echo json_encode($response);
                exit;
            }
            
            // Check rate limiting - allow resend only after 60 seconds
            $stmt = $pdo->prepare("SELECT created_at FROM password_reset_tokens WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $response['message'] = 'Please wait 60 seconds before requesting another OTP';
                echo json_encode($response);
                exit;
            }
            
            // Clean old tokens
            $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ? OR expires_at < NOW()")->execute([$email]);
            
            // Generate OTP
            $otp = sprintf('%06d', random_int(100000, 999999));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Save to database
            $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $otp, $expiresAt]);
            
            // Set session
            $_SESSION['reset_email'] = $email;
            
            // Send email using SMTP
            $emailManager = new EmailManager();
            $emailSent = $emailManager->sendOTP($email, $otp);
            
            $response['success'] = true;
            if ($emailSent) {
                $response['message'] = "OTP sent to your email successfully! Check your inbox.";
            } else {
                $response['message'] = "OTP generated but email sending failed. Please try again or contact support.";
            }
            $response['step'] = 2;
            
        } elseif ($action === 'verify_otp') {
            $otp = trim($_POST['otp'] ?? '');
            
            if (!isset($_SESSION['reset_email'])) {
                $response['message'] = 'Session expired. Please start over.';
                echo json_encode($response);
                exit;
            }
            
            $email = $_SESSION['reset_email'];
            
            // Validate OTP format
            if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
                $response['message'] = 'Please enter a valid 6-digit OTP';
                echo json_encode($response);
                exit;
            }
            
            // Check OTP
            $stmt = $pdo->prepare("SELECT id, token, expires_at FROM password_reset_tokens WHERE email = ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$email]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                $response['message'] = 'No valid token found. Please request a new OTP.';
                echo json_encode($response);
                exit;
            }
            
            if ($otp !== $tokenData['token']) {
                $response['message'] = 'Invalid OTP. Please check and try again.';
                echo json_encode($response);
                exit;
            }
            
            if (strtotime($tokenData['expires_at']) <= time()) {
                $response['message'] = 'OTP has expired. Please request a new one.';
                echo json_encode($response);
                exit;
            }
            
            // Mark token as used
            $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?")->execute([$tokenData['id']]);
            
            $_SESSION['reset_verified'] = true;
            
            $response['success'] = true;
            $response['message'] = 'OTP verified successfully!';
            $response['step'] = 3;
            
        } elseif ($action === 'reset_password') {
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            
            if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_verified'])) {
                $response['message'] = 'Session expired. Please start over.';
                echo json_encode($response);
                exit;
            }
            
            if (empty($newPassword) || empty($confirmPassword)) {
                $response['message'] = 'Please fill in all fields';
                echo json_encode($response);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                $response['message'] = 'Passwords do not match';
                echo json_encode($response);
                exit;
            }
            
            if (strlen($newPassword) < 8) {
                $response['message'] = 'Password must be at least 8 characters long';
                echo json_encode($response);
                exit;
            }
            
            $email = $_SESSION['reset_email'];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            
            // Clean up
            $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?")->execute([$email]);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);
            
            $response['success'] = true;
            $response['message'] = 'Password reset successfully! Redirecting to login...';
            $response['redirect'] = '../../login/view/index.php?reset=success';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'An error occurred. Please try again.';
    }
    
    echo json_encode($response);
    exit;
}

// Clear session when accessing the page fresh
unset($_SESSION['reset_email']);
unset($_SESSION['reset_verified']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../shared/css/common.css">
    <link rel="stylesheet" href="../../login/css/style.css">
    <script src="../js/script.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-key"></i> Reset Password</h1>
            <p id="step-description">Enter your email address to receive an OTP</p>
        </div>

        <div id="message-area" style="margin-bottom: 20px;"></div>

        <!-- Step 1: Email Input -->
        <div id="step1" class="step-content">
            <form id="emailForm">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                    <span class="error-message" id="email-error" style="display: none;"></span>
                </div>
                <button type="submit" class="submit-btn" id="sendOtpBtn">
                    <span class="btn-text"><i class="fas fa-paper-plane"></i> Send OTP</span>
                    <span class="btn-loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Sending...</span>
                </button>
            </form>
        </div>

        <!-- Step 2: OTP Verification -->
        <div id="step2" class="step-content" style="display: none;">
            <div style="text-align: center; margin-bottom: 20px;">
                <p><strong>Email:</strong> <span id="user-email"></span></p>
            </div>
            <form id="otpForm">
                <div class="form-group">
                    <label for="otp"><i class="fas fa-shield-alt"></i> Enter 6-digit OTP:</label>
                    <input type="text" id="otp" name="otp" class="form-control" maxlength="6" pattern="[0-9]{6}"
                           style="text-align: center; font-size: 18px; letter-spacing: 3px;" required>
                    <span class="error-message" id="otp-error" style="display: none;"></span>
                </div>
                <button type="submit" class="submit-btn" id="verifyOtpBtn">
                    <span class="btn-text"><i class="fas fa-check"></i> Verify OTP</span>
                    <span class="btn-loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Verifying...</span>
                </button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <button id="resendBtn" onclick="resendOTP()" style="background: none; border: none; color: #007bff; text-decoration: underline; cursor: pointer;">
                    <i class="fas fa-redo"></i> Resend OTP
                </button>
                <div id="resendTimer" style="display: none; color: #666; font-size: 14px; margin-top: 10px;">
                    <i class="fas fa-clock"></i> Resend available in <span id="countdown">60</span> seconds
                </div>
            </div>
        </div>

        <!-- Step 3: New Password -->
        <div id="step3" class="step-content" style="display: none;">
            <form id="passwordForm">
                <div class="form-group">
                    <label for="new_password"><i class="fas fa-lock"></i> New Password:</label>
                    <div class="password-container">
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswords()" id="password-toggle"></i>
                    </div>
                    <span class="error-message" id="new_password-error" style="display: none;"></span>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                    <span class="error-message" id="confirm_password-error" style="display: none;"></span>
                </div>
                <button type="submit" class="submit-btn" id="resetPasswordBtn">
                    <span class="btn-text"><i class="fas fa-save"></i> Reset Password</span>
                    <span class="btn-loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Updating...</span>
                </button>
            </form>
        </div>

        <div class="signup-links">
            <a href="../../login/view/index.php" style="color: #666; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>
