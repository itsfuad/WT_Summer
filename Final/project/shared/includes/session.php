<?php
// Simple session management functions
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function loginUser($user) {
    startSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
}

function logoutUser() {
    startSession();
    session_destroy();
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
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

    $role = $user['role'];
    
    return "../../$role/view/index.php";
}
