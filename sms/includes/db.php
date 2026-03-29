<?php
/**
 * Project: School Management System
 * Author: Enock Samson Mushi
 * Signature: Enock Samson Mushi Made this project
 */
require_once __DIR__ . '/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function to check if a column exists
function columnExists($pdo, $table, $column) {
    try {
        $rs = $pdo->query("SELECT $column FROM $table LIMIT 1");
        // Check for student_handbook table
    try {
        $pdo->query("SELECT 1 FROM student_handbook LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("CREATE TABLE student_handbook (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chapter_number VARCHAR(10),
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            sort_order INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Add some default data
        $pdo->exec("INSERT INTO student_handbook (chapter_number, title, content, sort_order) VALUES 
            ('01', 'Academic Integrity', 'Expectations for honest academic work and consequences of plagiarism.', 1),
            ('02', 'Attendance Policy', 'Rules regarding absences, tardiness, and leave requests.', 2),
            ('03', 'Code of Conduct', 'Behavioral expectations on campus and during school events.', 3),
            ('04', 'Dress Code', 'Uniform requirements and grooming standards.', 4)");
    }
} catch (PDOException $e) {
        return false;
    }
    return $rs !== false;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Check for link column in notifications
    if (!columnExists($pdo, 'notifications', 'link')) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN link VARCHAR(255) DEFAULT NULL");
    }
    // Check for teacher_id column in classes
    if (!columnExists($pdo, 'classes', 'teacher_id')) {
        $pdo->exec("ALTER TABLE classes ADD COLUMN teacher_id INT DEFAULT NULL");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to get site settings
function getSetting($key, $pdo) {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}
?>
