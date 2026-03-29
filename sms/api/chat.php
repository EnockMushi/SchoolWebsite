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

// Auto-create blocked_users table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_block (blocker_id, blocked_id)
)");

if ($action == 'send') {
    $data = json_decode(file_get_contents('php://input'), true);
    $receiver_id = $data['receiver_id'];
    $message = sanitize($data['message']);
    $student_id = $data['student_id'] ?? null;

    // Check if blocked
    $stmt = $pdo->prepare("SELECT id FROM blocked_users WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'blocked', 'message' => 'Conversation is blocked']);
        exit();
    }

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, student_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message, $student_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Empty message']);
    }
} elseif ($action == 'fetch') {
    $other_id = $_GET['other_id'];
    $student_id = $_GET['student_id'] ?? null;

    $query = "SELECT * FROM messages WHERE 
              ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
    $params = [$user_id, $other_id, $other_id, $user_id];

    if ($student_id) {
        $query .= " AND student_id = ?";
        $params[] = $student_id;
    }

    $query .= " ORDER BY created_at ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    // Mark as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$other_id, $user_id]);

    // Check block status
    $stmt = $pdo->prepare("SELECT blocker_id FROM blocked_users WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    $block_info = $stmt->fetch();

    echo json_encode([
        'messages' => $messages,
        'blocked' => (bool)$block_info,
        'i_blocked' => $block_info && $block_info['blocker_id'] == $user_id
    ]);
} elseif ($action == 'block') {
    $data = json_decode(file_get_contents('php://input'), true);
    $target_id = $data['target_id'];

    // Check if target is admin or headmaster
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $target = $stmt->fetch();

    if ($target && in_array($target['role'], ['admin', 'headmaster'])) {
        echo json_encode(['error' => 'Cannot block administrators or headmasters']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $target_id]);
    echo json_encode(['success' => true]);
} elseif ($action == 'unblock') {
    $data = json_decode(file_get_contents('php://input'), true);
    $target_id = $data['target_id'];

    $stmt = $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->execute([$user_id, $target_id]);
    echo json_encode(['success' => true]);
} elseif ($action == 'clear_chat') {
    $data = json_decode(file_get_contents('php://input'), true);
    $other_id = $data['other_id'];

    $stmt = $pdo->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    echo json_encode(['success' => true]);
} elseif ($action == 'delete_message') {
    $data = json_decode(file_get_contents('php://input'), true);
    $message_id = $data['message_id'];

    // Check if user is sender or admin
    $stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $msg = $stmt->fetch();

    if ($msg) {
        if ($msg['sender_id'] == $user_id || $_SESSION['role'] == 'admin') {
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->execute([$message_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Unauthorized to delete this message']);
        }
    } else {
        echo json_encode(['error' => 'Message not found']);
    }
}
?>