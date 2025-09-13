<?php
// Simple session management functions
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function loginUser($user, $rememberMe = false) {
    startSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Set remember me cookie if requested
    if ($rememberMe) {
        setRememberMeCookie($user);
    }
}

function setRememberMeCookie($user) {
    // Create a unique token for this login session
    $token = bin2hex(random_bytes(32));
    
    // Store token with user info (you might want to store this in database for production)
    $rememberData = [
        'user_id' => $user['id'],
        'token' => $token,
        'expires' => time() + (30 * 24 * 60 * 60) // 30 days
    ];
    
    // Set cookie for 30 days
    setcookie('remember_me', base64_encode(json_encode($rememberData)), time() + (30 * 24 * 60 * 60), '/');
}

function checkRememberMeCookie() {
    if (isset($_COOKIE['remember_me']) && !isLoggedIn()) {
        $rememberData = json_decode(base64_decode($_COOKIE['remember_me']), true);
        
        if ($rememberData && $rememberData['expires'] > time()) {
            // In production, verify token against database
            // For now, we'll just restore the session
            require_once '../../config/database.php';
            
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$rememberData['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Restore session without remember me to avoid infinite loop
                    loginUser($user, false);
                    return true;
                }
            } catch (Exception $e) {
                // Clear invalid cookie
                clearRememberMeCookie();
            }
        } else {
            // Clear expired cookie
            clearRememberMeCookie();
        }
    }
    return false;
}

function clearRememberMeCookie() {
    setcookie('remember_me', '', time() - 3600, '/');
}

function logoutUser() {
    startSession();
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie if it exists
    if (ini_get("session.use_cookies")) {
        setcookie(session_name(), '', time() - 42000);
    }
    
    // Clear remember me cookie
    clearRememberMeCookie();
    
    // Destroy the session
    session_destroy();
}

function isLoggedIn() {
    startSession();
    
    // Check session first
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    
    // Check remember me cookie if session is not active
    return checkRememberMeCookie();
}

function getCurrentUser() {
    startSession();
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../../login/view/index.php');
        exit();
    }
}

function requireRole($role) {
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        header('Location: ../../home/view/index.php');
        exit();
    }
}

function requireNoLogin() {
    if (isLoggedIn()) {
        $redirectUrl = redirectBasedOnRole();
        header("Location: $redirectUrl");
        exit();
    }
}

function redirectBasedOnRole() {
    $user = getCurrentUser();
    if (!$user) {
        return '../../home/view/index.php';
    }

    return "../../" . $user['role'] . "/view/index.php";
}