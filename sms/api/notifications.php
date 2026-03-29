<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action == 'mark_read') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        // Mark specific notification as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    } else {
        // Mark all as read for this user
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    echo json_encode(['success' => true]);
} elseif ($action == 'fetch') {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE id IN (
            SELECT MAX(id) 
            FROM notifications 
            WHERE user_id = ? 
            GROUP BY message, link
        ) 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    echo json_encode($notifications);
}
?>
