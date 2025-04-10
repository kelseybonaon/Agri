<?php
require_once '../../includes/config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = sanitizeInput($_POST['fullname']);
    $farm_size = sanitizeInput($_POST['farm_size']);
    $crop_type = sanitizeInput($_POST['crop_type']);

    $stmt = $conn->prepare("INSERT INTO farmers (user_id, farm_size, crop_type) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $_SESSION['user_id'], $farm_size, $crop_type);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Farmer added successfully!";
    } else {
        $_SESSION['error'] = "Error saving data!";
    }
    header("Location: ../view.php");
}
?>