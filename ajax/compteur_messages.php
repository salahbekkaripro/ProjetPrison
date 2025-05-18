<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    echo json_encode(['unread' => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM private_messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$count = $stmt->fetchColumn();

echo json_encode(['unread' => (int)$count]);
exit;
