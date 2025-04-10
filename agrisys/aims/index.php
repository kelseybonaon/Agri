<?php
session_start();
require_once 'includes/config.php';

if(isset($_SESSION['user_id'])) {
    header("Location: ../../dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIMS - Login</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(45deg, #1a5632, #0d2d1a);
            position: relative;
            overflow: hidden;
        }

        .background-bubbles {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .glass-container {
            position: relative;
            z-index: 2;
            width: 90%;
            max-width: 500px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: #fff;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.8);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-control {
            width: 100%;
            padding: 12px 20px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #2ecc71, #1a5632);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .additional-links {
            text-align: center;
            margin-top: 25px;
        }

        .additional-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .additional-links a:hover {
            color: #fff;
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
        }

        .alert-danger {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.2);
            color: #ff4444;
        }

        .alert-success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.2);
            color: #00ff00;
        }
    </style>
</head>
<body>
    <div class="background-bubbles">
        <!-- Add dynamic bubbles for background effect -->
        <?php for($i = 0; $i < 15; $i++): ?>
        <div class="bubble" style="
            width: <?= rand(30, 80) ?>px;
            height: <?= rand(30, 80) ?>px;
            left: <?= rand(0, 95) ?>%;
            top: <?= rand(0, 95) ?>%;
            animation-delay: <?= rand(0, 20) ?>s;
        "></div>
        <?php endfor; ?>
    </div>

    <div class="glass-container">
        <div class="login-header">
            <h1>Welcome to AIMS</h1>
            <p>Agricultural Information Management System</p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form class="login-form" action="modules/auth/login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <input type="email" class="form-control" name="email" 
                    placeholder="Email Address" required>
            </div>

            <div class="form-group">
                <input type="password" class="form-control" name="password" 
                    placeholder="Password" required>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="additional-links">
            <a href="register.php">Create Account</a> | 
            <a href="forgot-password.php">Forgot Password?</a>
        </div>

    </div>

    <script>
        // Add dynamic movement to bubbles
        document.addEventListener('mousemove', (e) => {
            const bubbles = document.querySelectorAll('.bubble');
            const mouseX = e.clientX;
            const mouseY = e.clientY;

            bubbles.forEach(bubble => {
                const bubbleX = bubble.offsetLeft + bubble.offsetWidth / 2;
                const bubbleY = bubble.offsetTop + bubble.offsetHeight / 2;
                
                const distanceX = mouseX - bubbleX;
                const distanceY = mouseY - bubbleY;
                
                bubble.style.transform = `translate(
                    ${distanceX * 0.02}px, 
                    ${distanceY * 0.02}px
                )`;
            });
        });
    </script>
</body>
</html>