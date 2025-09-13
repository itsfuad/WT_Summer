<?php

// Database configuration
$host = 'localhost';
$username = 'root';
$db_password = '';
$database = 'crowdfund_db';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    $pdo->exec("USE $database");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
