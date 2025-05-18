<?php
session_start();
require_once '../includes/db.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => "Vous devez être connecté pour voter."]);
    exit;
}

$userId = $_SESSION['user']['id'];
$commentId = $_POST['comment_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$commentId || !in_array($action, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'error' => "Paramètres invalides."]);
    exit;
}

// Vérifier si un vote existe déjà
$stmt = $pdo->prepare("SELECT type FROM likes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$commentId, $userId]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['type'] === $action) {
        // Même action => supprimer le vote (toggle off)
        $pdo->prepare("DELETE FROM likes WHERE comment_id = ? AND user_id = ?")
            ->execute([$commentId, $userId]);
    } else {
        // Changer le type de vote
        $pdo->prepare("UPDATE likes SET type = ? WHERE comment_id = ? AND user_id = ?")
            ->execute([$action, $commentId, $userId]);
    }
} else {
    // Nouveau vote
    $pdo->prepare("INSERT INTO likes (comment_id, user_id, type) VALUES (?, ?, ?)")
        ->execute([$commentId, $userId, $action]);
}

// Récupérer score mis à jour
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN type = 'like' THEN 1 ELSE 0 END) AS likes,
    SUM(CASE WHEN type = 'dislike' THEN 1 ELSE 0 END) AS dislikes
    FROM likes WHERE comment_id = ?");
$stmt->execute([$commentId]);
$result = $stmt->fetch();

$score = ($result['likes'] ?? 0) - ($result['dislikes'] ?? 0);

echo json_encode([
    'success' => true,
    'score' => $score,
    'type' => $action
]);
