<?php
session_start();
require_once 'includes/config.php';

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

// Generate CSRF token
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --farm-green: #4CAF50;
            --earth-brown: #8D6E63;
            --sunflower-yellow: #FFD54F;
            --cornstalk-beige: #FFF3E0;
            --barn-red: #D32F2F;
            --sky-blue: #B3E5FC;
            --sidebar-width: 280px;
        }

        body {
            background: url('data:image/svg+xml,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" opacity="0.05"><path d="M50 0L100 50 50 100 0 50z" fill="%234CAF50"/></svg>');
            background-color: var(--cornstalk-beige);
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(145deg, var(--farm-green) 0%, #388E3C 100%);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }

        .sidebar::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: url('data:image/svg+xml,<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 3.22l-.61.6-4.24 4.24-.7-.71L10 1.8l5.55 5.55-.71.7-4.24-4.24-.6-.61z" fill="%23FFF3E0" opacity="0.1"/></svg>');
        }

        .sidebar-header {
            padding: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--cornstalk-beige);
        }

        .sidebar-header h2 {
            color: var(--cornstalk-beige);
            font-size: 1.8rem;
            margin: 0;
        }

        .nav-item {
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 12px;
            color: white;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(10px);
        }

        .nav-item.active {
            background: var(--sunflower-yellow);
            color: var(--earth-brown);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-item i {
            font-size: 1.2rem;
            width: 30px;
            text-align: center;
        }

        /* Header */
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            background: var(--cornstalk-beige);
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            transform: rotate(-1deg) scale(1.02);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--sky-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--farm-green);
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            background: rgba(255, 243, 224, 0.3);
        }

        .calendar-card {
            background: linear-gradient(145deg, #ffffff 0%, var(--cornstalk-beige) 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--farm-green);
            color: white;
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card-header i {
            font-size: 1.5rem;
        }

        #calendar {
            padding: 2rem;
        }

        .fc-button-primary {
            background: var(--sunflower-yellow) !important;
            border-color: var(--earth-brown) !important;
            color: var(--earth-brown) !important;
            transition: all 0.3s ease;
        }

        .fc-button-primary:hover {
            transform: scale(1.05) rotate(-2deg);
        }

        .logout-btn {
            position: absolute;
            bottom: 2rem;
            left: 1rem;
            right: 1rem;
            padding: 1rem;
            background: var(--barn-red);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                bottom: 0;
                width: 100%;
                height: auto;
                padding: 1rem;
                z-index: 1000;
            }

            .nav-menu {
                display: flex;
                overflow-x: auto;
            }

            .nav-item {
                flex-direction: column;
                padding: 0.5rem;
                min-width: 100px;
                text-align: center;
            }

            .main-content {
                padding-bottom: 100px;
            }

            .logout-btn {
                position: static;
                margin-top: 1rem;
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>Farm Operations Dashboard</h1>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
                    </div>
                    <div>
                        <div><?= htmlspecialchars($user['fullname']) ?></div>
                        <small><?= ucfirst($user['role']) ?></small>
                    </div>
                </div>
            </header>

            <div class="calendar-card">
                <div class="card-header">
                    <i class="fas fa-calendar-check"></i>
                    <h2>Agricultural Schedule</h2>
                </div>
                <div id="calendar"></div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [],
                eventDidMount: function (info) {
                    info.el.style.background = var(--farm - green);
                    info.el.style.borderColor = var(--earth - brown);
                    info.el.style.color = 'white';
                }
            });
            calendar.render();
        });

        // Add hover effects to all buttons
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('mouseover', () => {
                button.style.transform = 'rotate(2deg) scale(1.05)';
            });
            button.addEventListener('mouseout', () => {
                button.style.transform = '';
            });
        });
    </script>
</body>

</html>