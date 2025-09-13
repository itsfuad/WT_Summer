<?php

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function loginUser(array $user, bool $rememberMe = false): void {
    startSession();

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['logged_in']  = true;

    if ($rememberMe) {
        setRememberMeCookie($user);
    }
}

function setRememberMeCookie(array $user): void {
    $token = bin2hex(random_bytes(32));

    $rememberData = [
        'user_id' => $user['id'],
        'token'   => $token,
        'expires' => time() + (30 * 24 * 60 * 60) // 30 days
    ];

    setcookie(
        'remember_me',
        base64_encode(json_encode($rememberData)),
        $rememberData['expires'],
        '/',
        '',
        false,
        true // HTTPOnly
    );
}

function checkRememberMeCookie(): bool {
    static $checking = false;

    if ($checking) {
        return false; // prevent recursion
    }
    $checking = true;

    startSession();

    if (!empty($_COOKIE['remember_me']) && empty($_SESSION['logged_in'])) {
        $rememberData = json_decode(base64_decode($_COOKIE['remember_me']), true);

        if (is_array($rememberData) && $rememberData['expires'] > time()) {
            require_once '../../config/database.php';
            global $pdo; // ✅ make $pdo visible inside this function

            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$rememberData['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    loginUser($user, false);
                    $checking = false;
                    return true;
                }
            } catch (Exception $e) {
                clearRememberMeCookie();
            }
        } else {
            clearRememberMeCookie();
        }
    }

    $checking = false;
    return false;
}


function clearRememberMeCookie(): void {
    setcookie('remember_me', '', time() - 3600, '/');
}

function logoutUser(): void {
    startSession();

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        setcookie(session_name(), '', time() - 42000, '/');
    }

    clearRememberMeCookie();

    session_destroy();
}


function isLoggedIn(): bool {
    startSession();

    if (!empty($_SESSION['logged_in'])) {
        return true;
    }

    return checkRememberMeCookie();
}

function getCurrentUser(): ?array {
    startSession();

    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role'  => $_SESSION['user_role'] ?? null
    ];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ../../login/view/index.php');
        exit();
    }
}

function requireRole(string $role): void {
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        header('Location: ../../home/view/index.php');
        exit();
    }
}

function requireNoLogin(): void {
    if (isLoggedIn()) {
        $redirectUrl = redirectBasedOnRole();
        header("Location: $redirectUrl");
        exit();
    }
}

function redirectBasedOnRole(): string {
    $user = getCurrentUser();
    if (!$user) {
        return '../../home/view/index.php';
    }
  
    $role = $user['role'];
    
    return "../../$role/view/index.php";
}