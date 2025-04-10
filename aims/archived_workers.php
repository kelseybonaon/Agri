<?php
// archived_workers.php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = Database::getInstance();

// Fetch archived workers
$query = "SELECT w.*, u.fullname as archived_by_name 
          FROM workers w
          JOIN users u ON w.archived_by = u.id
          WHERE w.is_archived = 1
          ORDER BY w.archived_at DESC";

$result = $conn->query($query);
$archived_workers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIMS - Archived Workers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --farm-green: #4CAF50;
            --earth-brown: #8D6E63;
            --sunflower-yellow: #FFD54F;
            --cornstalk-beige: #FFF3E0;
            --barn-red: #D32F2F;
            --sky-blue: #B3E5FC;
        }

        .archived-workers-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: url('data:image/svg+xml,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" opacity="0.05"><path d="M50 0L100 50 50 100 0 50z" fill="%234CAF50"/></svg>');
        }

        .card {
            background: linear-gradient(145deg, #ffffff 0%, #FFF3E0 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--farm-green);
            color: white;
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
        }

        .btn-primary {
            background: var(--sunflower-yellow);
            color: var(--earth-brown);
            border: 2px solid var(--earth-brown);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: scale(1.05) rotate(-2deg);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .archived-worker-card {
            background: white;
            border-left: 6px solid var(--farm-green);
            margin: 1rem;
            padding: 1.5rem;
            border-radius: 12px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .archived-worker-card:hover {
            transform: translateX(10px) rotate(1deg);
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.1);
        }

        .worker-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--sky-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--farm-green);
            position: relative;
            overflow: hidden;
        }

        .worker-avatar::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 3.22l-.61.6-4.24 4.24-.7-.71L10 1.8l5.55 5.55-.71.7-4.24-4.24-.6-.61z" fill="%234CAF50"/></svg>');
            opacity: 0.1;
        }

        .archive-details {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .detail-chip {
            background: var(--cornstalk-beige);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .restore-btn {
            background: var(--farm-green);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .restore-btn:hover {
            background: var(--sunflower-yellow);
            color: var(--earth-brown);
            transform: rotate(5deg) scale(1.1);
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            background: url('data:image/svg+xml,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M50 15a35 35 0 00-35 35 34.9 34.9 0 005 18.2L50 85l30-16.8A34.9 34.9 0 0085 50a35 35 0 00-35-35z" fill="%234CAF50" opacity="0.1"/></svg>');
        }

        .scarecrow {
            font-size: 4rem;
            animation: sway 4s ease-in-out infinite;
            color: var(--earth-brown);
        }

        @keyframes sway {

            0%,
            100% {
                transform: rotate(-5deg);
            }

            50% {
                transform: rotate(5deg);
            }
        }

        @media (max-width: 768px) {
            .archived-worker-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .worker-avatar {
                margin: 0 auto;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="archived-workers-container">
                <div class="card">
                    <div class="card-header">
                        <h2 style="margin: 0; display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-seedling"></i>
                            Archived Workers Garden
                        </h2>
                        <a href="workers.php" class="btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Active Field
                        </a>
                    </div>

                    <?php if (!empty($archived_workers)): ?>
                        <div class="archived-list">
                            <?php foreach ($archived_workers as $worker): ?>
                                <div class="archived-worker-card">
                                    <div class="worker-avatar">
                                        <?= strtoupper(substr($worker['first_name'], 0, 1) . substr($worker['last_name'], 0, 1)) ?>
                                    </div>
                                    <div class="worker-meta">
                                        <h3 class="worker-name">
                                            <?= htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']) ?></h3>
                                        <div class="archive-details">
                                            <div class="detail-chip">
                                                <i class="fas fa-tractor"></i>
                                                <?= htmlspecialchars(ucfirst($worker['role'])) ?>
                                            </div>
                                            <div class="detail-chip">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?= date('M j, Y', strtotime($worker['archived_at'])) ?>
                                            </div>
                                            <div class="detail-chip">
                                                <i class="fas fa-user-farmer"></i>
                                                <?= htmlspecialchars($worker['archived_by_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <form action="restore_worker.php" method="POST" onsubmit="return confirmRestore(this)">
                                        <input type="hidden" name="worker_id" value="<?= $worker['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="restore-btn">
                                            <i class="fas fa-recycle"></i>
                                            Regrow Worker
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="scarecrow">
                                <i class="fas fa-scarecrow"></i>
                            </div>
                            <h3>Empty Harvest Basket!</h3>
                            <p>No archived workers found in our fields</p>
                            <div style="margin-top: 2rem; animation: bounce 2s infinite;">
                                <i class="fas fa-angle-double-down" style="font-size: 2rem; color: var(--farm-green);"></i>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function confirmRestore(form) {
            if (confirm('Ready to replant this worker in active fields?')) {
                const btn = form.querySelector('button');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Growing...';
                btn.disabled = true;

                // Add playful confetti
                const colors = ['#4CAF50', '#8D6E63', '#FFD54F'];
                for (let i = 0; i < 50; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                        position: fixed;
                        width: 10px;
                        height: 10px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        border-radius: 50%;
                        left: ${event.pageX}px;
                        top: ${event.pageY}px;
                        pointer-events: none;
                        animation: confetti 1s linear;
                    `;

                    document.body.appendChild(confetti);
                    setTimeout(() => confetti.remove(), 1000);
                }
                return true;
            }
            return false;
        }

        // Add confetti animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes confetti {
                0% { transform: translate(0,0) rotate(0); opacity: 1; }
                100% { transform: translate(${Math.random() * 400 - 200}px, 500px) rotate(360deg); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>