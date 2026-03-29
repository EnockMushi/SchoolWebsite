<?php
require_once 'sms/includes/db.php';
require_once 'sms/includes/functions.php';

// Fetch settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$site_name = $settings['site_name'] ?? 'School Management System';
$primary_color = $settings['primary_color'] ?? '#1a5f7a';
$secondary_color = $settings['secondary_color'] ?? '#86c232';

// Social links
$facebook_url = $settings['facebook_url'] ?? '';
$twitter_url = $settings['twitter_url'] ?? '';
$instagram_url = $settings['instagram_url'] ?? '';
$linkedin_url = $settings['linkedin_url'] ?? '';

$copyright_text = $settings['copyright_text'] ?? '© ' . date('Y') . ' ' . $site_name . '. All rights reserved.';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo $site_name; ?></title>
    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" type="image/png" href="sms/<?php echo $settings['site_favicon']; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="sms/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="sms/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="sms/assets/css/style.css">
    <style>
        body { padding-top: 80px; }
        .policy-container { max-width: 800px; margin: 40px auto; }
        .glass-card {
            background: rgba(var(--bs-body-bg-rgb), 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
        }
    </style>
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand-lg fixed-top bg-body shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <?php if (!empty($settings['site_logo'])): ?>
                    <div class="logo-container d-inline-block">
                        <img src="sms/<?php echo $settings['site_logo']; ?>" alt="Logo" style="max-height: 45px; width: auto; object-fit: contain;">
                    </div>
                <?php endif; ?>
                <span><?php echo $site_name; ?></span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="theme-toggle-wrapper">
                    <button class="btn" id="themeToggle" type="button"
                            onclick="if(event) event.stopPropagation(); if(typeof ThemeManager !== 'undefined') { const currentTheme = document.documentElement.getAttribute('data-bs-theme'); ThemeManager.setTheme(currentTheme === 'dark' ? 'light' : 'dark'); }">
                        <i class="bi bi-sun-fill theme-icon-light"></i>
                        <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
                    </button>
                </div>
                <a href="index.php" class="btn btn-outline-primary rounded-pill px-4">Back to Home</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="policy-container">
            <div class="glass-card shadow-sm">
                <h1 class="fw-bold mb-4 text-primary">Privacy Policy</h1>
                <p class="text-secondary mb-4">Last Updated: <?php echo date('F d, Y'); ?></p>
                
                <section class="mb-5">
                    <h3 class="h5 fw-bold text-body">1. Information We Collect</h3>
                    <p class="text-secondary">We collect information that you provide directly to us, including when you create an account, update your profile, or communicate with us through the School Management System.</p>
                </section>

                <section class="mb-5">
                    <h3 class="h5 fw-bold text-body">2. How We Use Your Information</h3>
                    <p class="text-secondary">We use the information we collect to facilitate educational processes, track attendance, manage communication between parents and teachers, and improve our services.</p>
                </section>

                <section class="mb-5">
                    <h3 class="h5 fw-bold text-body">3. Data Security</h3>
                    <p class="text-secondary">We implement appropriate technical and organizational measures to protect your personal data against unauthorized access, loss, or alteration.</p>
                </section>

                <section class="mb-5">
                    <h3 class="h5 fw-bold text-body">4. Contact Us</h3>
                    <p class="text-secondary">If you have any questions about this Privacy Policy, please contact us at <?php echo $settings['site_email'] ?? 'admin@example.com'; ?>.</p>
                </section>
            </div>
        </div>
    </div>

    <footer class="bg-body border-top py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0 text-secondary"><?php echo $copyright_text; ?></p>
        </div>
    </footer>

    <script src="sms/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="sms/assets/js/theme-manager.js"></script>
    <script src="sms/assets/js/main.js"></script>
</body>
</html>
