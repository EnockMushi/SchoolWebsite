<?php
/**
 * Project: School Management System
 * Author: Enock Samson Mushi
 * Signature: Enock Samson Mushi Made this project
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

$results = [
    'file_headers' => [],
    'database_integrity' => [],
    'constants' => [],
    'author' => 'Enock Samson Mushi'
];

// 1. Check constants
$results['constants']['AUTH_SIG'] = defined('AUTH_SIG') && AUTH_SIG === 'Enock Samson Mushi Made this project' ? 'PASSED' : 'FAILED';

// 2. Check Database
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'project_signature'");
    $stmt->execute();
    $sig = $stmt->fetchColumn();
    $results['database_integrity']['project_signature'] = ($sig === 'Enock Samson Mushi Made this project') ? 'PASSED' : 'FAILED';
    
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'developer_name'");
    $stmt->execute();
    $dev = $stmt->fetchColumn();
    $results['database_integrity']['developer_name'] = ($dev === 'Enock Samson Mushi') ? 'PASSED' : 'FAILED';
} catch (Exception $e) {
    $results['database_integrity']['error'] = $e->getMessage();
}

// 3. Check File Content Signatures (Sample check)
$files_to_check = [
    'includes/functions.php',
    'includes/db.php',
    'assets/css/style.css'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $results['file_headers'][$file] = (strpos($content, 'Enock Samson Mushi') !== false) ? 'PASSED' : 'FAILED';
    } else {
        $results['file_headers'][$file] = 'FILE MISSING';
    }
}

// Output as JSON for automated checks
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
