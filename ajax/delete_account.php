<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_user_login();

$userId = $_SESSION['user']['id'];

// Récupération du chemin de l'avatar
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$userId]);
$avatar = $stmt->fetchColumn();

// Suppression de l'avatar sur le serveur
if ($avatar && file_exists($_SERVER['DOCUMENT_ROOT'] . $avatar)) {
    unlink($_SERVER['DOCUMENT_ROOT'] . $avatar);
}

// Suppression des commentaires
$pdo->prepare("DELETE FROM comments WHERE author = ?")->execute([$_SESSION['user']['username']]);

// Suppression du compte utilisateur
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
require_once '../includes/functions.php';
showOverlayRedirect("Suppression du compte...", "final_logout.php", "Suppression du compte...", "delete.mp3");
exit;
