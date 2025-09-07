<?php
    require_once '../../shared/includes/session.php';
    requireNoLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/script.js"></script>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <h1><i class="fas fa-user-plus"></i> Join CrowdFund</h1>
            <p>Create your account to get started</p>
        </div>

        <?php
        // Define variables
        $name = $email = $password = $confirmPassword = $role = "";
        $nameErr = $emailErr = $passwordErr = $confirmPasswordErr = $roleErr = "";
        $signupSuccess = "";

        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            
            // Validate name
            if (empty($_POST["name"])) {
                $nameErr = "Name is required";
            } else {
                $name = trim($_POST["name"]);
                if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
                    $nameErr = "Only letters and spaces allowed";
                }
            }

            // Validate email
            if (empty($_POST["email"])) {
                $emailErr = "Email is required";
            } else {
                $email = trim($_POST["email"]);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailErr = "Invalid email format";
                }
            }

            // Validate role
            if (empty($_POST["role"])) {
                $roleErr = "Please select your role";
            } else {
                $role = $_POST["role"];
                if ($role != "fundraiser" && $role != "backer") {
                    $roleErr = "Invalid role selected";
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

            // Validate confirm password
            if (empty($_POST["confirmPassword"])) {
                $confirmPasswordErr = "Please confirm password";
            } else {
                $confirmPassword = trim($_POST["confirmPassword"]);
                if ($password != $confirmPassword) {
                    $confirmPasswordErr = "Passwords do not match";
                }
            }

            // If no errors, process signup
            if (empty($nameErr) && empty($emailErr) && empty($roleErr) && empty($passwordErr) && empty($confirmPasswordErr)) {
                // Save to database
                require_once '../../shared/includes/functions.php';
                $userManager = new UserManager();
                
                $userId = $userManager->createUser($name, $email, $password, $role);
                
                if ($userId) {
                    // Redirect to login page with success message
                    header("Location: ../../login/view/index.php?signup=success");
                    exit();
                } else {
                    $emailErr = "Email already exists. Please use a different email.";
                }
                
                // Clear form data
                $name = $email = $password = $confirmPassword = $role = "";
            }
        }
        ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Full Name:</label>
                <input type="text" id="name" name="name" value="<?php echo $name; ?>"
                       class="form-control <?php echo !empty($nameErr) ? 'error' : ''; ?>">
                <?php if (!empty($nameErr)): ?>
                    <span class="error-message"><?php echo $nameErr; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>"
                       class="form-control <?php echo !empty($emailErr) ? 'error' : ''; ?>">
                <?php if (!empty($emailErr)): ?>
                    <span class="error-message"><?php echo $emailErr; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label><i class="fas fa-users"></i> I want to join as:</label>
                <div class="role-selection">
                    <label class="role-option <?php echo ($role == 'fundraiser') ? 'selected' : ''; ?>" onclick="selectRole('fundraiser')">
                        <input type="radio" name="role" value="fundraiser" <?php echo ($role == 'fundraiser') ? 'checked' : ''; ?>>
                        <i class="fas fa-lightbulb fundraiser"></i>
                        <strong>Fundraiser</strong>
                        <small>Raise funds for projects</small>
                    </label>
                    <label class="role-option <?php echo ($role == 'backer') ? 'selected' : ''; ?>" onclick="selectRole('backer')">
                        <input type="radio" name="role" value="backer" <?php echo ($role == 'backer') ? 'checked' : ''; ?>>
                        <i class="fas fa-hand-holding-heart backer"></i>
                        <strong>Backer</strong>
                        <small>Support amazing projects</small>
                    </label>
                </div>
                <?php if (!empty($roleErr)): ?>
                    <span class="error-message"><?php echo $roleErr; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password"
                           class="form-control <?php echo !empty($passwordErr) ? 'error' : ''; ?>">
                    <i class="fas fa-eye password-toggle" onclick="toggleBothPasswords()" id="password-toggle"></i>
                </div>
                <?php if (!empty($passwordErr)): ?>
                    <span class="error-message"><?php echo $passwordErr; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm Password:</label>
                <div class="password-container">
                    <input type="password" id="confirmPassword" name="confirmPassword"
                           class="form-control <?php echo !empty($confirmPasswordErr) ? 'error' : ''; ?>">
                </div>
                <?php if (!empty($confirmPasswordErr)): ?>
                    <span class="error-message"><?php echo $confirmPasswordErr; ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="../../login/view/index.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
