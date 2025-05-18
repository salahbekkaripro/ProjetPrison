<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';


header('Content-Type: application/json');

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$postId = (int) $_POST['post_id'];

$stmt = $pdo->prepare("UPDATE posts SET is_approved = 1 WHERE id = ?");
$success = $stmt->execute([$postId]);

echo json_encode(['success' => $success]);
