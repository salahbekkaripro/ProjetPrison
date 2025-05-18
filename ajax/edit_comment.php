<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

// 🛡️ Vérifie que l'utilisateur est connecté et est admin
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);
    exit;
}

$commentId = $_POST['comment_id'] ?? null;
$content = trim($_POST['content'] ?? '');

if (!$commentId || !$content) {
    echo json_encode(['success' => false, 'error' => 'Champs manquants']);
    exit;
}

try {
    // 🔒 Update uniquement si admin
    $stmt = $pdo->prepare("UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$content, $commentId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
