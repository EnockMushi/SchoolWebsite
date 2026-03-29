<?php
require_once 'sms/includes/db.php';
require_once 'sms/includes/functions.php';

// Fetch settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$site_name = $settings['site_name'] ?? 'School Management System';
$primary_color = $settings['primary_color'] ?? '#1a5f7a';
$secondary_color = $settings['secondary_color'] ?? '#86c232';

// Fetch events from announcements table
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE type = 'event' AND is_public = 1 ORDER BY created_at DESC");
$stmt->execute();
$events = $stmt->fetchAll();

$copyright_text = $settings['copyright_text'] ?? '© ' . date('Y') . ' ' . $site_name . '. All rights reserved.';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Calendar - <?php echo $site_name; ?></title>
    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" type="image/png" href="sms/<?php echo $settings['site_favicon']; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="sms/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="sms/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="sms/assets/css/style.css">
    <style>
        body { padding-top: 80px; }
        .calendar-container { max-width: 1000px; margin: 40px auto; }
        .event-card {
            background: var(--bs-body-bg);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
            height: 100%;
        }
        .event-card:hover { transform: translateY(-5px); }
        .event-date {
            background: var(--primary-color);
            color: white;
            padding: 10px;
            text-align: center;
            min-width: 80px;
        }
        .event-day { font-size: 1.5rem; font-weight: 800; line-height: 1; }
        .event-month { font-size: 0.8rem; text-transform: uppercase; font-weight: 600; }
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
        <div class="calendar-container">
            <div class="text-center mb-5">
                <h1 class="fw-bold text-primary">School Calendar & Events</h1>
                <p class="text-secondary">Stay updated with the latest happenings at <?php echo $site_name; ?></p>
            </div>

            <?php if (empty($events)): ?>
                <div class="text-center p-5 bg-body rounded-4 shadow-sm">
                    <i class="bi bi-calendar-x fs-1 text-secondary mb-3"></i>
                    <h3 class="h5 text-secondary">No upcoming events scheduled at the moment.</h3>
                    <p class="text-muted small">Please check back later for updates.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($events as $event): 
                        $date = strtotime($event['created_at']);
                        $day = date('d', $date);
                        $month = date('M', $date);
                        $year = date('Y', $date);
                    ?>
                        <div class="col-md-6">
                            <div class="event-card shadow-sm d-flex">
                                <div class="event-date d-flex flex-column justify-content-center">
                                    <span class="event-month"><?php echo $month; ?></span>
                                    <span class="event-day"><?php echo $day; ?></span>
                                    <span class="event-month"><?php echo $year; ?></span>
                                </div>
                                <div class="p-4 flex-grow-1">
                                    <h3 class="h5 fw-bold mb-2"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p class="text-secondary small mb-0"><?php echo nl2br(htmlspecialchars(substr($event['content'], 0, 150))); ?>...</p>
                                    <a href="announcement.php?id=<?php echo $event['id']; ?>" class="btn btn-link text-primary p-0 mt-3 text-decoration-none fw-bold small">Read More <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
