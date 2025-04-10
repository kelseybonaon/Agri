<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';

// Initialize error message
$_SESSION['error'] = '';

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verify CSRF token if you have it implemented
        if (isset($_POST['csrf_token']) && !verifyCSRFToken($_POST['csrf_token'])) {
            throw new Exception("Security token validation failed");
        }

        // Validate inputs
        if (empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Email and password are required");
        }

        $email = sanitizeInput($_POST['email']);
        $password = sanitizeInput($_POST['password']);

        // Get database connection
        $conn = Database::getInstance();
        
        // Prepare statement
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Login failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Clear any existing error
                unset($_SESSION['error']);
                
                // Redirect to dashboard
                header("Location: ../../dashboard.php");
                exit();
            }
        }
        
        // If we get here, credentials were invalid
        throw new Exception("Invalid email or password");
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Login error: " . $e->getMessage());
    
    // Set user-friendly error message
    $_SESSION['error'] = "Login failed. Please try again.";
    
    // Redirect back to login page
    header("Location: /index.php");
    exit();
}

// If not a POST request, redirect to login
header("Location: /index.php");
exit();
?>