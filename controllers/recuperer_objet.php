<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rolep'] !== 'prisonnier') {
    header("Location: ../index.php");
    exit();
}

$nom = $_GET['nom'] ?? '';
$description = $_GET['description'] ?? '';
$interdit = isset($_GET['interdit']) ? (int) $_GET['interdit'] : 0;

if ($nom && $description) {
    // 1. Enregistrer l'objet récupéré
    $stmt = $pdo->prepare("INSERT INTO objets_prisonniers (prisonnier_id, nom_objet, description, interdit) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $nom, $description, $interdit]);

    // 2. Enregistrement dans la table notifications
    $notif_msg = "Objet récupéré : $nom (" . ($interdit ? "interdit" : "autorisé") . ")";
    $notif_stmt = $pdo->prepare("INSERT INTO notifications (utilisateur_id, message) VALUES (?, ?)");
    $notif_stmt->execute([$_SESSION['user_id'], $notif_msg]);
}

// 3. Redirection
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
