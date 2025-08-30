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
            if (empty($_POST["password"])) {
                $passwordErr = "Password is required";
            } else {
                $password = trim($_POST["password"]);
                if (strlen($password) < 6) {
                    $passwordErr = "Password must be at least 6 characters";
                }
            }

            // If no errors, check login
            if (empty($emailErr) && empty($passwordErr)) {
                
                $users = array(
                    "admin@crowdfund.com" => array("password" => "admin123", "role" => "admin", "name" => "Fuad Hasan"),
                    "fundraiser@crowdfund.com" => array("password" => "fundraiser123", "role" => "fundraiser", "name" => "Sakib Samad"),
                    "backer@crowdfund.com" => array("password" => "backer123", "role" => "backer", "name" => "Mahtab Habib")
                );

                // Check if user exists and password matches
                if (isset($users[$email]) && $users[$email]["password"] == $password) {
                    $userRole = $users[$email]["role"];
                    $userName = $users[$email]["name"];
                    $loginSuccess = "Login successful! Welcome " . $userName;
                } else {
                    $loginErr = "Invalid email or password";
                }
            }
        }
        ?>

        <?php if (!empty($loginSuccess)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $loginSuccess; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" 
                       class="<?php echo !empty($emailErr) ? 'error' : ''; ?>">
                <?php if (!empty($emailErr)): ?>
                    <span class="error-message"><?php echo $emailErr; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password"
                           class="<?php echo !empty($passwordErr) ? 'error' : ''; ?>">
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
</body>
</html>
