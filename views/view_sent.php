<?php
// Démarrage de session
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../messages/sent.php');
    exit;
}

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT m.*, u.username AS receiver_name
    FROM private_messages m
    JOIN users u ON m.receiver_id = u.id
    WHERE m.id = ? AND m.sender_id = ?
");
$stmt->execute([$id, $_SESSION['user']['id']]);
$message = $stmt->fetch();

if (!$message) {
    echo "<p style='color:white; text-align:center;'>Message introuvable.</p>";
    exit;
}

$customHeadStyle = <<<CSS

.container {
    max-width: 800px;
    margin: 50px auto;
}
.message-header {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
    text-align: center;
}
.message-info {
    background: rgba(255,255,255,0.03);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    color: white;
}
.message-info p {
    margin: 8px 0;
}
.message-content {
    background: rgba(255,255,255,0.05);
    padding: 20px;
    border-radius: 10px;
    color: white;
    font-size: 1.1em;
    line-height: 1.5em;
}
.button {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 20px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 10px;
    color: white;
    text-decoration: none;
}
.button:hover {
    background: rgba(255,165,0,0.2);
}

CSS;
require_once '../includes/head.php';
include '../includes/navbar.php';
?>



<div class="container">
    <div class="message-header">
        <h2>📨 Message envoyé</h2>
    </div>

    <div class="message-info">
        <p><strong style="color:#ffaa00;">✉️ Sujet :</strong> <?= htmlspecialchars($message['subject']) ?></p>
        <p><strong style="color:#ffaa00;">👤 Destinataire :</strong> <a href="profil.php?id=<?= $message['receiver_id'] ?>" style="color:#ff5555; text-decoration:none;"> <?= htmlspecialchars($message['receiver_name']) ?> </a></p>
        <p><strong style="color:#ffaa00;">📅 Envoyé le :</strong> <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></p>
    </div>

    <div class="message-content">
        <?= nl2br(htmlspecialchars($message['content'])) ?>
    </div>

    <div style="text-align:center;">
        <a href="../messages/sent.php" class="button">🔙 Retour aux messages envoyés</a>
    </div>
</div>