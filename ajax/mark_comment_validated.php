<?php
require_once '../includes/db.php';
session_start();

// Protection minimale
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $comment_id = (int) $_POST['id'];

    $stmt = $pdo->prepare("UPDATE comments SET reported = 0 WHERE id = ?");
    $stmt->execute([$comment_id]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
}
