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

// Fetch active workers from database
$query = "SELECT * FROM workers WHERE is_archived = 0 ORDER BY last_name, first_name";
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
    <title>AIMS - Workers Management</title>
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

        .profile-picture {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture .initials {
            font-weight: bold;
            color: var(--primary-color);
        }

        /* Main Content */
        .main-content {
            grid-column: 2;
            padding: 30px;
            overflow-y: auto;
        }

        /* Success Message */
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .success-message i {
            margin-right: 10px;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-header h2 {
            color: var(--text-dark);
            font-size: 1.5rem;
            margin: 0;
        }

        /* Button Styles */
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: #3d8b40;
        }

        /* Filter Section */
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-section input,
        .filter-section select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 200px;
        }

        .filter-section input {
            flex: 1;
            max-width: 400px;
        }

        .filter-section input:focus,
        .filter-section select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        /* Worker List */
        .worker-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .worker-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .worker-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .worker-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--accent-color);
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .worker-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .worker-avatar .initials {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .worker-info {
            flex: 1;
            margin-left: 15px;
        }

        .worker-info h3 {
            margin: 0 0 5px 0;
            color: var(--text-dark);
        }

        .worker-info p {
            margin: 5px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .worker-info .role {
            color: var(--primary-color);
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge.active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.inactive {
            background-color: #ffebee;
            color: #c62828;
        }

        .no-workers {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-workers i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #e0e0e0;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: auto;
                flex-direction: row;
                padding: 0;
                z-index: 1000;
            }

            .nav-menu {
                display: flex;
                overflow-x: auto;
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
                padding-bottom: 100px;
            }
        }

        @media (max-width: 768px) {
            .worker-list {
                grid-template-columns: 1fr;
            }

            .filter-section input {
                max-width: 100%;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .archive-btn {
            background-color: #757575;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 6px 12px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .archive-btn:hover {
            background-color: #616161;
        }

        .archived-card {
            opacity: 0.7;
            border-left: 4px solid #757575;
            background-color: #f5f5f5;
        }

        .archived-label {
            color: #757575;
            font-size: 0.8rem;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-archived-link {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 15px;
        }

        .view-archived-link:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .archive-form {
            display: inline;
        }

        .worker-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
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
                <h1>Workers Management</h1>
            </div>

            <div class="user-profile">
                <div class="profile-picture">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="assets/images/profiles/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile">
                    <?php else: ?>
                        <?php
                        $initials = '';
                        $names = explode(' ', $user['fullname']);
                        foreach ($names as $name) {
                            $initials .= strtoupper(substr($name, 0, 1));
                        }
                        ?>
                        <div class="initials"><?= $initials ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <div><?= htmlspecialchars($user['fullname']) ?></div>
                    <small><?= ucfirst($user['role']) ?></small>
                </div>
            </div>
        </header>

        <main class="main-content">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Worker List</h2>
                    <div>
                        <a href="add_worker.php" class="btn-primary">
                            <i class="fas fa-user-plus"></i> Add Worker
                        </a>
                        <a href="archived_workers.php" class="view-archived-link">
                            <i class="fas fa-history"></i> View Archive History
                        </a>
                    </div>
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
                        <div class="worker-card" data-role="<?= htmlspecialchars($worker['role']) ?>">
                            <div class="worker-avatar">
                                <?php if (file_exists('assets/images/avatars/' . $worker['id'] . '.jpg')): ?>
                                    <img src="assets/images/avatars/<?= htmlspecialchars($worker['id']) ?>.jpg"
                                        alt="<?= htmlspecialchars($worker['first_name']) ?>">
                                <?php else: ?>
                                    <?php
                                    $initials = strtoupper(substr($worker['first_name'], 0, 1));
                                    if (!empty($worker['last_name'])) {
                                        $initials .= strtoupper(substr($worker['last_name'], 0, 1));
                                    }
                                    ?>
                                    <div class="initials"><?= $initials ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="worker-info">
                                <h3><?= htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']) ?></h3>
                                <p><span class="role">Role:</span> <?= htmlspecialchars(ucfirst($worker['role'])) ?></p>
                                <p><span class="role">Status:</span>
                                    <span class="status-badge <?= htmlspecialchars($worker['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($worker['status'])) ?>
                                    </span>
                                </p>
                                <div class="worker-actions">
                                    <a href="edit_worker.php?id=<?= $worker['id'] ?>" class="btn-action">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form class="archive-form" action="archive_worker.php" method="POST"
                                        onsubmit="return confirmArchive(this)">
                                        <input type="hidden" name="worker_id" value="<?= $worker['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="archive-btn">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    </form>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Worker filtering functionality
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('workerSearch');
            const roleFilter = document.getElementById('workerRoleFilter');
            const workerCards = document.querySelectorAll('.worker-card');

            function filterWorkers() {
                const searchTerm = searchInput.value.toLowerCase();
                const roleValue = roleFilter.value;

                workerCards.forEach(card => {
                    const name = card.querySelector('h3').textContent.toLowerCase();
                    const cardRole = card.getAttribute('data-role');

                    const matchesSearch = name.includes(searchTerm);
                    const matchesRole = roleValue === 'all' || cardRole === roleValue;

                    card.style.display = (matchesSearch && matchesRole) ? 'flex' : 'none';
                });
            }

            searchInput.addEventListener('input', filterWorkers);
            roleFilter.addEventListener('change', filterWorkers);
        });

        function confirmArchive(form) {
            if (confirm('Are you sure you want to archive this worker?')) {
                // Add loading indicator
                const btn = form.querySelector('button');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
                btn.disabled = true;
                return true;
            }
            return false;
        }

    </script>
</body>

</html>