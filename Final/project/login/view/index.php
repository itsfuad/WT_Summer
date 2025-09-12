<?php
    require_once '../../shared/includes/session.php';
    requireNoLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crowdfunding Platform - Login</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-hand-holding-usd"></i> CrowdFund</h1>
            <p>Login to your account</p>
        </div>

        <?php
        // Check for signup success parameter
        $signupSuccess = "";
        if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
            $signupSuccess = "Account created successfully! You can now login with your credentials.";
        }
        
        // Check for password reset success parameter
        $resetSuccess = "";
        if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
            $resetSuccess = "Password reset successfully! You can now login with your new password.";
        }
        
        // Define variables
        $email = $password = "";
        $emailErr = $passwordErr = $loginErr = "";
        $loginSuccess = "";

        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            
            // Validate email
            if (empty($_POST["email"])) {
                $emailErr = "Email is required";
            } else {
                $email = trim($_POST["email"]);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailErr = "Invalid email format";
                }
            }

            // Validate password
            $password = trim($_POST["password"]);
            if (empty($password)) {
                $passwordErr = "Password is required";
            } elseif (strlen($password) < 8) {
                $passwordErr = "Password must be at least 8 characters long";
            }

            // If no errors, check login
            if (empty($emailErr) && empty($passwordErr)) {
                
                // Authenticate using database
                require_once '../../shared/includes/functions.php';
                require_once '../../shared/includes/session.php';
                $userManager = new UserManager();

                $user = $userManager->authenticate($email, $password);
                
                if ($user) {
                    // Start session and store user data
                    loginUser($user);
                    // Redirect based on user role
                    $redirectUrl = redirectBasedOnRole();
                    header("Location: $redirectUrl");
                    exit();
                } else {
                    $loginErr = "Invalid email or password";
                }
            }
        }
        ?>

        <?php if (!empty($signupSuccess)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $signupSuccess; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($resetSuccess)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $resetSuccess; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($loginSuccess)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $loginSuccess; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>"
                       class="form-control <?php echo !empty($emailErr) ? 'error' : ''; ?>">
                <?php if (!empty($emailErr)): ?>
                    <span class="error-message"><?php echo $emailErr; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password"
                           class="form-control <?php echo !empty($passwordErr) ? 'error' : ''; ?>">
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password')" id="password-toggle"></i>
                </div>
                <?php if (!empty($passwordErr)): ?>
                    <span class="error-message"><?php echo $passwordErr; ?></span>
                <?php endif; ?>
            </div>            <?php if (!empty($loginErr)): ?>
                <div style="color: #ff4757; text-align: center; margin-bottom: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $loginErr; ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="submit-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="forgot-password-link">
            <a href="../../forgot_password/view/index.php">
                <i class="fas fa-key"></i> Forgot Password?
            </a>
        </div>

        <div class="signup-links">
            <p>Don't have an account?</p>
            <div class="role-links">
                <a href="../../signup/view/index.php" class="role-link fundraiser-link">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
                <a href="../../home/view/index.php" class="role-link guest-link">
                    <i class="fas fa-eye"></i> Browse as Guest
                </a>
            </div>
        </div>
    </div>
    
    <script src="../js/script.js"></script>
</body>
</html>
