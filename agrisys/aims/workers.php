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

// Check for success message - using isset() instead of null coalescing operator
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}

// Fetch workers from database
$workers = array();
$query = "SELECT * FROM workers ORDER BY last_name, first_name";
$result = $conn->query($query);
if ($result) {
    $workers = $result->fetch_all(MYSQLI_ASSOC);
}

try {
    generateCSRFToken();
} catch (Exception $e) {
    error_log("CSRF token generation failed: " . $e->getMessage());
    die("System security configuration error. Contact administrator.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIMS - Dashboard</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        :root {
            --primary-color: #1a5632;
            --secondary-color: #0d2d1a;
            --accent-color: #4CAF50;
            --text-light: rgba(255, 255, 255, 0.9);
            --text-muted: rgba(255, 255, 255, 0.7);
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 25px 45px rgba(0, 0, 0, 0.2);
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color), var(--primary-color));
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            position: relative;
            overflow-x: hidden;
            line-height: 1.6;
            color: white;
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

        /* Avatar Styles */
        .avatar-preview,
        .worker-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--accent-color);
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-preview img,
        .worker-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .status-badge.active {
            background-color: rgba(76, 175, 80, 0.2);
            color: #8bc34a;
        }

        .status-badge.inactive {
            background-color: rgba(244, 67, 54, 0.2);
            color: #ef5350;
        }

        /* Upload Button */
        .upload-btn {
            margin-top: 10px;
            padding: 8px 15px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            background: rgba(255, 255, 255, 0.2);
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

            <!-- In the sidebar section -->
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

        <!-- Header -->
        <header class="header">
            <div class="header-title">
                <h1>Dashboard Overview</h1>
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

        <main class="main-content">
            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Worker List</h2>
                    <a href="add_worker.php" class="btn-primary">
                        <i class="fas fa-user-plus"></i> Add Worker
                    </a>
                </div>

                <div class="filter-section">
                    <input type="text" id="workerSearch" placeholder="Search workers...">
                    <select id="workerRoleFilter">
                        <option value="all">All Roles</option>
                        <option value="farmer">Farmer</option>
                        <option value="technician">Technician</option>
                        <option value="laborer">Laborer</option>
                        <option value="driver">Driver</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>

                <div class="worker-list">
                    <?php foreach ($workers as $worker): ?>
                        <div class="worker-card" data-role="<?php echo htmlspecialchars($worker['role']); ?>">
                            <div class="worker-avatar"
                                style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 2px solid var(--accent-color);">
                                <img src="assets/images/avatars/<?php echo htmlspecialchars($worker['id']); ?>.jpg"
                                    onerror="this.src='assets/images/default-avatar.png'"
                                    style="width: 100%; height: 100%; object-fit: cover;"
                                    alt="<?php echo htmlspecialchars($worker['first_name']); ?>">
                            </div>
                            <div class="worker-info" style="flex: 1; margin-left: 15px;">
                                <h3 style="margin: 0 0 5px 0; color: white;">
                                    <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                </h3>
                                <p style="margin: 5px 0; color: var(--text-muted);">
                                    <span style="color: var(--accent-color);">Role:</span>
                                    <?php echo htmlspecialchars(ucfirst($worker['role'])); ?>
                                </p>
                                <p style="margin: 5px 0; color: var(--text-muted);">
                                    <span style="color: var(--accent-color);">Status:</span>
                                    <span class="status-badge <?php echo htmlspecialchars($worker['status']); ?>"
                                        style="display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 0.8em;">
                                        <?php echo htmlspecialchars(ucfirst($worker['status'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($workers)): ?>
                        <div class="no-workers">
                            <i class="fas fa-user-slash"></i>
                            <p>No workers found</p>
                        </div>
                    <?php endif; ?>
                </div>
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

        // Simple client-side filtering
        document.getElementById('workerSearch').addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.worker-card').forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                card.style.display = name.includes(searchTerm) ? 'flex' : 'none';
            });
        });

        document.getElementById('workerRoleFilter').addEventListener('change', function (e) {
            const role = e.target.value;
            document.querySelectorAll('.worker-card').forEach(card => {
                const cardRole = card.getAttribute('data-role');
                card.style.display = (role === 'all' || cardRole === role) ? 'flex' : 'none';
            });
        });
    </script>
</body>

</html>