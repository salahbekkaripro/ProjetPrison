<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

function log_debug($message) {
    file_put_contents(__DIR__ . '/../logs/upload_debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$post_id = $_POST['post_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$parent_id = $_POST['parent_id'] ?? null;
$tag = trim($_POST['tag'] ?? '');

if (!$post_id || !$content) {
    echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
    exit;
}

// Gestion de l'upload
$attachment = null;
if (!empty($_FILES['attachment']['name'])) {
    if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        log_debug('Erreur upload : ' . $_FILES['attachment']['error']);
        echo json_encode(['success' => false, 'error' => 'Erreur upload (code ' . $_FILES['attachment']['error'] . ')']);
        exit;
    }

    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];

    $fileType = mime_content_type($_FILES['attachment']['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/comments/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            log_debug('Impossible de créer le dossier : ' . $uploadDir);
            echo json_encode(['success' => false, 'error' => "Erreur lors de la création du dossier d'upload"]);
            exit;
        }
    }
log_debug('Fichier reçu : ' . print_r($_FILES['attachment'], true));

    $filename = uniqid() . '_' . basename($_FILES['attachment']['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
        $attachment = $filename;
        log_debug("Fichier uploadé avec succès : $filename");
    } else {
        log_debug("Échec de move_uploaded_file vers : $targetPath");
        echo json_encode(['success' => false, 'error' => "Erreur lors de l'upload"]);
        exit;
    }
}

// Insertion du commentaire
$stmt = $pdo->prepare("INSERT INTO comments (
    post_id, parent_id, user_id, author, content, attachment, tag, created_at, reported, validated_by_admin
) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0, 0)");

$stmt->execute([
    $post_id,
    $parent_id ?: null,
    $user_id,
    $_SESSION['user']['username'],
    $content,
    $attachment,
    $tag ?: null
]);

$comment_id = $pdo->lastInsertId();

// Notification
if (!empty($parent_id)) {
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();

    if ($parent && $parent['user_id'] != $user_id) {
        $notif = $pdo->prepare("INSERT INTO notifications (recipient_id, sender_id, comment_id, post_id) VALUES (?, ?, ?, ?)");
        $notif->execute([$parent['user_id'], $user_id, $comment_id, $post_id]);
    }
}

// ✅ Réponse JSON attendue par JS
echo json_encode(['success' => true]);
exit;
