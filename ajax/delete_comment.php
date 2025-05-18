<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Accès refusé.");
}

$commentId = $_POST['comment_id'] ?? null;

if ($commentId && is_numeric($commentId)) {
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
