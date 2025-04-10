<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = Database::getInstance();
$stmt = $conn->prepare("SELECT fullname, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token'])) {
        die("CSRF token missing");
    }

    try {
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            die("CSRF token verification failed");
        }
    } catch (Exception $e) {
        error_log("CSRF token verification error: " . $e->getMessage());
        die("System security configuration error. Contact administrator.");
    }

    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $contactNumber = sanitizeInput($_POST['contact_number']);
    $address = sanitizeInput($_POST['address']);
    $role = sanitizeInput($_POST['role']);
    $skills = sanitizeInput($_POST['skills']);
    $status = 'active';

    if (empty($firstName) || empty($lastName) || empty($role)) {
        $_SESSION['form_error'] = "First name, last name, and role are required fields.";
        $_SESSION['form_data'] = $_POST;
        header("Location: add_worker.php");
        exit();
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO workers (first_name, last_name, contact_number, address, role, skills, status, hire_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->bind_param("sssssss", $firstName, $lastName, $contactNumber, $address, $role, $skills, $status);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Worker added successfully!";
                header("Location: workers.php");
                exit();
            } else {
                $_SESSION['form_error'] = "Error adding worker: " . $conn->error;
                $_SESSION['form_data'] = $_POST;
                header("Location: add_worker.php");
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['form_error'] = "Database error: " . $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            header("Location: add_worker.php");
            exit();
        }
    }
}

try {
    generateCSRFToken();
} catch (Exception $e) {
    error_log("CSRF token generation failed: " . $e->getMessage());
    die("System security configuration error. Contact administrator.");
}

// Retrieve form data from session if available
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : array();
$error = isset($_SESSION['form_error']) ? $_SESSION['form_error'] : null;
if (isset($_SESSION['form_error'])) {
    unset($_SESSION['form_error']);
}
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIMS - Add Worker</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #8BC34A;
            --accent-color: #FFC107;
            --background-color: #F1F8E9;
            --text-dark: #2E7D32;
            --text-light: #FFFFFF;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            --sidebar-width: 280px;
            --header-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: var(--background-color);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Layout */
        .dashboard-container {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            grid-template-rows: var(--header-height) 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            grid-row: 1 / -1;
            background: var(--primary-color);
            color: var(--text-light);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .nav-menu {
            flex: 1;
            overflow-y: auto;
        }

        .nav-menu a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .nav-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 3px solid transparent;
            color: var(--text-light);
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 3px solid var(--accent-color);
        }

        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .nav-item span {
            font-weight: 500;
        }

        .logout-btn {
            margin-top: auto;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .logout-btn i {
            margin-right: 10px;
        }

        /* Header */
        .header {
            grid-column: 2;
            background: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .user-profile div {
            color: var(--text-dark);
        }

        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 30px;
            overflow-y: auto;
        }

        /* Form Styles */
        .form-container {
            background: var(--text-light);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-header h2 {
            color: var(--text-dark);
            font-size: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }

        .avatar-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
            border: 3px solid var(--accent-color);
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #3d8b40;
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: var(--text-dark);
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        .btn i {
            margin-right: 8px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .error-message {
            color: #d32f2f;
            background-color: #fde0e0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .error-message i {
            margin-right: 10px;
        }

        .success-message {
            color: #388e3c;
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .success-message i {
            margin-right: 10px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                grid-row: auto;
                grid-column: 1;
                width: 100%;
                position: fixed;
                bottom: 0;
                left: 0;
                height: auto;
                z-index: 100;
                flex-direction: row;
                padding: 0;
            }

            .sidebar-header {
                display: none;
            }

            .nav-menu {
                display: flex;
                overflow-x: auto;
                flex: 1;
            }

            .nav-item {
                flex-direction: column;
                padding: 10px;
                min-width: 80px;
                text-align: center;
                border-left: none;
                border-top: 3px solid transparent;
            }

            .nav-item.active {
                border-left: none;
                border-top: 3px solid var(--accent-color);
            }

            .nav-item i {
                margin-right: 0;
                margin-bottom: 5px;
            }

            .logout-btn {
                margin-top: 0;
                padding: 10px;
                min-width: 80px;
                flex-direction: column;
            }

            .logout-btn i {
                margin-right: 0;
                margin-bottom: 5px;
            }

            .header,
            .main-content {
                grid-column: 1;
            }

            .main-content {
                padding-bottom: 80px;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tractor"></i> Farm AIMS</h2>
            </div>

            <nav class="nav-menu">
                <a href="dashboard.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-seedling"></i>
                    <span>Dashboard</span>
                </a>
                <a href="crops.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'crops.php' ? 'active' : '' ?>">
                    <i class="fas fa-leaf"></i>
                    <span>Crops</span>
                </a>
                <a href="equipment.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'equipment.php' ? 'active' : '' ?>">
                    <i class="fas fa-tractor"></i>
                    <span>Equipment</span>
                </a>
                <a href="analytics.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="schedule.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </a>
                <a href="workers.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'workers.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Workers</span>
                </a>
            </nav>

            <div class="logout-btn" onclick="document.getElementById('logout-form').submit()">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </div>

            <form id="logout-form" action="modules/auth/logout.php" method="POST" style="display: none;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            </form>
        </aside>

        <!-- Header -->
        <header class="header">
            <div class="header-title">
                <h1>Add New Worker</h1>
            </div>

            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['fullname']) ?>&background=random"
                    alt="Profile">
                <div>
                    <div><?= htmlspecialchars($user['fullname']) ?></div>
                    <small><?= ucfirst($user['role']) ?></small>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="form-container">
                <div class="form-header">
                    <h2>Worker Information</h2>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_worker.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="avatar-upload">
                        <div class="avatar-preview">
                            <img id="avatarPreview" src="assets/images/default-avatar.png" alt="Worker Avatar">
                        </div>
                        <input type="file" id="avatarUpload" name="avatar" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-secondary"
                            onclick="document.getElementById('avatarUpload').click()">
                            <i class="fas fa-camera"></i> Upload Photo
                        </button>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control"
                                value="<?= isset($formData['first_name']) ? htmlspecialchars($formData['first_name']) : '' ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control"
                                value="<?= isset($formData['last_name']) ? htmlspecialchars($formData['last_name']) : '' ?>"
                                required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number" class="form-control"
                                value="<?= isset($formData['contact_number']) ? htmlspecialchars($formData['contact_number']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Role *</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="farmer" <?= (isset($formData['role']) && $formData['role'] === 'farmer' ? 'selected' : '') ?>>Farmer</option>
                                <option value="technician" <?= (isset($formData['role']) && $formData['role'] === 'technician' ? 'selected' : '') ?>>Technician</option>
                                <option value="laborer" <?= (isset($formData['role']) && $formData['role'] === 'laborer' ? 'selected' : '') ?>>Laborer</option>
                                <option value="driver" <?= (isset($formData['role']) && $formData['role'] === 'driver' ? 'selected' : '') ?>>Driver</option>
                                <option value="supervisor" <?= (isset($formData['role']) && $formData['role'] === 'supervisor' ? 'selected' : '') ?>>Supervisor</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control"
                            rows="3"><?= isset($formData['address']) ? htmlspecialchars($formData['address']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="skills" class="form-label">Skills/Qualifications</label>
                        <textarea id="skills" name="skills" class="form-control"
                            rows="3"><?= isset($formData['skills']) ? htmlspecialchars($formData['skills']) : '' ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="workers.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Worker
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Preview avatar image before upload
        document.getElementById('avatarUpload').addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>