<?php
require_once 'sms/includes/db.php';
$stmt = $pdo->query("DESCRIBE notifications");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($columns, JSON_PRETTY_PRINT);
unlink(__FILE__);
