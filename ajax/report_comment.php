<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$response = ['success' => false];

if (!isset($_SESSION['user']) || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

$comment_id = (int) $_POST['id'];

// Vérifie si déjà signalé
$stmt = $pdo->prepare("SELECT reported, validated_by_admin FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    echo json_encode(['success' => false, 'error' => 'Commentaire introuvable']);
    exit;
}

if ((int)$comment['reported'] === 1) {
    echo json_encode(['success' => false, 'already_checked' => true]);
    exit;
}

// Mise à jour
$update = $pdo->prepare("UPDATE comments SET reported = 1 WHERE id = ?");
$update->execute([$comment_id]);

echo json_encode(['success' => true]);
exit;
