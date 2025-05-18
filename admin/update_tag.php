<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $tag = trim($_POST['tag'] ?? '');

    $stmt = $pdo->prepare("UPDATE comments SET tag = ? WHERE id = ?");
    $stmt->execute([$tag ?: null, $commentId]);
}

header('Location: manage_comments.php');
exit;
