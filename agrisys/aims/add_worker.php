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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Additional styles for the form */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }

        .avatar-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .error-message {
            color: #ff6b6b;
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(255, 0, 0, 0.1);
            border-radius: 5px;
        }

        .success-message {
            color: #51cf66;
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(0, 255, 0, 0.1);
            border-radius: 5px;
        }

        :root {
            /* New color palette variables */
            --primary-dark: #251d05;
            /* Deep earthy brown */
            --primary-medium: #95600d;
            /* Warm brown */
            --primary-light: #f1c65f;
            /* Golden wheat */
            --accent-green: #517a19;
            /* Fresh green */
            --accent-orange: #ef9612;
            /* Vibrant orange */
            --neutral-light: #faf5cc;
            /* Creamy off-white */
            --neutral-medium: #91848b;
            /* Muted taupe */
            --neutral-dark: #646156;
            /* Dark gray-brown */
            --highlight-green: #669438;
            /* Bright green */
            --highlight-yellow: #6e6311;
            /* Olive green */
            --highlight-orange: #e09a56;
            /* Soft orange */

            /* Functional colors */
            --text-light: #faf5cc;
            /* Cream for text */
            --text-muted: #91848b;
            /* Muted taupe for secondary text */
            --glass-bg: rgba(37, 29, 5, 0.7);
            /* Semi-transparent dark brown */
            --glass-border: rgba(149, 96, 13, 0.3);
            --glass-shadow: 0 25px 45px rgba(0, 0, 0, 0.3);
            --sidebar-width: 280px;
            --header-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium), var(--highlight-yellow));
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            position: relative;
            overflow-x: hidden;
            line-height: 1.6;
            color: var(--text-light);
        }


        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
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
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.3rem;
            margin-bottom: 5px;
            background: linear-gradient(to right, #fff, #c8e6c9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.7;
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
            color: var(--text-muted);
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 3px solid var(--accent-color);
            color: var(--text-light);
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
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 500;
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

        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 30px;
            overflow-y: auto;
        }

        /* Cards */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1rem;
            opacity: 0.8;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .card-footer {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Charts */
        .chart-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.2rem;
        }

        /* Recent Activity */
        .activity-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .activity-header {
            margin-bottom: 20px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 0.8rem;
            opacity: 0.7;
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
            .cards-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Agricultural IMS</h2>
                <p>Management Dashboard</p>
            </div>

            <nav class="nav-menu">
                <a href="dashboard.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
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

        <header class="header">
            <div class="header-title">
                <h1>Add New Worker</h1>
            </div>

            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['fullname']); ?>&background=random"
                    alt="Profile">
                <div>
                    <div><?php echo htmlspecialchars($user['fullname']); ?></div>
                    <small><?php echo ucfirst($user['role']); ?></small>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="card form-container">
                <div class="card-header">
                    <h2>Worker Information</h2>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_worker.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="avatar-upload">
                        <div class="avatar-preview"
                            style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 2px solid var(--accent-color);">
                            <img id="avatarPreview" src="assets/images/default-avatar.png" alt="Worker Avatar"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <input type="file" id="avatarUpload" name="avatar" accept="image/*" style="display: none;">
                        <button type="button" class="btn-secondary"
                            onclick="document.getElementById('avatarUpload').click()"
                            style="margin-top: 10px; padding: 8px 15px; border-radius: 5px;">
                            <i class="fas fa-camera"></i> Upload Photo
                        </button>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control"
                                value="<?php echo isset($formData['first_name']) ? htmlspecialchars($formData['first_name']) : ''; ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control"
                                value="<?php echo isset($formData['last_name']) ? htmlspecialchars($formData['last_name']) : ''; ?>"
                                required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number" class="form-control"
                                value="<?php echo isset($formData['contact_number']) ? htmlspecialchars($formData['contact_number']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Role *</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="farmer" <?php echo (isset($formData['role']) && $formData['role'] === 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                                <option value="technician" <?php echo (isset($formData['role']) && $formData['role'] === 'technician') ? 'selected' : ''; ?>>Technician</option>
                                <option value="laborer" <?php echo (isset($formData['role']) && $formData['role'] === 'laborer') ? 'selected' : ''; ?>>Laborer</option>
                                <option value="driver" <?php echo (isset($formData['role']) && $formData['role'] === 'driver') ? 'selected' : ''; ?>>Driver</option>
                                <option value="supervisor" <?php echo (isset($formData['role']) && $formData['role'] === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control"
                            rows="3"><?php echo isset($formData['address']) ? htmlspecialchars($formData['address']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="skills" class="form-label">Skills/Qualifications</label>
                        <textarea id="skills" name="skills" class="form-control"
                            rows="3"><?php echo isset($formData['skills']) ? htmlspecialchars($formData['skills']) : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="workers.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Worker
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
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