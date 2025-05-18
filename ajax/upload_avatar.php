<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user']['id'])) {
    die("⛔ Accès refusé.");
}

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/avatars/';
    $serverPath = $_SERVER['DOCUMENT_ROOT'] . '/ProjetPrison/' . $uploadDir;

    // Crée le dossier s'il n'existe pas
    if (!is_dir($serverPath)) {
        mkdir($serverPath, 0777, true);
    }

    // Nom du fichier sécurisé et unique
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $targetPath = $serverPath . $filename;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
        // Enregistre le chemin relatif en BDD
        $relativePath = $uploadDir . $filename;

        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$relativePath, $_SESSION['user']['id']]);

        $_SESSION['user']['avatar'] = $relativePath; // Met à jour la session
        $_SESSION['flash_success'] = "Nouvel avatar enregistré avec succès.";
    } else {
        $_SESSION['flash_error'] = "❌ Erreur lors du déplacement du fichier.";
    }
} else {
    $_SESSION['flash_error'] = "❌ Aucun fichier reçu ou erreur d'upload.";
}

header("Location: ../views/profil.php");
exit;
