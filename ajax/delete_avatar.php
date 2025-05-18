<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_user_login();

$userId = $_SESSION['user']['id'];

// Récupérer l'avatar depuis la base
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user && !empty($user['avatar'])) {
    $file = basename($user['avatar']);
    $path = __DIR__ . '../uploads/avatars/' . $file;

    if (file_exists($path)) {
        unlink($path);
    }

    // Avatar supprimé = NULL
    $stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
    $stmt->execute([$userId]);

    $_SESSION['user']['avatar'] = null;
}

$_SESSION['flash_success'] = "Votre photo de profil a été supprimée.";
header("Location: ../views/profil.php");
exit;
