<?php
// Session management with conflict prevention
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agriculture_db');

// Security constants
define('CSRF_TOKEN_LIFE', 3600); // 1 hour

class Database {
    private static $instance = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$instance->connect_error) {
                error_log("Database connection failed: " . self::$instance->connect_error);
                die("System maintenance in progress. Please try again later.");
            }
            self::$instance->set_charset("utf8mb4");
        }
        return self::$instance;
    }
}

// CSRF Protection with fallback
function generateCSRFToken() {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    } else {
        throw new Exception("No secure random number generator available");
    }
    $_SESSION['csrf_token_time'] = time();
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $token) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFE) {
        error_log("CSRF token validation failed");
        return false;
    }
    return true;
}

// Secure input sanitization
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    try {
        generateCSRFToken();
    } catch (Exception $e) {
        error_log("CSRF token generation failed: " . $e->getMessage());
        die("System security configuration error. Contact administrator.");
    }
}
?>