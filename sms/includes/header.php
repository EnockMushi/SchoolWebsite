<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$site_name = getSetting('site_name', $pdo);
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];

// Update last seen
$stmt = $pdo->prepare("UPDATE users SET last_seen = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->execute([$user_id]);

// Get unread notification count
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT message, link) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_notifications = $stmt->fetchColumn();

// Get latest notifications for dropdown
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$latest_notifications = $stmt->fetchAll();

// Determine base path for assets
$base_path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
              strpos($_SERVER['PHP_SELF'], '/teacher/') !== false || 
              strpos($_SERVER['PHP_SELF'], '/parent/') !== false || 
              strpos($_SERVER['PHP_SELF'], '/headmaster/') !== false) ? '../' : '';

$site_logo = getSetting('site_logo', $pdo) ?: 'assets/images/logo-default.png';
$site_favicon = getSetting('site_favicon', $pdo) ?: 'assets/images/favicon-default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Enock Samson Mushi">
    <meta name="project-signature" content="Enock Samson Mushi Made this project">
    <!-- Enock Samson Mushi Made this project -->
    <title><?php echo ucfirst($role); ?> Dashboard - <?php echo $site_name; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $base_path . $site_favicon; ?>">
    <!-- Offline Bootstrap & Icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css?v=1.0.1">
    
    <!-- Theme Manager -->
    <script src="<?php echo $base_path; ?>assets/js/theme-manager.js?v=1.0.1"></script>
    
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
            --primary-color: <?php echo getSetting('primary_color', $pdo) ?: '#0d6efd'; ?>;
            --secondary-color: <?php echo getSetting('secondary_color', $pdo) ?: '#6c757d'; ?>;
        }
    </style>
    <style>
        #msg-flash {
            animation: slideInRight 0.5s ease-out;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-body-tertiary">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar shadow-lg" id="sidebar-wrapper">
            <div class="sidebar-header p-4 border-bottom border-opacity-25">
                <div class="d-flex align-items-center gap-3">
                    <?php if (getSetting('site_logo', $pdo)): ?>
                        <div class="sidebar-logo-container">
                            <img src="<?php echo $base_path . getSetting('site_logo', $pdo); ?>" alt="Logo" class="img-fluid" style="max-height: 40px;">
                        </div>
                    <?php endif; ?>
                    <div class="overflow-hidden">
                        <div class="h5 mb-0 fw-bold text-primary text-truncate"><?php echo $site_name; ?></div>
                        <?php if ($tagline = getSetting('site_tagline', $pdo)): ?>
                            <div class="text-secondary small text-truncate opacity-75" style="font-size: 0.7rem;"><?php echo $tagline; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="user-profile-card">
                <div class="d-flex align-items-center gap-3">
                    <?php 
                    $avatar_class = '';
                    $avatar_icon = '';
                    
                    switch($role) {
                        case 'admin':
                            $avatar_class = 'avatar-admin';
                            $avatar_icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M5 19h14v2H5v-2zm14-2H5V9l3.5 3 3.5-3 3.5 3 3.5-3v8z"/></svg>';
                            break;
                        case 'headmaster':
                            $avatar_class = 'avatar-headmaster';
                            $avatar_icon = '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 14.5c0-1.1-.9-2-2-2h-1V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-4.5zm-9 3.5c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM15 9h-2V7h2v2zm0 4h-2v-2h2v2z"/></svg>'; // Simplified Gavel
                            break;
                        case 'teacher':
                            $avatar_class = 'avatar-teacher';
                            $avatar_icon = '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>'; // Mortarboard
                            break;
                        case 'parent':
                            $avatar_class = 'avatar-parent';
                            $avatar_icon = '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>'; // User icon
                            break;
                        default:
                            $avatar_icon = strtoupper(substr($full_name, 0, 1));
                    }
                    ?>
                    <div class="avatar-pro <?php echo $avatar_class; ?>">
                        <?php echo $avatar_icon; ?>
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-bold text-truncate small"><?php echo $full_name; ?></div>
                        <div class="role-badge"><?php echo $role; ?></div>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav flex-grow-1 overflow-auto">
                <ul class="nav flex-column px-3 gap-1">
                    <li class="nav-item">
                        <a href="<?php echo $base_path . $role; ?>/dashboard.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="bi bi-grid-fill fs-5"></i> <span>Dashboard</span>
                        </a>
                    </li>

                    <?php if ($role == 'teacher' || $role == 'parent'): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>news.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : ''; ?>">
                                <i class="bi bi-megaphone-fill fs-5"></i> <span>School News</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($role == 'admin' || $role == 'headmaster'): ?>
                        <?php 
                        $pending_count = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
                        ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/approvals.php" class="nav-link d-flex align-items-center justify-content-between px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'approvals.php' ? 'active' : ''; ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-person-check-fill fs-5"></i> <span>Approvals</span>
                                </div>
                                <?php if ($pending_count > 0): ?>
                                    <span class="badge rounded-pill bg-danger shadow-sm"><?php echo $pending_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/announcements.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
                                <i class="bi bi-megaphone-fill fs-5"></i> <span>Announcements</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item mt-2"><small class="text-uppercase text-secondary opacity-50 fw-bold px-3" style="font-size: 0.65rem;">Management</small></li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/users.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                                <i class="bi bi-people-fill fs-5"></i> <span>Users</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/students.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                                <i class="bi bi-mortarboard-fill fs-5"></i> <span>Students</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/classes.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'classes.php' ? 'active' : ''; ?>">
                                <i class="bi bi-layers-fill fs-5"></i> <span>Classes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/attendance_tracking.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'attendance_tracking.php' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-check-fill fs-5"></i> <span>Attendance</span>
                            </a>
                        </li>
                        <li class="nav-item mt-2"><small class="text-uppercase text-secondary opacity-50 fw-bold px-3" style="font-size: 0.65rem;">System</small></li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/messages.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                                <i class="bi bi-chat-left-dots-fill fs-5"></i> <span>Messages</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/settings.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                                <i class="bi bi-gear-fill fs-5"></i> <span>Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/moderation.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'moderation.php' ? 'active' : ''; ?>">
                                <i class="bi bi-shield-lock-fill fs-5"></i> <span>Security</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($role == 'headmaster'): ?>
                        <li class="nav-item mt-2"><small class="text-uppercase text-secondary opacity-50 fw-bold px-3" style="font-size: 0.65rem;">Academic</small></li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>headmaster/teachers.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : ''; ?>">
                                <i class="bi bi-person-workspace fs-5"></i> <span>Teachers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>headmaster/classes.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'classes.php' ? 'active' : ''; ?>">
                                <i class="bi bi-building fs-5"></i> <span>Classes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>headmaster/students.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                                <i class="bi bi-mortarboard-fill fs-5"></i> <span>Students</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role == 'teacher'): ?>
                        <li class="nav-item mt-2"><small class="text-uppercase text-secondary opacity-50 fw-bold px-3" style="font-size: 0.65rem;">Classroom</small></li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>teacher/students.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                                <i class="bi bi-person-lines-fill fs-5"></i> <span>Registry</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>teacher/attendance.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                                <i class="bi bi-calendar-check-fill fs-5"></i> <span>Attendance</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>teacher/communication.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'communication.php' ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                                <i class="bi bi-chat-dots-fill fs-5"></i> <span>Messages</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>teacher/requests.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'requests.php' ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                                <i class="bi bi-file-earmark-text-fill fs-5"></i> <span>Requests</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role == 'parent'): ?>
                        <li class="nav-item mt-2"><small class="text-uppercase text-secondary opacity-50 fw-bold px-3" style="font-size: 0.65rem;">Child Info</small></li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>parent/progress.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'progress.php' ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                                <i class="bi bi-chart-line fs-5"></i> <span>Progress</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>parent/messages.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                                <i class="bi bi-comment-dots fs-5"></i> <span>Messages</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item mt-auto py-4">
                        <a href="<?php echo $base_path; ?>logout.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-danger border-top border-secondary border-opacity-10">
                            <i class="bi bi-box-arrow-left fs-5"></i> <span>Sign Out</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg glass border-bottom px-4 py-2 sticky-top">
                <div class="container-fluid p-0">
                    <button class="btn btn-link p-0 me-3 d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                        <i class="bi bi-list fs-3"></i>
                    </button>
                    
                    <form class="d-none d-md-flex ms-2" action="<?php echo $base_path; ?>search.php" method="GET" style="width: 300px;">
                        <div class="input-group input-group-sm rounded-pill px-3 py-1 border-0" style="background-color: var(--bs-tertiary-bg);">
                            <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="bi bi-search text-secondary"></i></span>
                            <input type="text" name="q" class="form-control bg-transparent border-0 shadow-none p-0" placeholder="Search records..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        </div>
                    </form>

                    <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
                        <!-- Theme Toggle -->
                        <div class="theme-toggle-wrapper">
                            <button class="btn" id="themeToggle" type="button"
                                    onclick="if(event) event.stopPropagation(); if(typeof ThemeManager !== 'undefined') { const currentTheme = document.documentElement.getAttribute('data-bs-theme'); ThemeManager.setTheme(currentTheme === 'dark' ? 'light' : 'dark'); }">
                                <i class="bi bi-sun-fill theme-icon-light"></i>
                                <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
                            </button>
                        </div>

                        <!-- Notifications -->
                        <div class="dropdown">
                            <button class="btn btn-body-secondary rounded-circle position-relative shadow-sm border-0 d-flex align-items-center justify-content-center p-0 flex-shrink-0" 
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false" id="notificationDropdown"
                                    style="width: 38px; height: 38px;">
                                <i class="bi bi-bell-fill text-primary fs-5"></i>
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle badge-dot" style="margin-top: 5px; margin-left: -5px;">
                                        <span class="visually-hidden">unread notifications</span>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 mt-2" style="width: 320px; border-radius: 15px;">
                                <div class="p-3 border-bottom rounded-top-4" style="background-color: var(--bs-tertiary-bg);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold">Notifications</h6>
                                        <span class="badge bg-primary text-white border-0 rounded-pill small"><?php echo $unread_notifications; ?> New</span>
                                    </div>
                                </div>
                                <div class="notification-list overflow-auto" style="max-height: 350px;">
                                    <?php if (empty($latest_notifications)): ?>
                                        <div class="p-4 text-center text-secondary">
                                            <i class="bi bi-bell-slash fs-2 d-block mb-2 opacity-25"></i>
                                            <small>No notifications yet</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($latest_notifications as $notif): 
                                            $link_raw = $notif['link'] ?? '';
                                            $target_path = '#';
                                            
                                            if (!empty($link_raw)) {
                                                // If link starts with http or https, use as is
                                                if (strpos($link_raw, 'http') === 0) {
                                                    $target_path = $link_raw;
                                                } 
                                                // If link already has a role prefix, just prepend base_path
                                                elseif (preg_match('/^(admin|headmaster|teacher|parent)\//', $link_raw)) {
                                                    $target_path = $base_path . $link_raw;
                                                }
                                                // Otherwise, prepend base_path and current user's role directory
                                                else {
                                                    $role_dir = ($role == 'admin' ? 'admin/' : ($role == 'headmaster' ? 'headmaster/' : ($role == 'teacher' ? 'teacher/' : 'parent/')));
                                                    $target_path = $base_path . $role_dir . $link_raw;
                                                }
                                            }
                                        ?>
                                            <a href="<?php echo $target_path; ?>" class="dropdown-item p-3 border-bottom d-flex gap-3 notification-dropdown-item <?php echo !$notif['is_read'] ? 'bg-body-secondary' : ''; ?>" data-id="<?php echo $notif['id']; ?>">
                                                <div class="flex-shrink-0">
                                                    <div class="icon-box-pro shadow-sm" style="width: 32px; height: 32px;">
                                                        <i class="bi bi-info-circle text-primary" style="font-size: 0.9rem;"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-1 small text-body fw-bold text-truncate"><?php echo htmlspecialchars($notif['title'] ?? 'Notification'); ?></p>
                                                    <p class="mb-1 small text-secondary text-wrap"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                    <small class="text-secondary opacity-75" style="font-size: 0.7rem;"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></small>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo $base_path; ?>notifications.php" class="dropdown-item p-3 text-center text-primary small fw-bold rounded-bottom-4 bg-body-secondary border-top">
                                    <i class="bi bi-eye me-2"></i>View All Notifications
                                </a>
                            </div>
                        </div>

                        <div class="vr mx-1 opacity-10"></div>

                        <!-- User Profile -->
                        <div class="dropdown">
                            <button class="btn btn-body-secondary btn-sm d-flex align-items-center gap-2 px-2 py-1 border-0 rounded-pill shadow-sm" type="button" data-bs-toggle="dropdown">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                                </div>
                                <span class="d-none d-sm-inline small fw-bold text-body"><?php echo explode(' ', $full_name)[0]; ?></span>
                                <i class="bi bi-chevron-down small text-secondary" style="font-size: 0.6rem;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2" style="border-radius: 12px; min-width: 200px;">
                                <li><a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-3" href="<?php echo $base_path; ?>profile.php"><i class="bi bi-person-circle text-primary fs-5"></i> My Profile</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-3" href="<?php echo $base_path; ?>profile.php#security"><i class="bi bi-shield-check text-success fs-5"></i> Security</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-3" href="<?php echo $base_path; ?>help.php"><i class="bi bi-question-circle text-info fs-5"></i> Help Center</a></li>
                                <li><hr class="dropdown-divider opacity-10"></li>
                                <li><a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-3 text-danger" href="<?php echo $base_path; ?>logout.php"><i class="bi bi-box-arrow-left fs-5"></i> Sign Out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Body -->
            <div class="container-fluid p-3 p-md-4">
                <?php flash('msg'); ?>
<?php // Content starts here ?>
