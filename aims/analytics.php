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
    <title>AIMS - Analytics</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Analytics Container */
        .analytics-container {
            background: var(--text-light);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .analytics-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .analytics-header h2 {
            color: var(--text-dark);
            font-size: 1.5rem;
        }

        /* Chart Containers */
        .chart-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .chart-btn i {
            margin-right: 5px;
        }

        .chart-canvas {
            width: 100%;
            height: 300px;
            position: relative;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(76, 175, 80, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
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
            .chart-row {
                flex-direction: column;
                gap: 20px;
            }

            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 576px) {
            .stats-container {
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
                <h1>Analytics Dashboard</h1>
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
            <div class="analytics-container">
                <div class="analytics-header">
                    <h2>Farm Performance Overview</h2>
                </div>

                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="stat-value" id="totalCrops">0</div>
                        <div class="stat-label">Total Crops</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="stat-value" id="avgYield">0 kg</div>
                        <div class="stat-label">Avg Yield</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tractor"></i>
                        </div>
                        <div class="stat-value" id="equipmentCount">0</div>
                        <div class="stat-label">Equipment</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value" id="workerCount">0</div>
                        <div class="stat-label">Workers</div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="chart-row">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Crop Yield by Type</h3>
                            <div class="chart-actions">
                                <button class="chart-btn" onclick="updateChartTimeframe('yieldChart', 'month')">
                                    <i class="fas fa-calendar-week"></i> Month
                                </button>
                                <button class="chart-btn" onclick="updateChartTimeframe('yieldChart', 'year')">
                                    <i class="fas fa-calendar-alt"></i> Year
                                </button>
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="yieldChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Equipment Utilization</h3>
                            <div class="chart-actions">
                                <button class="chart-btn" onclick="updateChartTimeframe('equipmentChart', 'month')">
                                    <i class="fas fa-calendar-week"></i> Month
                                </button>
                                <button class="chart-btn" onclick="updateChartTimeframe('equipmentChart', 'year')">
                                    <i class="fas fa-calendar-alt"></i> Year
                                </button>
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="equipmentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="chart-row">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Worker Productivity</h3>
                            <div class="chart-actions">
                                <button class="chart-btn" onclick="updateChartTimeframe('productivityChart', 'month')">
                                    <i class="fas fa-calendar-week"></i> Month
                                </button>
                                <button class="chart-btn" onclick="updateChartTimeframe('productivityChart', 'year')">
                                    <i class="fas fa-calendar-alt"></i> Year
                                </button>
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="productivityChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Soil Moisture Levels</h3>
                            <div class="chart-actions">
                                <button class="chart-btn" onclick="updateChartTimeframe('soilChart', 'month')">
                                    <i class="fas fa-calendar-week"></i> Month
                                </button>
                                <button class="chart-btn" onclick="updateChartTimeframe('soilChart', 'year')">
                                    <i class="fas fa-calendar-alt"></i> Year
                                </button>
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="soilChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize all charts when the page loads
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize stat counters with animation
            animateValue('totalCrops', 0, 124, 1000);
            animateValue('avgYield', 0, 342, 1000);
            animateValue('equipmentCount', 0, 28, 1000);
            animateValue('workerCount', 0, 15, 1000);

            // Initialize charts
            initYieldChart();
            initEquipmentChart();
            initProductivityChart();
            initSoilChart();
        });

        // Function to animate value counters
        function animateValue(id, start, end, duration) {
            const obj = document.getElementById(id);
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start;
                obj.innerHTML = id === 'avgYield' ? value + ' kg' : value;
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Function to update chart timeframe (placeholder)
        function updateChartTimeframe(chartId, timeframe) {
            // In a real application, this would fetch new data based on the timeframe
            console.log(`Updating ${chartId} to show ${timeframe} data`);

            // For demo purposes, we'll just show an alert
            alert(`Chart timeframe updated to show ${timeframe}ly data. In a real app, this would fetch new data.`);
        }

        // Yield Chart Initialization
        function initYieldChart() {
            const ctx = document.getElementById('yieldChart').getContext('2d');
            const yieldChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Wheat', 'Corn', 'Soybeans', 'Rice', 'Potatoes', 'Tomatoes'],
                    datasets: [{
                        label: 'Yield (kg per hectare)',
                        data: [3200, 4200, 2800, 3800, 2500, 4800],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Yield (kg/ha)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + context.raw + ' kg/ha';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Equipment Chart Initialization
        function initEquipmentChart() {
            const ctx = document.getElementById('equipmentChart').getContext('2d');
            const equipmentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Tractors', 'Harvesters', 'Plows', 'Irrigation', 'Sprayers', 'Other'],
                    datasets: [{
                        data: [12, 5, 8, 15, 7, 3],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Productivity Chart Initialization
        function initProductivityChart() {
            const ctx = document.getElementById('productivityChart').getContext('2d');
            const productivityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Worker Productivity Index',
                        data: [65, 59, 80, 81, 56, 72, 90],
                        fill: false,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        tension: 0.3,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Productivity Index'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }

        // Soil Moisture Chart Initialization
        function initSoilChart() {
            const ctx = document.getElementById('soilChart').getContext('2d');
            const soilChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Field 1', 'Field 2', 'Field 3', 'Field 4', 'Field 5', 'Field 6'],
                    datasets: [{
                        label: 'Soil Moisture Level (%)',
                        data: [75, 68, 82, 79, 85, 72],
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        pointBackgroundColor: 'rgba(153, 102, 255, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(153, 102, 255, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 50,
                            suggestedMax: 100
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>