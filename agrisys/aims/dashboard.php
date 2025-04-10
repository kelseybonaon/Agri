<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user data
$conn = Database::getInstance();
$stmt = $conn->prepare("SELECT fullname, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Generate new CSRF token for forms
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

    <style>
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
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--glass-border);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--glass-border);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.3rem;
            margin-bottom: 5px;
            background: linear-gradient(to right, var(--neutral-light), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            color: var(--primary-light);
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
            color: var(--primary-light);
        }

        .nav-item:hover {
            background: rgba(241, 198, 95, 0.1);
            color: var(--neutral-light);
        }

        .nav-item.active {
            background: rgba(241, 198, 95, 0.15);
            border-left: 3px solid var(--accent-orange);
            color: var(--neutral-light);
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
            background: rgba(149, 96, 13, 0.3);
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--primary-light);
        }

        .logout-btn:hover {
            background: rgba(149, 96, 13, 0.5);
        }

        .logout-btn i {
            margin-right: 10px;
        }

        /* Header */
        .header {
            grid-column: 2;
            background: rgba(37, 29, 5, 0.6);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            border-bottom: 1px solid var(--glass-border);
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--primary-light);
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
            border: 2px solid var(--primary-light);
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
            background: rgba(37, 29, 5, 0.7);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            color: var(--text-light);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            border-color: var(--accent-orange);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1rem;
            color: var(--primary-light);
        }

        .card-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--neutral-light);
        }

        .card-footer {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Charts */
        .chart-container {
            background: rgba(37, 29, 5, 0.7);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(5px);
            border: 1px solid var(--glass-border);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.2rem;
            color: var(--primary-light);
        }

        /* Recent Activity */
        .activity-container {
            background: rgba(37, 29, 5, 0.7);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid var(--glass-border);
        }

        .activity-header {
            margin-bottom: 20px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--glass-border);
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
            background: rgba(241, 198, 95, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            color: var(--primary-light);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--neutral-light);
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--accent-green);
            color: var(--neutral-light);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--highlight-green);
        }

        .btn-secondary {
            background-color: rgba(149, 96, 13, 0.3);
            color: var(--primary-light);
            border: 1px solid var(--glass-border);
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: rgba(149, 96, 13, 0.5);
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge.active {
            background-color: rgba(81, 122, 25, 0.3);
            color: var(--highlight-green);
        }

        .status-badge.inactive {
            background-color: rgba(149, 96, 13, 0.3);
            color: var(--primary-light);
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
                border-top: 3px solid var(--accent-orange);
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

        <!-- Main Content -->
        <main class="main-content">
            <!-- Stats Cards -->
            <div class="cards-container">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Total Crops</div>
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="card-value">1,248</div>
                    <div class="card-footer">+12% from last month</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Harvest Yield</div>
                        <i class="fas fa-weight-hanging"></i>
                    </div>
                    <div class="card-value">5.2 <small>tons</small></div>
                    <div class="card-footer">Projected this season</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Soil Moisture</div>
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="card-value">78%</div>
                    <div class="card-footer">Optimal range</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Tasks Completed</div>
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="card-value">24/36</div>
                    <div class="card-footer">66% of weekly tasks</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Crop Growth Progress</h3>
                    <select class="form-control" style="width: auto; padding: 5px 10px; font-size: 0.9rem;">
                        <option>Last 7 Days</option>
                        <option>Last 30 Days</option>
                        <option>This Season</option>
                    </select>
                </div>
                <div
                    style="height: 300px; background: rgba(255,255,255,0.05); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                    <p style="opacity: 0.5;">Chart visualization will appear here</p>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Weather Forecast</h3>
                    <select class="form-control" style="width: auto; padding: 5px 10px; font-size: 0.9rem;">
                        <option>Next 3 Days</option>
                        <option>Next 7 Days</option>
                        <option>Next 14 Days</option>
                    </select>
                </div>
                <div
                    style="height: 200px; background: rgba(255,255,255,0.05); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                    <p style="opacity: 0.5;">Weather data visualization will appear here</p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="activity-container">
                <div class="activity-header">
                    <h3>Recent Activity</h3>
                </div>

                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-tint"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">Irrigation System Activated</div>
                            <div class="activity-time">10 minutes ago</div>
                        </div>
                    </li>

                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">New corn seeds planted in Field B</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </li>

                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-cloud-sun-rain"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">Rainfall predicted for tomorrow</div>
                            <div class="activity-time">5 hours ago</div>
                        </div>
                    </li>

                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-tractor"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">Tractor maintenance completed</div>
                            <div class="activity-time">Yesterday</div>
                        </div>
                    </li>

                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">Fertilizer delivery received</div>
                            <div class="activity-time">2 days ago</div>
                        </div>
                    </li>
                </ul>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle (for smaller screens)
        document.addEventListener('DOMContentLoaded', function () {
            // You can add JavaScript functionality here
            // For example, chart initialization, API calls, etc.

            // Example: Make nav items clickable
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function () {
                    navItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Example: Responsive adjustments
            function handleResize() {
                // Add any responsive adjustments here
            }

            window.addEventListener('resize', handleResize);
            handleResize();
        });
    </script>
</body>

</html>