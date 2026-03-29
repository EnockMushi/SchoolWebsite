<?php
require_once 'sms/includes/db.php';
require_once 'sms/includes/functions.php';

// Fetch settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$site_name = $settings['site_name'] ?? 'School Management System';
$primary_color = $settings['primary_color'] ?? '#1a5f7a';
$secondary_color = $settings['secondary_color'] ?? '#86c232';

// Fetch dynamic handbook chapters
$stmt = $pdo->query("SELECT * FROM student_handbook ORDER BY sort_order ASC, chapter_number ASC");
$chapters = $stmt->fetchAll();

$copyright_text = $settings['copyright_text'] ?? '© ' . date('Y') . ' ' . $site_name . '. All rights reserved.';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Handbook - <?php echo $site_name; ?></title>
    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" type="image/png" href="sms/<?php echo $settings['site_favicon']; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="sms/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="sms/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="sms/assets/css/style.css">
    <style>
        body { padding-top: 80px; }
        .handbook-container { max-width: 900px; margin: 40px auto; }
        .glass-card {
            background: rgba(var(--bs-body-bg-rgb), 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
        }
        .chapter-list { list-style: none; padding: 0; }
        .chapter-item {
            padding: 15px;
            border-bottom: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .chapter-item:last-child { border-bottom: none; }
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
        <div class="handbook-container">
            <div class="glass-card shadow-sm">
                <div class="text-center mb-5">
                    <i class="bi bi-book text-primary fs-1 mb-3 d-block"></i>
                    <h1 class="fw-bold">Student Handbook</h1>
                    <p class="text-secondary">Guidelines and rules for students at <?php echo $site_name; ?></p>
                </div>

                <div class="alert alert-info border-0 rounded-4 p-4 mb-5 shadow-sm">
                    <div class="d-flex gap-3">
                        <i class="bi bi-info-circle-fill fs-4 text-primary"></i>
                        <div>
                            <h5 class="fw-bold mb-1">Notice to Students & Parents</h5>
                            <p class="small mb-0 opacity-75">This handbook serves as a guide for school policies, academic expectations, and student conduct. Please review it carefully at the start of each academic year.</p>
                        </div>
                    </div>
                </div>

                <h3 class="fw-bold mb-4">Table of Contents</h3>
                <div class="chapter-list">
                    <?php if (empty($chapters)): ?>
                        <div class="text-center py-4 opacity-50">
                            <p>Handbook content is being updated. Please check back soon.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chapters as $chapter): ?>
                            <div class="chapter-item">
                                <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($chapter['chapter_number']); ?></span>
                                <div>
                                    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($chapter['title']); ?></h5>
                                    <p class="small text-secondary mb-0"><?php echo nl2br(htmlspecialchars($chapter['content'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-5 text-center">
                    <button class="btn btn-primary btn-massive px-5">
                        <i class="bi bi-file-earmark-pdf me-2"></i> Download Full Handbook (PDF)
                    </button>
                </div>
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
