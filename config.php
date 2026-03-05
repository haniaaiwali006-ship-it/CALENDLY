<?php
// config.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'rsoa_rsoa278_19');
define('DB_USER', 'rsoa_rsoa278_19');
define('DB_PASS', '123456');

// Application configuration
define('APP_NAME', 'SchedulePro');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));

// Set default timezone
date_default_timezone_set('UTC');

// Error reporting (disable for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $conn;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Redirect with message
function redirect($url, $message = '') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
    }
    header('Location: ' . $url);
    exit;
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return '';
}
?>
