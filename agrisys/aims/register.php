<?php
session_start();
require_once 'includes/config.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security token invalid!";
    } else {
        $fullname = sanitizeInput($_POST['fullname']);
        $email = sanitizeInput($_POST['email']);
        $password = sanitizeInput($_POST['password']);
        $confirm_password = sanitizeInput($_POST['confirm_password']);

        // Validation
        if (empty($fullname) || empty($email) || empty($password)) {
            $error = "All fields are required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters!";
        } else {
            // Check if email exists
            $conn = Database::getInstance();
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already registered!";
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users 
                    (fullname, email, password, role) 
                    VALUES (?, ?, ?, 'farmer')");
                $stmt->bind_param("sss", $fullname, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Registration successful! Please login.";
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Registration failed: " . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIMS - Register</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color), var(--primary-color));
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            position: relative;
            overflow-x: hidden;
            line-height: 1.6;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .background-bubbles {
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }

        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            filter: blur(5px);
            animation: float 15s infinite ease-in-out;
            will-change: transform;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-5vh) rotate(180deg); }
        }

        .glass-container {
            position: relative;
            z-index: 2;
            width: 90vw;
            max-width: 500px;
            padding: clamp(1.5rem, 5vw, 2.5rem);
            background: var(--glass-bg);
            border-radius: clamp(15px, 4vw, 20px);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            margin: 2rem 0;
        }

        .glass-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0.1) 0%,
                rgba(255, 255, 255, 0) 50%
            );
            transform: rotate(30deg);
            pointer-events: none;
        }

        .login-header {
            text-align: center;
            margin-bottom: clamp(1.5rem, 5vw, 2.5rem);
            color: white;
        }

        .login-header h1 {
            font-size: clamp(1.8rem, 6vw, 2.2rem);
            margin-bottom: 0.5rem;
            font-weight: 600;
            background: linear-gradient(to right, #fff, #c8e6c9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .login-header p {
            font-size: clamp(0.9rem, 3.5vw, 1rem);
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: clamp(1rem, 4vw, 1.5rem);
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: clamp(0.8rem, 3vw, 1rem) clamp(1rem, 4vw, 1.5rem);
            border: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: clamp(10px, 3vw, 12px);
            color: white;
            font-size: clamp(0.9rem, 3.5vw, 1rem);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
            font-size: clamp(0.85rem, 3vw, 0.95rem);
        }

        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: clamp(0.9rem, 3.5vw, 1rem);
            background: linear-gradient(135deg, var(--accent-color), #2E7D32);
            border: none;
            border-radius: clamp(10px, 3vw, 12px);
            color: white;
            font-size: clamp(0.95rem, 3.8vw, 1rem);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
        }

        .btn-login:hover, .btn-login:focus {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }

        .additional-links {
            text-align: center;
            margin-top: clamp(1rem, 4vw, 1.5rem);
            color: var(--text-muted);
            font-size: clamp(0.8rem, 3.2vw, 0.9rem);
        }

        .additional-links a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .additional-links a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: white;
            transition: width 0.3s ease;
        }

        .additional-links a:hover::after {
            width: 100%;
        }

        .alert {
            padding: clamp(0.8rem, 3vw, 1rem);
            margin-bottom: clamp(1rem, 4vw, 1.5rem);
            border-radius: clamp(8px, 2.5vw, 12px);
            text-align: center;
            font-size: clamp(0.8rem, 3.2vw, 0.9rem);
        }

        .alert-danger {
            background: rgba(255, 88, 88, 0.15);
            border: 1px solid rgba(255, 88, 88, 0.2);
            color: #ffcccc;
        }

        /* Mobile-specific adjustments */
        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }
            
            .glass-container {
                width: 95vw;
                padding: 1.5rem;
            }
            
            .btn-login {
                padding: 1rem;
            }
        }

        /* Tablet landscape adjustments */
        @media (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {
            .glass-container {
                max-width: 600px;
            }
        }

        /* Very small devices (phones, 360px and down) */
        @media (max-width: 360px) {
            .form-control {
                padding: 0.7rem 1rem;
            }
            
            .btn-login {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-bubbles">
        <?php 
        // Generate random bubbles with size based on viewport
        for($i = 0; $i < 15; $i++): 
            $size = rand(5, 15); // in vmin units
            $posX = rand(0, 100);
            $posY = rand(0, 100);
            $delay = rand(0, 15);
            $duration = rand(10, 25);
        ?>
        <div class="bubble" style="
            width: <?= $size ?>vmin;
            height: <?= $size ?>vmin;
            left: <?= $posX ?>%;
            top: <?= $posY ?>%;
            animation-delay: <?= $delay ?>s;
            animation-duration: <?= $duration ?>s;
        "></div>
        <?php endfor; ?>
    </div>

    <div class="glass-container">
        <div class="login-header">
            <h1>Create Account</h1>
            <p>Join our agricultural community</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <input type="text" class="form-control" name="fullname" 
                    placeholder="Full Name" required 
                    value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>">
            </div>

            <div class="form-group">
                <input type="email" class="form-control" name="email" 
                    placeholder="Email Address" required
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <input type="password" class="form-control" name="password" 
                    placeholder="Password (min 8 characters)" required>
            </div>

            <div class="form-group">
                <input type="password" class="form-control" name="confirm_password" 
                    placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="btn-login">Register Now</button>
        </form>

        <div class="additional-links">
            <p>Already have an account? <a href="index.php">Sign in here</a></p>
        </div>
    </div>

    <script>
        // Enhanced bubble interaction for all devices
        document.addEventListener('mousemove', handleBubbleMove);
        document.addEventListener('touchmove', handleBubbleMove, {passive: true});

        function handleBubbleMove(e) {
            const bubbles = document.querySelectorAll('.bubble');
            const clientX = e.clientX || e.touches[0].clientX;
            const clientY = e.clientY || e.touches[0].clientY;
            
            const mouseX = (clientX / window.innerWidth) * 2 - 1;
            const mouseY = (clientY / window.innerHeight) * 2 - 1;
            
            bubbles.forEach((bubble, index) => {
                const moveX = mouseX * 20 * (index % 3 + 1);
                const moveY = mouseY * 20 * (index % 3 + 1);
                
                bubble.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
        }

        // Adjust bubble sizes on resize
        window.addEventListener('resize', function() {
            const bubbles = document.querySelectorAll('.bubble');
            bubbles.forEach(bubble => {
                const currentSize = parseFloat(bubble.style.width);
                bubble.style.width = `${currentSize}px`;
                bubble.style.height = `${currentSize}px`;
            });
        });
    </script>
</body>
</html>