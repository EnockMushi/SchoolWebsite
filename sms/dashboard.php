<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];

switch ($role) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;
    case 'headmaster':
        header("Location: headmaster/dashboard.php");
        break;
    case 'teacher':
        header("Location: teacher/dashboard.php");
        break;
    case 'parent':
        header("Location: parent/dashboard.php");
        break;
    default:
        session_destroy();
        header("Location: index.php?error=invalid_role");
        break;
}
exit();
?>
