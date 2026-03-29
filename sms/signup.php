<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Fetch settings
$site_name = getSetting('site_name', $pdo) ?: 'School Management System';
$site_favicon = getSetting('site_favicon', $pdo);
$site_logo = getSetting('site_logo', $pdo);
$primary_color = getSetting('primary_color', $pdo) ?: '#1a5f7a';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    $student_name = sanitize($_POST['student_name'] ?? '');

    if (empty($full_name) || empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif ($role === 'parent' && empty($student_name)) {
        $error = "Please provide your child's registration name.";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $status = 'pending'; // All signups are pending by default
            
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, status, signup_student_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $username, $email, $hashed_password, $role, $status, $student_name])) {
                $success = "The signup was successful but pending approval, please signin later while approval being analysed.";
                
                // Notify Admin and Headmaster
                $stmt_notify = $pdo->prepare("SELECT id FROM users WHERE role IN ('admin', 'headmaster')");
                $stmt_notify->execute();
                $admins = $stmt_notify->fetchAll();
                
                $notif_msg = "New signup request from $full_name ($role) requires your approval.";
                 foreach ($admins as $admin) {
                     $stmt_in = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                     $stmt_in->execute([$admin['id'], $notif_msg, 'admin/approvals.php']);
                 }
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Create Account - <?php echo $site_name; ?></title>
    <?php if ($site_favicon): ?>
        <link rel="icon" type="image/png" href="<?php echo $site_favicon; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Theme Manager -->
    <script src="assets/js/theme-manager.js"></script>
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
        .signup-card {
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            background: var(--bs-body-bg);
            backdrop-filter: blur(20px);
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--bs-border-color);
        }

        [data-bs-theme="dark"] .signup-card img {
            background-color: #ffffff !important;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        .form-control, .form-select {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--bs-border-color);
            background-color: var(--bs-body-secondary);
            color: var(--bs-body-color);
        }
        .form-control:focus, .form-select:focus {
            background-color: var(--bs-body-bg) !important;
            border-color: var(--primary-color);
            color: var(--bs-body-color);
        }
        .form-control::placeholder {
            color: var(--bs-secondary-color);
            opacity: 0.6;
        }
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.8rem;
            font-weight: 700;
            border-radius: 0.75rem;
        }
        .brand-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1.25rem;
            margin: 0 auto 1rem;
        }
        #studentField {
            display: none;
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
            background: var(--bs-body-secondary);
            backdrop-filter: blur(10px);
            border: 1px solid var(--bs-border-color);
        }
        .back-to-web:hover {
            opacity: 1;
            color: var(--primary-color);
            background: var(--bs-tertiary-bg);
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
            .theme-toggle-wrapper {
                top: 1rem;
                right: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-to-web">
        <i class="bi bi-arrow-left"></i> Back to Website
    </a>

    <div class="theme-toggle-wrapper">
        <button class="btn" id="themeToggle" type="button"
                onclick="if(event) event.stopPropagation(); if(typeof ThemeManager !== 'undefined') { const currentTheme = document.documentElement.getAttribute('data-bs-theme'); ThemeManager.setTheme(currentTheme === 'dark' ? 'light' : 'dark'); }">
            <i class="bi bi-sun-fill theme-icon-light"></i>
            <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
        </button>
    </div>
    <div class="signup-card">
        <div class="text-center mb-4">
            <?php if ($site_logo): ?>
                <div class="logo-container d-inline-block mb-4">
                    <img src="<?php echo $site_logo; ?>" alt="Logo" style="max-height: 80px; width: auto; object-fit: contain;">
                </div>
            <?php else: ?>
                <div class="brand-logo">
                    <i class="bi bi-person-plus-fill text-white fs-3"></i>
                </div>
            <?php endif; ?>
            <h4 class="fw-bold mb-1">Join Our Portal</h4>
            <p class="text-secondary small">Create an account to access school services.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 rounded-3 border-0 mb-3">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success small py-3 rounded-3 border-0 mb-3 text-center">
                <i class="bi bi-check-circle-fill d-block fs-3 mb-2"></i>
                <?php echo $success; ?>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-success btn-sm rounded-pill px-4">Go to Login</a>
                </div>
            </div>
        <?php else: ?>
            <form action="" method="POST" id="signupForm">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="John Doe" required value="<?php echo $_POST['full_name'] ?? ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="johndoe" required value="<?php echo $_POST['username'] ?? ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Email (Optional)</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">I am a...</label>
                        <select name="role" id="roleSelect" class="form-select" required onchange="toggleStudentField()">
                            <option value="">Choose role</option>
                            <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                            <option value="parent" <?php echo (isset($_POST['role']) && $_POST['role'] == 'parent') ? 'selected' : ''; ?>>Parent / Guardian</option>
                        </select>
                    </div>
                    <div class="col-12" id="studentField">
                        <label class="form-label small fw-bold text-secondary">Student's Full Registration Name</label>
                        <input type="text" name="student_name" class="form-control" placeholder="Enter student's full name" value="<?php echo $_POST['student_name'] ?? ''; ?>">
                        <div class="form-text small text-info"><i class="bi bi-info-circle me-1"></i> Required for verification by the Headmaster.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-4 py-3 fw-bold text-uppercase tracking-wider">
                    Create Account
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top border-secondary opacity-25">
                <p class="small text-secondary">
                    Already have an account? <a href="index.php" class="text-decoration-none fw-bold">Sign In</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleStudentField() {
            const role = document.getElementById('roleSelect').value;
            const studentField = document.getElementById('studentField');
            if (role === 'parent') {
                studentField.style.display = 'block';
                studentField.querySelector('input').required = true;
            } else {
                studentField.style.display = 'none';
                studentField.querySelector('input').required = false;
            }
        }
        // Initialize on load
        toggleStudentField();
    </script>
</body>
</html>
