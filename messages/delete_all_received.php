<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

require_once '../includes/functions.php';


// Supprimer tous les messages reçus de l'utilisateur connecté
$stmt = $pdo->prepare("DELETE FROM private_messages WHERE receiver_id = ?");
$stmt->execute([$_SESSION['user']['id']]);

// Redirection correcte vers la boîte de réception
header('Location: /ProjetPrison/views/inbox.php');
exit;
