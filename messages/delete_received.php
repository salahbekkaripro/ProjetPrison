<?php
session_start();
require_once '../includes/db.php';         // Connexion DB avant tout
require_once '../includes/functions.php';  // Puis fonctions
require_user_login();

$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    // Pas connecté, ou id non défini
    header('Location: ../login.php');
    exit;
}

// Supprimer un message si POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = (int) $_POST['message_id'];

    // Préparation suppression seulement si le message appartient bien au user connecté
    $stmt = $pdo->prepare("DELETE FROM private_messages WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$message_id, $user_id]);
}

// Redirection vers la boîte de réception après suppression
header('Location: /ProjetPrison/views/inbox.php');
exit;
