<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_user_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user']['id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['flash_error'] = "Tous les champs sont obligatoires.";
        header('Location: ../views/profil.php');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['flash_error'] = "Les nouveaux mots de passe ne correspondent pas.";
        header('Location:  ../views/profil.php');
        exit;
    }

    // Vérification de l'ancien mot de passe
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($currentPassword, $hash)) {
        $_SESSION['flash_error'] = "Mot de passe actuel incorrect.";
        header('Location:  ../views/profil.php');
        exit;
    }

    // Mise à jour
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$newHash, $userId]);

    $_SESSION['flash_success'] = "Mot de passe modifié avec succès.";
    header('Location:  ../views/profil.php');
    exit;
}
?>
