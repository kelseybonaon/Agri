<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';

// Verify CSRF token if it exists in POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        error_log("CSRF token validation failed during logout");
        $_SESSION['error'] = "Security token invalid. Please try again.";
        header("Location: ../../index.php");
        exit();
    }
}

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to login page
header("Location: ../../index.php");
exit();
?>