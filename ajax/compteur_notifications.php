<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    echo json_encode(['unread' => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread = $stmt->fetchColumn();

echo json_encode(['unread' => (int)$unread]);
