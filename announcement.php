<?php
require_once 'sms/includes/db.php';
require_once 'sms/includes/functions.php';

// Base URL for assets
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if (substr($base_path, -1) !== '/') $base_path .= '/';
$full_base = $base_url . $base_path;

// Fetch all settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$site_name = $settings['site_name'] ?? 'School Management System';
$primary_color = $settings['primary_color'] ?? '#1a5f7a';
$secondary_color = $settings['secondary_color'] ?? '#86c232';

// Get announcement ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: index.php");
    exit();
}

// Fetch announcement details
$stmt = $pdo->prepare("SELECT a.*, u.full_name as author FROM announcements a JOIN users u ON a.posted_by = u.id WHERE a.id = ? AND a.is_public = 1");
$stmt->execute([$id]);
$ann = $stmt->fetch();

if (!$ann) {
    header("Location: index.php");
    exit();
}

// Fetch other recent news
$stmt = $pdo->prepare("SELECT id, title, created_at, image FROM announcements WHERE id != ? AND is_public = 1 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$id]);
$recent_news = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ann['title']); ?> - <?php echo $site_name; ?></title>
    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" type="image/png" href="<?php echo $full_base . 'sms/' . $settings['site_favicon']; ?>">
    <?php endif; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo $full_base; ?>sms/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $full_base; ?>sms/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $full_base; ?>sms/assets/css/style.css">
    
    <!-- Theme Manager -->
    <script src="<?php echo $full_base; ?>sms/assets/js/theme-manager.js"></script>
    
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
            --secondary-color: <?php echo $secondary_color; ?>;
            --dark-color: var(--bs-body-color);
            --gray-color: var(--bs-secondary-color);
            --light-bg: var(--bs-tertiary-bg);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--bs-body-color);
            background: var(--bs-body-bg);
            line-height: 1.7;
        }

        .navbar {
            padding: 15px 0;
            background: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .article-header {
            padding: 60px 0;
            background: var(--bs-tertiary-bg);
            margin-bottom: 60px;
        }

        .article-title {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 25px;
            color: var(--bs-body-color);
        }

        .article-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--bs-secondary-color);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .article-image {
            width: 100%;
            max-height: 600px;
            object-fit: cover;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .image-container {
            position: relative;
            margin-bottom: 50px;
            overflow: hidden;
            border-radius: 30px;
            cursor: zoom-in;
            background: var(--bs-tertiary-bg);
        }

        .image-container:hover .article-image {
            transform: scale(1.02);
        }

        .zoom-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(var(--bs-body-bg-rgb), 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid var(--bs-border-color);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(10px);
            z-index: 10;
        }

        .image-container:hover .zoom-btn {
            opacity: 1;
            transform: translateY(0);
        }

        .zoom-btn:hover {
            background: var(--primary-color);
            color: #fff;
            transform: scale(1.1) !important;
        }

        #imageZoomModal .modal-content {
            background: transparent;
            border: none;
        }

        #imageZoomModal .modal-body {
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #imageZoomModal .zoom-img-full {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 15px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        #imageZoomModal .btn-close-white {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2010;
            background-color: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        #imageZoomModal .btn-close-white:hover {
            background-color: rgba(255,255,255,0.4);
            transform: rotate(90deg);
        }

        .article-content {
            font-size: 1.15rem;
            color: var(--bs-body-color);
            opacity: 0.9;
        }

        .article-content p {
            margin-bottom: 1.5rem;
        }

        .sidebar-card {
            background: var(--bs-tertiary-bg);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid var(--bs-border-color);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            position: sticky;
            top: 100px;
        }

        .recent-news-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            text-decoration: none;
            color: var(--bs-body-color);
            transition: all 0.3s ease;
        }

        .recent-news-item:hover {
            transform: translateX(5px);
            color: var(--primary-color);
        }

        .recent-news-img {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            object-fit: cover;
        }

        .recent-news-info h5 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .recent-news-info small {
            color: var(--bs-secondary-color);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateX(-5px);
        }

        footer {
            background: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
            padding: 60px 0;
            margin-top: 100px;
            border-top: 1px solid var(--bs-border-color);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <?php if (!empty($settings['site_logo'])): ?>
                    <div class="logo-container d-inline-block">
                        <img src="<?php echo $full_base . 'sms/' . $settings['site_logo']; ?>" alt="Logo" style="max-height: 50px; width: auto; object-fit: contain;">
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
                <a href="index.php" class="btn btn-outline-primary rounded-pill fw-bold px-4">Back to Home</a>
            </div>
        </div>
    </nav>

    <header class="article-header">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <a href="index.php#news" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to News
                    </a>
                    <div class="mb-3">
                        <span class="badge rounded-pill border <?php echo $ann['type'] == 'event' ? 'border-info text-info' : ($ann['type'] == 'notice' ? 'border-warning text-warning' : 'border-primary text-primary'); ?> bg-transparent px-4 py-2">
                            <?php echo ucfirst($ann['type']); ?>
                        </span>
                    </div>
                    <h1 class="article-title"><?php echo htmlspecialchars($ann['title']); ?></h1>
                    <div class="article-meta">
                        <div class="meta-item">
                            <i class="bi bi-person-circle text-primary"></i>
                            By <?php echo htmlspecialchars($ann['author']); ?>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-calendar3 text-primary"></i>
                            <?php echo date('F d, Y', strtotime($ann['created_at'])); ?>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-clock text-primary"></i>
                            <?php echo date('h:i A', strtotime($ann['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($ann['image']): ?>
                    <div class="image-container shadow-sm" data-bs-toggle="modal" data-bs-target="#imageZoomModal">
                        <img src="sms/assets/images/announcements/<?php echo $ann['image']; ?>" class="article-image" alt="<?php echo htmlspecialchars($ann['title']); ?>">
                        <button class="zoom-btn" title="Zoom Image">
                            <i class="bi bi-zoom-in fs-4"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="article-content">
                    <?php echo nl2br($ann['content']); ?>
                </div>

                <hr class="my-5">
                
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="share-buttons d-flex align-items-center gap-3">
                        <span class="fw-bold text-secondary">Share this:</span>
                        <a href="https://facebook.com/sharer/sharer.php?u=<?php echo urlencode($full_base . 'announcement.php?id=' . $id); ?>" target="_blank" class="btn btn-body-secondary rounded-circle"><i class="bi bi-facebook text-primary"></i></a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($full_base . 'announcement.php?id=' . $id); ?>&text=<?php echo urlencode($ann['title']); ?>" target="_blank" class="btn btn-body-secondary rounded-circle"><i class="bi bi-twitter-x text-primary"></i></a>
                        <a href="whatsapp://send?text=<?php echo urlencode($ann['title'] . ' ' . $full_base . 'announcement.php?id=' . $id); ?>" class="btn btn-body-secondary rounded-circle"><i class="bi bi-whatsapp text-primary"></i></a>
                    </div>
                    <a href="index.php#news" class="btn btn-primary rounded-pill px-5 fw-bold">View More News</a>
                </div>
            </div>
            
            <div class="col-lg-4 mt-5 mt-lg-0">
                <div class="sidebar-card">
                    <h4 class="fw-bold mb-4">Recent News</h4>
                    <?php foreach ($recent_news as $recent): ?>
                        <a href="announcement.php?id=<?php echo $recent['id']; ?>" class="recent-news-item">
                            <?php if ($recent['image']): ?>
                                <img src="sms/assets/images/announcements/<?php echo $recent['image']; ?>" class="recent-news-img" alt="">
                            <?php else: ?>
                                <div class="recent-news-img bg-body-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-newspaper text-secondary opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="recent-news-info">
                                <h5><?php echo htmlspecialchars($recent['title']); ?></h5>
                                <small><?php echo date('M d, Y', strtotime($recent['created_at'])); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. All rights reserved.</p>
        </div>
    </footer>

    <?php if ($ann['image']): ?>
    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <img src="sms/assets/images/announcements/<?php echo $ann['image']; ?>" class="zoom-img-full" alt="Full size image">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="sms/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="sms/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image Zoom Logic
            const zoomBtn = document.querySelector('.zoom-btn');
            const imageContainer = document.querySelector('.image-container');
            if (zoomBtn && imageContainer) {
                const zoomModal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
                
                const showZoom = (e) => {
                    e.stopPropagation();
                    zoomModal.show();
                };

                zoomBtn.addEventListener('click', showZoom);
                imageContainer.addEventListener('click', showZoom);
            }
        });
    </script>
</body>
</html>
