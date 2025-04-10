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
    <title>AIMS - Equipment Inventory</title>
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
        }

        /* Equipment Grid Styles */
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .equipment-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .equipment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .equipment-icon {
            width: 60px;
            height: 60px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .equipment-card h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .equipment-card p {
            color: #666;
            font-size: 0.9rem;
            margin: 5px 0;
        }

        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status.operational {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .status.maintenance {
            background: #FFF3E0;
            color: #EF6C00;
        }

        /* Search Filter Styles */
        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .search-filter input,
        .search-filter select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-filter input {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }

        .search-filter input:focus,
        .search-filter select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        /* Action Buttons */
        .equipment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .btn-action {
            flex: 1;
            padding: 8px 12px;
            background: rgba(76, 175, 80, 0.1);
            border: none;
            border-radius: 4px;
            color: var(--primary-color);
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-action:hover {
            background: rgba(76, 175, 80, 0.2);
        }

        .btn-action i {
            font-size: 0.9rem;
        }

        /* Add Equipment Button */
        .btn-add-equipment {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-equipment:hover {
            background: #3d8b40;
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
            .equipment-grid {
                grid-template-columns: 1fr;
            }

            .search-filter input {
                max-width: 100%;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
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
                <h1>Equipment Inventory</h1>
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
            <div class="card">
                <div class="card-header">
                    <h2>Equipment Management</h2>
                    <button class="btn-add-equipment" onclick="showAddEquipmentModal()">
                        <i class="fas fa-plus"></i> Add Equipment
                    </button>
                </div>

                <div class="search-filter">
                    <input type="text" placeholder="Search equipment..." id="equipmentSearch">
                    <select id="equipmentStatus">
                        <option value="all">All Statuses</option>
                        <option value="operational">Operational</option>
                        <option value="maintenance">Under Maintenance</option>
                    </select>
                </div>

                <div class="equipment-grid">
                    <!-- Sample Equipment Cards -->
                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-tractor"></i></div>
                        <h3>John Deere Tractor</h3>
                        <p>Model: 8R 410</p>
                        <p>Serial: JDTR-2023-415</p>
                        <p>Status: <span class="status operational">Operational</span></p>
                        <p>Last Maintenance: 2023-08-15</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-water"></i></div>
                        <h3>Irrigation System</h3>
                        <p>Model: RainMaster 3000</p>
                        <p>Serial: RM3K-2022-117</p>
                        <p>Status: <span class="status maintenance">Under Maintenance</span></p>
                        <p>Last Maintenance: 2023-07-28</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-harvest"></i></div>
                        <h3>Combine Harvester</h3>
                        <p>Model: Case IH 8250</p>
                        <p>Serial: CIHC-2021-8250</p>
                        <p>Status: <span class="status operational">Operational</span></p>
                        <p>Last Maintenance: 2023-09-05</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-spray-can"></i></div>
                        <h3>Sprayer</h3>
                        <p>Model: AgSpray 4000</p>
                        <p>Serial: AGSP-2023-4001</p>
                        <p>Status: <span class="status operational">Operational</span></p>
                        <p>Last Maintenance: 2023-08-22</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-trailer"></i></div>
                        <h3>Grain Trailer</h3>
                        <p>Model: Wilson 5000</p>
                        <p>Serial: WILS-2020-5002</p>
                        <p>Status: <span class="status maintenance">Under Maintenance</span></p>
                        <p>Last Maintenance: 2023-06-30</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-seedling"></i></div>
                        <h3>Planter</h3>
                        <p>Model: Great Plains 1500</p>
                        <p>Serial: GPPL-2022-1503</p>
                        <p>Status: <span class="status operational">Operational</span></p>
                        <p>Last Maintenance: 2023-09-12</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-wind"></i></div>
                        <h3>Windrower</h3>
                        <p>Model: MacDon 9000</p>
                        <p>Serial: MCDN-2021-9001</p>
                        <p>Status: <span class="status operational">Operational</span></p>
                        <p>Last Maintenance: 2023-08-18</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-balance-scale"></i></div>
                        <h3>Grain Scale</h3>
                        <p>Model: Fairbanks 750</p>
                        <p>Serial: FBKS-2023-7501</p>
                        <p>Status: <span class="status operational">Operational</span></p>
                        <p>Last Maintenance: 2023-07-15</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>

                    <div class="equipment-card">
                        <div class="equipment-icon"><i class="fas fa-truck-pickup"></i></div>
                        <h3>Farm Truck</h3>
                        <p>Model: Ford F-350</p>
                        <p>Serial: F350-2022-8892</p>
                        <p>Status: <span class="status maintenance">Under Maintenance</span></p>
                        <p>Last Maintenance: 2023-08-01</p>
                        <div class="equipment-actions">
                            <button class="btn-action"><i class="fas fa-info-circle"></i> Details</button>
                            <button class="btn-action"><i class="fas fa-wrench"></i> Service</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Equipment Filter Functionality
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('equipmentSearch');
            const statusFilter = document.getElementById('equipmentStatus');
            const equipmentCards = document.querySelectorAll('.equipment-card');

            function filterEquipment() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;

                equipmentCards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    const cardStatus = card.querySelector('.status').classList.contains(statusValue);

                    const matchesSearch = text.includes(searchTerm);
                    const matchesStatus = statusValue === 'all' || cardStatus;

                    card.style.display = (matchesSearch && matchesStatus) ? 'block' : 'none';
                });
            }

            searchInput.addEventListener('input', filterEquipment);
            statusFilter.addEventListener('change', filterEquipment);

            // Initialize with all cards visible
            filterEquipment();
        });

        function showAddEquipmentModal() {
            // In a real implementation, this would show a modal form
            alert('Add Equipment functionality would open a modal form here');
        }
    </script>
</body>

</html>