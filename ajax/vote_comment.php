<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$comment_id = (int)($_POST['comment_id'] ?? 0);
$type = $_POST['type'] ?? '';

if (!in_array($type, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'error' => 'Type invalide']);
    exit;
}

// Vérifie s’il existe déjà un vote
$stmt = $pdo->prepare("SELECT * FROM likes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$comment_id, $user_id]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['type'] === $type) {
        // Toggle (on retire le vote)
        $pdo->prepare("DELETE FROM likes WHERE id = ?")->execute([$existing['id']]);
    } else {
        // Mise à jour du type de vote
        $pdo->prepare("UPDATE likes SET type = ?, created_at = NOW() WHERE id = ?")->execute([$type, $existing['id']]);
    }
} else {
    // Nouveau vote
    $pdo->prepare("INSERT INTO likes (comment_id, user_id, type) VALUES (?, ?, ?)")->execute([$comment_id, $user_id, $type]);
}

// Récupération du score à jour
$stmtScore = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN type = 'like' THEN 1 
                 WHEN type = 'dislike' THEN -1 
                 ELSE 0 END) AS score
    FROM likes
    WHERE comment_id = ?
");
$stmtScore->execute([$comment_id]);
$score = (int)$stmtScore->fetchColumn();

echo json_encode([
    'success' => true,
    'score' => $score
]);
exit;
