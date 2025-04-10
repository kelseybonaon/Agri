<?php
// restore_worker.php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = Database::getInstance();

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            throw new Exception("CSRF token verification failed");
        }

        $worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
        if (!$worker_id) {
            throw new Exception("Invalid worker ID");
        }

        // Restore worker
        $restore_stmt = $conn->prepare("UPDATE workers 
                                      SET is_archived = 0, 
                                          archived_at = NULL, 
                                          archived_by = NULL 
                                      WHERE id = ?");
        $restore_stmt->bind_param("i", $worker_id);

        if (!$restore_stmt->execute()) {
            throw new Exception("Failed to restore worker: " . $conn->error);
        }

        $_SESSION['success_message'] = "Worker restored successfully";

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    } finally {
        header("Location: archived_workers.php");
        exit();
    }
} else {
    header("Location: archived_workers.php");
    exit();
}