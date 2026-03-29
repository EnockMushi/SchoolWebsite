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

$site_name = $settings['site_name'] ?? 'Kilimani Primary School';
$site_tagline = $settings['site_tagline'] ?? 'Excellence in Education';
$site_email = $settings['site_email'] ?? 'info@kilimanischool.ac.tz';
$site_phone = $settings['site_phone'] ?? '+255 22 211 0000';
$site_address = $settings['site_address'] ?? 'Kilimani St, Dar es Salaam, Tanzania';
$site_about = $settings['site_about'] ?? 'Kilimani Primary School is dedicated to providing high-quality education and fostering a supportive learning environment for all students.';
$primary_color = $settings['primary_color'] ?? '#1a5f7a';
$secondary_color = $settings['secondary_color'] ?? '#86c232';
$copyright_text = $settings['copyright_text'] ?? '© ' . date('Y') . ' ' . $site_name . '. All rights reserved.';
$google_maps_embed = $settings['google_maps_embed'] ?? '';

// Social links
$facebook_url = $settings['facebook_url'] ?? '';
$twitter_url = $settings['twitter_url'] ?? '';
$instagram_url = $settings['instagram_url'] ?? '';
$linkedin_url = $settings['linkedin_url'] ?? '';
$site_favicon = $settings['site_favicon'] ?? '';

// Fetch latest public announcements
$stmt = $pdo->query("SELECT * FROM announcements WHERE is_public = 1 ORDER BY created_at DESC LIMIT 3");
$public_announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_name; ?> - Dar es Salaam, Tanzania</title>
    <?php if ($site_favicon): ?>
        <link rel="icon" type="image/png" href="<?php echo $full_base . 'sms/' . $site_favicon; ?>">
    <?php endif; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Use full paths for assets to ensure they load regardless of URL structure -->
    <link rel="stylesheet" href="<?php echo $full_base; ?>sms/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $full_base; ?>sms/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $full_base; ?>sms/assets/css/style.css?v=1.0.1">
    
    <!-- Theme Manager -->
    <script src="<?php echo $full_base; ?>sms/assets/js/theme-manager.js?v=1.0.1"></script>
    
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
    
    <?php
    // Helper function to convert hex to rgba
    function hexToRgba($hex, $alpha = 1) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "rgba($r, $g, $b, $alpha)";
    }

    function hexToRgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "$r, $g, $b";
    }

    $primary_rgba = hexToRgba($primary_color, 0.9);
    $primary_rgb = hexToRgb($primary_color);
    ?>
    <style>
        :root {
            --primary-color: <?php echo $primary_color; ?>;
            --primary-color-rgb: <?php echo $primary_rgb; ?>;
            --secondary-color: <?php echo $secondary_color; ?>;
            --radius: 20px;
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --hero-overlay: rgba(9, 9, 11, 0.85);
            --hero-text: #ffffff;
        }

        [data-bs-theme="light"] {
            --hero-overlay: rgba(255, 255, 255, 0.85);
            --hero-text: var(--bs-body-color);
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 90px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--bs-body-color);
            overflow-x: hidden;
            background: var(--bs-body-bg);
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navbar {
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1030;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            will-change: padding, background, backdrop-filter;
        }

        .navbar.scrolled {
            padding: 10px 0;
            background: var(--bs-body-bg);
            backdrop-filter: blur(20px) saturate(180%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid var(--bs-border-color);
        }

        @media (max-width: 992px) {
            .navbar {
                padding: 15px 0;
                background: transparent;
                backdrop-filter: none;
                border-bottom: none;
            }

            .navbar.scrolled,
            .navbar.mobile-menu-open {
                background: var(--bs-body-bg);
                backdrop-filter: blur(15px);
                border-bottom: 1px solid var(--bs-border-color);
            }

            .navbar.mobile-menu-open .logo {
                color: var(--primary-color) !important;
            }

            .navbar.mobile-menu-open .hamburger-inner,
            .navbar.mobile-menu-open .hamburger-inner::before,
            .navbar.mobile-menu-open .hamburger-inner::after {
                background-color: var(--primary-color) !important;
            }
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--hero-text);
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.4s ease;
        }

        .logo img {
            max-height: 50px;
            width: auto;
            object-fit: contain;
        }

        [data-bs-theme="dark"] .logo img {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 5px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }

        .navbar.scrolled .logo {
            color: var(--primary-color);
        }

        .navbar ul {
            display: flex;
            list-style: none;
            gap: 30px;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .navbar ul li {
            position: relative;
        }

        .navbar ul li a {
            text-decoration: none;
            color: var(--hero-text);
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            opacity: 0.8;
            position: relative;
            padding: 5px 0;
            display: block;
        }

        .navbar.scrolled ul li a {
            color: var(--bs-body-color);
        }

        .navbar ul li a:hover:not(.nav-login-btn),
        .navbar ul li a.active:not(.nav-login-btn) {
            color: var(--primary-color) !important;
            opacity: 1 !important;
        }

        .navbar ul li a:hover:not(.nav-login-btn) {
            transform: translateY(-1px);
        }

        .navbar.scrolled ul li a:hover:not(.nav-login-btn),
        .navbar.scrolled ul li a.active:not(.nav-login-btn) {
            color: var(--primary-color) !important;
            opacity: 1 !important;
        }

        .navbar ul li a:not(.nav-login-btn)::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--secondary-color);
            transition: width 0.3s ease;
            pointer-events: none;
        }

        .navbar ul li a:hover:not(.nav-login-btn)::after,
        .navbar ul li a.active:not(.nav-login-btn)::after {
            width: 100% !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .nav-login-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff !important;
            border: 2px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 1;
            text-decoration: none;
            padding: 12px 35px !important;
            border-radius: 100px !important;
            font-weight: 700 !important;
            letter-spacing: 1px !important;
            text-transform: uppercase !important;
            font-size: 1rem !important;
            overflow: hidden;
            display: inline-block;
            isolation: isolate;
            transition: all 0.3s ease;
        }

        .nav-login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            transform: skewX(-25deg);
        }

        .nav-login-btn:hover::before {
            left: 150%;
        }

        .nav-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(var(--primary-color-rgb), 0.3);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .hero-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff !important;
            border: 2px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
            text-decoration: none;
            padding: 18px 45px !important;
            border-radius: 100px !important;
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            letter-spacing: 2px !important;
            text-transform: uppercase !important;
            overflow: hidden;
            display: inline-block;
            isolation: isolate;
            transition: all 0.3s ease;
        }

        .hero-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            transform: skewX(-25deg);
        }

        .hero-btn:hover::before {
            left: 150%;
        }

        .hero-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(var(--primary-color-rgb), 0.4);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .hero {
            background: linear-gradient(135deg, <?php echo $primary_rgba; ?>, var(--hero-overlay)), url('https://images.unsplash.com/photo-1546410531-bb4caa6b424d?q=80&w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--hero-text);
            text-align: center;
            padding: 0 20px;
            clip-path: ellipse(150% 100% at 50% 0%);
        }
        .hero h1 { 
            font-size: clamp(2.5rem, 5vw, 4.5rem); 
            margin: 0 auto 24px; 
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            max-width: 900px;
        }
        .hero p { 
            font-size: clamp(1rem, 2vw, 1.25rem); 
            max-width: 800px; 
            margin: 0 auto 40px; 
            opacity: 0.9;
            font-weight: 400;
        }
        
        .section { 
            padding: 120px 0; 
            position: relative;
            z-index: 1;
        }
        .section-title { text-align: center; margin-bottom: 70px; }
        .section-title h2 { 
            font-size: 3rem; 
            color: var(--bs-body-color);
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        .feature-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--bs-border-color);
            background: var(--bs-tertiary-bg);
            padding: 50px 40px;
            border-radius: var(--radius);
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: var(--shadow-lg);
        }

        .news-card {
            background: var(--bs-tertiary-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid var(--bs-border-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 50px;
            align-items: start;
        }

        .contact-card {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 40px;
            background: var(--bs-tertiary-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--bs-border-color);
        }

        .contact-icon {
            font-size: 1.5rem;
            width: 50px;
            height: 50px;
            background: var(--bs-body-bg);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-group { margin-bottom: 25px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            font-size: 0.9rem;
            color: var(--bs-secondary-color);
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
            transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--bs-tertiary-bg);
            box-shadow: 0 0 0 4px rgba(var(--primary-color-rgb), 0.1);
        }

        .footer {
            background: var(--bs-tertiary-bg);
            padding: 80px 0 30px;
            border-top: 1px solid var(--bs-border-color);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 60px;
            margin-bottom: 60px;
        }

        .footer-col h3 {
            color: var(--bs-body-color);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li { margin-bottom: 12px; }
        .footer-links li a {
            color: inherit;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .footer-links li a:hover {
            color: var(--secondary-color);
            padding-left: 5px;
        }

        .animate-fade {
            animation: fadeIn 1s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .reveal { transition: all 1s ease; }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-toggler {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            outline: none !important;
            box-shadow: none !important;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            z-index: 10001;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            line-height: 0;
        }

        .navbar-toggler:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .navbar.scrolled .navbar-toggler:hover {
            background: rgba(var(--primary-color-rgb), 0.05);
        }

        .hamburger-box {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .hamburger-inner, 
        .hamburger-inner::before, 
        .hamburger-inner::after {
            width: 24px;
            height: 2px;
            background-color: var(--hero-text);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 4px;
        }

        .hamburger-inner {
            position: relative;
        }

        .hamburger-inner::before, 
        .hamburger-inner::after {
            content: "";
            display: block;
            position: absolute;
            left: 0;
        }

        .hamburger-inner::before { top: -8px; }
        .hamburger-inner::after { top: 8px; }

        .navbar.scrolled .hamburger-inner,
        .navbar.scrolled .hamburger-inner::before,
        .navbar.scrolled .hamburger-inner::after {
            background-color: var(--primary-color);
        }

        /* Active State (X) */
        .navbar-toggler.active .hamburger-inner {
            background-color: transparent !important;
        }

        .navbar-toggler.active .hamburger-inner::before {
            transform: translateY(8px) rotate(45deg);
            background-color: var(--bs-body-color) !important;
        }

        .navbar-toggler.active .hamburger-inner::after {
            transform: translateY(-8px) rotate(-45deg);
            background-color: var(--bs-body-color) !important;
        }

        .navbar.mobile-menu-open .navbar-toggler {
            background: transparent;
        }

        /* Modal Theme Consistency */
        .modal-content {
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
        }
        .modal-header {
            border-bottom: 1px solid var(--bs-border-color);
        }
        .modal-footer {
            border-top: 1px solid var(--bs-border-color);
        }

        @media (max-width: 992px) {
            .contact-grid, .footer-grid, .about-grid { grid-template-columns: 1fr !important; }
            
            .navbar ul { 
                display: flex; 
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background: var(--bs-body-bg);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 30px;
                padding: 40px;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-20px);
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 9999;
            }

            .navbar ul.active {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .navbar ul li {
                width: 100%;
                text-align: center;
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .navbar ul.active li {
                opacity: 1;
                transform: translateY(0);
            }

            .navbar ul.active li:nth-child(1) { transition-delay: 0.1s; }
            .navbar ul.active li:nth-child(2) { transition-delay: 0.15s; }
            .navbar ul.active li:nth-child(3) { transition-delay: 0.2s; }
            .navbar ul.active li:nth-child(4) { transition-delay: 0.25s; }
            .navbar ul.active li:nth-child(5) { transition-delay: 0.3s; }

            .navbar ul li a {
                color: var(--bs-body-color) !important;
                font-size: 1.5rem;
                font-weight: 700;
                padding: 15px;
                display: block;
                transition: all 0.3s ease;
            }

            .navbar ul li a::after {
                display: none;
            }

            .navbar ul li a:hover:not(.nav-login-btn),
            .navbar ul li a.active:not(.nav-login-btn) {
                color: var(--primary-color) !important;
                transform: scale(1.05);
            }

            .navbar ul li a.nav-login-btn {
                margin-top: 20px;
                width: auto !important;
                display: inline-flex !important;
                font-size: 1.3rem !important;
                padding: 30px 150px !important;
                color: #fff !important;
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
                border-radius: 100px !important;
                transform: none !important;
                box-shadow: 0 10px 25px rgba(var(--primary-color-rgb), 0.3) !important;
                gap: 12px;
                line-height: 1;
                font-weight: 900 !important;
                text-transform: uppercase !important;
                letter-spacing: 2px !important;
            }

            .navbar ul li a.nav-login-btn:hover {
                transform: translateY(-5px) scale(1.02) !important;
                color: #fff !important;
                box-shadow: 0 15px 30px rgba(var(--primary-color-rgb), 0.4) !important;
            }
        }
    </style>
</head>
<body class="animate-fade">
    <nav class="navbar">
        <div class="container">
            <a href="<?php echo $full_base; ?>" class="logo">
                <?php if (!empty($settings['site_logo'])): ?>
                    <div class="logo-container">
                        <img src="<?php echo $full_base . 'sms/' . $settings['site_logo']; ?>" alt="Logo">
                    </div>
                <?php endif; ?>
                <span><?php echo $site_name; ?></span>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <div class="theme-toggle-wrapper ms-lg-3">
                    <button class="btn" id="themeToggle" type="button"
                            onclick="if(event) event.stopPropagation(); if(typeof ThemeManager !== 'undefined') { const currentTheme = document.documentElement.getAttribute('data-bs-theme'); ThemeManager.setTheme(currentTheme === 'dark' ? 'light' : 'dark'); }">
                        <i class="bi bi-sun-fill theme-icon-light"></i>
                        <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
                    </button>
                </div>

                <button class="navbar-toggler d-lg-none" type="button" onclick="toggleMenu()" aria-label="Toggle navigation">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>

            <ul id="navMenu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#academics">Academics</a></li>
                <li><a href="#news">News</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="sms/<?php echo $_SESSION['role'] ?? 'index.php'; ?>/dashboard.php" class="nav-login-btn">Dashboard</a></li>
                <?php else: ?>
                    <li><a href="sms/index.php" class="nav-login-btn">Portal Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <script>
        // Navbar and scroll handling
        const nav = document.querySelector('.navbar');
        const sections = document.querySelectorAll('section[id], header[id]');
        const navLinks = document.querySelectorAll('#navMenu a:not(.nav-login-btn)');
        let isScrolling = false;

        function handleScroll() {
            if (isScrolling) return; // Skip if we're smooth scrolling from a click
            
            const scrollPos = window.scrollY || window.pageYOffset;
            const windowHeight = window.innerHeight;
            const bodyHeight = document.body.offsetHeight;

            // Toggle scrolled class
            if (scrollPos > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            // Update active link based on scroll position
            let current = '';
            
            // Fallback for very top of page
            if (scrollPos < 100) {
                current = 'home';
            } else if (scrollPos + windowHeight >= bodyHeight - 100) {
                // Check if we are at the bottom of the page
                const lastSection = sections[sections.length - 1];
                if (lastSection) {
                    current = lastSection.getAttribute('id');
                }
            } else {
                // Precise section detection
                sections.forEach(section => {
                    const rect = section.getBoundingClientRect();
                    // If the section is taking up the majority of the top half of the screen
                    if (rect.top <= 150 && rect.bottom >= 150) {
                        current = section.getAttribute('id');
                    }
                });
            }

            if (current) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    const href = link.getAttribute('href');
                    if (href === '#' + current || href === current) {
                        link.classList.add('active');
                    }
                });
            }
        }

        window.addEventListener('scroll', handleScroll);
        window.addEventListener('load', handleScroll); // Initial check

        function toggleMenu() {
            const menu = document.getElementById('navMenu');
            const logo = document.querySelector('.logo');
            const toggler = document.querySelector('.navbar-toggler');
            const navbar = document.querySelector('.navbar');
            
            menu.classList.toggle('active');
            navbar.classList.toggle('mobile-menu-open');
            toggler.classList.toggle('active');
            
            if (menu.classList.contains('active')) {
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            } else {
                document.body.style.overflow = 'auto'; // Re-enable scrolling
            }
        }

        // Close menu on link click
        document.querySelectorAll('#navMenu a').forEach(link => {
            link.addEventListener('click', (e) => {
                const menu = document.getElementById('navMenu');
                if (menu.classList.contains('active')) {
                    toggleMenu(); // Close the menu properly
                }
                
                // If it's an internal link
                if (link.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const targetId = link.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        isScrolling = true;
                        
                        // Immediate active state feedback
                        navLinks.forEach(l => l.classList.remove('active'));
                        link.classList.add('active');

                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });

                        // Re-enable scroll spy after scroll finishes
                        setTimeout(() => {
                            isScrolling = false;
                            handleScroll(); // Run once to ensure correct state
                        }, 800); // 800ms is usually enough for smooth scroll
                    }
                }
            });
        });
    </script>

    <section id="home" class="hero">
        <div class="container">
            <h1 class="animate-fade reveal" style="animation-delay: 0.2s;"><?php echo $site_tagline; ?></h1>
            <p class="animate-fade reveal" style="animation-delay: 0.4s;"><?php echo $site_about; ?></p>
            <div class="animate-fade reveal" style="animation-delay: 0.6s;">
                <a href="#about" class="hero-btn">Discover More</a>
            </div>
        </div>
    </section>

    <section id="about" class="section reveal">
        <div class="container">
            <div class="about-grid" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 80px; align-items: center;">
                <div class="reveal-left">
                    <span style="color: var(--secondary-color); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Welcome to <?php echo $site_name; ?></span>
                    <h2 style="font-size: 3rem; color: var(--bs-body-color); font-weight: 800; margin: 15px 0 30px; line-height: 1.2;">Building a Brighter <span class="gradient-text">Future</span> Together</h2>
                    <p style="font-size: 1.15rem; line-height: 1.8; color: var(--bs-secondary-color); margin-bottom: 25px;"><?php echo $site_about; ?></p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div style="display: flex; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: rgba(var(--secondary-color-rgb, 134, 194, 50), 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--secondary-color); font-size: 1.5rem;">🎓</div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: var(--bs-body-color);">Expert Teachers</h4>
                                <p style="font-size: 0.9rem; color: var(--bs-secondary-color);">Highly qualified and passionate educators.</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: rgba(var(--primary-color-rgb), 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-size: 1.5rem;">🏢</div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: var(--bs-body-color);">Modern Campus</h4>
                                <p style="font-size: 0.9rem; color: var(--bs-secondary-color);">Safe and stimulating learning environment.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="reveal-right" style="position: relative;">
                    <div style="position: relative; z-index: 1;">
                        <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=800&auto=format&fit=crop" alt="Students learning" style="width: 100%; border-radius: 30px; box-shadow: var(--shadow-lg);">
                        <div style="position: absolute; bottom: -30px; left: -30px; background: var(--bs-body-bg); padding: 30px; border-radius: 20px; box-shadow: var(--shadow-lg); z-index: 2; max-width: 200px; border: 1px solid var(--bs-border-color);">
                            <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color);">25+</div>
                            <p style="font-size: 0.9rem; color: var(--bs-secondary-color); font-weight: 600;">Years of Academic Excellence</p>
                        </div>
                    </div>
                    <div style="position: absolute; top: -20px; right: -20px; width: 100%; height: 100%; border: 2px solid var(--secondary-color); border-radius: 30px; z-index: 0;"></div>
                </div>
            </div>
        </div>
    </section>

    <section id="academics" class="section" style="background: var(--bs-tertiary-bg);">
        <div class="container">
            <div class="section-title reveal">
                <h2>Academic Programs</h2>
            </div>
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                <div class="feature-card reveal bg-body-tertiary" style="animation-delay: 0.1s;">
                    <div style="font-size: 2.5rem; margin-bottom: 20px;">📚</div>
                    <h3 class="text-body">Quality Education</h3>
                    <p class="text-secondary">Following the national curriculum with enhanced focus on critical thinking and problem solving.</p>
                </div>
                <div class="feature-card reveal bg-body-tertiary" style="animation-delay: 0.2s;">
                    <div style="font-size: 2.5rem; margin-bottom: 20px;">🎨</div>
                    <h3 class="text-body">Extra-Curricular</h3>
                    <p class="text-secondary">Rich programs in arts, sports, and leadership to develop well-rounded individuals.</p>
                </div>
                <div class="feature-card reveal bg-body-tertiary" style="animation-delay: 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 20px;">💻</div>
                    <h3 class="text-body">Modern Facilities</h3>
                    <p class="text-secondary">Equipped with modern learning tools and a conducive environment for every student.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="news" class="section reveal">
        <div class="container">
            <div class="section-title reveal" style="text-align: center; margin-bottom: 60px;">
                <span style="color: var(--secondary-color); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Stay Updated</span>
                <h2 style="font-size: 2.5rem; margin-top: 10px;">News & <span class="gradient-text">Announcements</span></h2>
            </div>
            
            <?php if (empty($public_announcements)): ?>
                <div class="text-center py-5">
                    <p class="text-secondary">No recent news or events posted yet. Check back later!</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($public_announcements as $index => $ann): ?>
                <div class="col-lg-4 col-md-6 reveal" style="animation-delay: <?php echo ($index + 1) * 0.1; ?>s;">
                            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden transition-hover bg-body-tertiary" style="transition: transform 0.3s ease;">
                                <?php if ($ann['image']): ?>
                                    <img src="sms/assets/images/announcements/<?php echo $ann['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($ann['title']); ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="dash-header d-flex align-items-center justify-content-center rounded-4 border-0" style="height: 200px;">
                                        <div class="icon-box-pro">
                                            <i class="bi <?php echo $ann['type'] == 'event' ? 'bi-calendar-event' : ($ann['type'] == 'notice' ? 'bi-info-circle' : 'bi-newspaper'); ?> fs-1 text-primary"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="badge rounded-pill <?php echo $ann['type'] == 'event' ? 'border-info text-info' : ($ann['type'] == 'notice' ? 'border-warning text-warning' : 'border-primary text-primary'); ?> bg-transparent border px-3">
                                            <?php echo ucfirst($ann['type']); ?>
                                        </span>
                                        <small class="text-secondary"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small>
                                    </div>
                                    <h4 class="card-title fw-bold mb-3 h5 text-body"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                    <p class="card-text text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                                        <?php 
                                        $content = strip_tags($ann['content']);
                                        echo strlen($content) > 120 ? substr($content, 0, 120) . '...' : $content; 
                                        ?>
                                    </p>
                                    <a href="announcement.php?id=<?php echo $ann['id']; ?>" class="btn btn-outline-primary rounded-pill btn-sm px-4 fw-bold">Read More</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="contact" class="section">
        <div class="container">
            <div class="section-title reveal">
                <h2>Get In <span class="gradient-text">Touch</span></h2>
                <p style="color: var(--bs-secondary-color); text-align: center; max-width: 600px; margin: 15px auto 0;">Have questions? We're here to help. Reach out to our admissions office or general inquiries team.</p>
            </div>
            
            <div class="contact-grid">
                <div class="reveal-left">
                    <div class="contact-card bg-body-tertiary">
                        <div class="contact-icon bg-body">📍</div>
                        <div class="contact-details">
                            <h4 class="text-body">Visit Us</h4>
                            <p class="text-secondary"><?php echo $site_address; ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-card bg-body-tertiary">
                        <div class="contact-icon bg-body">📞</div>
                        <div class="contact-details">
                            <h4 class="text-body">Call Us</h4>
                            <p class="text-secondary"><?php echo $site_phone; ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-card bg-body-tertiary">
                        <div class="contact-icon bg-body">✉️</div>
                        <div class="contact-details">
                            <h4 class="text-body">Email Us</h4>
                            <p class="text-secondary"><?php echo $site_email; ?></p>
                        </div>
                    </div>

                    <?php if ($google_maps_embed): ?>
                        <div class="mt-4 rounded-4 overflow-hidden shadow-sm border" style="height: 250px;">
                            <iframe 
                                src="<?php echo $google_maps_embed; ?>" 
                                width="100%" 
                                height="100%" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="reveal-right">
                    <form class="card border-0 shadow-sm" style="padding: 40px; height: 100%; border-radius: 20px; background: var(--bs-tertiary-bg);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="small fw-bold text-secondary mb-2">Full Name</label>
                                <input type="text" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" placeholder="John Doe" required style="background: var(--bs-body-bg) !important; color: var(--bs-body-color);">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="small fw-bold text-secondary mb-2">Email Address</label>
                                <input type="email" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" placeholder="john@example.com" required style="background: var(--bs-body-bg) !important; color: var(--bs-body-color);">
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label class="small fw-bold text-secondary mb-2">Subject</label>
                            <input type="text" class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" placeholder="Admissions Inquiry" required style="background: var(--bs-body-bg) !important; color: var(--bs-body-color);">
                        </div>
                        <div class="form-group mb-4">
                            <label class="small fw-bold text-secondary mb-2">Message</label>
                            <textarea class="form-control bg-body-secondary border-0 py-2 px-3 rounded-3" rows="4" placeholder="How can we help you?" required style="background: var(--bs-body-bg) !important; color: var(--bs-body-color);"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-massive w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="logo mb-4" style="color: var(--bs-body-color);">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <div class="logo-container">
                                <img src="<?php echo $full_base . 'sms/' . $settings['site_logo']; ?>" alt="Logo" style="max-height: 60px; width: auto; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        <span><?php echo $site_name; ?></span>
                    </div>
                    <p class="mb-4 opacity-75" style="font-size: 0.95rem; line-height: 1.8;"><?php echo $site_about; ?></p>
                    <div class="d-flex gap-3 mt-4">
                        <?php if ($facebook_url): ?>
                            <a href="<?php echo $facebook_url; ?>" target="_blank" class="text-secondary fs-5 hover-translate"><i class="bi bi-facebook"></i></a>
                        <?php endif; ?>
                        <?php if ($twitter_url): ?>
                            <a href="<?php echo $twitter_url; ?>" target="_blank" class="text-secondary fs-5 hover-translate"><i class="bi bi-twitter-x"></i></a>
                        <?php endif; ?>
                        <?php if ($instagram_url): ?>
                            <a href="<?php echo $instagram_url; ?>" target="_blank" class="text-secondary fs-5 hover-translate"><i class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if ($linkedin_url): ?>
                            <a href="<?php echo $linkedin_url; ?>" target="_blank" class="text-secondary fs-5 hover-translate"><i class="bi bi-linkedin"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3 class="fw-bold h5 mb-4">Quick Links</h3>
                    <ul class="footer-links list-unstyled">
                        <li class="mb-2"><a href="#home" class="text-secondary text-decoration-none hover-secondary">Home</a></li>
                        <li class="mb-2"><a href="#about" class="text-secondary text-decoration-none hover-secondary">About Us</a></li>
                        <li class="mb-2"><a href="#academics" class="text-secondary text-decoration-none hover-secondary">Academics</a></li>
                        <li class="mb-2"><a href="#news" class="text-secondary text-decoration-none hover-secondary">News</a></li>
                        <li class="mb-2"><a href="#contact" class="text-secondary text-decoration-none hover-secondary">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3 class="fw-bold h5 mb-4">Resources</h3>
                    <ul class="footer-links list-unstyled">
                        <li class="mb-2"><a href="sms/index.php" class="text-secondary text-decoration-none hover-secondary">School Portal</a></li>
                        <li class="mb-2"><a href="calendar.php" class="text-secondary text-decoration-none hover-secondary">School Calendar</a></li>
                        <li class="mb-2"><a href="handbook.php" class="text-secondary text-decoration-none hover-secondary">Student Handbook</a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-secondary text-decoration-none hover-secondary">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3 class="fw-bold h5 mb-4">Contact Info</h3>
                    <p class="mb-3 text-secondary"><i class="bi bi-geo-alt-fill text-primary me-2"></i> <?php echo $site_address; ?></p>
                    <p class="mb-3 text-secondary"><i class="bi bi-telephone-fill text-primary me-2"></i> <?php echo $site_phone; ?></p>
                    <p class="mb-3 text-secondary"><i class="bi bi-envelope-fill text-primary me-2"></i> <?php echo $site_email; ?></p>
                </div>
            </div>
            
            <hr class="my-5 opacity-25">
            
            <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                <p class="mb-0 text-secondary"><?php echo $copyright_text; ?></p>
                <div class="text-secondary small">
                    Academic Year: <?php echo $settings['academic_year'] ?? '2025/2026'; ?> | Term: <?php echo $settings['current_term'] ?? 'Term 1'; ?>
                </div>
            </div>
        </div>
    </footer>

    <script src="sms/assets/js/main.js"></script>
</body>
</html>
