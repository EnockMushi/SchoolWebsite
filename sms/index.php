<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Fetch settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$site_name = $settings['site_name'] ?? 'School Management System';
$primary_color = $settings['primary_color'] ?? '#1a5f7a';
$site_logo = $settings['site_logo'] ?? '';
$site_favicon = $settings['site_favicon'] ?? '';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status'];
            
            if ($user['status'] === 'pending') {
                $error = "Your signup was successful but pending approval, please signin later while approval being analysed.";
                session_destroy();
            } elseif ($user['status'] === 'inactive') {
                $error = "Your account has been deactivated. Please contact the administrator.";
                session_destroy();
            } else {
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - <?php echo $site_name; ?></title>
    <?php if ($site_favicon): ?>
        <link rel="icon" type="image/png" href="<?php echo $site_favicon; ?>">
    <?php endif; ?>
    <!-- Offline Bootstrap -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.1">
    
    <!-- Theme Manager -->
    <script src="assets/js/theme-manager.js?v=1.0.1"></script>
    
    <script>
        // Theme initialization to prevent flicker
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'auto') {
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', systemTheme);
            } else {
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
            }
        })();
    </script>
    
    <style>
        :root {
            --primary-color: <?php echo $primary_color; ?>;
            --primary-rgb: <?php 
                list($r, $g, $b) = sscanf($primary_color, "#%02x%02x%02x");
                echo "$r, $g, $b";
            ?>;
        }
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at top right, rgba(var(--primary-rgb), 0.1), transparent),
                        radial-gradient(circle at bottom left, rgba(var(--primary-rgb), 0.1), transparent),
                        var(--bs-body-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 3rem;
            background: var(--bs-body-bg);
            backdrop-filter: blur(20px);
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--bs-border-color);
        }

        [data-bs-theme="dark"] .login-card img {
            background-color: #ffffff !important;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
                border-radius: 1.5rem;
            }
            .brand-logo {
                width: 50px;
                height: 50px;
                margin-bottom: 1rem;
            }
            .brand-logo i {
                font-size: 1.5rem !important;
            }
            h3 {
                font-size: 1.25rem;
            }
        }
        .password-toggle {
            cursor: pointer;
            transition: color 0.2s;
        }
        .password-toggle:hover {
            color: var(--primary-color);
        }
        .brand-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1.25rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(var(--primary-rgb), 0.3);
        }
        .back-to-web {
            position: absolute;
            top: 2rem;
            left: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--bs-body-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            opacity: 0.8;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            background: var(--bs-tertiary-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--bs-border-color);
            z-index: 1000;
        }
        .back-to-web:hover {
            opacity: 1;
            color: var(--bs-body-color);
            background: var(--bs-tertiary-bg);
            filter: brightness(1.1);
            transform: translateX(-5px);
        }
        .theme-toggle-wrapper {
            position: absolute;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
        }
        @media (max-width: 768px) {
            .back-to-web {
                top: 1rem;
                left: 1rem;
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-to-web">
        <i class="bi bi-arrow-left"></i>
        Back to Website
    </a>

    <div class="theme-toggle-wrapper">
        <button class="btn" id="themeToggle" type="button"
                onclick="if(event) event.stopPropagation(); if(typeof ThemeManager !== 'undefined') { const currentTheme = document.documentElement.getAttribute('data-bs-theme'); ThemeManager.setTheme(currentTheme === 'dark' ? 'light' : 'dark'); }">
            <i class="bi bi-sun-fill theme-icon-light"></i>
            <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
        </button>
    </div>
    <div class="login-card">
        <div class="text-center mb-5">
            <?php if ($site_logo): ?>
                <div class="logo-container d-inline-block mb-4">
                    <img src="<?php echo $site_logo; ?>" alt="Logo" style="max-height: 80px; width: auto; object-fit: contain;">
                </div>
            <?php else: ?>
                <div class="brand-logo">
                    <i class="bi bi-mortarboard-fill text-white fs-2"></i>
                </div>
            <?php endif; ?>
            <h3 class="fw-bold mb-1"><?php echo $site_name; ?></h3>
            <p class="text-secondary">Welcome back! Please sign in.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 rounded-4 border-0 mb-4">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label small fw-bold text-uppercase tracking-wider text-secondary ps-1" style="font-size: 0.7rem;">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label small fw-bold text-uppercase tracking-wider text-secondary ps-1" style="font-size: 0.7rem;">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    <span class="input-group-text border-start-0" style="border-left: none !important; border-radius: 0 0.75rem 0.75rem 0 !important; cursor: pointer;">
                        <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                    </span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 px-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label small text-secondary" for="rememberMe">Remember me</label>
                </div>
                <a href="#" class="small text-decoration-none fw-semibold" style="color: var(--primary-color);">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 rounded-4 py-3 fw-bold text-uppercase tracking-wider">
                Sign In
            </button>
        </form>
        
        <div class="text-center mt-5 pt-3 border-top">
            <p class="small text-secondary mb-3">
                Don't have an account? <a href="signup.php" class="text-decoration-none fw-bold">Sign Up</a>
            </p>
            <p class="small text-secondary mb-3">
                &copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>
            </p>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
        });
    </script>
</body>
</html>