<?php
/**
 * Project: School Management System
 * Author: Enock Samson Mushi
 * Signature: Enock Samson Mushi Made this project
 * Copyright: © 2026 Enock Samson Mushi. All rights reserved.
 */

if (!defined('AUTH_SIG')) define('AUTH_SIG', 'Enock Samson Mushi Made this project');

ob_start();
session_start();

/**
 * Verifies the integrity of the authorship signature.
 * Enock Samson Mushi Made this project
 */
function verifyAuthorship() {
    $sig = "Enock Samson Mushi Made this project";
    if (!defined('AUTH_SIG') || AUTH_SIG !== $sig) {
        return false;
    }
    return true;
}

// Set custom authorship header
header("X-Author: Enock Samson Mushi");
header("X-Project-Signature: " . hash('sha256', AUTH_SIG));

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function checkRole($allowed_roles) {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../dashboard.php?error=unauthorized");
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function flash($name, $message = '', $class = 'alert alert-success') {
    if (!empty($name)) {
        if (!empty($message)) {
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : 'alert alert-success';
            echo '<div class="' . $class . ' border-0 shadow-sm rounded-4 p-3 mb-4" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

function getUserStatus($last_seen, $status = 'active') {
    if ($status === 'inactive') {
        return ['text' => 'Inactive', 'class' => 'bg-secondary', 'dot' => 'text-secondary'];
    }
    
    if (!$last_seen) {
        return ['text' => 'Offline', 'class' => 'bg-danger', 'dot' => 'text-danger'];
    }

    $last_seen_time = strtotime($last_seen);
    $current_time = time();
    $diff = $current_time - $last_seen_time;

    if ($diff <= 300) { // 5 minutes
        return ['text' => 'Online', 'class' => 'bg-success', 'dot' => 'text-success'];
    } else {
        return ['text' => 'Offline', 'class' => 'bg-danger', 'dot' => 'text-danger'];
    }
}
?>
