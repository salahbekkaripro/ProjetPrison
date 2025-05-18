<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_user_login();

$bio = trim($_POST['bio'] ?? '');
$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
$stmt->execute([$bio, $userId]);

$_SESSION['flash_success'] = "Bio mise à jour avec succès.";
header("Location: /ProjetPrison/views/profil.php");
exit;
