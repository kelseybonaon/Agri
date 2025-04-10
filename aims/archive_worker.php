<?php
// archive_worker.php
session_start();
require_once __DIR__ . '/includes/config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database connection based on your Database class implementation
        $conn = Database::getInstance();

        // If your Database class returns the connection directly:
        if (!($conn instanceof mysqli)) {
            throw new Exception("Failed to establish database connection");
        }

        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("CSRF token verification failed");
        }

        // Validate worker ID
        $worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
        if (!$worker_id || $worker_id <= 0) {
            throw new Exception("Invalid worker ID");
        }

        // Check worker status
        $check_stmt = $conn->prepare("SELECT is_archived FROM workers WHERE id = ?");
        if ($check_stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $check_stmt->bind_param("i", $worker_id);
        if (!$check_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_stmt->error);
        }

        $result = $check_stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Worker not found");
        }

        $worker = $result->fetch_assoc();
        if ($worker['is_archived']) {
            throw new Exception("Worker already archived");
        }

        // Archive worker using transaction for safety
        $conn->begin_transaction();

        try {
            // Archive worker
// In your UPDATE query, modify to match actual column names:
            $archive_stmt = $conn->prepare("UPDATE workers 
    SET 
        is_archived = 1, 
        archived_at = NOW(), 
        archived_by = ?
    WHERE id = ?");
            if ($archive_stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $archive_stmt->bind_param("ii", $_SESSION['user_id'], $worker_id);
            if (!$archive_stmt->execute()) {
                throw new Exception("Failed to archive worker: " . $archive_stmt->error);
            }

            $conn->commit();
            $_SESSION['success_message'] = "Worker archived successfully";
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    } finally {
        header("Location: workers.php");
        exit();
    }
} else {
    header("Location: workers.php");
    exit();
}